<?php

namespace App\Http\Controllers;

use App\Models\PermintaanData;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function cek(Request $request, string $nomorTiket)
    {
        $permintaan = PermintaanData::query()
            ->where('nomor_tiket', $nomorTiket)
            ->firstOrFail();

        $berlakuSampai = (int) $request->session()->get("akses_status.{$permintaan->id}", 0);

        if ($berlakuSampai < now()->timestamp) {
            $request->session()->forget("akses_status.{$permintaan->id}");

            return redirect()->route('cek-status')
                ->with('error', 'Masukkan kembali nomor tiket dan nomor HP untuk melihat status.');
        }

        $permintaan->load(['pemohon', 'approvalLog.approver', 'klarifikasi', 'kategori']);

        return view('public.status.detail', compact('permintaan'));
    }
}
