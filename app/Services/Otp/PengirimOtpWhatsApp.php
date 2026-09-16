<?php

namespace App\Services\Otp;

use App\Contracts\PengirimOtp;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppTemplate;

class PengirimOtpWhatsApp implements PengirimOtp
{
    public function __construct(private readonly WhatsAppClient $client, private readonly WhatsAppTemplate $template) {}

    public function kirim(string $nomorHp, #[\SensitiveParameter] string $kode): string
    {
        return $this->client->kirimTemplate($nomorHp, $this->template->otp($kode));
    }
}
