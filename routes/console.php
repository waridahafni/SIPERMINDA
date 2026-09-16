<?php

use App\Models\OtpVerification;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('model:prune', [
    '--model' => [OtpVerification::class, WhatsAppMessage::class],
])->dailyAt('02:00')->withoutOverlapping();
