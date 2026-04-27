<?php

use App\Http\Controllers\WahaMediaController;
use App\Http\Controllers\Webhook\WahaWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::post('/webhooks/waha/{token?}', WahaWebhookController::class)
    ->name('webhooks.waha');

Route::get('/admin/waha-media/{message}', WahaMediaController::class)
    ->middleware('auth')
    ->name('admin.waha-media.show');
