<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PemohonOtpMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('pemohon_id')) {
            return redirect()->route('otp.form')->with('error', 'Silakan verifikasi nomor HP terlebih dahulu.');
        }

        return $next($request);
    }
}
