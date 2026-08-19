<?php

namespace App\Providers;

use App\Contracts\PengirimOtp;
use App\Services\Otp\PengirimOtpManager;
use Illuminate\Support\Facades\Blade;
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
    }
}
