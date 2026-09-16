<?php

namespace App\Services\Otp;

use App\Contracts\PengirimOtp;
use App\Exceptions\PengirimanOtpException;

class PengirimOtpManager implements PengirimOtp
{
    public function __construct(
        private readonly PengirimOtpLog $pengirimLog,
        private readonly PengirimOtpSms $pengirimSms,
        private readonly PengirimOtpWhatsApp $pengirimWhatsApp,
        private readonly PengirimOtpFonnte $pengirimFonnte,
    ) {}

    public function kirim(string $nomorHp, string $kode): string
    {
        return match (config('otp.driver')) {
            'log' => $this->pengirimLog->kirim($nomorHp, $kode),
            'sms' => $this->pengirimSms->kirim($nomorHp, $kode),
            'whatsapp' => $this->pengirimWhatsApp->kirim($nomorHp, $kode),
            'fonnte' => $this->pengirimFonnte->kirim($nomorHp, $kode),
            default => throw new PengirimanOtpException('Driver OTP belum dikonfigurasi.'),
        };
    }
}
