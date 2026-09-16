<?php

namespace App\Services\Otp;

use App\Contracts\PengirimOtp;
use App\Exceptions\PengirimanOtpException;
use App\Support\NomorTeleponIndonesia;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PengirimOtpSms implements PengirimOtp
{
    public function kirim(string $nomorHp, string $kode): string
    {
        $konfigurasi = $this->konfigurasi();
        $nomorTujuan = NomorTeleponIndonesia::keFormatSms($nomorHp);
        $url = sprintf(
            '%s/otp/send%s',
            rtrim($konfigurasi['base_url'], '/'),
            $konfigurasi['sandbox'] ? '/sandbox' : '',
        );

        try {
            $respons = Http::withHeaders([
                'App-ID' => $konfigurasi['app_id'],
                'API-Key' => $konfigurasi['api_key'],
            ])
                ->acceptJson()
                ->asJson()
                ->connectTimeout(min(5, $konfigurasi['timeout_seconds']))
                ->timeout($konfigurasi['timeout_seconds'])
                ->post($url, [
                    'msisdn' => $nomorTujuan,
                    'template' => $konfigurasi['template'],
                    'otp' => $kode,
                    'time_limit' => (string) $this->masaBerlakuDetik(),
                    'challenge' => $konfigurasi['challenge'],
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Provider SMS OTP tidak dapat dihubungi.', [
                'driver' => 'sms',
                'provider' => $konfigurasi['provider'],
                'jenis_error' => $exception::class,
            ]);

            throw new PengirimanOtpException('Provider SMS tidak dapat dihubungi.', previous: $exception);
        }

        if ($respons->status() !== 201) {
            Log::warning('Provider SMS OTP menolak permintaan.', [
                'driver' => 'sms',
                'provider' => $konfigurasi['provider'],
                'status_http' => $respons->status(),
                'kode_provider' => $this->kodeProvider($respons),
            ]);

            throw new PengirimanOtpException('Provider SMS menolak pengiriman OTP.');
        }

        $idPesan = $this->idPesan($respons, $nomorTujuan, $kode);

        if ($idPesan === null) {
            Log::warning('Respons SMS OTP tidak sesuai kontrak provider.', [
                'driver' => 'sms',
                'provider' => $konfigurasi['provider'],
                'status_http' => $respons->status(),
            ]);

            throw new PengirimanOtpException('Respons provider SMS tidak valid.');
        }

        return $idPesan;
    }

    private function konfigurasi(): array
    {
        $konfigurasi = config('otp.sms', []);
        $wajib = ['provider', 'base_url', 'app_id', 'api_key', 'template', 'challenge'];

        foreach ($wajib as $kunci) {
            if (! is_string($konfigurasi[$kunci] ?? null) || trim($konfigurasi[$kunci]) === '') {
                throw new PengirimanOtpException("Konfigurasi SMS [{$kunci}] belum diisi.");
            }
        }

        if ($konfigurasi['provider'] !== 'verihubs') {
            throw new PengirimanOtpException('Provider SMS tidak didukung.');
        }

        if ($konfigurasi['base_url'] !== 'https://api.verihubs.com/v2') {
            throw new PengirimanOtpException('Base URL provider SMS tidak diizinkan.');
        }

        if (! str_contains($konfigurasi['template'], '$OTP')
            || ! str_contains(strtoupper($konfigurasi['template']), 'SIPERMINDA')) {
            throw new PengirimanOtpException('Template SMS wajib memuat SIPERMINDA dan variabel $OTP.');
        }

        if (strlen($konfigurasi['template']) > 160) {
            throw new PengirimanOtpException('Template SMS OTP maksimal 160 karakter.');
        }

        if (! preg_match('/^[a-z0-9_-]{1,64}$/', $konfigurasi['challenge'])) {
            throw new PengirimanOtpException('Challenge SMS OTP tidak valid.');
        }

        if (! is_bool($konfigurasi['sandbox'] ?? null)) {
            throw new PengirimanOtpException('Konfigurasi sandbox SMS tidak valid.');
        }

        $konfigurasi['timeout_seconds'] = min(
            30,
            max(1, (int) ($konfigurasi['timeout_seconds'] ?? 10)),
        );

        return $konfigurasi;
    }

    private function masaBerlakuDetik(): int
    {
        return min(300, max(60, (int) config('otp.kedaluwarsa_menit', 5) * 60));
    }

    private function idPesan(Response $respons, string $nomorTujuan, string $kode): ?string
    {
        $idSesi = $respons->json('session_id');
        $nomorRespons = $respons->json('msisdn');
        $otpRespons = $respons->json('otp');

        if (! (is_string($idSesi) || is_int($idSesi))
            || trim((string) $idSesi) === ''
            || ! (is_string($nomorRespons) || is_int($nomorRespons))
            || ! (is_string($otpRespons) || is_int($otpRespons))
            || ! hash_equals($nomorTujuan, (string) $nomorRespons)
            || ! hash_equals($kode, (string) $otpRespons)) {
            return null;
        }

        return (string) $idSesi;
    }

    private function kodeProvider(Response $respons): string|int|null
    {
        foreach (['code', 'status', 'error.code'] as $jalur) {
            $nilai = $respons->json($jalur);

            if (is_string($nilai) || is_int($nilai)) {
                return $nilai;
            }
        }

        return null;
    }
}
