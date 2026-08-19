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
        $pemohon = (is_int($pemohonId) || (is_string($pemohonId) && ctype_digit($pemohonId)))
            ? Pemohon::whereKey((int) $pemohonId)
                ->whereNotNull('no_hp_verified_at')
                ->first()
            : null;

        if (! $pemohon) {
            $request->session()->forget([
                'pemohon_id',
                'pemohon_otp',
                'pemohon_nama',
                'otp_nomor',
            ]);

            if (in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
                // Simpan URI relatif, bukan fullUrl(), agar Host header tidak
                // dapat mengubah intended redirect menjadi domain eksternal.
                $tujuan = '/'.ltrim($request->getPathInfo(), '/');
                $query = $request->getQueryString();

                if (is_string($query) && $query !== '') {
                    $tujuan .= '?'.$query;
                }

                $request->session()->put('url.intended', $tujuan);
            }

            return redirect()->route('pemohon.masuk')
                ->with('error', 'Silakan masuk sebagai pemohon terlebih dahulu.');
        }

        $request->session()->put([
            'pemohon_otp' => $pemohon->no_hp,
            'pemohon_nama' => $pemohon->nama,
            'otp_nomor' => $pemohon->no_hp,
        ]);
        $request->attributes->set('pemohon', $pemohon);

        return $next($request);
    }
}
