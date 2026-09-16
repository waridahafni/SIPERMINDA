<?php

namespace App\Services\Otp;

use App\Contracts\PengirimOtp;
use App\Exceptions\PengirimanOtpException;
use App\Support\NomorTeleponIndonesia;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PengirimOtpFonnte implements PengirimOtp
{
    public function kirim(string $nomorHp, string $kode): string
    {
        if (! app()->environment('local', 'testing')) {
            throw new PengirimanOtpException('Driver Fonnte OTP hanya boleh digunakan pada local atau testing.');
        }

        $konfigurasi = $this->konfigurasi();
        $nomorTujuan = NomorTeleponIndonesia::kanonis($nomorHp);

        if (! preg_match('/^[0-9]{6}$/', $kode)) {
            throw new PengirimanOtpException('Format kode OTP untuk Fonnte tidak valid.');
        }

        try {
            $respons = Http::withHeaders([
                'Authorization' => $konfigurasi['token'],
            ])
                ->acceptJson()
                ->asMultipart()
                ->connectTimeout(min(5, $konfigurasi['timeout_seconds']))
                ->timeout($konfigurasi['timeout_seconds'])
                ->post($konfigurasi['endpoint'], [
                    ['name' => 'target', 'contents' => $nomorTujuan],
                    ['name' => 'message', 'contents' => str_replace('$OTP', $kode, $konfigurasi['template'])],
                    ['name' => 'countryCode', 'contents' => '0'],
                    ['name' => 'connectOnly', 'contents' => 'true'],
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Provider Fonnte OTP tidak dapat dihubungi.', [
                'driver' => 'fonnte',
                'jenis_error' => $exception::class,
            ]);

            throw new PengirimanOtpException('Provider Fonnte tidak dapat dihubungi.', previous: $exception);
        }

        if (! $respons->successful()) {
            Log::warning('Provider Fonnte OTP menolak permintaan.', [
                'driver' => 'fonnte',
                'status_http' => $respons->status(),
            ]);

            throw new PengirimanOtpException('Provider Fonnte menolak pengiriman OTP.');
        }

        $idPesan = $this->idPesanValid($respons, $nomorTujuan);

        if ($idPesan === null) {
            Log::warning('Respons Fonnte OTP tidak sesuai kontrak provider.', [
                'driver' => 'fonnte',
                'status_http' => $respons->status(),
            ]);

            throw new PengirimanOtpException('Respons provider Fonnte tidak valid.');
        }

        return $idPesan;
    }

    private function konfigurasi(): array
    {
        $konfigurasi = config('otp.fonnte', []);
        $wajib = ['endpoint', 'token', 'template'];

        foreach ($wajib as $kunci) {
            if (! is_string($konfigurasi[$kunci] ?? null) || trim($konfigurasi[$kunci]) === '') {
                throw new PengirimanOtpException("Konfigurasi Fonnte [{$kunci}] belum diisi.");
            }
        }

        if ($konfigurasi['endpoint'] !== 'https://api.fonnte.com/send') {
            throw new PengirimanOtpException('Endpoint provider Fonnte tidak diizinkan.');
        }

        if (substr_count($konfigurasi['template'], '$OTP') !== 1
            || ! str_contains(strtoupper($konfigurasi['template']), 'SIPERMINDA')) {
            throw new PengirimanOtpException('Template Fonnte wajib memuat SIPERMINDA dan satu variabel $OTP.');
        }

        if (mb_strlen($konfigurasi['template']) > 500) {
            throw new PengirimanOtpException('Template Fonnte maksimal 500 karakter.');
        }

        $konfigurasi['timeout_seconds'] = min(
            30,
            max(1, (int) ($konfigurasi['timeout_seconds'] ?? 10)),
        );

        return $konfigurasi;
    }

    private function idPesanValid(Response $respons, string $nomorTujuan): ?string
    {
        $data = $respons->json();

        if (! is_array($data) || ($data['status'] ?? null) !== true) {
            return null;
        }

        $idPesan = $data['id'] ?? null;

        if (! is_array($idPesan)
            || count($idPesan) !== 1
            || ! (is_string($idPesan[0] ?? null) || is_int($idPesan[0] ?? null))
            || trim((string) $idPesan[0]) === '') {
            return null;
        }

        $target = $data['target'] ?? null;

        if (! is_array($target)
            || count($target) !== 1
            || ! (is_string($target[0] ?? null) || is_int($target[0] ?? null))
            || ! hash_equals($nomorTujuan, (string) $target[0])) {
            return null;
        }

        return (string) $idPesan[0];
    }
}
