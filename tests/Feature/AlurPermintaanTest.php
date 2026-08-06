<?php

namespace Tests\Feature;

use App\Models\KategoriData;
use App\Models\Pemohon;
use App\Models\PermintaanData;
use App\Models\PermintaanApprovalLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AlurPermintaanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\KategoriSeeder::class);
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

        $this->withSession([
            'pemohon_otp' => $pemohon->no_hp,
            'pemohon_id' => $pemohon->id,
        ])->post('/permintaan', [
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
        $this->get('/permintaan/create')->assertRedirect(route('otp.form'));
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

        $this->get('/status/' . $permintaan->nomor_tiket . '?no_hp=' . $pemohon->no_hp)
            ->assertOk()
            ->assertSee($permintaan->nomor_tiket);

        $this->get('/status/' . $permintaan->nomor_tiket . '?no_hp=089999999999')
            ->assertNotFound();
    }

    public function test_alur_approval_berjenjang_staf_kasi_kabid_dan_upload(): void
    {
        $staf = \App\Models\User::factory()->create();
        $staf->assignRole('staf');
        $kasi = \App\Models\User::factory()->create();
        $kasi->assignRole('kasi');
        $kabid = \App\Models\User::factory()->create();
        $kabid->assignRole('kabid');

        $pemohon = $this->registerPemohon();
        $permintaan = PermintaanData::create([
            'nomor_tiket' => 'BPS/PD/2025/00002',
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Khusus',
            'tujuan_penggunaan' => 'Riset',
            'periode_data' => '2024',
            'status' => 'diajukan',
        ]);

        // Tahap 1: Staf menyetujui.
        $this->actingAs($staf)->post("/internal/permintaan/{$permintaan->id}/keputusan", [
            'keputusan' => 'setuju',
        ])->assertRedirect(route('internal.permintaan.index'));

        $this->assertDatabaseHas('permintaan_data', ['id' => $permintaan->id, 'status' => 'diverifikasi_staf']);
        $this->assertDatabaseHas('permintaan_approval_log', [
            'permintaan_data_id' => $permintaan->id,
            'tahap' => 'staf',
            'keputusan' => 'setuju',
        ]);

        // Tahap 2: Kasi menyetujui.
        $this->actingAs($kasi)->post("/internal/permintaan/{$permintaan->id}/keputusan", [
            'keputusan' => 'setuju',
        ])->assertRedirect(route('internal.permintaan.index'));

        $this->assertDatabaseHas('permintaan_data', ['id' => $permintaan->id, 'status' => 'disetujui_kasi']);

        // Tahap 3: Kabid menyetujui -> menunggu upload.
        $this->actingAs($kabid)->post("/internal/permintaan/{$permintaan->id}/keputusan", [
            'keputusan' => 'setuju',
        ])->assertRedirect(route('internal.permintaan.index'));

        $this->assertDatabaseHas('permintaan_data', ['id' => $permintaan->id, 'status' => 'menunggu_upload']);

        // Tahap 4: Staf upload hasil -> data siap.
        $file = \Illuminate\Http\UploadedFile::fake()->create('hasil.xlsx', 100);
        $this->actingAs($staf)->post("/internal/permintaan/{$permintaan->id}/upload", [
            'file_hasil' => $file,
        ])->assertRedirect(route('internal.permintaan.index'));

        $this->assertDatabaseHas('permintaan_data', ['id' => $permintaan->id, 'status' => 'data_siap']);
        $this->assertNotNull(PermintaanData::find($permintaan->id)->file_hasil_path);
    }

    public function test_user_tanpa_role_tidak_bisa_melakukan_approval(): void
    {
        $user = \App\Models\User::factory()->create();
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
        $staf = \App\Models\User::factory()->create();
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
        $staf = \App\Models\User::factory()->create();
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
    }
}
