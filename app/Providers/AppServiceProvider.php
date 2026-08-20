<?php

namespace App\Providers;

use App\Contracts\PengirimOtp;
use App\Models\Pemohon;
use App\Services\Otp\PengirimOtpManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PengirimOtp::class, PengirimOtpManager::class);
    }

    public function boot(): void
    {
        Blade::withoutDoubleEncoding();

        $responsTerlaluBanyak = static fn (Request $request, array $headers): Response => response(
            'Terlalu banyak pengajuan. Tunggu sebentar lalu coba lagi.',
            Response::HTTP_TOO_MANY_REQUESTS,
            $headers,
        );

        RateLimiter::for('permintaan-pemohon', function (Request $request) use ($responsTerlaluBanyak): array {
            $pemohonId = $request->session()->get('pemohon_id');
            $kunciPemohon = (is_int($pemohonId) || (is_string($pemohonId) && ctype_digit($pemohonId)))
                ? (string) $pemohonId
                : 'sesi-tidak-valid-'.hash('sha256', $request->session()->getId());
            $kunciIp = hash('sha256', (string) ($request->ip() ?? 'tidak-diketahui'));

            return [
                Limit::perMinute(5)
                    ->by('pemohon:'.$kunciPemohon)
                    ->response($responsTerlaluBanyak),
                Limit::perMinute(20)
                    ->by('ip:'.$kunciIp)
                    ->response($responsTerlaluBanyak),
            ];
        });

        $this->app->make(ExceptionHandler::class)->renderable(
            static function (LockTimeoutException $exception, Request $request): ?Response {
                if (! $request->routeIs('permintaan.create', 'permintaan.store')) {
                    return null;
                }

                return response(
                    'Formulir sedang diproses. Tunggu beberapa detik lalu coba lagi.',
                    Response::HTTP_TOO_MANY_REQUESTS,
                    ['Retry-After' => '5'],
                );
            }
        );

        View::composer('layouts.public', function ($view): void {
            $pemohonId = session('pemohon_id');
            $pemohonAktif = $pemohonId ? Pemohon::find($pemohonId) : null;

            if ($pemohonAktif && ! $pemohonAktif->no_hp_verified_at) {
                $pemohonAktif = null;
            }

            if (! $pemohonAktif && $pemohonId) {
                session()->forget([
                    'pemohon_id',
                    'pemohon_otp',
                    'pemohon_nama',
                    'otp_nomor',
                ]);
            }

            $view->with('pemohonAktif', $pemohonAktif);
        });
    }
}
