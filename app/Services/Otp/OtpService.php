<?php

namespace App\Services\Otp;

use App\Models\OtpVerification;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function generateKode(): string
    {
        return (string) random_int(100000, 999999);
    }

    public function buat(string $nomor, #[\SensitiveParameter] string $kode): OtpVerification
    {
        return OtpVerification::create([
            'no_hp' => $nomor,
            'kode_otp' => Hash::make($kode),
            'expired_at' => now()->addMinutes(min(5, max(1, (int) config('otp.kedaluwarsa_menit', 5)))),
            'attempt_count' => 0,
            'created_at' => now(),
        ]);
    }

    /** Konsumsi kode dan aktivasi akun berada dalam transaksi yang sama. */
    public function konsumsi(int $id, string $nomor, #[\SensitiveParameter] string $kode, Closure $aktifkan): array
    {
        return DB::transaction(function () use ($id, $nomor, $kode, $aktifkan): array {
            $otp = OtpVerification::whereKey($id)->where('no_hp', $nomor)
                ->whereNull('verified_at')->where('expired_at', '>', now())
                ->lockForUpdate()->first();

            if (! $otp) {
                return ['status' => 'tidak_valid'];
            }

            if ($otp->attempt_count >= 5) {
                return ['status' => 'terkunci'];
            }

            if (! Hash::check($kode, $otp->kode_otp)) {
                $otp->increment('attempt_count');

                return ['status' => 'tidak_valid'];
            }

            $dikonsumsi = OtpVerification::whereKey($id)->whereNull('verified_at')
                ->where('expired_at', '>', now())->update(['verified_at' => now()]);

            if ($dikonsumsi !== 1) {
                return ['status' => 'tidak_valid'];
            }

            return $aktifkan();
        }, 3);
    }
}
