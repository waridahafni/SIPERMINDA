<?php

namespace App\Contracts;

interface PengirimOtp
{
    /**
     * Mengirim kode OTP dan mengembalikan ID pesan dari provider.
     */
    public function kirim(string $nomorHp, string $kode): string;
}
