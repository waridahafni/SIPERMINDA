<?php

namespace Tests\Unit;

use App\Support\NomorTeleponIndonesia;
use InvalidArgumentException;
use Tests\TestCase;

class NomorTeleponIndonesiaTest extends TestCase
{
    public function test_format_nomor_indonesia_dinormalisasi_secara_kanonis(): void
    {
        $hasil = [
            NomorTeleponIndonesia::kanonis('081234567890'),
            NomorTeleponIndonesia::kanonis('6281234567890'),
            NomorTeleponIndonesia::kanonis('+6281234567890'),
        ];

        $this->assertSame([
            '6281234567890',
            '6281234567890',
            '6281234567890',
        ], $hasil);

        $this->assertSame(
            NomorTeleponIndonesia::kunciRateLimit('081234567890'),
            NomorTeleponIndonesia::kunciRateLimit('+6281234567890'),
        );
    }

    public function test_format_sms_dan_alias_whatsapp_tetap_kompatibel(): void
    {
        $this->assertSame(
            '6281234567890',
            NomorTeleponIndonesia::keFormatSms('081234567890'),
        );
        $this->assertSame(
            '6281234567890',
            NomorTeleponIndonesia::keFormatWhatsApp('+6281234567890'),
        );
    }

    public function test_nomor_di_luar_format_indonesia_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NomorTeleponIndonesia::kanonis('12345');
    }

    public function test_nomor_dengan_prefix_lokal_tidak_valid_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NomorTeleponIndonesia::kanonis('000000000');
    }

    public function test_nomor_telepon_rumah_ditolak_sebagai_nomor_hp(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NomorTeleponIndonesia::kanonis('02123456789');
    }
}
