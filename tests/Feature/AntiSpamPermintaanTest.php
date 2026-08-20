<?php

namespace Tests\Feature;

use App\Http\Requests\StorePermintaanRequest;
use App\Models\Pemohon;
use App\Models\PermintaanData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AntiSpamPermintaanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('mail.default', 'array');
        $this->transportEmail()->flush();
    }

    public function test_token_yang_sama_hanya_membuat_satu_permintaan_dan_satu_email(): void
    {
        $pemohon = $this->buatPemohon(1);
        $token = $this->ambilTokenForm($pemohon);
        $payload = $this->payloadValid($token);

        $this->post(route('permintaan.store'), $payload)->assertRedirect();

        $permintaan = PermintaanData::sole();
        $this->assertSame(hash('sha256', $token), $permintaan->idempotensi_hash);
        $this->assertCount(1, $this->transportEmail()->messages());
        $this->assertDatabaseCount('notifikasi_log', 1);

        $this->from(route('permintaan.create'))
            ->post(route('permintaan.store'), $payload)
            ->assertRedirect(route('permintaan.create'))
            ->assertSessionHasErrors('idempotensi_token');

        $this->assertDatabaseCount('permintaan_data', 1);
        $this->assertDatabaseHas('nomor_tiket_counters', [
            'tahun' => now()->year,
            'nomor_terakhir' => 1,
        ]);
        $this->assertCount(1, $this->transportEmail()->messages());
        $this->assertDatabaseCount('notifikasi_log', 1);
    }

    public function test_digest_database_mencegah_duplikasi_saat_session_lama_tersimpan_ulang(): void
    {
        $pemohon = $this->buatPemohon(2);
        $token = $this->ambilTokenForm($pemohon);
        $payload = $this->payloadValid($token);

        $this->post(route('permintaan.store'), $payload)->assertRedirect();
        $permintaan = PermintaanData::sole();
        $this->assertCount(1, $this->transportEmail()->messages());

        $hash = StorePermintaanRequest::hashTokenIdempotensi($token);
        $this->withSession([
            StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI => [
                $hash => [
                    'pemohon_id' => $pemohon->id,
                    'expires_at' => now()->addMinutes(30)->timestamp,
                ],
            ],
        ])->post(route('permintaan.store'), $payload)
            ->assertRedirect(route('permintaan.selesai', $permintaan));

        $this->assertDatabaseCount('permintaan_data', 1);
        $this->assertDatabaseHas('nomor_tiket_counters', [
            'tahun' => now()->year,
            'nomor_terakhir' => 1,
        ]);
        $this->assertCount(1, $this->transportEmail()->messages());
        $this->assertDatabaseCount('notifikasi_log', 1);
        $this->assertNull(session(StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI));
    }

    public function test_validasi_gagal_mempertahankan_token_dan_beberapa_tab_mendapat_token_berbeda(): void
    {
        $pemohon = $this->buatPemohon(3);
        $tokenTabPertama = $this->ambilTokenForm($pemohon);
        $tokenTabKedua = $this->ambilTokenForm($pemohon);

        $this->assertNotSame($tokenTabPertama, $tokenTabKedua);

        $this->from(route('permintaan.create'))
            ->post(route('permintaan.store'), [
                ...$this->payloadValid($tokenTabPertama),
                'jenis_data' => '',
            ])
            ->assertSessionHasErrors('jenis_data');

        $tokens = session(StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI);
        $this->assertIsArray($tokens);
        $this->assertArrayHasKey(hash('sha256', $tokenTabPertama), $tokens);
        $this->assertArrayHasKey(hash('sha256', $tokenTabKedua), $tokens);

        $this->post(route('permintaan.store'), $this->payloadValid($tokenTabPertama))
            ->assertRedirect();

        $this->post(route('permintaan.store'), [
            ...$this->payloadValid($tokenTabKedua),
            'jenis_data' => 'Data dari tab kedua',
        ])->assertRedirect();

        $this->assertDatabaseCount('permintaan_data', 2);
        $this->assertCount(2, $this->transportEmail()->messages());
    }

    public function test_limiter_pemohon_menolak_pengajuan_keenam_dalam_satu_menit(): void
    {
        $pemohon = $this->buatPemohon(4);
        $this->withSession(['pemohon_id' => $pemohon->id]);

        for ($percobaan = 1; $percobaan <= 5; $percobaan++) {
            $this->from(route('permintaan.create'))
                ->post(route('permintaan.store'), [])
                ->assertSessionHasErrors('idempotensi_token');
        }

        $this->post(route('permintaan.store'), [])
            ->assertStatus(429)
            ->assertSeeText('Terlalu banyak pengajuan');

        $this->assertDatabaseCount('permintaan_data', 0);
    }

    public function test_logout_membersihkan_semua_token_idempotensi(): void
    {
        $pemohon = $this->buatPemohon(5);
        $this->ambilTokenForm($pemohon);

        $this->assertNotNull(session(StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI));

        $this->post(route('pemohon.keluar'))
            ->assertRedirect(route('beranda'))
            ->assertSessionMissing(StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI);
    }

    public function test_pool_token_dibatasi_dan_token_kedaluwarsa_ditolak(): void
    {
        $pemohon = $this->buatPemohon(6);
        $tokensMentah = [];

        for ($nomor = 1; $nomor <= 11; $nomor++) {
            $tokensMentah[] = $this->ambilTokenForm($pemohon);
        }

        $tokensSesi = session(StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI);
        $this->assertIsArray($tokensSesi);
        $this->assertCount(10, $tokensSesi);
        $this->assertArrayNotHasKey(hash('sha256', $tokensMentah[0]), $tokensSesi);
        $this->assertArrayHasKey(hash('sha256', $tokensMentah[10]), $tokensSesi);

        $this->travel(1801)->seconds();

        $this->from(route('permintaan.create'))
            ->post(route('permintaan.store'), $this->payloadValid($tokensMentah[10]))
            ->assertSessionHasErrors('idempotensi_token');

        $tokenBaru = $this->ambilTokenForm($pemohon);
        $this->assertNotSame($tokensMentah[10], $tokenBaru);
        $this->assertCount(1, session(StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI));
        $this->assertDatabaseCount('permintaan_data', 0);
    }

    public function test_limiter_ip_terpisah_menolak_percobaan_kedua_puluh_satu(): void
    {
        $pemohon = [];
        for ($nomor = 10; $nomor <= 15; $nomor++) {
            $pemohon[] = $this->buatPemohon($nomor);
        }

        for ($indexPemohon = 0; $indexPemohon < 5; $indexPemohon++) {
            $this->withSession(['pemohon_id' => $pemohon[$indexPemohon]->id]);

            for ($percobaan = 1; $percobaan <= 4; $percobaan++) {
                $this->post(route('permintaan.store'), [])->assertRedirect();
            }
        }

        $this->withSession(['pemohon_id' => $pemohon[5]->id])
            ->post(route('permintaan.store'), [])
            ->assertStatus(429)
            ->assertSeeText('Terlalu banyak pengajuan');

        $this->assertDatabaseCount('permintaan_data', 0);
    }

    private function ambilTokenForm(Pemohon $pemohon): string
    {
        $response = $this->withSession(['pemohon_id' => $pemohon->id])
            ->get(route('permintaan.create'))
            ->assertOk();

        $token = $response->viewData('idempotensiToken');

        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $token);

        return $token;
    }

    /** @return array<string, mixed> */
    private function payloadValid(string $token): array
    {
        return [
            'idempotensi_token' => $token,
            'jenis_data' => 'Data penduduk per kecamatan',
            'tujuan_penggunaan' => 'Analisis kebutuhan pelayanan publik.',
            'periode_data' => '2025-2026',
            'kategori_id' => null,
        ];
    }

    private function buatPemohon(int $nomor): Pemohon
    {
        return Pemohon::create([
            'nama' => 'Pemohon '.$nomor,
            'no_hp' => '6281234'.str_pad((string) $nomor, 6, '0', STR_PAD_LEFT),
            'email' => "pemohon{$nomor}@example.com",
            'jenis_pemohon' => 'publik',
            'nama_instansi' => null,
            'no_hp_verified_at' => now(),
        ]);
    }

    private function transportEmail(): ArrayTransport
    {
        $transport = Mail::mailer()->getSymfonyTransport();

        $this->assertInstanceOf(ArrayTransport::class, $transport);

        return $transport;
    }
}
