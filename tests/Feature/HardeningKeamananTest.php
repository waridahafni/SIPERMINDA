<?php

namespace Tests\Feature;

use App\Exceptions\PengirimanOtpException;
use App\Models\DatasetTerbuka;
use App\Models\KategoriData;
use App\Models\OtpVerification;
use App\Models\Pemohon;
use App\Models\PermintaanData;
use App\Models\User;
use App\Services\Otp\PengirimOtpLog;
use App\Support\NomorTeleponIndonesia;
use Database\Seeders\KategoriSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HardeningKeamananTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $this->seed(KategoriSeeder::class);
    }

    private function gunakanDriverWhatsApp(): void
    {
        config()->set([
            'otp.driver' => 'whatsapp',
            'otp.whatsapp.api_version' => 'v99.0',
            'otp.whatsapp.phone_number_id' => '123456789',
            'otp.whatsapp.access_token' => 'token-pengujian',
            'otp.whatsapp.template_name' => 'siperminda_kode_otp',
            'otp.whatsapp.template_language' => 'id',
            'otp.whatsapp.timeout_seconds' => 5,
        ]);
    }

    private function dataKirimOtp(string $noHp, string $nama, array $tambahan = []): array
    {
        return array_merge([
            'no_hp' => $noHp,
            'nama' => $nama,
            'email' => null,
            'jenis_pemohon' => 'publik',
            'nama_instansi' => null,
        ], $tambahan);
    }

    public function test_otp_disimpan_dalam_bentuk_hash(): void
    {
        $this->post('/otp/kirim', $this->dataKirimOtp(
            '081234567890',
            'Pemohon Test',
        ))->assertRedirect(route('otp.form'));

        $otp = OtpVerification::firstOrFail();

        $this->assertFalse((bool) preg_match('/^\d{6}$/', $otp->kode_otp));
        $this->assertFalse(Hash::needsRehash($otp->kode_otp));
    }

    public function test_otp_hash_yang_valid_dapat_diverifikasi(): void
    {
        OtpVerification::create([
            'no_hp' => '6281234567890',
            'kode_otp' => Hash::make('123456'),
            'expired_at' => now()->addMinutes(5),
            'attempt_count' => 0,
            'created_at' => now(),
        ]);

        $this->withSession([
            'otp_pemohon' => [
                'no_hp' => '6281234567890',
                'nama' => 'Pemohon Test',
                'email' => 'pemohon@example.com',
                'jenis_pemohon' => 'publik',
            ],
        ])->post('/otp/verifikasi', [
            'no_hp' => '081234567890',
            'kode_otp' => '123456',
        ])->assertRedirect(route('permintaan.create'));

        $this->assertDatabaseHas('pemohon', [
            'no_hp' => '6281234567890',
            'nama' => 'Pemohon Test',
        ]);
        $this->assertNotNull(OtpVerification::first()->verified_at);
    }

    public function test_otp_dikirim_melalui_template_whatsapp_dan_hanya_disimpan_sebagai_hash(): void
    {
        $this->gunakanDriverWhatsApp();
        Http::fake([
            'https://graph.facebook.com/v99.0/123456789/messages' => Http::response([
                'messages' => [['id' => 'wamid.pengujian']],
            ]),
        ]);

        $this->post('/otp/kirim', $this->dataKirimOtp(
            '081234567890',
            'Pemohon WhatsApp',
        ))->assertRedirect(route('otp.form'))
            ->assertSessionHas('success');

        $kodeTerkirim = null;

        Http::assertSent(function (ClientRequest $request) use (&$kodeTerkirim): bool {
            $payload = $request->data();
            $kodeTerkirim = data_get($payload, 'template.components.0.parameters.0.text');

            return $request->url() === 'https://graph.facebook.com/v99.0/123456789/messages'
                && $request->hasHeader('Authorization', 'Bearer token-pengujian')
                && $request->hasHeader('Content-Type', 'application/json')
                && data_get($payload, 'messaging_product') === 'whatsapp'
                && data_get($payload, 'recipient_type') === 'individual'
                && data_get($payload, 'to') === '6281234567890'
                && data_get($payload, 'type') === 'template'
                && data_get($payload, 'template.name') === 'siperminda_kode_otp'
                && data_get($payload, 'template.language.code') === 'id'
                && data_get($payload, 'template.components.0.type') === 'body'
                && data_get($payload, 'template.components.1.type') === 'button'
                && data_get($payload, 'template.components.1.sub_type') === 'url'
                && data_get($payload, 'template.components.1.index') === '0'
                && data_get($payload, 'template.components.1.parameters.0.text') === $kodeTerkirim;
        });
        Http::assertSentCount(1);

        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $kodeTerkirim);

        $otp = OtpVerification::firstOrFail();
        $this->assertSame('6281234567890', $otp->no_hp);
        $this->assertTrue(Hash::check($kodeTerkirim, $otp->kode_otp));
        $this->assertNotSame($kodeTerkirim, $otp->kode_otp);
    }

    public function test_kegagalan_provider_whatsapp_tidak_mematikan_otp_lama(): void
    {
        $this->gunakanDriverWhatsApp();
        Http::fake([
            'https://graph.facebook.com/v99.0/123456789/messages' => Http::response([
                'error' => [
                    'code' => 131000,
                    'fbtrace_id' => 'trace-pengujian',
                ],
            ], 500),
        ]);

        $otpLama = OtpVerification::create([
            'no_hp' => '6281234567891',
            'kode_otp' => Hash::make('111111'),
            'expired_at' => now()->addMinutes(5),
            'attempt_count' => 0,
            'created_at' => now()->subMinute(),
        ]);

        $this->post('/otp/kirim', $this->dataKirimOtp(
            '+6281234567891',
            'Pemohon Gagal',
        ))->assertSessionHas('error')
            ->assertSessionMissing('success');

        $this->assertTrue($otpLama->fresh()->expired_at->isFuture());

        $otpBaru = OtpVerification::whereKeyNot($otpLama->id)->firstOrFail();
        $this->assertTrue($otpBaru->expired_at->lessThanOrEqualTo(now()));
    }

    public function test_respons_whatsapp_tanpa_id_pesan_gagal_tertutup(): void
    {
        $this->gunakanDriverWhatsApp();
        Http::fake([
            'https://graph.facebook.com/v99.0/123456789/messages' => Http::response([
                'messages' => [],
            ]),
        ]);

        $this->post('/otp/kirim', $this->dataKirimOtp(
            '081234567892',
            'Pemohon Respons Kosong',
        ))->assertSessionHas('error')
            ->assertSessionMissing('success');

        $this->assertTrue(OtpVerification::firstOrFail()->expired_at->lessThanOrEqualTo(now()));
    }

    public function test_konfigurasi_whatsapp_yang_tidak_lengkap_gagal_tertutup(): void
    {
        $this->gunakanDriverWhatsApp();
        config()->set('otp.whatsapp.access_token');
        $this->post('/otp/kirim', $this->dataKirimOtp(
            '081234567893',
            'Pemohon Tanpa Token',
        ))->assertSessionHas('error')
            ->assertSessionMissing('success');

        Http::assertNothingSent();
        $this->assertTrue(OtpVerification::firstOrFail()->expired_at->lessThanOrEqualTo(now()));
    }

    public function test_driver_log_ditolak_pada_environment_production(): void
    {
        $this->app->instance('env', 'production');
        $this->assertTrue(app()->environment('production'));
        $this->expectException(PengirimanOtpException::class);

        app(PengirimOtpLog::class)->kirim('6281234567894', '123456');
    }

    public function test_rate_limit_memperlakukan_semua_format_nomor_sebagai_nomor_yang_sama(): void
    {
        $this->gunakanDriverWhatsApp();
        Http::fake(fn () => Http::response([
            'messages' => [['id' => 'wamid.pengujian']],
        ]));

        foreach (['081234567895', '6281234567895', '+6281234567895'] as $nomorHp) {
            $this->post('/otp/kirim', $this->dataKirimOtp(
                $nomorHp,
                'Pemohon Rate Limit',
            ))->assertSessionHas('success');
        }

        $this->post('/otp/kirim', $this->dataKirimOtp(
            '081234567895',
            'Pemohon Rate Limit',
        ))->assertSessionHas('error', fn (string $pesan) => str_contains($pesan, 'Terlalu banyak permintaan OTP'));

        Http::assertSentCount(3);
    }

    public function test_nomor_verifikasi_harus_sama_dengan_nomor_di_session(): void
    {
        $otp = OtpVerification::create([
            'no_hp' => '6281234567896',
            'kode_otp' => Hash::make('123456'),
            'expired_at' => now()->addMinutes(5),
            'attempt_count' => 0,
            'created_at' => now(),
        ]);

        $this->withSession([
            'otp_pemohon' => ['no_hp' => '6281234567897'],
        ])->post('/otp/verifikasi', [
            'no_hp' => '081234567896',
            'kode_otp' => '123456',
        ])->assertRedirect(route('otp.form'))
            ->assertSessionHas('error');

        $this->assertNull($otp->fresh()->verified_at);
    }

    public function test_nomor_tidak_valid_dikembalikan_sebagai_error_validasi(): void
    {
        $this->post('/otp/kirim', $this->dataKirimOtp(
            '000000000',
            'Pemohon Nomor Tidak Valid',
        ))->assertSessionHasErrors('no_hp');

        $this->assertDatabaseCount('otp_verifications', 0);
        Http::assertNothingSent();
    }

    public function test_verifikasi_otp_menggunakan_kembali_pemohon_dengan_format_nomor_lama(): void
    {
        $pemohonLama = Pemohon::create([
            'nama' => 'Pemohon Lama',
            'no_hp' => '081234567898',
            'email' => 'lama@example.com',
            'jenis_pemohon' => 'publik',
            'no_hp_verified_at' => now()->subDay(),
        ]);
        $permintaanLama = PermintaanData::create([
            'nomor_tiket' => 'BPS/PD/2026/00998',
            'pemohon_id' => $pemohonLama->id,
            'jenis_data' => 'Riwayat Data Lama',
            'tujuan_penggunaan' => 'Menguji kompatibilitas nomor lama',
            'periode_data' => '2025',
            'status' => 'data_siap',
            'file_hasil_path' => 'hasil_permintaan/riwayat-lama.pdf',
        ]);
        Storage::disk('local')->put($permintaanLama->file_hasil_path, 'hasil lama');

        OtpVerification::create([
            'no_hp' => '6281234567898',
            'kode_otp' => Hash::make('123456'),
            'expired_at' => now()->addMinutes(5),
            'attempt_count' => 0,
            'created_at' => now(),
        ]);

        $this->withSession([
            'otp_pemohon' => [
                'no_hp' => '6281234567898',
                'nama' => 'Pemohon Lama',
                'email' => 'lama@example.com',
                'jenis_pemohon' => 'publik',
            ],
        ])->post('/otp/verifikasi', [
            'no_hp' => '+6281234567898',
            'kode_otp' => '123456',
        ])->assertRedirect(route('permintaan.create'))
            ->assertSessionHas('pemohon_id', $pemohonLama->id)
            ->assertSessionHas('pemohon_otp', '6281234567898');

        $this->assertDatabaseCount('pemohon', 1);
        $this->assertDatabaseHas('pemohon', [
            'id' => $pemohonLama->id,
            'no_hp' => '6281234567898',
        ]);
        $this->assertDatabaseHas('permintaan_data', [
            'id' => $permintaanLama->id,
            'pemohon_id' => $pemohonLama->id,
        ]);
        $this->get(route('permintaan.selesai', $permintaanLama))->assertOk();
        $this->get(route('permintaan.unduh', $permintaanLama))->assertDownload();
    }

    public function test_duplikasi_varian_nomor_pemohon_ditolak_tanpa_menghabiskan_otp(): void
    {
        foreach (['081234567897', '6281234567897'] as $index => $nomorHp) {
            Pemohon::create([
                'nama' => 'Pemohon Duplikat '.($index + 1),
                'no_hp' => $nomorHp,
                'jenis_pemohon' => 'publik',
                'no_hp_verified_at' => now(),
            ]);
        }

        $otp = OtpVerification::create([
            'no_hp' => '6281234567897',
            'kode_otp' => Hash::make('123456'),
            'expired_at' => now()->addMinutes(5),
            'attempt_count' => 0,
            'created_at' => now(),
        ]);

        $this->withSession([
            'otp_pemohon' => [
                'no_hp' => '6281234567897',
                'nama' => 'Pemohon Duplikat',
                'jenis_pemohon' => 'publik',
            ],
        ])->post('/otp/verifikasi', [
            'no_hp' => '081234567897',
            'kode_otp' => '123456',
        ])->assertSessionHas('error', fn (string $pesan) => str_contains($pesan, 'ditinjau oleh admin'));

        $this->assertNull($otp->fresh()->verified_at);
        $this->assertDatabaseCount('pemohon', 2);
    }

    public function test_data_profil_tidak_valid_tidak_memicu_pengiriman_whatsapp(): void
    {
        $this->gunakanDriverWhatsApp();

        $this->post('/otp/kirim', [
            'no_hp' => '081234567880',
            'nama' => '',
            'email' => 'bukan-email',
            'jenis_pemohon' => 'tidak-dikenal',
        ])->assertSessionHasErrors(['nama', 'email', 'jenis_pemohon']);

        Http::assertNothingSent();
        $this->assertDatabaseCount('otp_verifications', 0);
    }

    public function test_kirim_ulang_memakai_data_session_yang_sudah_divalidasi(): void
    {
        $this->gunakanDriverWhatsApp();
        Http::fake(fn () => Http::response([
            'messages' => [['id' => 'wamid.pengujian']],
        ]));

        $this->post('/otp/kirim', $this->dataKirimOtp(
            '081234567881',
            'Pemohon Kirim Ulang',
            ['email' => 'ulang@example.com'],
        ))->assertSessionHas('success');

        $this->post('/otp/kirim-ulang')->assertSessionHas('success');

        Http::assertSentCount(2);
        $this->assertSame('Pemohon Kirim Ulang', session('otp_pemohon.nama'));
        $this->assertSame('ulang@example.com', session('otp_pemohon.email'));
        $this->assertDatabaseCount('otp_verifications', 2);
        $this->assertSame(1, OtpVerification::where('expired_at', '>', now())->count());
    }

    public function test_koneksi_provider_putus_gagal_tertutup_tanpa_retry_otomatis(): void
    {
        $this->gunakanDriverWhatsApp();
        Http::fake([
            'https://graph.facebook.com/v99.0/123456789/messages' => Http::failedConnection('timeout pengujian'),
        ]);

        $this->post('/otp/kirim', $this->dataKirimOtp(
            '081234567882',
            'Pemohon Timeout',
        ))->assertSessionHas('error');

        Http::assertSentCount(1);
        $this->assertTrue(OtpVerification::firstOrFail()->expired_at->lessThanOrEqualTo(now()));
    }

    public function test_lock_nomor_mencegah_pengiriman_paralel(): void
    {
        $this->gunakanDriverWhatsApp();
        $nomorHp = '6281234567883';
        $lock = Cache::lock(
            'otp-kirim-lock:'.NomorTeleponIndonesia::kunciRateLimit($nomorHp),
            30,
        );
        $this->assertTrue($lock->get());

        try {
            $this->post('/otp/kirim', $this->dataKirimOtp(
                $nomorHp,
                'Pemohon Paralel',
            ))->assertSessionHas('error', fn (string $pesan) => str_contains($pesan, 'sedang diproses'));
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('otp_verifications', 0);
    }

    public function test_cek_status_menerima_semua_format_nomor_yang_setara(): void
    {
        $pemohon = Pemohon::create([
            'nama' => 'Pemohon Status',
            'no_hp' => '6281234567884',
            'jenis_pemohon' => 'publik',
            'no_hp_verified_at' => now(),
        ]);
        $permintaan = PermintaanData::create([
            'nomor_tiket' => 'BPS/PD/2026/00999',
            'pemohon_id' => $pemohon->id,
            'jenis_data' => 'Data Status',
            'tujuan_penggunaan' => 'Menguji format nomor status',
            'periode_data' => '2026',
            'status' => 'diajukan',
        ]);

        foreach (['081234567884', '6281234567884', '+6281234567884'] as $nomorHp) {
            $this->post('/cek-status', [
                'nomor_tiket' => $permintaan->nomor_tiket,
                'no_hp' => $nomorHp,
            ])->assertRedirect(route('status.cek', ['nomorTiket' => $permintaan->nomor_tiket]));
        }
    }

    public function test_masa_berlaku_otp_dibatasi_maksimal_lima_menit(): void
    {
        config()->set('otp.kedaluwarsa_menit', 999);

        $this->post('/otp/kirim', $this->dataKirimOtp(
            '081234567885',
            'Pemohon TTL',
        ))->assertSessionHas('success');

        $sisaDetik = now()->diffInSeconds(OtpVerification::firstOrFail()->expired_at, false);
        $this->assertGreaterThan(290, $sisaDetik);
        $this->assertLessThanOrEqual(300, $sisaDetik);
    }

    public function test_scheduler_dapat_memangkas_metadata_otp_melewati_retensi(): void
    {
        config()->set('otp.retensi_hari', 7);

        $otpLama = OtpVerification::create([
            'no_hp' => '6281234567886',
            'kode_otp' => Hash::make('111111'),
            'expired_at' => now()->subDays(8),
            'attempt_count' => 0,
            'created_at' => now()->subDays(8),
        ]);
        $otpBaru = OtpVerification::create([
            'no_hp' => '6281234567886',
            'kode_otp' => Hash::make('222222'),
            'expired_at' => now()->subDays(6),
            'attempt_count' => 0,
            'created_at' => now()->subDays(6),
        ]);

        $this->artisan('model:prune', [
            '--model' => [OtpVerification::class],
        ])->assertSuccessful();

        $this->assertModelMissing($otpLama);
        $this->assertModelExists($otpBaru);
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
