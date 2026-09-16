<?php

namespace App\Services;

use App\Support\DokumenStorage;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\File;

class UnggahDokumen
{
    public const MAKSIMAL_BYTE = 50 * 1024 * 1024;

    public static function langsung(): bool
    {
        return config('filesystems.direct_upload', true)
            && config('filesystems.disks.'.DokumenStorage::namaDisk().'.driver') === 's3';
    }

    public static function aturan(string $field, bool $wajib = true): array
    {
        $ekstensi = $field === 'file_hasil' ? 'pdf,xlsx,xls,csv,zip' : 'pdf,xlsx,xls,csv,zip,json';

        return [
            $field => self::langsung() ? 'prohibited' : ($wajib ? 'required' : 'nullable')."|file|mimes:{$ekstensi}|max:51200",
            'upload_token' => self::langsung()
                ? [$wajib ? 'required' : 'nullable', 'string', 'regex:/\A[a-f0-9]{64}\z/']
                : 'prohibited',
        ];
    }

    public function izinkan(int $userId, string $tujuan, ?int $target, int $ukuran, string $contentType = 'application/octet-stream'): array
    {
        abort_unless(self::langsung(), 404);
        $disk = $this->s3();
        $token = bin2hex(random_bytes(32));
        $path = 'pending_uploads/'.Str::uuid();
        $key = $disk->path($path);
        $bucket = $disk->getConfig()['bucket'];

        $command = $disk->getClient()->getCommand('PutObject', [
            'Bucket' => $bucket, 'Key' => $key, 'ContentType' => $contentType,
        ]);
        $signed = $disk->getClient()->createPresignedRequest($command, now()->addMinutes(5));

        Cache::put($this->cacheKey($token), [
            'user_id' => $userId, 'tujuan' => $tujuan, 'target' => $target,
            'ukuran' => $ukuran, 'path' => $path, 'disk' => DokumenStorage::namaDisk(),
        ], now()->addMinutes(15));

        return [
            'upload_url' => (string) $signed->getUri(),
            'method' => 'PUT',
            'key' => $key,
            'headers' => ['Content-Type' => $contentType],
            'finalize_token' => $token,
        ];
    }

    public function simpan(Request $request, string $field, string $tujuan, ?int $target = null): array
    {
        $direktori = $field === 'file_hasil' ? 'hasil_permintaan' : 'dataset_terbuka';
        if (! self::langsung()) {
            $file = $request->file($field);

            return [
                'path' => $file->storeAs($direktori, Str::uuid().'.'.$file->extension(), DokumenStorage::namaDisk()),
                'ukuran' => $file->getSize(),
            ];
        }

        $cacheKey = $this->cacheKey((string) $request->input('upload_token'));
        try {
            $hasil = Cache::lock($cacheKey.':lock', 180)->get(function () use ($request, $field, $tujuan, $target, $direktori, $cacheKey) {
                $izin = Cache::get($cacheKey);
                if (! is_array($izin)
                    || $izin['user_id'] !== (int) $request->user()->id
                    || $izin['tujuan'] !== $tujuan || $izin['target'] !== $target
                    || $izin['disk'] !== DokumenStorage::namaDisk()) {
                    throw ValidationException::withMessages([$field => 'Izin upload tidak sesuai atau sudah kedaluwarsa. Pilih file dan ulangi upload.']);
                }

                // Konsumsi sekali sebelum finalisasi, termasuk ketika verifikasi gagal.
                Cache::forget($cacheKey);

                return $this->verifikasiDanSalin($izin, $field, $direktori);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('Gagal memfinalisasi upload dokumen.', ['jenis_error' => $e::class]);
            throw ValidationException::withMessages([$field => 'File belum berhasil diverifikasi. Silakan ulangi upload.']);
        }

        if ($hasil === false) {
            throw ValidationException::withMessages([$field => 'Upload sedang diproses. Tunggu sebelum mencoba lagi.']);
        }

        return $hasil;
    }

    private function verifikasiDanSalin(array $izin, string $field, string $direktori): array
    {
        $disk = $this->s3();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];
        $key = $disk->path($izin['path']);
        $head = $client->headObject(['Bucket' => $bucket, 'Key' => $key]);
        $ukuran = (int) $head['ContentLength'];
        if ($ukuran < 1 || $ukuran > self::MAKSIMAL_BYTE || $ukuran !== $izin['ukuran'] || empty($head['ETag'])) {
            throw ValidationException::withMessages([$field => 'Ukuran file upload tidak sesuai. Maksimal 50 MB.']);
        }

        $sementara = tempnam(sys_get_temp_dir(), 'siperminda-upload-');
        if ($sementara === false) {
            throw new \RuntimeException('Penyimpanan sementara tidak tersedia.');
        }

        try {
            // Transfer S3 ke server tidak menjadi payload request browser. MIME diperiksa dari isi file.
            $client->getObject([
                'Bucket' => $bucket, 'Key' => $key, 'IfMatch' => $head['ETag'], 'SaveAs' => $sementara,
            ]);
            clearstatcache(true, $sementara);
            $file = new File($sementara);
            $ekstensi = $field === 'file_hasil' ? 'pdf,xlsx,xls,csv,zip' : 'pdf,xlsx,xls,csv,zip,json';
            Validator::make([$field => $file], [$field => "required|file|mimes:{$ekstensi}|max:51200"])->validate();
            if ($file->getSize() !== $ukuran) {
                throw ValidationException::withMessages([$field => 'Transfer file belum lengkap. Silakan ulangi upload.']);
            }

            $path = $direktori.'/'.Str::uuid().'.'.$file->guessExtension();
            // Key final tidak dapat ditulis melalui URL upload browser. ETag mencegah pergantian file setelah pemeriksaan.
            $client->copyObject([
                'Bucket' => $bucket, 'Key' => $disk->path($path),
                'CopySource' => $bucket.'/'.str_replace('%2F', '/', rawurlencode($key)),
                'CopySourceIfMatch' => $head['ETag'],
                'MetadataDirective' => 'REPLACE', 'ContentType' => $file->getMimeType(),
                'ContentDisposition' => 'attachment',
            ]);
        } finally {
            unlink($sementara);
        }

        try {
            $client->deleteObject(['Bucket' => $bucket, 'Key' => $key]);
        } catch (\Throwable $e) {
            // Lifecycle bucket membersihkan sisa staging; file final sudah tersimpan.
            Log::warning('Pembersihan staging upload tertunda.', ['jenis_error' => $e::class]);
        }

        return ['path' => $path, 'ukuran' => $ukuran];
    }

    private function s3(): AwsS3V3Adapter
    {
        $disk = DokumenStorage::disk();
        if (! $disk instanceof AwsS3V3Adapter) {
            throw new \RuntimeException('Upload langsung membutuhkan adapter S3.');
        }

        return $disk;
    }

    private function cacheKey(string $token): string
    {
        return 'dokumen-upload:'.hash('sha256', $token);
    }
}
