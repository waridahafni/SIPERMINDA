<?php

namespace App\Services\WhatsApp;

use App\Exceptions\PengirimanOtpException;
use App\Models\WhatsAppMessage;
use App\Support\NomorTeleponIndonesia;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppClient
{
    public function kirimTemplate(string $nomor, #[\SensitiveParameter] array $template, string $type = 'otp'): string
    {
        $nomor = NomorTeleponIndonesia::kanonis($nomor);
        $log = WhatsAppMessage::create(['phone' => $nomor, 'type' => $type]);
        $konfigurasi = config('otp.whatsapp', []);

        if (! is_string($konfigurasi['access_token'] ?? null) || trim($konfigurasi['access_token']) === ''
            || ! preg_match('/^v[0-9]+\.[0-9]+$/', (string) ($konfigurasi['api_version'] ?? ''))
            || ! preg_match('/^[0-9]+$/', (string) ($konfigurasi['phone_number_id'] ?? ''))
            || ! preg_match('/^[a-z0-9_]+$/', (string) ($template['name'] ?? ''))
            || ! preg_match('/^[a-z]{2,3}(?:_[A-Z]{2})?$/', (string) data_get($template, 'language.code'))) {
            $this->gagal($log, 'configuration');
        }

        $timeout = min(30, max(1, (int) ($konfigurasi['timeout_seconds'] ?? 10)));

        try {
            // Host tetap; jangan izinkan konfigurasi mengalihkan access token.
            $respons = Http::withToken($konfigurasi['access_token'])->acceptJson()->asJson()
                ->withOptions(['allow_redirects' => false])
                ->connectTimeout(min(5, $timeout))->timeout($timeout)
                ->post('https://graph.facebook.com/'.$konfigurasi['api_version'].'/'.$konfigurasi['phone_number_id'].'/messages', [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $nomor,
                    'type' => 'template',
                    'template' => $template,
                ]);
        } catch (ConnectionException) {
            // Jangan lampirkan exception HTTP: dapat berisi request, token, atau kode.
            $this->gagal($log, 'connection');
        }

        if (! $respons->successful()) {
            $code = $respons->json('error.code');
            $this->gagal($log, is_int($code) ? 'meta_'.$code : 'http_'.$respons->status());
        }

        $id = $respons->json('messages.0.id');
        if (! is_string($id) || $id === '' || strlen($id) > 255) {
            $this->gagal($log, 'invalid_response');
        }

        $log->update(['message_id' => $id, 'status' => 'accepted', 'sent_at' => now()]);

        return $id;
    }

    private function gagal(WhatsAppMessage $log, string $kode): never
    {
        $log->update(['status' => 'failed', 'error' => $kode]);
        Log::warning('Pengiriman WhatsApp gagal.', ['pengiriman_id' => $log->id, 'kode' => $kode]);

        throw new PengirimanOtpException('Provider WhatsApp belum dapat mengirim kode OTP.');
    }
}
