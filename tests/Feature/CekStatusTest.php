<?php

namespace Tests\Feature;

use App\Models\Pemohon;
use App\Models\PermintaanData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CekStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_pencarian_yang_gagal_menampilkan_pesan_dan_mempertahankan_input(): void
    {
        $this->followingRedirects()
            ->from(route('cek-status'))
            ->post(route('cek-status.post'), [
                'nomor_tiket' => '  bps/pd/2026/99999  ',
                'no_hp' => '081234567890',
            ])
            ->assertOk()
            ->assertSee('Permintaan belum ditemukan')
            ->assertSee('BPS/PD/2026/99999')
            ->assertSee('081234567890');
    }

    public function test_nomor_tiket_dapat_dicari_dengan_huruf_kecil_dan_spasi_tepi(): void
    {
        $pemohon = Pemohon::create([
            'nama' => 'Pemohon Status',
            'no_hp' => '6281234567890',
            'jenis_pemohon' => 'publik',
            'no_hp_verified_at' => now(),
        ]);

        $permintaan = PermintaanData::create([
            'nomor_tiket' => 'BPS/PD/2026/00001',
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Sosial',
            'tujuan_penggunaan' => 'Riset',
            'periode_data' => '2026',
            'status' => 'diajukan',
        ]);

        $this->post(route('cek-status.post'), [
            'nomor_tiket' => '  bps/pd/2026/00001  ',
            'no_hp' => '081234567890',
        ])->assertRedirect(route('status.cek', ['nomorTiket' => $permintaan->nomor_tiket]));
    }
}
