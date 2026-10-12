<?php

use App\Http\Controllers\GoogleSheetSyncController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// Telegram sends the secret_token given to setWebhook in this header
Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])
    ->middleware('webhook.secret:services.telegram.webhook_secret,X-Telegram-Bot-Api-Secret-Token');
// The Apps Script sends the shared token as X-Sync-Token
Route::post('/sync-sheets', [GoogleSheetSyncController::class, 'syncSheetData'])
    ->middleware(['webhook.secret:services.sheets_sync.token,X-Sync-Token', 'throttle:30,1']);

// WhatsApp Webhook for receiving messages & evidence photos
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle'])
    ->middleware('throttle:60,1');
