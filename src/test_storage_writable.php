
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Storage Writable ===\n\n";

$dirs = [
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/framework/cache/data',
    'storage/logs',
];

foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    $exists = is_dir($path);
    $writable = $exists ? is_writable($path) : false;
    echo "$dir: " . ($exists ? 'EXISTS' : 'NOT EXISTS') . ", " . ($writable ? 'WRITABLE' : 'NOT WRITABLE') . "\n";
}

echo "\n=== Selesai ===\n";
