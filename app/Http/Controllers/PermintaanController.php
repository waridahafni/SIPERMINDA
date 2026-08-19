<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermintaanRequest;
use App\Models\KategoriData;
use App\Models\NomorTiketCounter;
use App\Models\NotifikasiLog;
use App\Models\Pemohon;
use App\Models\PermintaanData;
use App\Models\UnduhanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PermintaanController extends Controller
{
    public function indexPemohon(Request $request)
    {
        $pemohon = $this->pemohonAktif($request);
        $permintaan = $pemohon->permintaanData()
            ->with('kategori')
            ->latest()
            ->paginate(10);

        return view('public.akun.permintaan', compact('pemohon', 'permintaan'));
    }

    public function showPemohon(PermintaanData $permintaan, Request $request)
    {
        $pemohon = $this->pemohonAktif($request);

        if ((int) $permintaan->pemohon_id !== (int) $pemohon->id) {
            abort(404);
        }

        $permintaan->load(['pemohon', 'approvalLog.approver', 'kategori']);

        return view('public.status.detail', compact('permintaan'));
    }

    public function create(Request $request)
    {
        $kategori = KategoriData::all();
        $pemohon = $this->pemohonAktif($request);

        return view('public.permintaan.create', compact('kategori', 'pemohon'));
    }

    public function store(StorePermintaanRequest $request)
    {
        $pemohon = $this->pemohonAktif($request);

        $nomorTiket = DB::transaction(function () {
            $tahun = now()->year;

            $counter = NomorTiketCounter::lockForUpdate()->firstOrCreate(
                ['tahun' => $tahun],
                ['nomor_terakhir' => 0]
            );

            $nomorBaru = $counter->nomor_terakhir + 1;
            $counter->update(['nomor_terakhir' => $nomorBaru]);

            return sprintf('BPS/PD/%d/%05d', $tahun, $nomorBaru);
        });

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
                Mail::raw(
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
                Log::warning('Gagal kirim email: '.$e->getMessage());

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

    public function selesai(PermintaanData $permintaan, Request $request)
    {
        $pemohon = $this->pemohonAktif($request);

        if ((int) $permintaan->pemohon_id !== (int) $pemohon->id) {
            abort(404);
        }

        return view('public.permintaan.selesai', compact('permintaan'));
    }

    public function unduhHasil(PermintaanData $permintaan, Request $request)
    {
        $pemohon = $this->pemohonAktif($request);

        if ((int) $permintaan->pemohon_id !== (int) $pemohon->id) {
            abort(404);
        }

        if (! $permintaan->file_hasil_path) {
            return redirect()->back()->with('error', 'File hasil belum tersedia.');
        }

        if (! in_array($permintaan->status, ['data_siap', 'selesai'], true)) {
            return redirect()->back()->with('error', 'File hasil belum dapat diunduh.');
        }

        if (! Storage::disk('local')->exists($permintaan->file_hasil_path)) {
            return redirect()->back()->with('error', 'File hasil tidak ditemukan di penyimpanan.');
        }

        UnduhanLog::create([
            'permintaan_data_id' => $permintaan->id,
            'pemohon_id' => $permintaan->pemohon_id,
            'ip_address' => $request->ip(),
            'downloaded_at' => now(),
        ]);

        $ekstensi = pathinfo($permintaan->file_hasil_path, PATHINFO_EXTENSION);
        $namaUnduhan = 'hasil-'.Str::slug($permintaan->nomor_tiket).'.'.$ekstensi;

        return response()->download(Storage::disk('local')->path($permintaan->file_hasil_path), $namaUnduhan);
    }

    private function pemohonAktif(Request $request): Pemohon
    {
        $pemohon = $request->attributes->get('pemohon');

        abort_unless($pemohon instanceof Pemohon, 403, 'Sesi pemohon tidak valid.');

        return $pemohon;
    }
}
