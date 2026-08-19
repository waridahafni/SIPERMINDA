<?php

namespace App\Http\Middleware;

use App\Models\Pemohon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PemohonOtpMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $pemohonId = $request->session()->get('pemohon_id');
        $noHp = $request->session()->get('pemohon_otp');

        $pemohon = $pemohonId ? Pemohon::whereKey($pemohonId)->first() : null;

        if (! $pemohon
            || $pemohon->no_hp !== $noHp
            || ! $pemohon->no_hp_verified_at) {
            return redirect()->route('otp.form')->with('error', 'Silakan verifikasi nomor HP terlebih dahulu.');
        }

        return $next($request);
    }
}
