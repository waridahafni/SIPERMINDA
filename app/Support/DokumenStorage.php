<?php

namespace App\Support;

use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class DokumenStorage
{
    public static function namaDisk(): string
    {
        return (string) config('filesystems.documents', 'local');
    }

    public static function disk(): FilesystemAdapter
    {
        return Storage::disk(self::namaDisk());
    }

    public static function unduh(string $path, string $nama)
    {
        if (self::disk() instanceof AwsS3V3Adapter) {
            $url = self::disk()->temporaryUrl($path, now()->addMinutes(5), [
                'ResponseContentType' => 'application/octet-stream',
                'ResponseContentDisposition' => HeaderUtils::makeDisposition('attachment', $nama, Str::ascii($nama)),
            ]);

            return redirect()->away($url)->withHeaders(['Cache-Control' => 'private, no-store', 'Referrer-Policy' => 'no-referrer']);
        }

        return self::disk()->download($path, $nama);
    }
}
