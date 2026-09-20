<?php

namespace App\Services\WhatsApp;

use App\Models\PermintaanData;
use App\Support\NomorTeleponIndonesia;
use Illuminate\Support\Facades\Log;

class NotifikasiStatusPermintaan
{
    public function __construct(private readonly WhatsAppClient $whatsApp) {}

    /**
     * Pengiriman bersifat best-effort; status database tidak boleh bergantung pada Meta.
     */
    public function kirim(PermintaanData $permintaan, string $peristiwa): void
    {
        $this->kirimKeNomor($permintaan, $permintaan->pemohon?->no_hp, $peristiwa, 'pemohon');
    }

    /**
     * Mengirim peristiwa dari akun pemohon ke nomor operasional petugas.
     * Nomor hanya berasal dari environment agar tidak menjadi data profil publik.
     */
    public function kirimKePetugas(PermintaanData $permintaan, string $peristiwa): void
    {
        $penerima = config('otp.whatsapp.internal_recipients', '');
        $nomorUnik = [];

        foreach (explode(',', is_string($penerima) ? $penerima : '') as $nomor) {
            try {
                $nomor = NomorTeleponIndonesia::kanonis(trim($nomor));
                $nomorUnik[$nomor] = true;
            } catch (\InvalidArgumentException) {
                Log::warning('Nomor penerima WhatsApp internal tidak valid.');
            }
        }

        foreach (array_keys($nomorUnik) as $nomor) {
            $this->kirimKeNomor($permintaan, $nomor, $peristiwa, 'petugas');
        }
    }

    private function kirimKeNomor(PermintaanData $permintaan, ?string $nomor, string $peristiwa, string $penerima): void
    {
        $namaTemplate = (string) config('otp.whatsapp.status_template_name', '');

        if ($namaTemplate === '' || ! is_string($nomor) || trim($nomor) === '') {
            return;
        }

        $labelStatus = match ($peristiwa) {
            'diajukan' => 'permintaan telah diterima',
            'diverifikasi_staf' => 'telah diverifikasi staf',
            'disetujui_kasi' => 'telah disetujui kasi',
            'disetujui_kabid' => 'telah disetujui kabid',
            'ditolak' => 'ditolak',
            'info_tambahan_diminta' => 'memerlukan informasi tambahan',
            'data_siap' => 'siap diunduh',
            'selesai' => 'selesai',
            'permintaan_baru' => 'ada permintaan baru',
            'info_tambahan_dijawab' => 'informasi tambahan telah dijawab',
            'hasil_diunduh' => 'hasil telah diunduh pemohon',
            'feedback_diterima' => 'feedback baru telah diterima',
            default => 'diperbarui',
        };

        try {
            $this->whatsApp->kirimTemplate($nomor, [
                'name' => $namaTemplate,
                'language' => ['code' => config('otp.whatsapp.status_template_language', 'id')],
                'components' => [[
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $permintaan->nomor_tiket],
                        ['type' => 'text', 'text' => $labelStatus],
                    ],
                ]],
            ], 'status_'.$penerima.'_'.$peristiwa);
        } catch (\Throwable $exception) {
            Log::warning('Notifikasi status WhatsApp tidak terkirim.', [
                'permintaan_id' => $permintaan->id,
                'peristiwa' => $peristiwa,
                'penerima' => $penerima,
                'exception' => $exception::class,
            ]);
        }
    }
}
