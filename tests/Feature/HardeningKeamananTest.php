<?php

namespace Tests\Feature;

use App\Models\DatasetTerbuka;
use App\Models\KategoriData;
use App\Models\OtpVerification;
use App\Models\Pemohon;
use App\Models\PermintaanData;
use App\Models\User;
use Database\Seeders\KategoriSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HardeningKeamananTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $this->seed(KategoriSeeder::class);
    }

    public function test_otp_disimpan_dalam_bentuk_hash(): void
    {
        $this->post('/otp/kirim', [
            'no_hp' => '081234567890',
            'nama' => 'Pemohon Test',
        ])->assertRedirect(route('otp.form'));

        $otp = OtpVerification::firstOrFail();

        $this->assertFalse((bool) preg_match('/^\d{6}$/', $otp->kode_otp));
        $this->assertFalse(Hash::needsRehash($otp->kode_otp));
    }

    public function test_otp_hash_yang_valid_dapat_diverifikasi(): void
    {
        OtpVerification::create([
            'no_hp' => '081234567890',
            'kode_otp' => Hash::make('123456'),
            'expired_at' => now()->addMinutes(5),
            'attempt_count' => 0,
            'created_at' => now(),
        ]);

        $this->withSession([
            'otp_pemohon' => [
                'no_hp' => '081234567890',
                'nama' => 'Pemohon Test',
                'email' => 'pemohon@example.com',
                'jenis_pemohon' => 'publik',
            ],
        ])->post('/otp/verifikasi', [
            'no_hp' => '081234567890',
            'kode_otp' => '123456',
        ])->assertRedirect(route('permintaan.create'));

        $this->assertDatabaseHas('pemohon', [
            'no_hp' => '081234567890',
            'nama' => 'Pemohon Test',
        ]);
        $this->assertNotNull(OtpVerification::first()->verified_at);
    }

    public function test_dataset_yang_digantikan_tidak_dapat_diakses_publik(): void
    {
        $dataset = DatasetTerbuka::create([
            'judul' => 'Dataset Lama',
            'periode' => '2025',
            'file_path' => 'dataset_terbuka/lama.pdf',
            'status' => 'digantikan',
        ]);

        Storage::disk('local')->put($dataset->file_path, 'isi');

        $this->get(route('katalog.detail', $dataset))->assertNotFound();
        $this->get(route('katalog.unduh', $dataset))->assertNotFound();
    }

    public function test_user_tanpa_role_tidak_dapat_melihat_permintaan_internal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('internal.permintaan.index'))
            ->assertForbidden();
    }

    public function test_upload_katalog_memakai_nama_acak_dan_menolak_file_berbahaya(): void
    {
        $staf = User::factory()->create();
        $staf->assignRole('staf');
        $kategori = KategoriData::firstOrFail();

        $this->actingAs($staf)->post(route('internal.katalog.store'), [
            'judul' => 'Dataset Aman',
            'kategori_id' => $kategori->id,
            'periode' => '2025',
            'deskripsi' => 'Dataset untuk pengujian keamanan.',
            'file' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('internal.katalog.index'));

        $dataset = DatasetTerbuka::firstOrFail();
        $this->assertMatchesRegularExpression(
            '/^dataset_terbuka\/[0-9a-f-]{36}\.pdf$/',
            $dataset->file_path
        );
        Storage::disk('local')->assertExists($dataset->file_path);

        $this->actingAs($staf)->post(route('internal.katalog.store'), [
            'judul' => 'File Berbahaya',
            'kategori_id' => $kategori->id,
            'periode' => '2025',
            'deskripsi' => 'File ini harus ditolak.',
            'file' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('file');
    }

    public function test_admin_tidak_dapat_menghapus_akunnya_sendiri(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->delete(route('internal.pengguna.destroy', $admin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_dashboard_dan_laporan_mendukung_database_sqlite(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $pemohon = Pemohon::create([
            'nama' => 'Pemohon Laporan',
            'no_hp' => '081234567899',
            'email' => 'laporan@example.com',
            'jenis_pemohon' => 'publik',
            'no_hp_verified_at' => now(),
        ]);

        PermintaanData::create([
            'nomor_tiket' => 'BPS/PD/2026/00001',
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Pengujian',
            'tujuan_penggunaan' => 'Menguji rekap bulanan',
            'periode_data' => '2026',
            'status' => 'diajukan',
        ]);

        $this->actingAs($admin)
            ->get(route('internal.dashboard'))
            ->assertOk()
            ->assertViewHas('perBulan', fn ($rekap) => $rekap->first()?->total === 1);

        $this->actingAs($admin)
            ->get(route('internal.laporan'))
            ->assertOk()
            ->assertViewHas('rekapBulanan', fn ($rekap) => $rekap->first()?->total === 1);
    }
}
