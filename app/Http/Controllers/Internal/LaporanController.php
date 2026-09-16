<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\DatasetTerbuka;
use App\Models\KategoriData;
use App\Models\PermintaanData;
use App\Models\UnduhanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'string', 'max:30'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategori_data,id'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);
        $query = $this->queryTersaring($filter);

        $totalPermintaan = (clone $query)->count();
        $perStatus = (clone $query)->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $perKategori = (clone $query)
            ->select('kategori_id', DB::raw('count(*) as total'))
            ->whereNotNull('kategori_id')
            ->with('kategori')
            ->groupBy('kategori_id')
            ->get();

        $totalUnduhan = UnduhanLog::count();
        $totalDataset = DatasetTerbuka::where('status', 'aktif')->count();

        $rekapBulanan = PermintaanData::query()
            ->jumlahPerBulan()
            ->orderBy('bulan', 'desc')
            ->limit(12)
            ->get();

        $kategori = KategoriData::orderBy('nama')->get(['id', 'nama']);

        return view('internal.laporan.index', compact(
            'totalPermintaan',
            'perStatus',
            'perKategori',
            'totalUnduhan',
            'totalDataset',
            'rekapBulanan',
            'kategori'
        ));
    }

    public function export(Request $request)
    {
        $filter = $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'string', 'max:30'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategori_data,id'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        return response()->streamDownload(function () use ($filter) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Nomor tiket', 'Tanggal diajukan', 'Pemohon', 'Kategori', 'Jenis data', 'Status']);

            $this->queryTersaring($filter)->with(['pemohon:id,nama', 'kategori:id,nama'])
                ->orderBy('created_at')
                ->each(function (PermintaanData $permintaan) use ($output) {
                    fputcsv($output, [
                        $permintaan->nomor_tiket,
                        $permintaan->created_at->format('Y-m-d H:i'),
                        $permintaan->pemohon?->nama,
                        $permintaan->kategori?->nama,
                        $permintaan->jenis_data,
                        $permintaan->status,
                    ]);
                });

            fclose($output);
        }, 'laporan-permintaan-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function queryTersaring(array $filter)
    {
        $query = PermintaanData::query();

        if ($filter['bulan'] ?? null) {
            $query->whereYear('created_at', substr($filter['bulan'], 0, 4))
                ->whereMonth('created_at', substr($filter['bulan'], 5, 2));
        }
        if ($filter['status'] ?? null) {
            $query->where('status', $filter['status']);
        }
        if ($filter['kategori_id'] ?? null) {
            $query->where('kategori_id', $filter['kategori_id']);
        }
        if ($filter['tanggal_mulai'] ?? null) {
            $query->whereDate('created_at', '>=', $filter['tanggal_mulai']);
        }
        if ($filter['tanggal_selesai'] ?? null) {
            $query->whereDate('created_at', '<=', $filter['tanggal_selesai']);
        }

        return $query;
    }
}
