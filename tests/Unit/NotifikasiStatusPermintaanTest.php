<?php

namespace Tests\Unit;

use App\Models\Pemohon;
use App\Models\PermintaanData;
use App\Services\WhatsApp\NotifikasiStatusPermintaan;
use App\Services\WhatsApp\WhatsAppClient;
use RuntimeException;
use Tests\TestCase;

class NotifikasiStatusPermintaanTest extends TestCase
{
    public function test_kegagalan_whatsapp_tidak_melempar_ulang_exception(): void
    {
        config()->set('otp.whatsapp.status_template_name', 'status_permintaan');
        $client = $this->createMock(WhatsAppClient::class);
        $client->method('kirimTemplate')->willThrowException(new RuntimeException('provider tidak tersedia'));

        $permintaan = new PermintaanData(['nomor_tiket' => 'SPD-2026-0001']);
        $permintaan->id = 1;
        $permintaan->setRelation('pemohon', new Pemohon(['no_hp' => '081234567890']));

        app()->instance(WhatsAppClient::class, $client);

        app(NotifikasiStatusPermintaan::class)->kirim($permintaan, 'data_siap');

        $this->addToAssertionCount(1);
    }

    public function test_notifikasi_petugas_dikirim_ke_setiap_nomor_unik_yang_valid(): void
    {
        config()->set([
            'otp.whatsapp.status_template_name' => 'status_permintaan',
            'otp.whatsapp.internal_recipients' => '081234567890, +6281234567890, 628111111111',
        ]);
        $client = $this->createMock(WhatsAppClient::class);
        $client->expects($this->exactly(2))
            ->method('kirimTemplate')
            ->withAnyParameters()
            ->willReturn('wamid.test');

        $permintaan = new PermintaanData(['nomor_tiket' => 'SPD-2026-0001']);
        $permintaan->id = 1;

        app()->instance(WhatsAppClient::class, $client);

        app(NotifikasiStatusPermintaan::class)->kirimKePetugas($permintaan, 'permintaan_baru');
    }
}
