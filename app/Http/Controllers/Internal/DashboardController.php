<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\PermintaanData;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $slaHariKerja = config('layanan.sla_hari_kerja', 2);
        $batasSla = now()->subWeekdays($slaHariKerja);
        $statusAktif = ['diajukan', 'disetujui_petugas', 'menunggu_info_pemohon'];
        $totalPermintaan = PermintaanData::count();

        $perStatus = PermintaanData::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $perBulan = PermintaanData::query()
            ->jumlahPerBulan()
            ->orderBy('bulan')
            ->get();

        $permintaanTerbaru = PermintaanData::with('pemohon')
            ->latest()
            ->take(10)
            ->get();

        $permintaanMelewatiSla = PermintaanData::with('pemohon')
            ->whereIn('status', $statusAktif)
            ->where('created_at', '<=', $batasSla)
            ->oldest('created_at')
            ->take(5)
            ->get();

        return view('internal.dashboard.index', compact(
            'totalPermintaan',
            'perStatus',
            'perBulan',
            'permintaanTerbaru',
            'slaHariKerja',
            'permintaanMelewatiSla'
        ));
    }
}
