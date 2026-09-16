<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class WhatsAppGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config()->set([
            'otp.driver' => 'whatsapp',
            'otp.whatsapp.api_version' => 'v99.0',
            'otp.whatsapp.phone_number_id' => '123456',
            'otp.whatsapp.waba_id' => '456789',
            'otp.whatsapp.access_token' => 'test-access-token',
            'otp.whatsapp.webhook_verify_token' => 'test-verify-token',
            'otp.whatsapp.app_secret' => 'test-app-secret',
        ]);
    }

    private function daftar()
    {
        return $this->post('/daftar/kirim-otp', [
            'nama' => 'Pemohon Meta', 'no_hp' => '081234567890', 'jenis_pemohon' => 'publik',
        ]);
    }

    public function test_register_meta_mencatat_metadata_terenkripsi_dan_mengaktifkan_setelah_otp(): void
    {
        $kode = '';
        Http::fake(function ($request) use (&$kode) {
            $kode = $request['template']['components'][0]['parameters'][0]['text'];

            return Http::response(['messages' => [['id' => 'wamid.test']]]);
        });
        $this->daftar()->assertRedirect('/otp')->assertSessionMissing('pemohon_id');
        $this->assertDatabaseCount('pemohon', 0);
        $otp = OtpVerification::firstOrFail();
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $kode);
        $this->assertTrue(Hash::check($kode, $otp->kode_otp));
        $this->assertTrue($otp->expired_at->equalTo($otp->created_at->addMinutes(5)));
        $log = WhatsAppMessage::firstOrFail();
        $this->assertSame('6281234567890', $log->phone);
        $this->assertSame('accepted', $log->status);
        $this->assertNotNull($log->sent_at);
        $this->assertStringNotContainsString('6281234567890', DB::table('whatsapp_messages')->value('phone'));
        $this->assertStringNotContainsString($kode, $log->toJson());
        $this->get('/otp')->assertDontSee($kode);

        $this->post('/otp/verifikasi', ['kode_otp' => $kode])->assertRedirect('/permintaan/create');
        $this->assertDatabaseCount('pemohon', 1);
        $this->assertNotNull($otp->fresh()->verified_at);
        $this->post('/otp/verifikasi', ['kode_otp' => $kode])->assertRedirect('/masuk');
    }

    public function test_gagal_kirim_dapat_resend_tanpa_akun_aktif_dan_tidak_bisa_melewati_cooldown(): void
    {
        Http::fakeSequence()->push(['error' => ['code' => 131000, 'message' => 'secret-body']], 500)
            ->push(['messages' => [['id' => 'wamid.retry']]]);
        $this->daftar()->assertRedirect('/otp')->assertSessionHas('error')->assertSessionMissing('pemohon_id');
        $this->assertDatabaseHas('whatsapp_messages', ['status' => 'failed', 'error' => 'meta_131000']);
        $this->assertDatabaseCount('pemohon', 0);
        $this->get('/otp')->assertOk()->assertDontSee('secret-body');
        $this->post('/otp/kirim-ulang')->assertSessionHas('error');
        $this->daftar()->assertSessionHas('error');
        $this->post('/masuk/kirim-otp', ['no_hp' => '+6281234567890'])->assertSessionHas('error');
        Http::assertSentCount(1);
        $this->travel(61)->seconds();
        $this->post('/otp/kirim-ulang')->assertRedirect('/otp')->assertSessionHas('success');
        Http::assertSentCount(2);
    }

    public function test_otp_salah_dibatasi_lima_percobaan_dan_tidak_diflash(): void
    {
        $kode = '';
        Http::fake(function ($request) use (&$kode) {
            $kode = $request['template']['components'][0]['parameters'][0]['text'];

            return Http::response(['messages' => [['id' => 'wamid.lock']]]);
        });
        $this->daftar();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/otp/verifikasi', ['kode_otp' => '000000'])->assertSessionHas('error');
        }
        $this->post('/otp/verifikasi', ['kode_otp' => $kode])->assertSessionMissing('pemohon_id');
        $this->assertSame(5, (int) OtpVerification::firstOrFail()->attempt_count);
        $this->post('/otp/verifikasi', ['kode_otp' => '12345'])->assertSessionHasErrors('kode_otp')
            ->assertSessionMissing('_old_input.kode_otp');
        $this->assertDatabaseCount('pemohon', 0);
    }

    public function test_otp_kedaluwarsa_dan_kode_lama_setelah_resend_ditolak(): void
    {
        $kode = [];
        Http::fake(function ($request) use (&$kode) {
            $kode[] = $request['template']['components'][0]['parameters'][0]['text'];

            return Http::response(['messages' => [['id' => 'wamid.'.count($kode)]]]);
        });
        $this->daftar();
        $id = session('otp_verification_id');
        $this->travel(301)->seconds();
        $this->post('/otp/verifikasi', ['kode_otp' => $kode[0]])->assertSessionHas('error')->assertSessionMissing('pemohon_id');
        $this->post('/otp/kirim-ulang')->assertRedirect('/otp');
        $this->withSession(['otp_verification_id' => $id])
            ->post('/otp/verifikasi', ['kode_otp' => $kode[0]])->assertSessionMissing('pemohon_id');
    }

    public function test_webhook_challenge_dan_signature_diperiksa(): void
    {
        $this->get('/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=test-verify-token&hub.challenge=1234')
            ->assertOk()->assertSeeText('1234');
        $this->get('/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=salah&hub.challenge=1234')->assertForbidden();
        $this->postJson('/api/webhooks/whatsapp', ['object' => 'whatsapp_business_account'])->assertForbidden();
        config()->set('otp.whatsapp.webhook_verify_token', '');
        $this->get('/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=&hub.challenge=1234')->assertForbidden();
    }

    private function statusWebhook(string $status, int $timestamp, string $waba = '456789')
    {
        $body = json_encode(['object' => 'whatsapp_business_account', 'entry' => [[
            'id' => $waba, 'changes' => [['field' => 'messages', 'value' => [
                'metadata' => ['phone_number_id' => '123456'],
                'statuses' => [['id' => 'wamid.status', 'status' => $status, 'timestamp' => (string) $timestamp]],
            ]]],
        ]]], JSON_THROW_ON_ERROR);

        return $this->call('POST', '/api/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'test-app-secret'),
        ], $body);
    }

    public function test_webhook_idempoten_menolak_akun_lain_dan_tidak_memundurkan_status(): void
    {
        $log = WhatsAppMessage::create(['phone' => '6281234567890', 'message_id' => 'wamid.status', 'status' => 'accepted']);
        $time = now()->timestamp;
        $this->statusWebhook('read', $time, 'waba-lain')->assertOk();
        $this->assertSame('accepted', $log->fresh()->status);
        $this->statusWebhook('delivered', $time)->assertOk();
        $this->statusWebhook('delivered', $time)->assertOk();
        $this->statusWebhook('sent', $time - 1)->assertOk();
        $this->assertSame('delivered', $log->fresh()->status);
        $this->statusWebhook('read', $time + 1)->assertOk();
        $this->assertSame('read', $log->fresh()->status);
        $this->assertDatabaseCount('whatsapp_messages', 1);
        $this->assertDatabaseCount('pemohon', 0);
    }

    public function test_cron_wajib_secret_dan_hanya_memangkas_metadata_lama(): void
    {
        config()->set('services.vercel.cron_secret', 'test-cron');
        $this->get('/api/maintenance/prune')->assertForbidden();
        WhatsAppMessage::create(['phone' => '6281234567890', 'created_at' => now()->subDays(31)]);
        WhatsAppMessage::create(['phone' => '6281234567891']);
        $this->withToken('test-cron')->get('/api/maintenance/prune')->assertOk();
        $this->assertDatabaseCount('whatsapp_messages', 1);
    }

    public function test_otp_dengan_session_dan_cache_database_menyimpan_cooldown_bersama(): void
    {
        config()->set(['cache.default' => 'database', 'session.driver' => 'database']);
        app('session')->forgetDrivers();
        // Provider sudah me-resolve limiter saat bootstrap test masih memakai array.
        RateLimiter::swap(new \Illuminate\Cache\RateLimiter(Cache::store('database')));
        Http::fake(fn () => Http::response(['messages' => [['id' => 'wamid.database']]]));

        $this->daftar()->assertRedirect('/otp');
        $this->assertGreaterThan(0, DB::table('sessions')->count());
        $this->assertGreaterThan(0, DB::table('cache')->count());
        $this->post('/otp/kirim-ulang')->assertSessionHas('error');
        Http::assertSentCount(1);
    }
}
