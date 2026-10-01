<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\PermintaanData;
use App\Models\PermintaanFeedback;
use App\Models\PermintaanKendala;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $slaHariKerja = config('layanan.sla_hari_kerja', 2);
        $statusAktif = PermintaanData::STATUS_AKTIF;
        $jumlahSurvei = PermintaanFeedback::count();
        $rataRataSurvei = PermintaanFeedback::avg('rating');
        $kendalaTerbuka = PermintaanKendala::with('permintaan')->whereNull('diselesaikan_at')->oldest()->paginate(5, ['*'], 'halaman_kendala');
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

        $melewatiSla = PermintaanData::query()
            ->whereIn('status', $statusAktif)
            ->where('created_at', '<=', now()->subDays($slaHariKerja))
            ->oldest('created_at')
            ->get(['id', 'created_at'])
            ->filter(fn ($item) => $item->batasLayanan()->lte(now()));
        $halaman = LengthAwarePaginator::resolveCurrentPage('halaman_sla');
        $permintaanMelewatiSla = new LengthAwarePaginator(
            PermintaanData::with('pemohon')->whereIn('id', $melewatiSla->forPage($halaman, 5)->pluck('id'))->oldest('created_at')->get(),
            $melewatiSla->count(), 5, $halaman,
            ['path' => request()->url(), 'pageName' => 'halaman_sla']
        );

        return view('internal.dashboard.index', compact(
            'totalPermintaan',
            'jumlahSurvei',
            'rataRataSurvei',
            'kendalaTerbuka',
            'perStatus',
            'perBulan',
            'permintaanTerbaru',
            'slaHariKerja',
            'permintaanMelewatiSla'
        ));
    }
}
