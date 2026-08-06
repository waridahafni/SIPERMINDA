<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\DatasetTerbuka;
use App\Models\PermintaanData;
use App\Models\UnduhanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $query = PermintaanData::query();

        if ($request->filled('bulan')) {
            $query->whereYear('created_at', substr($request->bulan, 0, 4))
                ->whereMonth('created_at', substr($request->bulan, 5, 2));
        }

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

        $rekapBulanan = PermintaanData::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as bulan"),
            DB::raw('count(*) as total')
        )
            ->groupBy('bulan')
            ->orderBy('bulan', 'desc')
            ->limit(12)
            ->get();

        return view('internal.laporan.index', compact(
            'totalPermintaan',
            'perStatus',
            'perKategori',
            'totalUnduhan',
            'totalDataset',
            'rekapBulanan'
        ));
    }
}
