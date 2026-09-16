<?php

namespace App\Services\WhatsApp;

class WhatsAppTemplate
{
    public function otp(#[\SensitiveParameter] string $kode): array
    {
        return [
            'name' => config('otp.whatsapp.template_name'),
            'language' => ['code' => config('otp.whatsapp.template_language')],
            'components' => [
                ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $kode]]],
                [
                    'type' => 'button', 'sub_type' => 'url', 'index' => '0',
                    'parameters' => [['type' => 'text', 'text' => $kode]],
                ],
            ],
        ];
    }
}
