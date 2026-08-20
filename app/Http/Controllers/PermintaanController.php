<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermintaanRequest;
use App\Models\KategoriData;
use App\Models\NomorTiketCounter;
use App\Models\NotifikasiLog;
use App\Models\Pemohon;
use App\Models\PermintaanData;
use App\Models\UnduhanLog;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PermintaanController extends Controller
{
    private const MASA_AKTIF_TOKEN_DETIK = 1800;

    private const MAKSIMAL_TOKEN_PER_SESI = 10;

    public function indexPemohon(Request $request)
    {
        $pemohon = $this->pemohonAktif($request);
        $permintaan = $pemohon->permintaanData()
            ->with('kategori')
            ->latest()
            ->paginate(10);

        return view('public.akun.permintaan', compact('pemohon', 'permintaan'));
    }

    public function showPemohon(PermintaanData $permintaan, Request $request)
    {
        $pemohon = $this->pemohonAktif($request);

        if ((int) $permintaan->pemohon_id !== (int) $pemohon->id) {
            abort(404);
        }

        $permintaan->load(['pemohon', 'approvalLog.approver', 'kategori']);

        return view('public.status.detail', compact('permintaan'));
    }

    public function create(Request $request)
    {
        $kategori = KategoriData::all();
        $pemohon = $this->pemohonAktif($request);
        $idempotensiToken = $this->buatTokenIdempotensi($request, $pemohon);

        return view('public.permintaan.create', compact('kategori', 'pemohon', 'idempotensiToken'));
    }

    public function store(StorePermintaanRequest $request)
    {
        $pemohon = $this->pemohonAktif($request);
        $data = $request->validated();
        $tokenHash = StorePermintaanRequest::hashTokenIdempotensi($data['idempotensi_token']);

        try {
            [$permintaan, $dibuatBaru] = DB::transaction(function () use ($data, $pemohon, $tokenHash): array {
                $permintaanTersimpan = PermintaanData::query()
                    ->where('idempotensi_hash', $tokenHash)
                    ->first();

                if ($permintaanTersimpan) {
                    abort_unless(
                        (int) $permintaanTersimpan->pemohon_id === (int) $pemohon->id,
                        422,
                        'Token pengajuan tidak valid.'
                    );

                    return [$permintaanTersimpan, false];
                }

                $tahun = now()->year;

                $counter = NomorTiketCounter::lockForUpdate()->firstOrCreate(
                    ['tahun' => $tahun],
                    ['nomor_terakhir' => 0]
                );

                $nomorBaru = $counter->nomor_terakhir + 1;
                $counter->update(['nomor_terakhir' => $nomorBaru]);
                $nomorTiket = sprintf('BPS/PD/%d/%05d', $tahun, $nomorBaru);

                $permintaan = PermintaanData::create([
                    'nomor_tiket' => $nomorTiket,
                    'idempotensi_hash' => $tokenHash,
                    'pemohon_id' => $pemohon->id,
                    'kategori_id' => $data['kategori_id'] ?? null,
                    'jenis_data' => $data['jenis_data'],
                    'tujuan_penggunaan' => $data['tujuan_penggunaan'],
                    'periode_data' => $data['periode_data'],
                    'status' => 'diajukan',
                ]);

                return [$permintaan, true];
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $permintaan = PermintaanData::query()
                ->useWritePdo()
                ->where('idempotensi_hash', $tokenHash)
                ->where('pemohon_id', $pemohon->id)
                ->first();

            if (! $permintaan) {
                throw $exception;
            }

            $dibuatBaru = false;
        }

        $this->hapusTokenIdempotensi($request, $tokenHash);

        if ($dibuatBaru && $pemohon->email) {
            try {
                Mail::raw(
                    "Permintaan data Anda telah diterima.\nNomor Tiket: {$permintaan->nomor_tiket}\nSilakan cek status secara berkala.",
                    function ($message) use ($pemohon) {
                        $message->to($pemohon->email)
                            ->subject('Permintaan Data Diterima - BPS Padang Lawas');
                    }
                );

                NotifikasiLog::create([
                    'permintaan_data_id' => $permintaan->id,
                    'tujuan_email' => $pemohon->email,
                    'jenis_notifikasi' => 'permintaan_diterima',
                    'status_kirim' => 'berhasil',
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::warning('Gagal kirim email: '.$e->getMessage());

                NotifikasiLog::create([
                    'permintaan_data_id' => $permintaan->id,
                    'tujuan_email' => $pemohon->email,
                    'jenis_notifikasi' => 'permintaan_diterima',
                    'status_kirim' => 'gagal',
                ]);
            }
        }

        return redirect()->route('permintaan.selesai', $permintaan);
    }

    public function selesai(PermintaanData $permintaan, Request $request)
    {
        $pemohon = $this->pemohonAktif($request);

        if ((int) $permintaan->pemohon_id !== (int) $pemohon->id) {
            abort(404);
        }

        return view('public.permintaan.selesai', compact('permintaan'));
    }

    public function unduhHasil(PermintaanData $permintaan, Request $request)
    {
        $pemohon = $this->pemohonAktif($request);

        if ((int) $permintaan->pemohon_id !== (int) $pemohon->id) {
            abort(404);
        }

        if (! $permintaan->file_hasil_path) {
            return redirect()->back()->with('error', 'File hasil belum tersedia.');
        }

        if (! in_array($permintaan->status, ['data_siap', 'selesai'], true)) {
            return redirect()->back()->with('error', 'File hasil belum dapat diunduh.');
        }

        if (! Storage::disk('local')->exists($permintaan->file_hasil_path)) {
            return redirect()->back()->with('error', 'File hasil tidak ditemukan di penyimpanan.');
        }

        UnduhanLog::create([
            'permintaan_data_id' => $permintaan->id,
            'pemohon_id' => $permintaan->pemohon_id,
            'ip_address' => $request->ip(),
            'downloaded_at' => now(),
        ]);

        $ekstensi = pathinfo($permintaan->file_hasil_path, PATHINFO_EXTENSION);
        $namaUnduhan = 'hasil-'.Str::slug($permintaan->nomor_tiket).'.'.$ekstensi;

        return response()->download(Storage::disk('local')->path($permintaan->file_hasil_path), $namaUnduhan);
    }

    private function pemohonAktif(Request $request): Pemohon
    {
        $pemohon = $request->attributes->get('pemohon');

        abort_unless($pemohon instanceof Pemohon, 403, 'Sesi pemohon tidak valid.');

        return $pemohon;
    }

    private function buatTokenIdempotensi(Request $request, Pemohon $pemohon): string
    {
        $tokens = $request->session()->get(StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI, []);
        $tokens = is_array($tokens) ? $tokens : [];
        $sekarang = now()->timestamp;

        $tokens = array_filter(
            $tokens,
            static fn (mixed $metadata): bool => is_array($metadata)
                && isset($metadata['pemohon_id'], $metadata['expires_at'])
                && is_numeric($metadata['pemohon_id'])
                && is_numeric($metadata['expires_at'])
                && (int) $metadata['pemohon_id'] === (int) $pemohon->id
                && (int) $metadata['expires_at'] >= $sekarang,
        );

        $tokenLama = $request->old('idempotensi_token');
        if (is_string($tokenLama)
            && preg_match('/\A[a-f0-9]{64}\z/', $tokenLama) === 1
            && isset($tokens[StorePermintaanRequest::hashTokenIdempotensi($tokenLama)])) {
            $idempotensiToken = $tokenLama;
        } else {
            $idempotensiToken = bin2hex(random_bytes(32));
            $tokens[StorePermintaanRequest::hashTokenIdempotensi($idempotensiToken)] = [
                'pemohon_id' => $pemohon->id,
                'expires_at' => now()->addSeconds(self::MASA_AKTIF_TOKEN_DETIK)->timestamp,
            ];
        }

        if (count($tokens) > self::MAKSIMAL_TOKEN_PER_SESI) {
            $tokens = array_slice($tokens, -self::MAKSIMAL_TOKEN_PER_SESI, null, true);
        }

        $request->session()->put(StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI, $tokens);

        return $idempotensiToken;
    }

    private function hapusTokenIdempotensi(Request $request, string $tokenHash): void
    {
        $tokens = $request->session()->get(StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI, []);

        if (! is_array($tokens)) {
            $request->session()->forget(StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI);

            return;
        }

        unset($tokens[$tokenHash]);

        if ($tokens === []) {
            $request->session()->forget(StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI);

            return;
        }

        $request->session()->put(StorePermintaanRequest::SESSION_TOKEN_IDEMPOTENSI, $tokens);
    }
}
