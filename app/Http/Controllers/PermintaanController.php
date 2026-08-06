<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermintaanRequest;
use App\Models\KategoriData;
use App\Models\NotifikasiLog;
use App\Models\Pemohon;
use App\Models\PermintaanData;
use App\Models\UnduhanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PermintaanController extends Controller
{
    public function create()
    {
        $kategori = KategoriData::all();
        return view('public.permintaan.create', compact('kategori'));
    }

    public function store(StorePermintaanRequest $request)
    {
        $pemohon = Pemohon::where('no_hp', session('pemohon_otp'))->firstOrFail();

        $tahun = now()->year;
        $lastPermintaan = PermintaanData::whereYear('created_at', $tahun)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPermintaan) {
            $lastNumber = (int) substr($lastPermintaan->nomor_tiket, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $nomorTiket = sprintf('BPS/PD/%d/%05d', $tahun, $newNumber);

        $permintaan = PermintaanData::create([
            'nomor_tiket' => $nomorTiket,
            'pemohon_id' => $pemohon->id,
            'kategori_id' => $request->kategori_id,
            'jenis_data' => $request->jenis_data,
            'tujuan_penggunaan' => $request->tujuan_penggunaan,
            'periode_data' => $request->periode_data,
            'status' => 'diajukan',
        ]);

        if ($pemohon->email) {
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "Permintaan data Anda telah diterima.\nNomor Tiket: {$permintaan->nomor_tiket}\nSilakan cek status secara berkala.",
                    function ($message) use ($pemohon) {
                        $message->to($pemohon->email)
                            ->subject('Permintaan Data Diterima - BPS Padang Lawas');
                    }
                );

                NotifikasiLog::create([
                    'permintaan_data_id' => $permintaan->id,
                    'tujuan_email' => $pemohon->email,
                    'jenis_notifikasi' => 'permintaan_diterima',
                    'status_kirim' => 'berhasil',
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::warning('Gagal kirim email: ' . $e->getMessage());

                NotifikasiLog::create([
                    'permintaan_data_id' => $permintaan->id,
                    'tujuan_email' => $pemohon->email,
                    'jenis_notifikasi' => 'permintaan_diterima',
                    'status_kirim' => 'gagal',
                ]);
            }
        }

        return redirect()->route('permintaan.selesai', $permintaan);
    }

    public function selesai(PermintaanData $permintaan)
    {
        $noHp = session('pemohon_otp');

        if (!$noHp || !$permintaan->pemohon || $permintaan->pemohon->no_hp !== $noHp) {
            abort(403, 'Anda tidak berhak melihat halaman ini.');
        }

        return view('public.permintaan.selesai', compact('permintaan'));
    }

    public function unduhHasil(PermintaanData $permintaan, Request $request)
    {
        $noHp = session('pemohon_otp');

        if (!$noHp || $permintaan->pemohon->no_hp !== $noHp) {
            return redirect()->back()->with('error', 'Anda tidak berhak mengunduh file ini.');
        }

        if (!$permintaan->file_hasil_path) {
            return redirect()->back()->with('error', 'File hasil belum tersedia.');
        }

        UnduhanLog::create([
            'permintaan_data_id' => $permintaan->id,
            'pemohon_id' => $permintaan->pemohon_id,
            'ip_address' => $request->ip(),
            'downloaded_at' => now(),
        ]);

        return response()->download(storage_path('app/' . $permintaan->file_hasil_path));
    }
}
