<?php

namespace App\Http\Controllers;

use App\Contracts\PengirimOtp;
use App\Exceptions\KonflikIdentitasPemohonException;
use App\Exceptions\PengirimanOtpException;
use App\Models\OtpVerification;
use App\Models\Pemohon;
use App\Rules\NomorHpIndonesia;
use App\Support\NomorTeleponIndonesia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

class OtpController extends Controller
{
    public function __construct(private readonly PengirimOtp $pengirimOtp) {}

    /**
     * Generate kode OTP 6 digit acak.
     */
    protected function generateKode(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * Menampilkan form verifikasi nomor HP.
     */
    public function showForm()
    {
        $nomorHp = session('otp_pemohon.no_hp') ?? session('otp_nomor');
        $nomorTersamar = null;
        $menungguOtp = false;
        $durasiOtpDetik = 0;
        $kanalOtp = config('otp.driver') === 'whatsapp' ? 'WhatsApp' : 'log lokal';

        if (is_string($nomorHp)) {
            try {
                $nomorHp = NomorTeleponIndonesia::keFormatWhatsApp($nomorHp);
                $nomorTersamar = NomorTeleponIndonesia::samarkan($nomorHp);

                $otpAktif = OtpVerification::where('no_hp', $nomorHp)
                    ->whereNull('verified_at')
                    ->where('expired_at', '>', now())
                    ->latest('created_at')
                    ->latest('id')
                    ->first();

                $menungguOtp = $otpAktif !== null;
                $durasiOtpDetik = $otpAktif
                    ? max(0, (int) now()->diffInSeconds($otpAktif->expired_at, false))
                    : 0;
            } catch (InvalidArgumentException) {
                // Abaikan data sesi lama yang tidak lagi sesuai format nomor saat ini.
            }
        }

        return view('public.otp.form', compact(
            'nomorTersamar',
            'menungguOtp',
            'durasiOtpDetik',
            'kanalOtp',
        ));
    }

    /**
     * Mengirim kode OTP ke nomor HP (dengan rate limit).
     */
    public function kirimOtp(Request $request)
    {
        $dataPemohon = $request->validate([
            'no_hp' => ['required', 'string', 'max:20', new NomorHpIndonesia],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'jenis_pemohon' => ['required', 'in:publik,instansi'],
            'nama_instansi' => ['required_if:jenis_pemohon,instansi', 'nullable', 'string', 'max:255'],
        ]);

        $noHp = NomorTeleponIndonesia::keFormatWhatsApp($dataPemohon['no_hp']);

        return $this->prosesKirimOtp($request, $noHp, $dataPemohon);
    }

    /**
     * Mengirim ulang OTP memakai identitas yang sudah tervalidasi di session.
     */
    public function kirimUlang(Request $request)
    {
        $noHp = $request->session()->get('otp_pemohon.no_hp')
            ?? $request->session()->get('otp_nomor');

        if (! is_string($noHp)) {
            return redirect()->route('otp.form')->with('error', 'Silakan masukkan nomor HP terlebih dahulu.');
        }

        try {
            $noHp = NomorTeleponIndonesia::keFormatWhatsApp($noHp);
        } catch (InvalidArgumentException) {
            $request->session()->forget(['otp_pemohon', 'otp_nomor']);

            return redirect()->route('otp.form')->with('error', 'Sesi nomor HP tidak valid. Silakan isi ulang.');
        }

        return $this->prosesKirimOtp(
            $request,
            $noHp,
            $request->session()->get('otp_pemohon', []),
        );
    }

    /**
     * Memverifikasi kode OTP dan mendaftarkan pemohon.
     */
    public function verifikasiOtp(Request $request)
    {
        $request->validate([
            'no_hp' => ['required', 'string', 'max:20', new NomorHpIndonesia],
            'kode_otp' => ['required', 'digits:6'],
        ]);

        $nomorSesi = $request->session()->get('otp_pemohon.no_hp')
            ?? $request->session()->get('otp_nomor');
        $noHp = NomorTeleponIndonesia::keFormatWhatsApp($request->no_hp);

        try {
            $nomorSesiNormal = is_string($nomorSesi)
                ? NomorTeleponIndonesia::keFormatWhatsApp($nomorSesi)
                : null;
        } catch (InvalidArgumentException) {
            $nomorSesiNormal = null;
        }

        if (! is_string($nomorSesiNormal) || ! hash_equals($nomorSesiNormal, $noHp)) {
            return redirect()->route('otp.form')
                ->with('error', 'Sesi OTP tidak sesuai. Silakan kirim ulang kode OTP.');
        }

        $request->merge(['no_hp' => $noHp]);

        try {
            $hasil = $this->verifikasiDanSimpanPemohon($request, $noHp);
        } catch (KonflikIdentitasPemohonException $exception) {
            Log::error('Nomor HP terhubung ke lebih dari satu pemohon.', [
                'nomor_hp' => NomorTeleponIndonesia::samarkan($noHp),
                'jenis_error' => $exception::class,
            ]);

            return redirect()->back()->with(
                'error',
                'Data nomor HP perlu ditinjau oleh admin. Kode OTP belum digunakan.',
            );
        }

        if ($hasil['status'] === 'terkunci') {
            return redirect()->back()->with('error', 'Terlalu banyak percobaan. Silakan kirim ulang OTP.');
        }

        if ($hasil['status'] !== 'berhasil') {
            return redirect()->back()
                ->with('error', 'Kode OTP tidak valid atau sudah kadaluwarsa.')
                ->withErrors(['kode_otp' => 'Kode OTP tidak valid.']);
        }

        /** @var Pemohon $pemohon */
        $pemohon = $hasil['pemohon'];

        // Rotasi ID session untuk mencegah session fixation setelah login OTP.
        $request->session()->regenerate();

        $request->session()->put([
            'pemohon_otp' => $pemohon->no_hp,
            'pemohon_id' => $pemohon->id,
            'pemohon_nama' => $pemohon->nama,
            'otp_nomor' => $pemohon->no_hp,
        ]);

        return redirect()->intended(route('permintaan.create'))->with('success', 'Verifikasi OTP berhasil.');
    }

    /**
     * Menjalankan pengiriman di dalam lock per nomor agar request paralel tidak
     * membuat dua OTP saling membatalkan.
     */
    private function prosesKirimOtp(Request $request, string $noHp, array $dataPemohon)
    {
        $kunciNomor = NomorTeleponIndonesia::kunciRateLimit($noHp);
        $lockSeconds = min(30, max(1, (int) config('otp.whatsapp.timeout_seconds', 10))) + 5;
        $lock = Cache::lock('otp-kirim-lock:'.$kunciNomor, $lockSeconds);

        if (! $lock->get()) {
            return redirect()->back()->with(
                'error',
                'Pengiriman OTP untuk nomor ini sedang diproses. Silakan tunggu sebentar.',
            );
        }

        try {
            // Rate limit: maksimal 3x pengiriman per nomor HP per 10 menit.
            $key = 'otp-kirim:'.$kunciNomor;
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableIn($key);

                return redirect()->back()->with('error', "Terlalu banyak permintaan OTP. Coba lagi dalam {$seconds} detik.");
            }

            $kode = $this->generateKode();
            $kedaluwarsaMenit = min(5, max(1, (int) config('otp.kedaluwarsa_menit', 5)));
            $otpBaru = OtpVerification::create([
                'no_hp' => $noHp,
                'kode_otp' => Hash::make($kode),
                'expired_at' => now()->addMinutes($kedaluwarsaMenit),
                'attempt_count' => 0,
                'created_at' => now(),
            ]);

            // Tetap hit sebelum request eksternal agar kegagalan provider tidak
            // dapat dipakai untuk membanjiri endpoint WhatsApp.
            RateLimiter::hit($key, 600);

            try {
                $this->pengirimOtp->kirim($noHp, $kode);
            } catch (PengirimanOtpException $exception) {
                $otpBaru->update(['expired_at' => now()]);

                Log::error('Pengiriman OTP gagal.', [
                    'driver' => config('otp.driver'),
                    'jenis_error' => $exception::class,
                    'alasan' => $exception->getMessage(),
                ]);

                return redirect()->back()
                    ->withInput($request->except('kode_otp'))
                    ->with('error', 'Kode OTP belum dapat dikirim. Silakan coba lagi beberapa saat.');
            }

            // OTP lama baru dibatalkan setelah provider menerima OTP baru.
            OtpVerification::where('no_hp', $noHp)
                ->where('id', '!=', $otpBaru->id)
                ->whereNull('verified_at')
                ->update(['expired_at' => now()]);

            $request->session()->put('otp_pemohon', [
                'no_hp' => $noHp,
                'nama' => $dataPemohon['nama'] ?? null,
                'email' => $dataPemohon['email'] ?? null,
                'jenis_pemohon' => $dataPemohon['jenis_pemohon'] ?? null,
                'nama_instansi' => $dataPemohon['nama_instansi'] ?? null,
            ]);

            $kanal = config('otp.driver') === 'whatsapp' ? 'WhatsApp' : 'log lokal';

            return redirect()->route('otp.form')
                ->with('otp_nomor', $noHp)
                ->with('success', "Kode OTP telah dikirim melalui {$kanal}.");
        } finally {
            $lock->release();
        }
    }

    /**
     * Memeriksa dan mengonsumsi OTP serta menyelesaikan identitas pemohon
     * secara atomik agar satu kode tidak dapat dipakai dua kali.
     *
     * @return array{status: string, pemohon?: Pemohon}
     */
    private function verifikasiDanSimpanPemohon(Request $request, string $noHp): array
    {
        return DB::transaction(function () use ($request, $noHp): array {
            $otp = OtpVerification::where('no_hp', $noHp)
                ->whereNull('verified_at')
                ->where('expired_at', '>', now())
                ->latest('created_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $otp) {
                return ['status' => 'tidak_valid'];
            }

            if ($otp->attempt_count >= 5) {
                return ['status' => 'terkunci'];
            }

            if (! Hash::check($request->kode_otp, $otp->kode_otp)) {
                $otp->increment('attempt_count');

                return ['status' => 'tidak_valid'];
            }

            $dikonsumsi = OtpVerification::whereKey($otp->id)
                ->whereNull('verified_at')
                ->where('expired_at', '>', now())
                ->update(['verified_at' => now()]);

            if ($dikonsumsi !== 1) {
                return ['status' => 'tidak_valid'];
            }

            $pemohonCocok = Pemohon::whereIn(
                'no_hp',
                NomorTeleponIndonesia::varianPenyimpanan($noHp),
            )->lockForUpdate()->get();

            if ($pemohonCocok->count() > 1) {
                throw new KonflikIdentitasPemohonException('Ditemukan duplikasi identitas pemohon.');
            }

            /** @var Pemohon|null $pemohon */
            $pemohon = $pemohonCocok->first();
            $pemohon ??= Pemohon::firstOrCreate(
                ['no_hp' => $noHp],
                $this->dataPemohonDariSession($request),
            );

            // Kanonisasi nomor pada row yang sama; seluruh foreign key dan
            // riwayat permintaan lama tetap terhubung ke ID pemohon tersebut.
            $pemohon->forceFill([
                'no_hp' => $noHp,
                'no_hp_verified_at' => now(),
            ])->save();

            return ['status' => 'berhasil', 'pemohon' => $pemohon];
        }, 3);
    }

    /**
     * Membangun data pemohon dari sesi yang tersimpan saat kirimOtp.
     */
    protected function dataPemohonDariSession(Request $request): array
    {
        $data = $request->session()->get('otp_pemohon', []);

        return [
            'nama' => $data['nama'] ?? 'Pemohon',
            'email' => $data['email'] ?? null,
            'jenis_pemohon' => $data['jenis_pemohon'] ?? 'publik',
            'nama_instansi' => $data['nama_instansi'] ?? null,
        ];
    }
}
