<?php

namespace Tests\Feature;

use App\Exceptions\PengirimanOtpException;
use App\Models\OtpVerification;
use App\Services\Otp\PengirimOtpFonnte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PengirimOtpFonnteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->gunakanKonfigurasiFonnte();
    }

    public function test_mengirim_otp_sebagai_form_multipart_dengan_token_mentah(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'id' => ['pesan-fonnte-123'],
                'target' => ['6281234567890'],
            ]),
        ]);

        $idPesan = app(PengirimOtpFonnte::class)->kirim('081234567890', '123456');

        $this->assertSame('pesan-fonnte-123', $idPesan);
        Http::assertSent(function (ClientRequest $request): bool {
            $form = collect($request->data())->mapWithKeys(
                fn (array $bagian): array => [$bagian['name'] => (string) $bagian['contents']],
            );

            return $request->url() === 'https://api.fonnte.com/send'
                && $request->hasHeader('Authorization', 'token-fonnte-pengujian')
                && $request->isMultipart()
                && $form->all() === [
                    'target' => '6281234567890',
                    'message' => 'Kode OTP SIPERMINDA: 123456. Berlaku 5 menit. Jangan bagikan kode ini.',
                    'countryCode' => '0',
                    'connectOnly' => 'true',
                ];
        });
        Http::assertSentCount(1);
    }

    public function test_token_fonnte_wajib_diisi_sebelum_http_dikirim(): void
    {
        config()->set('otp.fonnte.token');

        $this->assertPengirimanGagal(fn () => app(PengirimOtpFonnte::class)
            ->kirim('081234567891', '123456'));

        Http::assertNothingSent();
    }

    public function test_status_false_dari_fonnte_gagal_tertutup(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => false,
                'id' => ['pesan-yang-tidak-valid'],
                'target' => ['6281234567892'],
            ]),
        ]);

        $this->assertPengirimanGagal(fn () => app(PengirimOtpFonnte::class)
            ->kirim('081234567892', '123456'));

        Http::assertSentCount(1);
    }

    public function test_respons_fonnte_tanpa_id_pesan_gagal_tertutup(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'target' => ['6281234567893'],
            ]),
        ]);

        $this->assertPengirimanGagal(fn () => app(PengirimOtpFonnte::class)
            ->kirim('081234567893', '123456'));

        Http::assertSentCount(1);
    }

    public function test_target_respons_fonnte_harus_sama_dengan_nomor_tujuan(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'id' => ['pesan-target-berbeda'],
                'target' => ['6289999999999'],
            ]),
        ]);

        $this->assertPengirimanGagal(fn () => app(PengirimOtpFonnte::class)
            ->kirim('081234567894', '123456'));

        Http::assertSentCount(1);
    }

    public function test_respons_fonnte_tanpa_target_gagal_tertutup(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'id' => ['pesan-tanpa-target'],
            ]),
        ]);

        $this->assertPengirimanGagal(fn () => app(PengirimOtpFonnte::class)
            ->kirim('081234567898', '123456'));

        Http::assertSentCount(1);
    }

    public function test_koneksi_fonnte_putus_tidak_dicoba_ulang(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::failedConnection('timeout pengujian Fonnte'),
        ]);

        $this->assertPengirimanGagal(fn () => app(PengirimOtpFonnte::class)
            ->kirim('081234567895', '123456'));

        Http::assertSentCount(1);
    }

    public function test_driver_fonnte_ditolak_pada_environment_production(): void
    {
        $this->app->instance('env', 'production');
        $this->assertTrue(app()->environment('production'));

        $this->assertPengirimanGagal(fn () => app(PengirimOtpFonnte::class)
            ->kirim('081234567896', '123456'));

        Http::assertNothingSent();
    }

    public function test_kegagalan_fonnte_tidak_mematikan_otp_lama(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => false,
                'reason' => 'device tidak tersambung',
            ]),
        ]);

        $otpLama = OtpVerification::create([
            'no_hp' => '6281234567897',
            'kode_otp' => Hash::make('111111'),
            'expired_at' => now()->addMinutes(5),
            'attempt_count' => 0,
            'created_at' => now()->subMinute(),
        ]);

        $this->post('/otp/kirim', [
            'no_hp' => '081234567897',
            'nama' => 'Pemohon Fonnte Gagal',
            'email' => null,
            'jenis_pemohon' => 'publik',
            'nama_instansi' => null,
        ])->assertSessionHas('error')
            ->assertSessionMissing('success');

        Http::assertSentCount(1);
        $this->assertTrue($otpLama->fresh()->expired_at->isFuture());

        $otpBaru = OtpVerification::whereKeyNot($otpLama->id)->firstOrFail();
        $this->assertTrue($otpBaru->expired_at->lessThanOrEqualTo(now()));
    }

    private function gunakanKonfigurasiFonnte(): void
    {
        config()->set([
            'otp.driver' => 'fonnte',
            'otp.fonnte.endpoint' => 'https://api.fonnte.com/send',
            'otp.fonnte.token' => 'token-fonnte-pengujian',
            'otp.fonnte.template' => 'Kode OTP SIPERMINDA: $OTP. Berlaku 5 menit. Jangan bagikan kode ini.',
            'otp.fonnte.timeout_seconds' => 5,
        ]);
    }

    private function assertPengirimanGagal(callable $pengiriman): void
    {
        try {
            $pengiriman();
            $this->fail('Pengiriman OTP seharusnya gagal tertutup.');
        } catch (PengirimanOtpException) {
            $this->addToAssertionCount(1);
        }
    }
}
