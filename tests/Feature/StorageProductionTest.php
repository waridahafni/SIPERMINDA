<?php

namespace Tests\Feature;

use App\Models\Pemohon;
use App\Models\PermintaanData;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageProductionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config()->set('filesystems.documents', 's3');
        // Kelompok ini memeriksa jalur multipart; direct upload diuji terpisah memakai SDK S3.
        config()->set('filesystems.direct_upload', false);
        Storage::fake('s3');
        Storage::fake('local');
        Mail::fake();
    }

    private function permintaan(): PermintaanData
    {
        $pemohon = Pemohon::create([
            'nama' => 'Pemohon Storage', 'no_hp' => '6281234567890',
            'email' => 'storage@example.com', 'jenis_pemohon' => 'publik', 'no_hp_verified_at' => now(),
        ]);

        return PermintaanData::create([
            'pemohon_id' => $pemohon->id, 'nomor_tiket' => 'BPS/PD/2026/00001',
            'jenis_data' => 'Statistik', 'tujuan_penggunaan' => 'Penelitian', 'status' => 'disetujui_petugas',
        ]);
    }

    public function test_upload_memakai_disk_production_dan_hanya_pemilik_bisa_mengunduh(): void
    {
        $permintaan = $this->permintaan();
        $staf = User::factory()->create();
        $staf->assignRole('staf');
        $this->actingAs($staf)->post(route('internal.permintaan.upload', $permintaan), [
            'file_hasil' => UploadedFile::fake()->create('hasil.pdf', 8, 'application/pdf'),
        ])->assertRedirect(route('internal.permintaan.index'));

        $permintaan->refresh();
        $this->assertSame('data_siap', $permintaan->status);
        Storage::disk('s3')->assertExists($permintaan->file_hasil_path);
        Storage::disk('local')->assertMissing($permintaan->file_hasil_path);
        Storage::disk('s3')->put($permintaan->file_hasil_path, 'dokumen privat');
        $this->assertDatabaseHas('notifikasi_log', ['permintaan_data_id' => $permintaan->id, 'status_kirim' => 'berhasil']);

        $lain = Pemohon::create(['nama' => 'Lain', 'no_hp' => '6281234567891', 'jenis_pemohon' => 'publik', 'no_hp_verified_at' => now()]);
        $this->withSession(['pemohon_id' => $lain->id])->get(route('permintaan.unduh', $permintaan))->assertNotFound();
        $response = $this->withSession(['pemohon_id' => $permintaan->pemohon_id])->get(route('permintaan.unduh', $permintaan));
        $response->assertDownload();
        $this->assertSame('dokumen privat', $response->streamedContent());
    }

    public function test_email_gagal_dicatat_tanpa_membatalkan_upload_atau_membocorkan_error_provider(): void
    {
        $permintaan = $this->permintaan();
        $staf = User::factory()->create();
        $staf->assignRole('staf');
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('password-smtp-rahasia'));
        Log::spy();
        $this->actingAs($staf)->post(route('internal.permintaan.upload', $permintaan), [
            'file_hasil' => UploadedFile::fake()->create('hasil.pdf', 8, 'application/pdf'),
        ])->assertRedirect(route('internal.permintaan.index'));
        $this->assertSame('data_siap', $permintaan->fresh()->status);
        $this->assertDatabaseHas('notifikasi_log', ['permintaan_data_id' => $permintaan->id, 'status_kirim' => 'gagal']);
        Log::shouldHaveReceived('warning')->once()->with('Gagal kirim email notifikasi.', [
            'permintaan_id' => $permintaan->id, 'jenis_error' => \RuntimeException::class,
        ]);
    }

    public function test_error_storage_tidak_menyimpan_path_atau_mengaktifkan_unduhan(): void
    {
        $permintaan = $this->permintaan();
        $staf = User::factory()->create();
        $staf->assignRole('staf');
        Storage::shouldReceive('disk')->with('s3')->andReturnSelf();
        Storage::shouldReceive('putFileAs')->once()->andThrow(new \RuntimeException('storage tidak tersedia'));
        $this->actingAs($staf)->post(route('internal.permintaan.upload', $permintaan), [
            'file_hasil' => UploadedFile::fake()->create('hasil.pdf', 8, 'application/pdf'),
        ])->assertStatus(500);
        $this->assertNull($permintaan->fresh()->file_hasil_path);
        $this->assertSame('disetujui_petugas', $permintaan->status);
        $this->assertDatabaseCount('notifikasi_log', 0);
    }

    public function test_proxy_tepercaya_mempertahankan_https_tanpa_mempercayai_host_palsu(): void
    {
        config()->set('trustedproxy.proxies', '*');
        $this->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'jahat.example'])
            ->get('/daftar')->assertOk()
            ->assertSee('action="'.secure_url('/daftar/kirim-otp').'"', false)
            ->assertDontSee('jahat.example');
    }
}
