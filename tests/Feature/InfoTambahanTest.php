<?php

namespace Tests\Feature;

use App\Models\Pemohon;
use App\Models\PermintaanData;
use App\Models\PermintaanKlarifikasi;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InfoTambahanTest extends TestCase
{
    use RefreshDatabase;

    private int $urutanTiket = 1;

    private int $urutanPemohon = 1;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_petugas_dapat_meminta_info_pada_tahap_yang_tepat(): void
    {
        $skenario = [
            ['role' => 'staf', 'status' => 'diajukan', 'tahap' => 'staf'],
        ];

        foreach ($skenario as $item) {
            $petugas = $this->buatPetugas($item['role']);
            $permintaan = $this->buatPermintaan($item['status']);

            $this->actingAs($petugas)
                ->post(route('internal.permintaan.keputusan', $permintaan), [
                    'keputusan' => 'minta_info',
                    'catatan' => "Pertanyaan publik tahap {$item['tahap']}",
                    'catatan_internal' => "Catatan internal tahap {$item['tahap']}",
                ])
                ->assertRedirect(route('internal.permintaan.index'));

            $this->assertDatabaseHas('permintaan_data', [
                'id' => $permintaan->id,
                'status' => 'menunggu_info_pemohon',
            ]);
            $this->assertDatabaseHas('permintaan_klarifikasi', [
                'permintaan_data_id' => $permintaan->id,
                'tahap' => $item['tahap'],
                'diminta_oleh' => $petugas->id,
                'pertanyaan' => "Pertanyaan publik tahap {$item['tahap']}",
                'catatan_internal' => "Catatan internal tahap {$item['tahap']}",
                'dijawab_at' => null,
            ]);
        }
    }

    public function test_catatan_wajib_saat_meminta_info_tambahan(): void
    {
        $staf = $this->buatPetugas('staf');
        $permintaan = $this->buatPermintaan('diajukan');

        $this->actingAs($staf)
            ->from(route('internal.permintaan.show', $permintaan))
            ->post(route('internal.permintaan.keputusan', $permintaan), [
                'keputusan' => 'minta_info',
                'catatan' => '',
            ])
            ->assertRedirect(route('internal.permintaan.show', $permintaan))
            ->assertSessionHasErrors('catatan');

        $this->assertDatabaseHas('permintaan_data', [
            'id' => $permintaan->id,
            'status' => 'diajukan',
        ]);
        $this->assertDatabaseCount('permintaan_klarifikasi', 0);
    }

    public function test_role_yang_tidak_sesuai_tahap_mendapat_403(): void
    {
        $kasi = $this->buatPetugas('kasi');
        $permintaan = $this->buatPermintaan('diajukan');

        $this->actingAs($kasi)
            ->post(route('internal.permintaan.keputusan', $permintaan), [
                'keputusan' => 'minta_info',
                'catatan' => 'Pertanyaan yang tidak berwenang',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('permintaan_data', [
            'id' => $permintaan->id,
            'status' => 'diajukan',
        ]);
        $this->assertDatabaseCount('permintaan_klarifikasi', 0);
    }

    public function test_jawaban_pemilik_mengembalikan_status_ke_tahap_yang_meminta(): void
    {
        $skenario = [
            ['role' => 'staf', 'status' => 'diajukan', 'status_kembali' => 'diajukan'],
        ];

        foreach ($skenario as $item) {
            $pemohon = $this->buatPemohon();
            $permintaan = $this->buatPermintaan($item['status'], $pemohon);
            $petugas = $this->buatPetugas($item['role']);
            $this->mintaInfo($petugas, $permintaan, "Pertanyaan {$item['role']}");

            $this->withSession($this->sesiPemohon($pemohon))
                ->post(route('pemohon.permintaan.info-tambahan.jawab', $permintaan), [
                    'jawaban' => "Jawaban untuk {$item['role']}",
                ])
                ->assertRedirect(route('pemohon.permintaan.show', $permintaan));

            $this->assertDatabaseHas('permintaan_data', [
                'id' => $permintaan->id,
                'status' => $item['status_kembali'],
            ]);
            $this->assertDatabaseHas('permintaan_klarifikasi', [
                'permintaan_data_id' => $permintaan->id,
                'jawaban' => "Jawaban untuk {$item['role']}",
            ]);
            $this->assertNotNull(
                PermintaanKlarifikasi::where('permintaan_data_id', $permintaan->id)->value('dijawab_at')
            );
            $this->assertDatabaseHas('notifikasi_log', [
                'permintaan_data_id' => $permintaan->id,
                'tujuan_email' => $petugas->email,
                'jenis_notifikasi' => 'info_tambahan_dijawab',
                'status_kirim' => 'berhasil',
            ]);
        }
    }

    public function test_pemohon_lain_tidak_dapat_menjawab_klarifikasi(): void
    {
        $pemilik = $this->buatPemohon();
        $pemohonLain = $this->buatPemohon();
        $permintaan = $this->buatPermintaan('diajukan', $pemilik);
        $this->mintaInfo($this->buatPetugas('staf'), $permintaan, 'Mohon lengkapi tujuan penggunaan.');

        $this->withSession($this->sesiPemohon($pemohonLain))
            ->post(route('pemohon.permintaan.info-tambahan.jawab', $permintaan), [
                'jawaban' => 'Jawaban dari pemohon lain',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('permintaan_data', [
            'id' => $permintaan->id,
            'status' => 'menunggu_info_pemohon',
        ]);
        $this->assertDatabaseHas('permintaan_klarifikasi', [
            'permintaan_data_id' => $permintaan->id,
            'jawaban' => null,
            'dijawab_at' => null,
        ]);
    }

    public function test_pengiriman_jawaban_kedua_tidak_menimpa_jawaban_pertama(): void
    {
        $pemohon = $this->buatPemohon();
        $permintaan = $this->buatPermintaan('diajukan', $pemohon);
        $this->mintaInfo($this->buatPetugas('staf'), $permintaan, 'Mohon jelaskan keluaran yang diperlukan.');

        $this->withSession($this->sesiPemohon($pemohon))
            ->post(route('pemohon.permintaan.info-tambahan.jawab', $permintaan), [
                'jawaban' => 'Jawaban pertama yang sah',
            ])
            ->assertRedirect(route('pemohon.permintaan.show', $permintaan));

        $this->from(route('pemohon.permintaan.show', $permintaan))
            ->post(route('pemohon.permintaan.info-tambahan.jawab', $permintaan), [
                'jawaban' => 'Jawaban kedua yang tidak boleh tersimpan',
            ])
            ->assertRedirect(route('pemohon.permintaan.show', $permintaan))
            ->assertSessionHasErrors('jawaban');

        $this->assertDatabaseCount('permintaan_klarifikasi', 1);
        $this->assertDatabaseHas('permintaan_klarifikasi', [
            'permintaan_data_id' => $permintaan->id,
            'jawaban' => 'Jawaban pertama yang sah',
        ]);
        $this->assertDatabaseMissing('permintaan_klarifikasi', [
            'permintaan_data_id' => $permintaan->id,
            'jawaban' => 'Jawaban kedua yang tidak boleh tersimpan',
        ]);
    }

    public function test_approval_ditolak_selama_masih_menunggu_jawaban_pemohon(): void
    {
        $staf = $this->buatPetugas('staf');
        $permintaan = $this->buatPermintaan('diajukan');
        $this->mintaInfo($staf, $permintaan, 'Mohon lengkapi informasi.');

        $this->actingAs($staf)
            ->from(route('internal.permintaan.show', $permintaan))
            ->post(route('internal.permintaan.keputusan', $permintaan), [
                'keputusan' => 'setuju',
            ])
            ->assertRedirect(route('internal.permintaan.show', $permintaan))
            ->assertSessionHasErrors('keputusan');

        $this->assertDatabaseHas('permintaan_data', [
            'id' => $permintaan->id,
            'status' => 'menunggu_info_pemohon',
        ]);
        $this->assertDatabaseCount('permintaan_approval_log', 0);
    }

    public function test_putaran_klarifikasi_kedua_disimpan_tanpa_menghapus_putaran_pertama(): void
    {
        $staf = $this->buatPetugas('staf');
        $pemohon = $this->buatPemohon();
        $permintaan = $this->buatPermintaan('diajukan', $pemohon);

        $this->mintaInfo($staf, $permintaan, 'Pertanyaan putaran pertama');
        $this->withSession($this->sesiPemohon($pemohon))
            ->post(route('pemohon.permintaan.info-tambahan.jawab', $permintaan), [
                'jawaban' => 'Jawaban putaran pertama',
            ]);

        $this->mintaInfo($staf, $permintaan->fresh(), 'Pertanyaan putaran kedua');

        $klarifikasi = PermintaanKlarifikasi::where('permintaan_data_id', $permintaan->id)
            ->oldest('id')
            ->get();

        $this->assertCount(2, $klarifikasi);
        $this->assertSame('Pertanyaan putaran pertama', $klarifikasi[0]->pertanyaan);
        $this->assertSame('Jawaban putaran pertama', $klarifikasi[0]->jawaban);
        $this->assertNotNull($klarifikasi[0]->dijawab_at);
        $this->assertSame('Pertanyaan putaran kedua', $klarifikasi[1]->pertanyaan);
        $this->assertNull($klarifikasi[1]->jawaban);
        $this->assertNull($klarifikasi[1]->dijawab_at);
        $this->assertSame('menunggu_info_pemohon', $permintaan->fresh()->status);
    }

    public function test_catatan_internal_tidak_tampil_pada_halaman_pemohon(): void
    {
        $pemohon = $this->buatPemohon();
        $permintaan = $this->buatPermintaan('diajukan', $pemohon);

        $this->mintaInfo(
            $this->buatPetugas('staf'),
            $permintaan,
            'Pertanyaan yang boleh dibaca pemohon',
            'RAHASIA-INTERNAL-TIDAK-BOLEH-BOCOR'
        );

        $this->withSession($this->sesiPemohon($pemohon))
            ->get(route('pemohon.permintaan.show', $permintaan))
            ->assertOk()
            ->assertSee('Pertanyaan yang boleh dibaca pemohon')
            ->assertDontSee('RAHASIA-INTERNAL-TIDAK-BOLEH-BOCOR');
    }

    public function test_cek_status_tamu_tidak_membocorkan_isi_klarifikasi(): void
    {
        $pemohon = $this->buatPemohon();
        $permintaan = $this->buatPermintaan('diajukan', $pemohon);
        $pertanyaan = 'RINCIAN-WILAYAH-YANG-BERSIFAT-PRIVAT';

        $this->mintaInfo(
            $this->buatPetugas('staf'),
            $permintaan,
            $pertanyaan,
            'CATATAN-INTERNAL-RAHASIA'
        );

        $this->withSession([
            "akses_status.{$permintaan->id}" => now()->addMinutes(10)->timestamp,
        ])->get(route('status.cek', $permintaan->nomor_tiket))
            ->assertOk()
            ->assertSee('Menunggu Info Pemohon')
            ->assertSee('Masuk untuk Menjawab')
            ->assertDontSee($pertanyaan)
            ->assertDontSee('CATATAN-INTERNAL-RAHASIA');
    }

    public function test_permintaan_info_mengirim_email_dan_mencatat_log_bila_email_tersedia(): void
    {
        Mail::spy();

        $pemohon = $this->buatPemohon('pemohon.notifikasi@example.com');
        $permintaan = $this->buatPermintaan('diajukan', $pemohon);
        $pertanyaan = 'Mohon tambahkan rincian wilayah yang dibutuhkan.';
        $catatanInternal = 'Catatan ini hanya untuk petugas.';

        $this->mintaInfo(
            $this->buatPetugas('staf'),
            $permintaan,
            $pertanyaan,
            $catatanInternal
        );

        Mail::shouldHaveReceived('raw')
            ->once()
            ->withArgs(fn (string $pesan, mixed $callback): bool => str_contains($pesan, $pertanyaan)
                && ! str_contains($pesan, $catatanInternal)
                && is_callable($callback));

        $this->assertDatabaseHas('notifikasi_log', [
            'permintaan_data_id' => $permintaan->id,
            'tujuan_email' => 'pemohon.notifikasi@example.com',
            'jenis_notifikasi' => 'info_tambahan_diminta',
            'status_kirim' => 'berhasil',
        ]);
    }

    private function buatPetugas(string $role): User
    {
        $petugas = User::factory()->create();
        $petugas->assignRole($role);

        return $petugas;
    }

    private function buatPemohon(?string $email = null): Pemohon
    {
        $urutan = $this->urutanPemohon++;

        return Pemohon::create([
            'nama' => "Pemohon {$urutan}",
            'no_hp' => sprintf('62812000%05d', $urutan),
            'email' => $email,
            'jenis_pemohon' => 'publik',
            'no_hp_verified_at' => now(),
        ]);
    }

    private function buatPermintaan(string $status, ?Pemohon $pemohon = null): PermintaanData
    {
        $pemohon ??= $this->buatPemohon();

        return PermintaanData::create([
            'nomor_tiket' => sprintf('BPS/PD/2026/%05d', $this->urutanTiket++),
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Kependudukan',
            'tujuan_penggunaan' => 'Penelitian dan perencanaan',
            'periode_data' => '2025',
            'status' => $status,
        ]);
    }

    /**
     * @return array{pemohon_id: int, pemohon_otp: string}
     */
    private function sesiPemohon(Pemohon $pemohon): array
    {
        return [
            'pemohon_id' => $pemohon->id,
            'pemohon_otp' => $pemohon->no_hp,
        ];
    }

    private function mintaInfo(
        User $petugas,
        PermintaanData $permintaan,
        string $pertanyaan,
        ?string $catatanInternal = null
    ): void {
        $respons = $this->actingAs($petugas)
            ->post(route('internal.permintaan.keputusan', $permintaan), [
                'keputusan' => 'minta_info',
                'catatan' => $pertanyaan,
                'catatan_internal' => $catatanInternal,
            ]);

        $respons->assertRedirect(route('internal.permintaan.index'));
    }
}
