<?php

namespace App\Http\Controllers;

use App\Models\Pemohon;
use App\Models\PermintaanData;
use App\Models\PermintaanKendala;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KendalaController extends Controller
{
    public function store(Request $request, PermintaanData $permintaan)
    {
        $pemohon = $request->attributes->get('pemohon');
        abort_unless($pemohon instanceof Pemohon && (int) $pemohon->id === (int) $permintaan->pemohon_id, 404);
        abort_unless(in_array($permintaan->status, ['data_siap', 'selesai'], true) && $permintaan->file_hasil_path, 422, 'Dokumen belum tersedia.');
        $data = $request->validate(['laporan' => ['required', 'string', 'max:2000']]);

        $dibuat = DB::transaction(function () use ($permintaan, $data) {
            PermintaanData::whereKey($permintaan->id)->lockForUpdate()->firstOrFail();
            if ($permintaan->kendala()->whereNull('diselesaikan_at')->exists()) {
                return false;
            }
            $permintaan->kendala()->create($data);

            return true;
        });

        return redirect()->route('pemohon.permintaan.show', $permintaan)
            ->with($dibuat ? 'success' : 'error', $dibuat ? 'Kendala berhasil dilaporkan. Petugas akan menindaklanjuti laporan Anda.' : 'Laporan sebelumnya masih menunggu tindak lanjut petugas.');
    }

    public function selesai(Request $request, PermintaanData $permintaan, PermintaanKendala $kendala)
    {
        abort_unless((int) $kendala->permintaan_data_id === (int) $permintaan->id, 404);
        $data = $request->validate(['tanggapan' => ['required', 'string', 'max:2000']]);
        $diubah = PermintaanKendala::whereKey($kendala->id)->whereNull('diselesaikan_at')->update([
            'tanggapan' => $data['tanggapan'],
            'ditangani_oleh' => $request->user()->id,
            'diselesaikan_at' => now(),
        ]);

        return redirect()->route('internal.permintaan.show', $permintaan)
            ->with($diubah ? 'success' : 'error', $diubah ? 'Kendala ditandai selesai. Tanggapan dapat dilihat pemohon.' : 'Kendala ini sudah ditangani.');
    }
}
