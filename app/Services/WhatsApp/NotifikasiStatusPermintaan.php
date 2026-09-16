<?php

namespace App\Services\WhatsApp;

use App\Models\PermintaanData;
use Illuminate\Support\Facades\Log;

class NotifikasiStatusPermintaan
{
    public function __construct(private readonly WhatsAppClient $whatsApp) {}

    /**
     * Pengiriman bersifat best-effort; status database tidak boleh bergantung pada Meta.
     */
    public function kirim(PermintaanData $permintaan, string $status): void
    {
        $namaTemplate = (string) config('otp.whatsapp.status_template_name', '');

        if ($namaTemplate === '' || ! $permintaan->pemohon?->no_hp) {
            return;
        }

        $labelStatus = match ($status) {
            'disetujui' => 'sedang disiapkan',
            'ditolak' => 'ditolak',
            'info_tambahan_diminta' => 'memerlukan informasi tambahan',
            'data_siap' => 'siap diunduh',
            'selesai' => 'selesai',
            default => 'diperbarui',
        };

        try {
            $this->whatsApp->kirimTemplate($permintaan->pemohon->no_hp, [
                'name' => $namaTemplate,
                'language' => ['code' => config('otp.whatsapp.status_template_language', 'id')],
                'components' => [[
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $permintaan->nomor_tiket],
                        ['type' => 'text', 'text' => $labelStatus],
                    ],
                ]],
            ], 'status_'.$status);
        } catch (\Throwable $exception) {
            Log::warning('Notifikasi status WhatsApp tidak terkirim.', [
                'permintaan_id' => $permintaan->id,
                'status' => $status,
                'exception' => $exception::class,
            ]);
        }
    }
}
