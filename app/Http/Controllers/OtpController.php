<?php

namespace App\Http\Controllers;

use App\Models\OtpVerification;
use App\Models\Pemohon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class OtpController extends Controller
{
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
        return view('public.otp.form');
    }

    /**
     * Mengirim kode OTP ke nomor HP (dengan rate limit).
     */
    public function kirimOtp(Request $request)
    {
        $request->validate([
            'no_hp' => 'required|string|max:20',
        ]);

        $noHp = $request->no_hp;

        // Rate limit: maksimal 3x pengiriman per nomor HP per 10 menit.
        $cekRitase = OtpVerification::where('no_hp', $noHp)
            ->where('created_at', '>', now()->subMinutes(10))
            ->count();

        if ($cekRitase >= 3) {
            return redirect()->back()->with('error', 'Terlalu banyak permintaan OTP. Silakan coba lagi 10 menit kemudian.');
        }

        $kode = $this->generateKode();

        OtpVerification::create([
            'no_hp' => $noHp,
            'kode_otp' => $kode,
            'expired_at' => now()->addMinutes(5),
            'attempt_count' => 0,
            'created_at' => now(),
        ]);

        // Simpan data pemohon sementara di session untuk tahap berikutnya.
        $request->session()->put('otp_pemohon', [
            'no_hp' => $noHp,
            'nama' => $request->nama,
            'email' => $request->email,
            'jenis_pemohon' => $request->jenis_pemohon,
            'nama_instansi' => $request->nama_instansi,
        ]);

        // Kirim OTP via SMS/WA gateway. Fallback ke log hanya saat environment local,
        // karena OTP tidak boleh terekspos ke log di lingkungan production.
        if (app()->environment('local')) {
            Log::info("OTP untuk {$noHp} dikirim (mode local): {$kode}");
        } else {
            // TODO: konfirmasi provider SMS/WA gateway (lihat PRD bagian 9).
        }

        return redirect()->route('otp.form')->with('otp_kirim', true)->with('otp_nomor', $noHp)
            ->with('success', 'Kode OTP telah dikirim ke nomor HP Anda.');
    }

    /**
     * Mengirim ulang OTP.
     */
    public function kirimUlang(Request $request)
    {
        $noHp = $request->session()->get('otp_pemohon.no_hp')
            ?? $request->session()->get('otp_nomor');

        if (!$noHp) {
            return redirect()->route('otp.form')->with('error', 'Silakan masukkan nomor HP terlebih dahulu.');
        }

        return $this->kirimOtp($request->merge(['no_hp' => $noHp]));
    }

    /**
     * Memverifikasi kode OTP dan mendaftarkan pemohon.
     */
    public function verifikasiOtp(Request $request)
    {
        $request->validate([
            'no_hp' => 'required|string|max:20',
            'kode_otp' => 'required|string|size:6',
        ]);

        $otp = OtpVerification::where('no_hp', $request->no_hp)
            ->whereNull('verified_at')
            ->where('expired_at', '>', now())
            ->latest('created_at')
            ->first();

        // Rate limit percobaan: maksimal 5x.
        if ($otp && $otp->attempt_count >= 5) {
            return redirect()->back()->with('error', 'Terlalu banyak percobaan. Silakan kirim ulang OTP.');
        }

        if (!$otp || !hash_equals($otp->kode_otp, $request->kode_otp)) {
            // Catat percobaan gagal pada record OTP terbaru yang masih berlaku.
            $latestActive = OtpVerification::where('no_hp', $request->no_hp)
                ->whereNull('verified_at')
                ->where('expired_at', '>', now())
                ->latest('created_at')
                ->first();

            if ($latestActive) {
                $latestActive->increment('attempt_count');
            }

            return redirect()->back()->with('error', 'Kode OTP tidak valid atau sudah kadaluwarsa.')->withErrors(['kode_otp' => 'Kode OTP tidak valid.']);
        }

        $otp->update(['verified_at' => now()]);

        $pemohon = Pemohon::firstOrCreate(
            ['no_hp' => $request->no_hp],
            $this->dataPemohonDariSession($request)
        );

        // Perbarui no_hp_verified dan data dari session jika perlu.
        $pemohon->forceFill(['no_hp_verified_at' => now()])->save();

        $request->session()->put([
            'pemohon_otp' => $pemohon->no_hp,
            'pemohon_id' => $pemohon->id,
            'pemohon_nama' => $pemohon->nama,
            'otp_nomor' => $pemohon->no_hp,
        ]);

        return redirect()->intended(route('permintaan.create'))->with('success', 'Verifikasi OTP berhasil.');
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