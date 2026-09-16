<?php

$lingkungan = env('APP_ENV', 'production');

return [
    'driver' => env(
        'OTP_DRIVER',
        in_array($lingkungan, ['local', 'testing'], true) ? 'log' : null,
    ),

    'kedaluwarsa_menit' => (int) env('OTP_EXPIRES_MINUTES', 5),
    'retensi_hari' => (int) env('OTP_RETENTION_DAYS', 7),

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'verihubs'),
        'base_url' => 'https://api.verihubs.com/v2',
        'app_id' => env('VERIHUBS_APP_ID'),
        'api_key' => env('VERIHUBS_API_KEY'),
        'sandbox' => filter_var(
            env('VERIHUBS_SANDBOX', false),
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        ),
        'template' => env(
            'VERIHUBS_OTP_TEMPLATE',
            'Kode OTP SIPERMINDA: $OTP. Berlaku 5 menit. Jangan bagikan kode ini.',
        ),
        'challenge' => env('VERIHUBS_OTP_CHALLENGE', 'autentikasi_pemohon'),
        'timeout_seconds' => (int) env('SMS_TIMEOUT_SECONDS', 10),
    ],

    'whatsapp' => [
        'base_url' => 'https://graph.facebook.com',
        'api_version' => env('WHATSAPP_API_VERSION'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'waba_id' => env('WHATSAPP_WABA_ID'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'retention_days' => (int) env('WHATSAPP_LOG_RETENTION_DAYS', 30),
        'template_name' => env('WHATSAPP_OTP_TEMPLATE_NAME', 'siperminda_kode_otp'),
        'template_language' => env('WHATSAPP_OTP_TEMPLATE_LANGUAGE', 'id'),
        'status_template_name' => env('WHATSAPP_STATUS_TEMPLATE_NAME'),
        'status_template_language' => env('WHATSAPP_STATUS_TEMPLATE_LANGUAGE', 'id'),
        'timeout_seconds' => (int) env('WHATSAPP_TIMEOUT_SECONDS', 10),
    ],

    'fonnte' => [
        'endpoint' => 'https://api.fonnte.com/send',
        'token' => env('FONNTE_TOKEN'),
        'template' => env(
            'FONNTE_OTP_TEMPLATE',
            'Kode OTP SIPERMINDA: $OTP. Berlaku 5 menit. Jangan bagikan kode ini.',
        ),
        'timeout_seconds' => (int) env('FONNTE_TIMEOUT_SECONDS', 10),
    ],
];
