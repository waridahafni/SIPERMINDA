<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermintaanRequest extends FormRequest
{
    public const SESSION_TOKEN_IDEMPOTENSI = 'permintaan.token_idempotensi';

    public const PANJANG_TOKEN_IDEMPOTENSI = 64;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idempotensi_token' => [
                'bail',
                'required',
                'string',
                'size:'.self::PANJANG_TOKEN_IDEMPOTENSI,
                'regex:/\A[a-f0-9]{64}\z/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! $this->tokenIdempotensiAktif($value)) {
                        $fail('Sesi formulir sudah tidak valid. Muat ulang halaman lalu coba lagi.');
                    }
                },
            ],
            'jenis_data' => 'required|string|max:255',
            'tujuan_penggunaan' => 'required|string|max:5000',
            'periode_data' => 'required|string|max:50',
            'kategori_id' => 'sometimes|nullable|exists:kategori_data,id',
        ];
    }

    public function messages(): array
    {
        return [
            'idempotensi_token.required' => 'Sesi formulir tidak ditemukan. Muat ulang halaman lalu coba lagi.',
            'idempotensi_token.string' => 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.',
            'idempotensi_token.size' => 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.',
            'idempotensi_token.regex' => 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.',
            'jenis_data.required' => 'Jenis data wajib diisi.',
            'jenis_data.max' => 'Jenis data maksimal 255 karakter.',
            'tujuan_penggunaan.required' => 'Tujuan penggunaan wajib diisi.',
            'tujuan_penggunaan.max' => 'Tujuan penggunaan maksimal 5.000 karakter.',
            'periode_data.required' => 'Periode data wajib diisi.',
            'periode_data.max' => 'Periode data maksimal 50 karakter.',
            'kategori_id.exists' => 'Kategori data tidak valid.',
        ];
    }

    public static function hashTokenIdempotensi(string $token): string
    {
        return hash('sha256', $token);
    }

    private function tokenIdempotensiAktif(string $token): bool
    {
        $pemohon = $this->attributes->get('pemohon');
        $tokens = $this->session()->get(self::SESSION_TOKEN_IDEMPOTENSI, []);
        $tokenHash = self::hashTokenIdempotensi($token);
        $metadata = is_array($tokens) ? ($tokens[$tokenHash] ?? null) : null;

        return is_array($metadata)
            && isset($metadata['pemohon_id'], $metadata['expires_at'])
            && is_numeric($metadata['pemohon_id'])
            && is_numeric($metadata['expires_at'])
            && (int) $metadata['pemohon_id'] === (int) ($pemohon?->id ?? 0)
            && (int) $metadata['expires_at'] >= now()->timestamp;
    }
}
