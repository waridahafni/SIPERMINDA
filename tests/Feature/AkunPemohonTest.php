<?php

namespace Tests\Feature;

use App\Models\Pemohon;
use App\Models\PermintaanData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkunPemohonTest extends TestCase
{
    use RefreshDatabase;

    public function test_profil_hanya_bisa_diakses_pemohon_yang_sudah_masuk(): void
    {
        $this->get(route('pemohon.profil'))
            ->assertRedirect(route('pemohon.masuk'));

        $pemohon = $this->buatPemohon();

        $this->withSession(['pemohon_id' => $pemohon->id])
            ->get(route('pemohon.profil'))
            ->assertOk()
            ->assertSee($pemohon->nama)
            ->assertSee('Status verifikasi nomor WhatsApp');
    }

    public function test_pemohon_dapat_memperbarui_profilnya_sendiri(): void
    {
        $pemohon = $this->buatPemohon(['jenis_pemohon' => 'publik']);

        $this->withSession(['pemohon_id' => $pemohon->id])
            ->put(route('pemohon.profil.update'), [
                'nama' => 'Nama Pemohon Diperbarui',
                'email' => 'profil@example.test',
                'jenis_pemohon' => 'instansi',
                'nama_instansi' => 'Bappeda Padang Lawas',
                'provinsi' => 'Sumatera Utara',
                'kabupaten_kota' => 'Kabupaten Padang Lawas',
                'alamat_lengkap' => 'Jl. Karya Pembangunan Lingkungan VI',
            ])
            ->assertRedirect(route('pemohon.profil'));

        $this->assertDatabaseHas('pemohon', [
            'id' => $pemohon->id,
            'nama' => 'Nama Pemohon Diperbarui',
            'email' => 'profil@example.test',
            'jenis_pemohon' => 'instansi',
            'nama_instansi' => 'Bappeda Padang Lawas',
            'provinsi' => 'Sumatera Utara',
            'kabupaten_kota' => 'Kabupaten Padang Lawas',
        ]);
        $this->assertSame($pemohon->no_hp, $pemohon->fresh()->no_hp);
    }

    public function test_validasi_profil_menyimpan_input_lama_dan_tidak_mengubah_data(): void
    {
        $pemohon = $this->buatPemohon();

        $this->withSession(['pemohon_id' => $pemohon->id])
            ->from(route('pemohon.profil.edit'))
            ->put(route('pemohon.profil.update'), [
                'nama' => '',
                'email' => 'bukan-email',
                'jenis_pemohon' => 'instansi',
                'nama_instansi' => '',
                'provinsi' => '',
                'kabupaten_kota' => '',
                'alamat_lengkap' => '',
            ])
            ->assertRedirect(route('pemohon.profil.edit'))
            ->assertSessionHasErrors(['nama', 'email', 'nama_instansi', 'provinsi', 'kabupaten_kota', 'alamat_lengkap'])
            ->assertSessionHasInput('email', 'bukan-email');

        $this->assertSame($pemohon->nama, $pemohon->fresh()->nama);
    }

    public function test_riwayat_dan_detail_hanya_menampilkan_permintaan_milik_pemohon_aktif(): void
    {
        $pemohonA = $this->buatPemohon(['no_hp' => '6281234567891']);
        $pemohonB = $this->buatPemohon(['no_hp' => '6281234567892']);
        $permintaanA = $this->buatPermintaan($pemohonA, 'BPS/PD/2026/00001');
        $permintaanB = $this->buatPermintaan($pemohonB, 'BPS/PD/2026/00002');

        $this->withSession(['pemohon_id' => $pemohonA->id])
            ->get(route('pemohon.permintaan.index'))
            ->assertOk()
            ->assertSee($permintaanA->nomor_tiket)
            ->assertDontSee($permintaanB->nomor_tiket);

        $this->get(route('pemohon.permintaan.show', $permintaanA))->assertOk();
        $this->get(route('pemohon.permintaan.show', $permintaanB))->assertNotFound();
    }

    private function buatPemohon(array $override = []): Pemohon
    {
        return Pemohon::create(array_merge([
            'nama' => 'Pemohon Uji',
            'no_hp' => '6281234567890',
            'email' => 'uji@example.test',
            'jenis_pemohon' => 'publik',
            'no_hp_verified_at' => now(),
        ], $override));
    }

    private function buatPermintaan(Pemohon $pemohon, string $nomorTiket): PermintaanData
    {
        return PermintaanData::create([
            'nomor_tiket' => $nomorTiket,
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Produk Domestik Regional Bruto',
            'tujuan_penggunaan' => 'Pengujian akses akun pemohon.',
            'periode_data' => '2025',
            'status' => 'diajukan',
        ]);
    }
}
