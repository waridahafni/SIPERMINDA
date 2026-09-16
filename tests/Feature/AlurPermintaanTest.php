<?php

namespace Tests\Feature;

use App\Models\KategoriData;
use App\Models\Pemohon;
use App\Models\PermintaanApprovalLog;
use App\Models\PermintaanData;
use App\Models\User;
use Database\Seeders\KategoriSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlurPermintaanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $this->seed(KategoriSeeder::class);
    }

    private function registerPemohon(string $noHp = '081234567890', string $email = 'pemohon@example.com'): Pemohon
    {
        return Pemohon::create([
            'nama' => 'Pemohon Test',
            'no_hp' => $noHp,
            'email' => $email,
            'jenis_pemohon' => 'publik',
            'no_hp_verified_at' => now(),
        ]);
    }

    public function test_permintaan_dapat_diajukan_dan_mendapat_nomor_tiket(): void
    {
        $pemohon = $this->registerPemohon();
        $kategori = KategoriData::first();

        $form = $this->withSession([
            'pemohon_otp' => $pemohon->no_hp,
            'pemohon_id' => $pemohon->id,
        ])->get(route('permintaan.create'))->assertOk();

        $this->post('/permintaan', [
            'idempotensi_token' => $form->viewData('idempotensiToken'),
            'jenis_data' => 'KCDA 2025',
            'tujuan_penggunaan' => 'Untuk penelitian skripsi',
            'periode_data' => '2025',
            'kategori_id' => $kategori->id,
        ])->assertRedirect();

        $permintaan = PermintaanData::first();
        $this->assertNotNull($permintaan);
        $this->assertStringContainsString('BPS/PD/', $permintaan->nomor_tiket);
        $this->assertEquals('diajukan', $permintaan->status);
        $this->assertEquals($pemohon->id, $permintaan->pemohon_id);
    }

    public function test_permintaan_tidak_bisa_diajukan_tanpa_verifikasi_otp(): void
    {
        $this->get('/permintaan/create')->assertRedirect(route('pemohon.masuk'));
    }

    public function test_cek_status_membutuhkan_tiket_dan_no_hp_yang_cocok(): void
    {
        $pemohon = $this->registerPemohon();
        $permintaan = PermintaanData::create([
            'nomor_tiket' => 'BPS/PD/2025/00001',
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Sosial',
            'tujuan_penggunaan' => 'Riset',
            'periode_data' => '2024',
            'status' => 'diajukan',
        ]);

        $this->get('/status/'.$permintaan->nomor_tiket)
            ->assertRedirect(route('cek-status'));

        $response = $this->post('/cek-status', [
            'nomor_tiket' => $permintaan->nomor_tiket,
            'no_hp' => $pemohon->no_hp,
        ])->assertRedirect();

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee($permintaan->nomor_tiket);

        $this->post('/cek-status', [
            'nomor_tiket' => $permintaan->nomor_tiket,
            'no_hp' => '089999999999',
        ])->assertSessionHasErrors('not_found');
    }

    public function test_petugas_menyetujui_lalu_mengunggah_hasil(): void
    {
        $staf = User::factory()->create();
        $staf->assignRole('staf');

        $pemohon = $this->registerPemohon();
        $permintaan = PermintaanData::create([
            'nomor_tiket' => 'BPS/PD/2025/00002',
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Khusus',
            'tujuan_penggunaan' => 'Riset',
            'periode_data' => '2024',
            'status' => 'diajukan',
        ]);

        // Petugas menyetujui permintaan.
        $this->actingAs($staf)->post("/internal/permintaan/{$permintaan->id}/keputusan", [
            'keputusan' => 'setuju',
        ])->assertRedirect(route('internal.permintaan.index'));

        $this->assertDatabaseHas('permintaan_data', ['id' => $permintaan->id, 'status' => 'disetujui_petugas']);
        $this->assertDatabaseHas('permintaan_approval_log', [
            'permintaan_data_id' => $permintaan->id,
            'tahap' => 'staf',
            'keputusan' => 'setuju',
        ]);

        // Petugas langsung mengunggah hasil setelah menyetujui.
        $file = UploadedFile::fake()->create('hasil.xlsx', 100);
        $this->actingAs($staf)->post("/internal/permintaan/{$permintaan->id}/upload", [
            'file_hasil' => $file,
        ])->assertRedirect(route('internal.permintaan.index'));

        $this->assertDatabaseHas('permintaan_data', ['id' => $permintaan->id, 'status' => 'data_siap']);
        $this->assertNotNull(PermintaanData::find($permintaan->id)->file_hasil_path);
    }

    public function test_user_tanpa_role_tidak_bisa_melakukan_approval(): void
    {
        $user = User::factory()->create();
        $pemohon = $this->registerPemohon();
        $permintaan = PermintaanData::create([
            'nomor_tiket' => 'BPS/PD/2025/00003',
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Khusus',
            'tujuan_penggunaan' => 'Riset',
            'periode_data' => '2024',
            'status' => 'diajukan',
        ]);

        $this->actingAs($user)->post("/internal/permintaan/{$permintaan->id}/keputusan", [
            'keputusan' => 'setuju',
        ])->assertForbidden();

        $this->assertDatabaseHas('permintaan_data', ['id' => $permintaan->id, 'status' => 'diajukan']);
    }

    public function test_penolakan_harus_mencantumkan_catatan(): void
    {
        $staf = User::factory()->create();
        $staf->assignRole('staf');

        $pemohon = $this->registerPemohon();
        $permintaan = PermintaanData::create([
            'nomor_tiket' => 'BPS/PD/2025/00004',
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Khusus',
            'tujuan_penggunaan' => 'Riset',
            'periode_data' => '2024',
            'status' => 'diajukan',
        ]);

        $this->actingAs($staf)->from("/internal/permintaan/{$permintaan->id}")
            ->post("/internal/permintaan/{$permintaan->id}/keputusan", [
                'keputusan' => 'tolak',
                'catatan' => '',
            ])
            ->assertSessionHasErrors('catatan');

        $this->assertDatabaseHas('permintaan_data', ['id' => $permintaan->id, 'status' => 'diajukan']);
    }

    public function test_timeline_approval_tercatat_di_log(): void
    {
        $staf = User::factory()->create();
        $staf->assignRole('staf');

        $pemohon = $this->registerPemohon();
        $permintaan = PermintaanData::create([
            'nomor_tiket' => 'BPS/PD/2025/00005',
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Khusus',
            'tujuan_penggunaan' => 'Riset',
            'periode_data' => '2024',
            'status' => 'diajukan',
        ]);

        $this->actingAs($staf)->post("/internal/permintaan/{$permintaan->id}/keputusan", [
            'keputusan' => 'setuju',
            'catatan' => 'Kelengkapan ok',
        ]);

        $log = PermintaanApprovalLog::where('permintaan_data_id', $permintaan->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('staf', $log->tahap);
        $this->assertEquals('setuju', $log->keputusan);
        $this->assertEquals('Kelengkapan ok', $log->catatan);
        $this->assertEquals($staf->id, $log->approver_id);
        $this->assertInstanceOf(\DateTimeInterface::class, $log->created_at);

        $this->actingAs($staf)
            ->get(route('internal.permintaan.show', $permintaan))
            ->assertOk()
            ->assertSee($log->created_at->format('d M Y H:i'));
    }

    public function test_permintaan_data_siap_dapat_ditandai_selesai(): void
    {
        $staf = User::factory()->create();
        $staf->assignRole('staf');

        $pemohon = $this->registerPemohon();
        $permintaan = PermintaanData::create([
            'nomor_tiket' => 'BPS/PD/2025/00006',
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Khusus',
            'tujuan_penggunaan' => 'Riset',
            'periode_data' => '2024',
            'status' => 'data_siap',
            'file_hasil_path' => 'hasil_permintaan/uji.xlsx',
        ]);

        $this->actingAs($staf)->post("/internal/permintaan/{$permintaan->id}/selesai")
            ->assertRedirect(route('internal.permintaan.index'));

        $this->assertDatabaseHas('permintaan_data', ['id' => $permintaan->id, 'status' => 'selesai']);
    }

    public function test_permintaan_belum_data_siap_tidak_bisa_ditandai_selesai(): void
    {
        $staf = User::factory()->create();
        $staf->assignRole('staf');

        $pemohon = $this->registerPemohon();
        $permintaan = PermintaanData::create([
            'nomor_tiket' => 'BPS/PD/2025/00007',
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Khusus',
            'tujuan_penggunaan' => 'Riset',
            'periode_data' => '2024',
            'status' => 'diajukan',
        ]);

        $this->actingAs($staf)->post("/internal/permintaan/{$permintaan->id}/selesai");

        $this->assertDatabaseHas('permintaan_data', ['id' => $permintaan->id, 'status' => 'diajukan']);
    }
}
