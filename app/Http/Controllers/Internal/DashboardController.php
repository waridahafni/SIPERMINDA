<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\PermintaanData;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalPermintaan = PermintaanData::count();

        $perStatus = PermintaanData::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $perBulan = PermintaanData::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as bulan"),
            DB::raw('count(*) as total')
        )
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        $permintaanTerbaru = PermintaanData::with('pemohon')
            ->latest()
            ->take(10)
            ->get();

        return view('internal.dashboard.index', compact(
            'totalPermintaan',
            'perStatus',
            'perBulan',
            'permintaanTerbaru'
        ));
    }
}
