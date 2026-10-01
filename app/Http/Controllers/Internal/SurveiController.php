<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\PermintaanFeedback;
use Illuminate\Http\Request;

class SurveiController extends Controller
{
    private function query(Request $request)
    {
        $filter = $request->validate([
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
        ]);

        return PermintaanFeedback::query()
            ->when($filter['tanggal_mulai'] ?? null, fn ($q, $tanggal) => $q->whereDate('created_at', '>=', $tanggal))
            ->when($filter['tanggal_selesai'] ?? null, fn ($q, $tanggal) => $q->whereDate('created_at', '<=', $tanggal));
    }

    public function index(Request $request)
    {
        $query = $this->query($request);
        $jumlah = (clone $query)->count();
        $rataRata = (clone $query)->avg('rating');
        $survei = $query->with(['permintaan', 'pemohon'])->latest('id')->paginate(20)->withQueryString();

        return view('internal.survei.index', compact('survei', 'jumlah', 'rataRata'));
    }

    public function export(Request $request)
    {
        $query = $this->query($request);

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal survei', 'Nomor tiket', 'Pemohon', 'Rating (1-5)', 'Komentar'], ',', '"', '');
            foreach ($query->with(['permintaan', 'pemohon'])->lazyById(500) as $survei) {
                $cells = [
                    $survei->created_at->format('Y-m-d H:i'),
                    $survei->permintaan?->nomor_tiket ?? '',
                    $survei->pemohon?->nama ?? '',
                    (string) $survei->rating,
                    $survei->komentar ?? '',
                ];
                // Hindari nilai masukan pemohon dijalankan sebagai rumus spreadsheet.
                $cells = array_map(static fn ($cell) => preg_match('/^[\s\x{FEFF}]*[=+@-]/u', $cell) || preg_match('/^[\t\r\n]/', $cell) ? "'".$cell : $cell, $cells);
                fputcsv($output, $cells, ',', '"', '');
            }
            fclose($output);
        }, 'hasil-survei-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
