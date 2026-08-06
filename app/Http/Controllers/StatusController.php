<?php

namespace App\Http\Controllers;

use App\Models\PermintaanData;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function cek(Request $request, string $nomorTiket)
    {
        $request->validate([
            'no_hp' => 'required|string',
        ]);

        $permintaan = PermintaanData::with(['pemohon', 'approvalLog.approver', 'kategori'])
            ->where('nomor_tiket', $nomorTiket)
            ->whereHas('pemohon', function ($q) use ($request) {
                $q->where('no_hp', $request->no_hp);
            })
            ->firstOrFail();

        return view('public.status.detail', compact('permintaan'));
    }
}
