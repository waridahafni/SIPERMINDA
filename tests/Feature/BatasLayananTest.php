<?php

namespace Tests\Feature;

use App\Models\PermintaanData;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BatasLayananTest extends TestCase
{
    public static function tanggalLayanan(): array
    {
        return [
            'hari biasa' => ['2026-10-01 10:30:00', '2026-10-05 10:30:00'],
            'libur Senin setelah akhir pekan' => ['2026-08-14 10:30:00', '2026-08-19 10:30:00'],
            'libur Jumat' => ['2026-12-24 10:30:00', '2026-12-29 10:30:00'],
            'lintas tahun' => ['2026-12-31 10:30:00', '2027-01-06 10:30:00'],
            'libur berturut-turut' => ['2027-03-09 10:30:00', '2027-03-15 10:30:00'],
            'cuti bersama tetap dihitung' => ['2026-03-18 10:30:00', '2026-03-23 10:30:00'],
            'pengajuan pada libur nasional' => ['2026-08-17 10:30:00', '2026-08-19 10:30:00'],
            'libur Minggu tidak dihitung dua kali' => ['2026-04-02 10:30:00', '2026-04-07 10:30:00'],
        ];
    }

    #[DataProvider('tanggalLayanan')]
    public function test_batas_layanan_melewati_akhir_pekan_dan_libur_nasional(string $mulai, string $hasil): void
    {
        config(['layanan.sla_hari_kerja' => 2]);
        $permintaan = new PermintaanData(['created_at' => $mulai]);

        $this->assertSame($hasil, $permintaan->batasLayanan()->format('Y-m-d H:i:s'));
        $this->assertSame($mulai, $permintaan->created_at->format('Y-m-d H:i:s'));
    }

    public function test_perubahan_kalender_dan_sla_dipakai_dalam_perhitungan(): void
    {
        config(['layanan.sla_hari_kerja' => 1, 'hari_libur.2028' => ['2028-01-03']]);
        $permintaan = new PermintaanData(['created_at' => '2027-12-31 10:00:00']);

        $this->assertSame('2028-01-04 10:00:00', $permintaan->batasLayanan()->format('Y-m-d H:i:s'));
    }
}
