<?php

namespace App\Services\Otp;

use App\Contracts\PengirimOtp;
use App\Exceptions\PengirimanOtpException;

class PengirimOtpManager implements PengirimOtp
{
    public function __construct(
        private readonly PengirimOtpLog $pengirimLog,
        private readonly PengirimOtpWhatsApp $pengirimWhatsApp,
    ) {}

    public function kirim(string $nomorHp, string $kode): string
    {
        return match (config('otp.driver')) {
            'log' => $this->pengirimLog->kirim($nomorHp, $kode),
            'whatsapp' => $this->pengirimWhatsApp->kirim($nomorHp, $kode),
            default => throw new PengirimanOtpException('Driver OTP belum dikonfigurasi.'),
        };
    }
}
