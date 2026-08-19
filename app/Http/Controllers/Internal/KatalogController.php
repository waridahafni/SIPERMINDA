<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\DatasetTerbuka;
use App\Models\KategoriData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = DatasetTerbuka::with('kategori', 'uploader');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $datasets = $query->latest()->paginate(15);
        $kategori = KategoriData::all();

        return view('internal.katalog.index', compact('datasets', 'kategori'));
    }

    public function create()
    {
        $kategori = KategoriData::all();

        return view('internal.katalog.create', compact('kategori'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori_data,id',
            'periode' => 'required|string|max:50',
            'deskripsi' => 'required|string|max:5000',
            'file' => 'required|file|mimes:pdf,xls,xlsx,csv,json,zip|max:51200',
        ]);

        $file = $request->file('file');
        $filename = Str::uuid().'.'.$file->extension();
        $path = $file->storeAs('dataset_terbuka', $filename, 'local');

        try {
            DatasetTerbuka::create([
                'judul' => $request->judul,
                'kategori_id' => $request->kategori_id,
                'periode' => $request->periode,
                'deskripsi' => $request->deskripsi,
                'file_path' => $path,
                'ukuran_file' => $file->getSize(),
                'uploaded_by' => Auth::id(),
                'published_at' => now(),
                'status' => 'aktif',
            ]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            throw $e;
        }

        return redirect()->route('internal.katalog.index')->with('success', 'Dataset berhasil ditambahkan.');
    }

    public function edit(DatasetTerbuka $dataset)
    {
        $kategori = KategoriData::all();

        return view('internal.katalog.edit', compact('dataset', 'kategori'));
    }

    public function update(Request $request, DatasetTerbuka $dataset)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori_data,id',
            'periode' => 'required|string|max:50',
            'deskripsi' => 'required|string|max:5000',
            'file' => 'nullable|file|mimes:pdf,xls,xlsx,csv,json,zip|max:51200',
        ]);

        $data = $request->only(['judul', 'kategori_id', 'periode', 'deskripsi']);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = Str::uuid().'.'.$file->extension();
            $path = $file->storeAs('dataset_terbuka', $filename, 'local');
            $data['file_path'] = $path;
            $data['ukuran_file'] = $file->getSize();
        }

        $fileLama = $dataset->file_path;

        try {
            $dataset->update($data);
        } catch (\Throwable $e) {
            if (isset($path)) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }

        if (isset($path) && $fileLama !== $path) {
            Storage::disk('local')->delete($fileLama);
        }

        return redirect()->route('internal.katalog.index')->with('success', 'Dataset berhasil diupdate.');
    }

    public function revisi(Request $request, DatasetTerbuka $dataset)
    {
        $request->validate([
            'judul' => 'nullable|string|max:255',
            'kategori_id' => 'nullable|exists:kategori_data,id',
            'periode' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string|max:5000',
            'file' => 'required|file|mimes:pdf,xls,xlsx,csv,json,zip|max:51200',
        ]);

        $file = $request->file('file');
        $filename = Str::uuid().'.'.$file->extension();
        $path = $file->storeAs('dataset_terbuka', $filename, 'local');

        try {
            DB::transaction(function () use ($dataset, $request, $file, $path) {
                $dataset = DatasetTerbuka::whereKey($dataset->id)->lockForUpdate()->firstOrFail();

                if ($dataset->status !== 'aktif') {
                    abort(409, 'Hanya dataset aktif yang dapat direvisi.');
                }

                $dataset->update(['status' => 'digantikan']);

                DatasetTerbuka::create([
                    'judul' => $request->judul ?? $dataset->judul,
                    'kategori_id' => $request->kategori_id ?? $dataset->kategori_id,
                    'periode' => $request->periode ?? $dataset->periode,
                    'deskripsi' => $request->deskripsi ?? $dataset->deskripsi,
                    'file_path' => $path,
                    'ukuran_file' => $file->getSize(),
                    'versi' => $dataset->versi + 1,
                    'dataset_induk_id' => $dataset->id,
                    'uploaded_by' => Auth::id(),
                    'published_at' => now(),
                    'status' => 'aktif',
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            throw $e;
        }

        return redirect()->route('internal.katalog.index')->with('success', 'Revisi dataset berhasil dibuat.');
    }
}
