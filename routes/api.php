<?php

use App\Http\Controllers\PruneMetadataController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verifikasi']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'terima']);
Route::get('/maintenance/prune', PruneMetadataController::class);
