<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\KategoriData;
use App\Models\NotifikasiLog;
use App\Models\PermintaanApprovalLog;
use App\Models\PermintaanData;
use App\Models\PermintaanKlarifikasi;
use App\Services\WhatsApp\NotifikasiStatusPermintaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PermintaanController extends Controller
{
    public function __construct(private readonly NotifikasiStatusPermintaan $notifikasiWhatsApp) {}

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

        $permintaan = $query->latest('updated_at')->paginate(15);
        $kategori = KategoriData::all();

        return view('internal.permintaan.index', compact('permintaan', 'kategori'));
    }

    public function show(PermintaanData $permintaan)
    {
        $permintaan->load([
            'pemohon',
            'kategori',
            'approvalLog.approver',
            'klarifikasi.peminta',
            'uploader',
        ]);

        return view('internal.permintaan.show', compact('permintaan'));
    }

    /**
     * Menentukan tahap approval berdasarkan status saat ini.
     */
    public function tahapSaatIni(PermintaanData $permintaan): ?string
    {
        return match ($permintaan->status) {
            'diajukan' => 'staf',
            'disetujui_petugas' => 'upload',
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
            'upload' => Auth::user()->can('upload-hasil'),
            default => false,
        };
    }

    /**
     * Mengambil keputusan pada tahap yang relevan berdasarkan status.
     */
    public function keputusan(PermintaanData $permintaan, Request $request)
    {
        $data = $request->validate([
            'keputusan' => 'required|in:setuju,tolak,minta_info',
            'catatan' => 'required_if:keputusan,tolak,minta_info|nullable|string|max:2000',
            'catatan_internal' => 'exclude_unless:keputusan,minta_info|nullable|string|max:2000',
        ]);

        [$permintaan, $tahap] = DB::transaction(function () use ($permintaan, $data) {
            $permintaan = PermintaanData::whereKey($permintaan->id)->lockForUpdate()->firstOrFail();
            $tahap = $this->tahapSaatIni($permintaan);

            if (! $tahap || $tahap === 'upload') {
                throw ValidationException::withMessages([
                    'keputusan' => 'Status permintaan sudah berubah atau tidak lagi berada pada tahap approval.',
                ]);
            }

            if (! $this->otoritasTahap($tahap)) {
                abort(403, 'Anda tidak berwenang melakukan aksi ini.');
            }

            if ($data['keputusan'] === 'minta_info') {
                $sudahMenungguJawaban = PermintaanKlarifikasi::query()
                    ->where('permintaan_data_id', $permintaan->id)
                    ->belumDijawab()
                    ->exists();

                if ($sudahMenungguJawaban) {
                    throw ValidationException::withMessages([
                        'keputusan' => 'Permintaan ini masih menunggu jawaban pemohon.',
                    ]);
                }

                PermintaanKlarifikasi::create([
                    'permintaan_data_id' => $permintaan->id,
                    'tahap' => $tahap,
                    'diminta_oleh' => Auth::id(),
                    'pertanyaan' => $data['catatan'],
                    'catatan_internal' => $data['catatan_internal'] ?? null,
                ]);

                $permintaan->update([
                    'status' => 'menunggu_info_pemohon',
                    'catatan_penolakan' => null,
                ]);

                return [$permintaan, $tahap];
            }

            $statusBaru = match ([$tahap, $data['keputusan']]) {
                ['staf', 'setuju'] => 'disetujui_petugas',
                ['staf', 'tolak'] => 'ditolak',
            };

            $permintaan->update([
                'status' => $statusBaru,
                'catatan_penolakan' => $data['keputusan'] === 'tolak' ? $data['catatan'] : null,
            ]);

            PermintaanApprovalLog::create([
                'permintaan_data_id' => $permintaan->id,
                'tahap' => $tahap,
                'approver_id' => Auth::id(),
                'keputusan' => $data['keputusan'],
                'catatan' => $data['catatan'] ?? null,
                'created_at' => now(),
            ]);

            return [$permintaan, $tahap];
        });

        $jenisNotifikasi = match ($data['keputusan']) {
            'setuju' => 'disetujui',
            'tolak' => 'ditolak',
            'minta_info' => 'info_tambahan_diminta',
        };
        $this->kirimNotifikasi($permintaan, $jenisNotifikasi, $data['catatan'] ?? null);

        $pesan = match ($data['keputusan']) {
            'setuju' => "Persetujuan $tahap berhasil.",
            'tolak' => 'Permintaan ditolak.',
            'minta_info' => 'Permintaan informasi tambahan berhasil dikirim kepada pemohon.',
        };

        return redirect()->route('internal.permintaan.index')->with('success', $pesan);
    }

    /**
     * Upload file hasil untuk permintaan yang sudah disetujui petugas.
     */
    public function storeUploadHasil(Request $request, PermintaanData $permintaan)
    {
        $request->validate([
            'file_hasil' => 'required|file|mimes:pdf,xlsx,xls,csv,zip|max:51200',
        ]);

        if (! $this->otoritasTahap('upload')) {
            abort(403, 'Anda tidak berhak mengupload file hasil.');
        }

        if ($permintaan->status !== 'disetujui_petugas') {
            return redirect()->back()->with('error', 'Permintaan belum disetujui oleh petugas.');
        }

        $file = $request->file('file_hasil');
        $filename = Str::uuid().'.'.$file->extension();
        $path = $file->storeAs('hasil_permintaan', $filename, 'local');

        try {
            $permintaan = DB::transaction(function () use ($permintaan, $path) {
                $permintaan = PermintaanData::whereKey($permintaan->id)->lockForUpdate()->firstOrFail();

                if ($permintaan->status !== 'disetujui_petugas') {
                    throw ValidationException::withMessages([
                        'file_hasil' => 'Status permintaan sudah berubah dan file tidak dapat diupload.',
                    ]);
                }

                $permintaan->update([
                    'file_hasil_path' => $path,
                    'uploaded_by' => Auth::id(),
                    'status' => 'data_siap',
                ]);

                PermintaanApprovalLog::create([
                    'permintaan_data_id' => $permintaan->id,
                    'tahap' => 'upload',
                    'approver_id' => Auth::id(),
                    'keputusan' => 'data_siap',
                    'created_at' => now(),
                ]);

                return $permintaan;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            throw $e;
        }

        $this->kirimNotifikasi($permintaan, 'data_siap');

        return redirect()->route('internal.permintaan.index')->with('success', 'File hasil berhasil diupload, data siap diunduh.');
    }

    /**
     * Menandai permintaan sebagai selesai setelah data siap diunduh.
     */
    public function tandaiSelesai(PermintaanData $permintaan)
    {
        if (! $this->otoritasTahap('upload')) {
            abort(403, 'Anda tidak berwenang melakukan aksi ini.');
        }

        if (! in_array($permintaan->status, ['data_siap', 'selesai'])) {
            return redirect()->back()->with('error', 'Permintaan belum dalam kondisi data siap.');
        }

        $permintaan = DB::transaction(function () use ($permintaan) {
            $permintaan = PermintaanData::whereKey($permintaan->id)->lockForUpdate()->firstOrFail();

            if ($permintaan->status === 'selesai') {
                return $permintaan;
            }

            $permintaan->update(['status' => 'selesai']);
            PermintaanApprovalLog::create([
                'permintaan_data_id' => $permintaan->id,
                'tahap' => 'upload',
                'approver_id' => Auth::id(),
                'keputusan' => 'selesai',
                'created_at' => now(),
            ]);

            return $permintaan;
        });

        $this->kirimNotifikasi($permintaan, 'selesai');

        return redirect()->route('internal.permintaan.index')->with('success', 'Permintaan ditandai selesai.');
    }

    /**
     * Unduh file hasil oleh petugas internal yang berwenang.
     */
    public function unduhHasil(PermintaanData $permintaan)
    {
        if (! $permintaan->file_hasil_path) {
            return redirect()->back()->with('error', 'File hasil belum tersedia.');
        }

        if (! Auth::user()->can('upload-hasil')) {
            abort(403, 'Anda tidak berwenang mengunduh file hasil.');
        }

        if (! Storage::disk('local')->exists($permintaan->file_hasil_path)) {
            return redirect()->back()->with('error', 'File hasil tidak ditemukan di penyimpanan.');
        }

        $ekstensi = pathinfo($permintaan->file_hasil_path, PATHINFO_EXTENSION);
        $namaUnduhan = 'hasil-'.Str::slug($permintaan->nomor_tiket).'.'.$ekstensi;

        return response()->download(Storage::disk('local')->path($permintaan->file_hasil_path), $namaUnduhan);
    }

    /**
     * Mengirim email notifikasi dan mencatat ke log.
     */
    protected function kirimNotifikasi(PermintaanData $permintaan, string $jenis, ?string $catatan = null): void
    {
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
            'info_tambahan_diminta' => [
                'subject' => 'Informasi Tambahan Diperlukan - BPS Padang Lawas',
                'pesan' => "Petugas memerlukan informasi tambahan untuk permintaan data dengan nomor tiket {$permintaan->nomor_tiket}. Silakan masuk ke portal SIPERMINDA untuk menjawabnya.",
            ],
            'selesai' => [
                'subject' => 'Permintaan Data Selesai - BPS Padang Lawas',
                'pesan' => "Permintaan data Anda dengan nomor tiket {$permintaan->nomor_tiket} telah selesai diproses.",
            ],
        ];

        $konfig = $template[$jenis] ?? null;
        if (! $konfig) {
            return;
        }

        $pesan = $konfig['pesan'];
        if ($catatan) {
            $labelCatatan = $jenis === 'info_tambahan_diminta' ? 'Pertanyaan' : 'Catatan';
            $pesan .= "\n{$labelCatatan}: {$catatan}";
        }

        if ($permintaan->pemohon->email) {
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
                Log::warning('Gagal kirim email notifikasi: '.$e->getMessage());

                NotifikasiLog::create([
                    'permintaan_data_id' => $permintaan->id,
                    'tujuan_email' => $permintaan->pemohon->email,
                    'jenis_notifikasi' => $jenis,
                    'status_kirim' => 'gagal',
                ]);
            }
        }

        $this->notifikasiWhatsApp->kirim($permintaan->loadMissing('pemohon'), $jenis);
    }
}
