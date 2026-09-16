<?php

namespace App\Http\Controllers;

use App\Models\OtpVerification;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class PruneMetadataController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = config('services.vercel.cron_secret');
        $token = $request->bearerToken();
        abort_unless(is_string($secret) && $secret !== '' && is_string($token) && hash_equals($secret, $token), 403);

        $lock = Cache::lock('prune-metadata', 300);
        if (! $lock->get()) {
            return response()->json(['status' => 'busy'], 409);
        }
        try {
            $exit = Artisan::call('model:prune', ['--model' => [OtpVerification::class, WhatsAppMessage::class]]);
            abort_unless($exit === 0, 500);
        } finally {
            $lock->release();
        }

        return response()->json(['status' => 'ok']);
    }
}
