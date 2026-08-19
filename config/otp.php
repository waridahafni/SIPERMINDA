<?php

$lingkungan = env('APP_ENV', 'production');

return [
    'driver' => env(
        'OTP_DRIVER',
        in_array($lingkungan, ['local', 'testing'], true) ? 'log' : null,
    ),

    'kedaluwarsa_menit' => (int) env('OTP_EXPIRES_MINUTES', 5),
    'retensi_hari' => (int) env('OTP_RETENTION_DAYS', 7),

    'whatsapp' => [
        'base_url' => 'https://graph.facebook.com',
        'api_version' => env('WHATSAPP_API_VERSION'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'template_name' => env('WHATSAPP_OTP_TEMPLATE_NAME', 'siperminda_kode_otp'),
        'template_language' => env('WHATSAPP_OTP_TEMPLATE_LANGUAGE', 'id'),
        'timeout_seconds' => (int) env('WHATSAPP_TIMEOUT_SECONDS', 10),
    ],
];
