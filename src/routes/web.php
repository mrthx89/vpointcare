<?php

use App\Http\Controllers\WahaMediaController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PublicStorageController;
use App\Http\Controllers\Webhook\WahaWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/locale/{locale}', LocaleController::class)
    ->whereIn('locale', array_keys(config('localization.supported', ['id' => [], 'en' => []])))
    ->name('locale.switch');

Route::post('/webhooks/waha/{token?}', WahaWebhookController::class)
    ->name('webhooks.waha');

Route::get('/admin/waha-media/{message}', WahaMediaController::class)
    ->middleware('auth')
    ->name('admin.waha-media.show');

Route::get('/profile-storage/{path}', PublicStorageController::class)
    ->where('path', '.*')
    ->name('public-storage.show');
