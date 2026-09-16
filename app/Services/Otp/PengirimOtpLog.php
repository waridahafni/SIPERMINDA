<?php

namespace App\Services\Otp;

use App\Contracts\PengirimOtp;
use App\Exceptions\PengirimanOtpException;
use App\Support\NomorTeleponIndonesia;
use Illuminate\Support\Facades\Log;

class PengirimOtpLog implements PengirimOtp
{
    public function kirim(string $nomorHp, string $kode): string
    {
        if (! app()->environment('local', 'testing')) {
            throw new PengirimanOtpException('Driver log OTP hanya boleh digunakan pada local atau testing.');
        }

        if (app()->environment('local')) {
            Log::stack(['single', 'stderr'])->info('OTP dikirim melalui driver log lokal.', [
                'nomor_hp' => NomorTeleponIndonesia::samarkan($nomorHp),
                'kode_otp' => $kode,
                'kedaluwarsa_menit' => config('otp.kedaluwarsa_menit'),
            ]);
        }

        return 'local-log';
    }
}
