<?php

namespace Tests\Feature;

use App\Models\Pemohon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigasiAutentikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_mendapat_satu_menu_masuk_dengan_dua_pilihan_dan_cta_daftar(): void
    {
        $this->get(route('beranda'))
            ->assertOk()
            ->assertSee('<details', false)
            ->assertSee('Masuk sebagai Pemohon')
            ->assertSee('Masuk sebagai Petugas')
            ->assertSee('Daftar Pemohon')
            ->assertSee(route('pemohon.masuk'), false)
            ->assertSee(route('internal.login'), false)
            ->assertSee(route('pemohon.daftar'), false);
    }

    public function test_pemohon_aktif_tetap_mendapat_identitas_riwayat_dan_tombol_keluar(): void
    {
        $pemohon = Pemohon::create([
            'nama' => 'Pemohon Navigasi',
            'no_hp' => '6281234567890',
            'jenis_pemohon' => 'publik',
            'no_hp_verified_at' => now(),
        ]);

        $this->withSession(['pemohon_id' => $pemohon->id])
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee($pemohon->nama)
            ->assertSee('Permintaan Saya')
            ->assertSee('Keluar')
            ->assertDontSee('Daftar Pemohon')
            ->assertDontSee('Masuk sebagai Pemohon');
    }
}
