<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\DatasetTerbuka;
use App\Models\PermintaanData;
use App\Services\UnggahDokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UnggahDokumenController extends Controller
{
    public function store(Request $request, UnggahDokumen $unggah)
    {
        abort_unless(UnggahDokumen::langsung(), 404);
        $data = $request->validate([
            'tujuan' => 'required|in:katalog-baru,katalog-edit,katalog-revisi,hasil-permintaan',
            'target_id' => 'nullable|integer|min:1',
            'ukuran' => 'required|integer|min:1|max:'.UnggahDokumen::MAKSIMAL_BYTE,
            'content_type' => ['nullable', 'string', 'max:255', 'regex:~\A[a-zA-Z0-9!#$&^_.+-]+/[a-zA-Z0-9!#$&^_.+-]+\z~'],
        ]);
        $target = isset($data['target_id']) ? (int) $data['target_id'] : null;

        if ($data['tujuan'] === 'hasil-permintaan') {
            abort_unless($request->user()->can('lihat-permintaan') && $request->user()->can('upload-hasil'), 403);
            $permintaan = PermintaanData::findOrFail($target);
            abort_unless($permintaan->status === 'disetujui_petugas', 409, 'Permintaan belum siap menerima file.');
        } else {
            abort_unless($request->user()->can('upload-dataset'), 403);
            if ($data['tujuan'] === 'katalog-baru') {
                abort_unless($target === null, 422);
            } else {
                $dataset = DatasetTerbuka::findOrFail($target);
                if ($data['tujuan'] === 'katalog-revisi') {
                    abort_unless($dataset->status === 'aktif', 409, 'Dataset tidak lagi aktif.');
                }
            }
        }

        try {
            $izin = $unggah->izinkan(
                (int) $request->user()->id, $data['tujuan'], $target, (int) $data['ukuran'],
                $data['content_type'] ?? 'application/octet-stream'
            );
        } catch (\Throwable $e) {
            Log::warning('Gagal menyiapkan upload dokumen.', ['jenis_error' => $e::class]);

            return response()->json(['message' => 'Upload belum tersedia. Silakan coba lagi.'], 503);
        }

        return response()->json($izin)->header('Cache-Control', 'no-store');
    }
}
