<?php

namespace App\Services\Otp;

use App\Contracts\PengirimOtp;
use App\Exceptions\PengirimanOtpException;
use App\Support\NomorTeleponIndonesia;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PengirimOtpWhatsApp implements PengirimOtp
{
    public function kirim(string $nomorHp, string $kode): string
    {
        $konfigurasi = $this->konfigurasi();
        $url = sprintf(
            '%s/%s/%s/messages',
            rtrim($konfigurasi['base_url'], '/'),
            $konfigurasi['api_version'],
            $konfigurasi['phone_number_id'],
        );

        try {
            $respons = Http::withToken($konfigurasi['access_token'])
                ->acceptJson()
                ->asJson()
                ->connectTimeout(min(5, $konfigurasi['timeout_seconds']))
                ->timeout($konfigurasi['timeout_seconds'])
                ->post($url, $this->payload(
                    NomorTeleponIndonesia::keFormatWhatsApp($nomorHp),
                    $kode,
                    $konfigurasi,
                ));
        } catch (ConnectionException $exception) {
            Log::warning('Provider WhatsApp OTP tidak dapat dihubungi.', [
                'driver' => 'whatsapp',
                'jenis_error' => $exception::class,
            ]);

            throw new PengirimanOtpException('Provider WhatsApp tidak dapat dihubungi.', previous: $exception);
        }

        if ($respons->failed()) {
            Log::warning('Provider WhatsApp OTP menolak permintaan.', [
                'status_http' => $respons->status(),
                'kode_provider' => $respons->json('error.code'),
                'fb_trace_id' => $respons->json('error.fbtrace_id'),
            ]);

            throw new PengirimanOtpException('Provider WhatsApp menolak pengiriman OTP.');
        }

        $idPesan = $respons->json('messages.0.id');

        if (! is_string($idPesan) || $idPesan === '') {
            Log::warning('Respons WhatsApp OTP tidak memuat ID pesan.', [
                'status_http' => $respons->status(),
            ]);

            throw new PengirimanOtpException('Respons provider WhatsApp tidak valid.');
        }

        return $idPesan;
    }

    private function konfigurasi(): array
    {
        $konfigurasi = config('otp.whatsapp', []);
        $wajib = ['base_url', 'api_version', 'phone_number_id', 'access_token', 'template_name', 'template_language'];

        foreach ($wajib as $kunci) {
            if (! is_string($konfigurasi[$kunci] ?? null) || trim($konfigurasi[$kunci]) === '') {
                throw new PengirimanOtpException("Konfigurasi WhatsApp [{$kunci}] belum diisi.");
            }
        }

        if (! preg_match('/^v[0-9]+\.[0-9]+$/', $konfigurasi['api_version'])) {
            throw new PengirimanOtpException('Versi Graph API WhatsApp tidak valid.');
        }

        if (! preg_match('/^[0-9]+$/', $konfigurasi['phone_number_id'])) {
            throw new PengirimanOtpException('Phone Number ID WhatsApp tidak valid.');
        }

        if (! preg_match('/^[a-z0-9_]+$/', $konfigurasi['template_name'])) {
            throw new PengirimanOtpException('Nama template WhatsApp tidak valid.');
        }

        if (! preg_match('/^[a-z]{2,3}(?:_[A-Z]{2})?$/', $konfigurasi['template_language'])) {
            throw new PengirimanOtpException('Bahasa template WhatsApp tidak valid.');
        }

        $konfigurasi['timeout_seconds'] = min(
            30,
            max(1, (int) ($konfigurasi['timeout_seconds'] ?? 10)),
        );

        return $konfigurasi;
    }

    private function payload(string $nomorHp, string $kode, array $konfigurasi): array
    {
        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $nomorHp,
            'type' => 'template',
            'template' => [
                'name' => $konfigurasi['template_name'],
                'language' => [
                    'code' => $konfigurasi['template_language'],
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $kode],
                        ],
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => '0',
                        'parameters' => [
                            ['type' => 'text', 'text' => $kode],
                        ],
                    ],
                ],
            ],
        ];
    }
}
