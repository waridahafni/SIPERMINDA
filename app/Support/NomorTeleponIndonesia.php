<?php

namespace App\Support;

use InvalidArgumentException;

final class NomorTeleponIndonesia
{
    /**
     * Mengubah 08..., 628..., atau +628... menjadi format WhatsApp 628....
     */
    public static function keFormatWhatsApp(string $nomorHp): string
    {
        $nomorHp = trim($nomorHp);

        if (! preg_match('/^(?:\+62|62|0)[0-9]{8,13}$/', $nomorHp)) {
            throw new InvalidArgumentException('Nomor HP Indonesia tidak valid.');
        }

        $angka = str_starts_with($nomorHp, '+') ? substr($nomorHp, 1) : $nomorHp;

        if (str_starts_with($angka, '0')) {
            $angka = '62'.substr($angka, 1);
        }

        if (! preg_match('/^628[0-9]{7,11}$/', $angka)) {
            throw new InvalidArgumentException('Nomor HP Indonesia tidak valid.');
        }

        return $angka;
    }

    public static function samarkan(string $nomorHp): string
    {
        $angka = self::keFormatWhatsApp($nomorHp);

        return str_repeat('*', max(0, strlen($angka) - 4)).substr($angka, -4);
    }

    /**
     * Mendukung data lama yang mungkin tersimpan sebagai 08... atau +628....
     *
     * @return array<int, string>
     */
    public static function varianPenyimpanan(string $nomorHp): array
    {
        $angka = self::keFormatWhatsApp($nomorHp);

        return [
            $angka,
            '0'.substr($angka, 2),
            '+'.$angka,
        ];
    }

    public static function kunciRateLimit(string $nomorHp): string
    {
        return hash_hmac('sha256', self::keFormatWhatsApp($nomorHp), (string) config('app.key'));
    }
}
