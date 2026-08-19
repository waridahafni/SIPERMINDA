<?php

namespace App\Http\Controllers;

use App\Models\DatasetTerbuka;
use App\Models\KategoriData;
use App\Models\PermintaanData;
use App\Models\UnduhanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    public function index()
    {
        $totalDataset = DatasetTerbuka::where('status', 'aktif')->count();
        $totalPermintaan = PermintaanData::count();
        $totalSelesai = PermintaanData::whereIn('status', ['data_siap', 'selesai'])->count();

        return view('public.beranda', compact('totalDataset', 'totalPermintaan', 'totalSelesai'));
    }

    public function alur()
    {
        return view('public.alur');
    }

    public function katalog(Request $request)
    {
        $query = DatasetTerbuka::with('kategori')->where('status', 'aktif');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kategori')) {
            $query->whereHas('kategori', function ($q) use ($request) {
                $q->where('nama', $request->kategori);
            });
        }

        $datasets = $query->paginate(12);

        $kategoris = KategoriData::pluck('nama');

        return view('public.katalog.index', compact('datasets', 'kategoris'));
    }

    public function detailDataset(DatasetTerbuka $dataset)
    {
        abort_unless($dataset->status === 'aktif', 404);

        return view('public.katalog.detail', compact('dataset'));
    }

    public function unduhDataset(DatasetTerbuka $dataset)
    {
        abort_unless($dataset->status === 'aktif', 404);

        if (! Storage::disk('local')->exists($dataset->file_path)) {
            abort(404, 'File dataset tidak ditemukan di penyimpanan.');
        }

        UnduhanLog::create([
            'dataset_terbuka_id' => $dataset->id,
            'ip_address' => request()->ip(),
            'downloaded_at' => now(),
        ]);

        $ekstensi = pathinfo($dataset->file_path, PATHINFO_EXTENSION);
        $namaUnduhan = Str::slug($dataset->judul).'.'.$ekstensi;

        return response()->download(Storage::disk('local')->path($dataset->file_path), $namaUnduhan);
    }

    public function cekStatus()
    {
        return view('public.status.cek');
    }

    public function cekStatusPost(Request $request)
    {
        $request->validate([
            'nomor_tiket' => 'required|string|max:30',
            'no_hp' => ['required', 'string', 'max:20', 'regex:/^(?:\+62|62|0)[0-9]{8,13}$/'],
        ]);

        $permintaan = PermintaanData::with('pemohon')
            ->where('nomor_tiket', $request->nomor_tiket)
            ->whereHas('pemohon', function ($q) use ($request) {
                $q->where('no_hp', $request->no_hp);
            })
            ->first();

        if (! $permintaan) {
            return redirect()->back()->withErrors(['not_found' => 'Data permintaan tidak ditemukan.']);
        }

        $request->session()->put("akses_status.{$permintaan->id}", now()->addMinutes(10)->timestamp);

        return redirect()->route('status.cek', ['nomorTiket' => $permintaan->nomor_tiket])
            ->with('success', 'Permintaan ditemukan.');
    }
}
