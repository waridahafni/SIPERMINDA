<?php

namespace App\Providers;

use App\Contracts\PengirimOtp;
use App\Models\Pemohon;
use App\Services\Otp\PengirimOtpManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PengirimOtp::class, PengirimOtpManager::class);
    }

    public function boot(): void
    {
        Blade::withoutDoubleEncoding();

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
