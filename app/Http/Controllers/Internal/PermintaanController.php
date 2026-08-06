<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\KategoriData;
use App\Models\NotifikasiLog;
use App\Models\PermintaanApprovalLog;
use App\Models\PermintaanData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PermintaanController extends Controller
{
    public function index(Request $request)
    {
        $query = PermintaanData::with('pemohon', 'kategori');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_tiket', 'like', "%{$search}%")
                    ->orWhere('jenis_data', 'like', "%{$search}%")
                    ->orWhereHas('pemohon', function ($q2) use ($search) {
                        $q2->where('nama', 'like', "%{$search}%")
                            ->orWhere('no_hp', 'like', "%{$search}%");
                    });
            });
        }

        $permintaan = $query->latest()->paginate(15);
        $kategori = KategoriData::all();

        return view('internal.permintaan.index', compact('permintaan', 'kategori'));
    }

    public function show(PermintaanData $permintaan)
    {
        $permintaan->load(['pemohon', 'kategori', 'approvalLog.approver', 'uploader']);

        return view('internal.permintaan.show', compact('permintaan'));
    }

    /**
     * Menentukan tahap approval berdasarkan status saat ini.
     */
    public function tahapSaatIni(PermintaanData $permintaan): ?string
    {
        return match ($permintaan->status) {
            'diajukan' => 'staf',
            'diverifikasi_staf' => 'kasi',
            'disetujui_kasi' => 'kabid',
            'disetujui_kabid' => 'upload',
            default => null,
        };
    }

    /**
     * Memvalidasi bahwa user berhak mengambil keputusan pada tahap tertentu.
     */
    public function otoritasTahap(string $tahap): bool
    {
        return match ($tahap) {
            'staf' => Auth::user()->can('verifikasi-permintaan'),
            'kasi' => Auth::user()->can('approve-level-1'),
            'kabid' => Auth::user()->can('approve-level-2'),
            'upload' => Auth::user()->can('upload-hasil'),
            default => false,
        };
    }

    /**
     * Mengambil keputusan (setujui/tolak) pada tahap yang relevan berdasarkan status.
     */
    public function keputusan(PermintaanData $permintaan, Request $request)
    {
        $request->validate([
            'keputusan' => 'required|in:setuju,tolak',
            'catatan' => 'required_if:keputusan,tolak|nullable|string',
        ]);

        $tahap = $this->tahapSaatIni($permintaan);

        if (!$tahap) {
            return redirect()->back()->with('error', 'Permintaan tidak dalam tahap approval.');
        }

        if (!$this->otoritasTahap($tahap)) {
            abort(403, 'Anda tidak berwenang melakukan aksi ini.');
        }

        $statusBaru = match ([$tahap, $request->keputusan]) {
            ['staf', 'setuju'] => 'diverifikasi_staf',
            ['staf', 'tolak'] => 'ditolak',
            ['kasi', 'setuju'] => 'disetujui_kasi',
            ['kasi', 'tolak'] => 'ditolak',
            ['kabid', 'setuju'] => 'disetujui_kabid',
            ['kabid', 'tolak'] => 'ditolak',
            default => 'ditolak',
        };

        $permintaan->update([
            'status' => $statusBaru,
            'catatan_penolakan' => $request->keputusan === 'tolak' ? $request->catatan : null,
        ]);

        PermintaanApprovalLog::create([
            'permintaan_data_id' => $permintaan->id,
            'tahap' => $tahap,
            'approver_id' => Auth::id(),
            'keputusan' => $request->keputusan,
            'catatan' => $request->catatan,
            'created_at' => now(),
        ]);

        $this->kirimNotifikasi($permintaan, $request->keputusan === 'setuju' ? 'disetujui' : 'ditolak', $request->catatan);

        $pesan = $request->keputusan === 'setuju' ? "Persetujuan $tahap berhasil." : 'Permintaan ditolak.';
        return redirect()->route('internal.permintaan.index')->with('success', $pesan);
    }

    /**
     * Upload file hasil untuk permintaan yang sudah disetujui final (kabid).
     */
    public function storeUploadHasil(Request $request, PermintaanData $permintaan)
    {
        $request->validate([
            'file_hasil' => 'required|file|mimes:pdf,xlsx,xls,csv,zip|max:51200',
        ]);

        if (!$this->otoritasTahap('upload')) {
            abort(403, 'Anda tidak berhak mengupload file hasil.');
        }

        if ($permintaan->status !== 'disetujui_kabid') {
            return redirect()->back()->with('error', 'Permintaan belum disetujui final oleh kabid.');
        }

        $file = $request->file('file_hasil');
        $filename = 'hasil_' . $permintaan->nomor_tiket . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('hasil_permintaan', $filename);

        $permintaan->update([
            'file_hasil_path' => $path,
            'uploaded_by' => Auth::id(),
            'status' => 'data_siap',
        ]);

        $this->kirimNotifikasi($permintaan, 'data_siap');

        return redirect()->route('internal.permintaan.index')->with('success', 'File hasil berhasil diupload, data siap diunduh.');
    }

    /**
     * Menandai permintaan sebagai selesai setelah data siap diunduh.
     */
    public function tandaiSelesai(PermintaanData $permintaan)
    {
        if (!$this->otoritasTahap('upload')) {
            abort(403, 'Anda tidak berwenang melakukan aksi ini.');
        }

        if (!in_array($permintaan->status, ['data_siap', 'selesai'])) {
            return redirect()->back()->with('error', 'Permintaan belum dalam kondisi data siap.');
        }

        $permintaan->update(['status' => 'selesai']);

        return redirect()->route('internal.permintaan.index')->with('success', 'Permintaan ditandai selesai.');
    }

    /**
     * Unduh file hasil oleh petugas internal yang berwenang.
     */
    public function unduhHasil(PermintaanData $permintaan)
    {
        if (!$permintaan->file_hasil_path) {
            return redirect()->back()->with('error', 'File hasil belum tersedia.');
        }

        if (!Auth::user()->can('upload-hasil')) {
            abort(403, 'Anda tidak berwenang mengunduh file hasil.');
        }

        return response()->download(storage_path('app/' . $permintaan->file_hasil_path));
    }

    /**
     * Mengirim email notifikasi dan mencatat ke log.
     */
    protected function kirimNotifikasi(PermintaanData $permintaan, string $jenis, ?string $catatan = null): void
    {
        if (!$permintaan->pemohon->email) {
            return;
        }

        $template = [
            'disetujui' => [
                'subject' => 'Permintaan Data Disetujui - BPS Padang Lawas',
                'pesan' => "Permintaan data Anda dengan nomor tiket {$permintaan->nomor_tiket} telah disetujui.",
            ],
            'ditolak' => [
                'subject' => 'Permintaan Data Ditolak - BPS Padang Lawas',
                'pesan' => "Permintaan data Anda dengan nomor tiket {$permintaan->nomor_tiket} ditolak.",
            ],
            'data_siap' => [
                'subject' => 'Data Siap Diunduh - BPS Padang Lawas',
                'pesan' => "Data hasil permintaan Anda dengan nomor tiket {$permintaan->nomor_tiket} sudah siap dan dapat diunduh melalui portal.",
            ],
        ];

        $konfig = $template[$jenis] ?? null;
        if (!$konfig) {
            return;
        }

        $pesan = $konfig['pesan'];
        if ($catatan) {
            $pesan .= "\nCatatan: {$catatan}";
        }

        try {
            Mail::raw($pesan, function ($message) use ($permintaan, $konfig) {
                $message->to($permintaan->pemohon->email)
                    ->subject($konfig['subject']);
            });

            NotifikasiLog::create([
                'permintaan_data_id' => $permintaan->id,
                'tujuan_email' => $permintaan->pemohon->email,
                'jenis_notifikasi' => $jenis,
                'status_kirim' => 'berhasil',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Gagal kirim email notifikasi: ' . $e->getMessage());

            NotifikasiLog::create([
                'permintaan_data_id' => $permintaan->id,
                'tujuan_email' => $permintaan->pemohon->email,
                'jenis_notifikasi' => $jenis,
                'status_kirim' => 'gagal',
            ]);
        }
    }
}