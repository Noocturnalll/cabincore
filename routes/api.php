<?php

use App\Http\Controllers\GoogleSheetSyncController;
use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);
Route::post('/sync-sheets', [GoogleSheetSyncController::class, 'syncSheetData']);
