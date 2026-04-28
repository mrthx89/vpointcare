<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$statusDitutupId = DB::table('MStatusChat')->where('KodeStatusChat', 'DITUTUP')->value('Id');
$chat = DB::table('TChatM')
    ->where('IdSesiWhatsapp', 'A1A45859-7FE6-41FF-AE4F-40A3D26EFDEC')
    ->where('JenisChat', 'Pribadi')
    ->where('NomorWhatsapp', '137799747518482')
    ->orderByDesc('TglChatTerakhir')
    ->first();

dump([
    'Chat ID' => $chat->Id ?? null,
    'Chat Status' => $chat->IdStatusChat ?? null,
    'Ditutup ID' => $statusDitutupId,
    'Condition (NOT EQUAL?)' => ($chat && strtoupper((string) $chat->IdStatusChat) !== strtoupper((string) $statusDitutupId))
]);
