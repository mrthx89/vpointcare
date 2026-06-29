
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Filament\Pages\AiAgent;
use Illuminate\Support\Facades\Auth;

echo "Testing AiAgent class...\n";

// Simulate authenticated user (dummy, just to pass FilamentAccess check)
// We'll create a dummy user in memory or just mock
Auth::shouldReceive('check')->andReturn(true);

try {
    // Create instance of AiAgent
    $page = new AiAgent();
    
    // Mock properties (like pengaturan, apiKeyBaru, etc.)
    $page->pengaturan = [
        'AutoReplyAktif' => true,
        'AutoReplyDiluarJamKerja' => true,
        'AutoReplyHariLibur' => true,
        'AutoReplyJamKerjaSapaan' => true,
        'AutoReplyJamKerjaBerlanjut' => false,
        'JamKerjaMulai' => '08:00',
        'JamKerjaSelesai' => '17:00',
        'HariKerja' => ['1','2','3','4','5'],
        'ZonaWaktu' => 'Asia/Jakarta',
        'ProviderAi' => '9Router',
        'ModelAi' => 'MedinaAI',
        'ModelInstructAi' => 'MedinaAI-Instruct',
        'BaseUrl' => 'https://mrthx89-9router.hf.space/v1/chat/completions',
        'PromptSistem' => 'Test prompt system full',
        'TemplateDiluarJamKerja' => 'Test template luar jam',
        'TemplateHariLibur' => 'Test template hari libur',
        'TemplateJamKerjaSapaan' => 'Test template sapaan',
        'TemplateFallback' => 'Test template fallback',
        'NotifikasiChatBelumTerbalasAktif' => true,
        'MenitTungguNotifikasi' => 10,
        'JedaNotifikasiMenit' => 30,
        'KodePeranPenerimaNotifikasi' => 'ADMIN,CS',
        'TemplateNotifikasiChatBelumTerbalas' => 'Test notif template',
        'ExcludeNomorWhatsapp' => '',
        'BatasRiwayatPesan' => 8,
        'KirimKeWaha' => true,
        'ModeKirim' => 'KirimWaha',
    ];
    $page->apiKeyBaru = '';
    
    // Mock validate() so it doesn't require request
    // We'll call loadPengaturan() first
    echo "Calling loadPengaturan()...\n";
    $page->mount(); // mount calls loadPengaturan()
    echo "loadPengaturan() SUCCESS!\n";
    
    // Now call normalizeProviderSettings()
    echo "\nCalling normalizeProviderSettings()...\n";
    // Use reflection to call private method
    $reflection = new ReflectionClass($page);
    $method = $reflection->getMethod('normalizeProviderSettings');
    $method->setAccessible(true);
    $result = $method->invoke($page, $page->pengaturan);
    echo "normalizeProviderSettings() SUCCESS!\n";
    var_dump($result);
    
    echo "\nAll tests passed!\n";
    
} catch (\Throwable $e) {
    echo "\nERROR!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
