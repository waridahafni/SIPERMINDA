<?php

namespace App\Http\Controllers;

use App\Contracts\PengirimOtp;
use App\Exceptions\KonflikIdentitasPemohonException;
use App\Exceptions\PengirimanOtpException;
use App\Http\Requests\DaftarPemohonRequest;
use App\Http\Requests\LengkapiPendaftaranRequest;
use App\Http\Requests\MasukPemohonRequest;
use App\Http\Requests\StorePermintaanRequest;
use App\Http\Requests\VerifikasiOtpRequest;
use App\Models\OtpVerification;
use App\Models\Pemohon;
use App\Services\Otp\OtpService;
use App\Support\NomorTeleponIndonesia;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

class OtpController extends Controller
{
    private const MODE_DAFTAR = 'daftar';

    private const MODE_MASUK = 'masuk';

    private const COOLDOWN_KIRIM_ULANG_DETIK = 60;

    public function __construct(private readonly PengirimOtp $pengirimOtp, private readonly OtpService $otpService) {}

    /**
     * Generate kode OTP 6 digit acak.
     */
    protected function generateKode(): string
    {
        return $this->otpService->generateKode();
    }

    public function showDaftar(Request $request)
    {
        if ($this->pemohonTerverifikasiDariSesi($request)) {
            return redirect()->route('pemohon.permintaan.index');
        }

        return view('public.auth.daftar');
    }

    public function showMasuk(Request $request)
    {
        if ($this->pemohonTerverifikasiDariSesi($request)) {
            return redirect()->route('pemohon.permintaan.index');
        }

        return view('public.auth.masuk');
    }

    /**
     * Menampilkan form verifikasi untuk challenge OTP yang sedang berjalan.
     */
    public function showForm(Request $request)
    {
        $nomorHp = $request->session()->get('otp_pemohon.no_hp');
        $modeOtp = $request->session()->get('otp_mode');
        $otpId = $request->session()->get('otp_verification_id');

        if (! is_string($nomorHp)
            || ! in_array($modeOtp, [self::MODE_DAFTAR, self::MODE_MASUK], true)
            || ! is_int($otpId)) {
            $this->bersihkanAlurOtp($request);

            if ($this->pemohonTerverifikasiDariSesi($request)) {
                return redirect()->route('pemohon.permintaan.index');
            }

            return redirect()->route('pemohon.masuk')
                ->with('error', 'Silakan mulai proses masuk atau pendaftaran terlebih dahulu.');
        }

        try {
            $nomorHp = NomorTeleponIndonesia::kanonis($nomorHp);
        } catch (InvalidArgumentException) {
            $this->bersihkanAlurOtp($request);

            return redirect()->route('pemohon.masuk')
                ->with('error', 'Sesi nomor HP tidak valid. Silakan mulai kembali.');
        }

        $otpAktif = OtpVerification::whereKey($otpId)
            ->where('no_hp', $nomorHp)
            ->whereNull('verified_at')
            ->where('expired_at', '>', now())
            ->latest('created_at')
            ->latest('id')
            ->first();

        $durasiOtpDetik = $otpAktif
            ? max(0, (int) now()->diffInSeconds($otpAktif->expired_at, false))
            : 0;
        $percobaanTerakhir = OtpVerification::where('no_hp', $nomorHp)->latest('id')->first();
        $durasiKirimUlangDetik = $percobaanTerakhir ? $this->sisaCooldownKirimUlang($percobaanTerakhir) : 0;
        $nomorTersamar = NomorTeleponIndonesia::samarkan($nomorHp);
        $kanalOtp = $this->namaKanalOtp();

        return view('public.otp.form', [
            'nomorTersamar' => $nomorTersamar,
            'durasiOtpDetik' => $durasiOtpDetik,
            'durasiKirimUlangDetik' => $durasiKirimUlangDetik,
            'kanalOtp' => $kanalOtp,
            'modeOtp' => $modeOtp,
        ]);
    }

    /**
     * Mengirim kode OTP ke nomor HP (dengan rate limit).
     */
    public function kirimOtp(DaftarPemohonRequest $request)
    {
        $dataPemohon = $request->validated();
        $noHp = NomorTeleponIndonesia::kanonis($dataPemohon['no_hp']);

        return $this->prosesKirimOtp(
            $request,
            $noHp,
            $dataPemohon,
            self::MODE_DAFTAR,
        );
    }

    /**
     * Memulai login pemohon hanya dengan nomor HP.
     * Keberadaan akun baru diperiksa setelah OTP berhasil diverifikasi agar
     * endpoint ini tidak dapat dipakai untuk enumerasi akun.
     */
    public function kirimOtpMasuk(MasukPemohonRequest $request)
    {
        $noHp = NomorTeleponIndonesia::kanonis($request->validated('no_hp'));

        return $this->prosesKirimOtp(
            $request,
            $noHp,
            [],
            self::MODE_MASUK,
        );
    }

    /**
     * Mengirim ulang OTP memakai identitas yang sudah tervalidasi di session.
     */
    public function kirimUlang(Request $request)
    {
        $noHp = $request->session()->get('otp_pemohon.no_hp');
        $modeOtp = $request->session()->get('otp_mode');
        $otpId = $request->session()->get('otp_verification_id');

        if (! is_string($noHp)
            || ! in_array($modeOtp, [self::MODE_DAFTAR, self::MODE_MASUK], true)
            || ! is_int($otpId)) {
            $this->bersihkanAlurOtp($request);

            return redirect()->route('pemohon.masuk')
                ->with('error', 'Silakan mulai proses masuk atau pendaftaran terlebih dahulu.');
        }

        try {
            $noHp = NomorTeleponIndonesia::kanonis($noHp);
        } catch (InvalidArgumentException) {
            $this->bersihkanAlurOtp($request);

            return redirect()->route('pemohon.masuk')->with('error', 'Sesi nomor HP tidak valid. Silakan mulai kembali.');
        }

        return $this->prosesKirimOtp(
            $request,
            $noHp,
            $request->session()->get('otp_pemohon', []),
            $modeOtp,
            true,
        );
    }

    /**
     * Memverifikasi kode OTP dan mendaftarkan pemohon.
     */
    public function verifikasiOtp(VerifikasiOtpRequest $request)
    {

        $nomorSesi = $request->session()->get('otp_pemohon.no_hp');
        $modeOtp = $request->session()->get('otp_mode');
        $otpId = $request->session()->get('otp_verification_id');

        if (! in_array($modeOtp, [self::MODE_DAFTAR, self::MODE_MASUK], true)
            || ! is_int($otpId)) {
            $this->bersihkanAlurOtp($request);

            return redirect()->route('pemohon.masuk')
                ->with('error', 'Sesi OTP tidak ditemukan. Silakan mulai kembali.');
        }

        try {
            $noHp = is_string($nomorSesi)
                ? NomorTeleponIndonesia::kanonis($nomorSesi)
                : null;
        } catch (InvalidArgumentException) {
            $noHp = null;
        }

        if (! is_string($noHp)) {
            $this->bersihkanAlurOtp($request);

            return redirect()->route('pemohon.masuk')
                ->with('error', 'Sesi OTP tidak sesuai. Silakan mulai kembali.');
        }

        $request->merge(['no_hp' => $noHp]);

        try {
            $hasil = $this->verifikasiDanSimpanPemohon($request, $noHp, $modeOtp, $otpId);
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

        if ($hasil['status'] === 'lengkapi_profil') {
            $request->session()->regenerate(true);
            $this->bersihkanAlurOtp($request);
            $request->session()->put('pendaftaran_terverifikasi', [
                'no_hp' => $noHp,
                'berlaku_sampai' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect()->route('pemohon.daftar.lengkapi')
                ->with('success', 'Nomor HP berhasil diverifikasi. Lengkapi profil untuk membuat akun.');
        }

        if ($hasil['status'] !== 'berhasil') {
            return redirect()->back()
                ->with('error', 'Kode OTP tidak valid atau sudah kadaluwarsa.')
                ->withErrors(['kode_otp' => 'Kode OTP tidak valid.']);
        }

        /** @var Pemohon $pemohon */
        $pemohon = $hasil['pemohon'];
        $this->aktifkanSesiPemohon($request, $pemohon);

        $tujuanDefault = $modeOtp === self::MODE_MASUK
            ? route('pemohon.permintaan.index')
            : route('permintaan.create');
        $pesan = match (true) {
            $modeOtp === self::MODE_MASUK => 'Berhasil masuk sebagai pemohon.',
            $hasil['akun_baru'] ?? false => 'Pendaftaran berhasil. Anda sudah masuk sebagai pemohon.',
            default => 'Nomor HP sudah terdaftar. Anda berhasil masuk dengan profil yang tersimpan.',
        };

        return $this->alihkanSetelahAutentikasi($request, $tujuanDefault)
            ->with('success', $pesan);
    }

    /**
     * Menjalankan pengiriman di dalam lock per nomor agar request paralel tidak
     * membuat dua OTP saling membatalkan.
     */
    private function prosesKirimOtp(
        Request $request,
        string $noHp,
        array $dataPemohon,
        string $modeOtp,
        bool $kirimUlang = false,
    ) {
        $kunciNomor = NomorTeleponIndonesia::kunciRateLimit($noHp);
        $lockSeconds = $this->timeoutProviderOtp() + 5;
        $lock = Cache::lock('otp-kirim-lock:'.$kunciNomor, $lockSeconds);

        if (! $lock->get()) {
            return redirect()->back()->with(
                'error',
                'Pengiriman OTP untuk nomor ini sedang diproses. Silakan tunggu sebentar.',
            );
        }

        try {
            $otpTerbaru = OtpVerification::where('no_hp', $noHp)
                ->latest('created_at')
                ->latest('id')
                ->first();
            $sisaCooldown = $otpTerbaru
                ? $this->sisaCooldownKirimUlang($otpTerbaru)
                : 0;

            if ($sisaCooldown > 0) {
                return redirect()->back()->with(
                    'error',
                    "Tunggu {$sisaCooldown} detik sebelum mengirim ulang OTP.",
                );
            }

            // Rate limit: maksimal 3x pengiriman per nomor HP per 10 menit.
            $key = 'otp-kirim:'.$kunciNomor;
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableIn($key);

                return redirect()->back()->with('error', "Terlalu banyak permintaan OTP. Coba lagi dalam {$seconds} detik.");
            }

            $kode = $this->generateKode();
            $otpBaru = $this->otpService->buat($noHp, $kode);

            // Tetap hit sebelum request eksternal agar kegagalan provider tidak
            // dapat dipakai untuk membanjiri endpoint provider berbayar.
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

                if (! $kirimUlang || ! $request->session()->has('otp_verification_id')) {
                    $request->session()->put([
                        'otp_pemohon' => array_merge($dataPemohon, ['no_hp' => $noHp]),
                        'otp_mode' => $modeOtp,
                        'otp_verification_id' => $otpBaru->id,
                    ]);
                }

                return redirect()->route('otp.form')
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
            $request->session()->forget('pendaftaran_terverifikasi');
            $request->session()->put([
                'otp_mode' => $modeOtp,
                'otp_verification_id' => $otpBaru->id,
            ]);

            $kanal = $this->namaKanalOtp();

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
     * @return array{status: string, pemohon?: Pemohon, akun_baru?: bool}
     */
    private function verifikasiDanSimpanPemohon(
        Request $request,
        string $noHp,
        string $modeOtp,
        int $otpId,
    ): array {
        try {
            return $this->otpService->konsumsi($otpId, $noHp, $request->kode_otp, function () use ($request, $noHp, $modeOtp): array {
                $pemohonCocok = Pemohon::whereIn(
                    'no_hp',
                    NomorTeleponIndonesia::varianPenyimpanan($noHp),
                )->lockForUpdate()->get();

                if ($pemohonCocok->count() > 1) {
                    throw new KonflikIdentitasPemohonException('Ditemukan duplikasi identitas pemohon.');
                }

                if ($modeOtp === self::MODE_MASUK && $pemohonCocok->isEmpty()) {
                    return ['status' => 'lengkapi_profil'];
                }

                /** @var Pemohon|null $pemohon */
                $pemohon = $pemohonCocok->first();
                $akunBaru = false;

                if (! $pemohon) {
                    $pemohon = Pemohon::firstOrCreate(
                        ['no_hp' => $noHp],
                        $this->dataPemohonDariSession($request),
                    );
                    $akunBaru = $pemohon->wasRecentlyCreated;
                }

                // Kanonisasi nomor pada row yang sama; seluruh foreign key dan
                // riwayat permintaan lama tetap terhubung ke ID pemohon tersebut.
                $pemohon->forceFill([
                    'no_hp' => $noHp,
                    'no_hp_verified_at' => now(),
                ])->save();

                return [
                    'status' => 'berhasil',
                    'pemohon' => $pemohon,
                    'akun_baru' => $akunBaru,
                ];
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw new KonflikIdentitasPemohonException(
                'Terjadi konflik nomor HP saat menyimpan pemohon.',
                previous: $exception,
            );
        }
    }

    public function showLengkapiPendaftaran(Request $request)
    {
        $bukti = $this->buktiPendaftaranTerverifikasi($request);

        if (! $bukti) {
            return redirect()->route('pemohon.daftar')
                ->with('error', 'Verifikasi nomor sudah berakhir. Silakan daftar kembali.');
        }

        return view('public.auth.lengkapi', [
            'nomorTersamar' => NomorTeleponIndonesia::samarkan($bukti['no_hp']),
        ]);
    }

    public function lengkapiPendaftaran(LengkapiPendaftaranRequest $request)
    {
        $bukti = $this->buktiPendaftaranTerverifikasi($request);

        if (! $bukti) {
            return redirect()->route('pemohon.daftar')
                ->with('error', 'Verifikasi nomor sudah berakhir. Silakan daftar kembali.');
        }

        $noHp = $bukti['no_hp'];

        try {
            $pemohon = DB::transaction(function () use ($request, $noHp): Pemohon {
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
                    $request->validated(),
                );

                $pemohon->forceFill([
                    'no_hp' => $noHp,
                    'no_hp_verified_at' => now(),
                ])->save();

                return $pemohon;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $exception = new KonflikIdentitasPemohonException(
                'Terjadi konflik nomor HP saat melengkapi pendaftaran.',
                previous: $exception,
            );
            Log::error('Konflik nomor HP saat melengkapi pendaftaran.', [
                'nomor_hp' => NomorTeleponIndonesia::samarkan($noHp),
                'jenis_error' => $exception::class,
            ]);

            return redirect()->back()->with(
                'error',
                'Data nomor HP perlu ditinjau oleh admin. Pendaftaran belum disimpan.',
            );
        } catch (KonflikIdentitasPemohonException $exception) {
            Log::error('Nomor HP terhubung ke lebih dari satu pemohon saat melengkapi pendaftaran.', [
                'nomor_hp' => NomorTeleponIndonesia::samarkan($noHp),
                'jenis_error' => $exception::class,
            ]);

            return redirect()->back()->with(
                'error',
                'Data nomor HP perlu ditinjau oleh admin. Pendaftaran belum disimpan.',
            );
        }

        $this->aktifkanSesiPemohon($request, $pemohon);

        return $this->alihkanSetelahAutentikasi($request, route('permintaan.create'))
            ->with('success', 'Pendaftaran berhasil. Anda sudah masuk sebagai pemohon.');
    }

    public function keluar(Request $request)
    {
        $request->session()->forget([
            'pemohon_otp',
            'pemohon_id',
            'pemohon_nama',
            'otp_nomor',
            'otp_pemohon',
            'otp_mode',
            'otp_verification_id',
            'pendaftaran_terverifikasi',
            'akses_status',
            'url.intended',
            StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI,
        ]);
        // Hancurkan ID lama agar cookie sesi yang pernah dicuri tidak tetap sah.
        // Data guard internal yang berada di session saat ini tetap dibawa ke ID baru.
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return redirect()->route('beranda')->with('success', 'Anda telah keluar dari akun pemohon.');
    }

    private function aktifkanSesiPemohon(Request $request, Pemohon $pemohon): void
    {
        // Rotasi ID session untuk mencegah session fixation setelah autentikasi.
        $request->session()->regenerate(true);
        $request->session()->forget([
            'otp_pemohon',
            'otp_mode',
            'otp_verification_id',
            'pendaftaran_terverifikasi',
            'akses_status',
            StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI,
        ]);
        $request->session()->put([
            'pemohon_otp' => $pemohon->no_hp,
            'pemohon_id' => $pemohon->id,
            'pemohon_nama' => $pemohon->nama,
            'otp_nomor' => $pemohon->no_hp,
        ]);
    }

    private function bersihkanAlurOtp(Request $request): void
    {
        $request->session()->forget([
            'otp_pemohon',
            'otp_mode',
            'otp_verification_id',
        ]);
    }

    private function pemohonTerverifikasiDariSesi(Request $request): ?Pemohon
    {
        $pemohonId = $request->session()->get('pemohon_id');

        if (! is_int($pemohonId)
            && (! is_string($pemohonId) || ! ctype_digit($pemohonId))) {
            if ($pemohonId !== null) {
                $this->bersihkanSesiPemohon($request);
            }

            return null;
        }

        $pemohon = Pemohon::whereKey((int) $pemohonId)
            ->whereNotNull('no_hp_verified_at')
            ->first();

        if (! $pemohon) {
            $this->bersihkanSesiPemohon($request);
        }

        return $pemohon;
    }

    private function bersihkanSesiPemohon(Request $request): void
    {
        $request->session()->forget([
            'pemohon_id',
            'pemohon_otp',
            'pemohon_nama',
            'otp_nomor',
            StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI,
        ]);
    }

    private function alihkanSetelahAutentikasi(
        Request $request,
        string $tujuanDefault,
    ): RedirectResponse {
        $tujuan = $request->session()->pull('url.intended');

        if (! $this->uriLokalAman($tujuan)) {
            return redirect()->to($tujuanDefault);
        }

        return redirect()->to($tujuan);
    }

    private function uriLokalAman(mixed $uri): bool
    {
        return is_string($uri)
            && str_starts_with($uri, '/')
            && ! str_starts_with($uri, '//')
            && ! str_contains($uri, '\\')
            && ! preg_match('/[\x00-\x1F\x7F]/', $uri);
    }

    private function sisaCooldownKirimUlang(OtpVerification $otp): int
    {
        return max(0, (int) now()->diffInSeconds(
            Carbon::parse($otp->created_at)
                ->addSeconds(self::COOLDOWN_KIRIM_ULANG_DETIK),
            false,
        ));
    }

    private function namaKanalOtp(): string
    {
        return match (config('otp.driver')) {
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
            'fonnte' => 'WhatsApp (demo)',
            'log' => 'log lokal',
            default => 'provider OTP',
        };
    }

    private function timeoutProviderOtp(): int
    {
        $driver = config('otp.driver');
        $timeout = match ($driver) {
            'sms' => config('otp.sms.timeout_seconds', 10),
            'whatsapp' => config('otp.whatsapp.timeout_seconds', 10),
            'fonnte' => config('otp.fonnte.timeout_seconds', 10),
            default => 10,
        };

        return min(30, max(1, (int) $timeout));
    }

    /**
     * @return array{no_hp: string, berlaku_sampai: int}|null
     */
    private function buktiPendaftaranTerverifikasi(Request $request): ?array
    {
        $bukti = $request->session()->get('pendaftaran_terverifikasi');

        if (! is_array($bukti)
            || ! is_string($bukti['no_hp'] ?? null)
            || (int) ($bukti['berlaku_sampai'] ?? 0) <= now()->timestamp) {
            $request->session()->forget('pendaftaran_terverifikasi');

            return null;
        }

        try {
            $bukti['no_hp'] = NomorTeleponIndonesia::kanonis($bukti['no_hp']);
        } catch (InvalidArgumentException) {
            $request->session()->forget('pendaftaran_terverifikasi');

            return null;
        }

        $bukti['berlaku_sampai'] = (int) $bukti['berlaku_sampai'];

        return $bukti;
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
