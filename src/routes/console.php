<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('serve:vpoint', function () {
    $host = env('APP_SERVE_HOST', '127.0.0.1');
    $port = env('APP_SERVE_PORT', '8008');

    $this->info("Starting VPoint Care at http://{$host}:{$port}/admin");

    $process = new Process([
        PHP_BINARY,
        'artisan',
        'serve',
        "--host={$host}",
        "--port={$port}",
    ], base_path());

    $process->setTimeout(null);
    $process->run(function (string $type, string $buffer): void {
        $this->output->write($buffer);
    });

    return $process->getExitCode() ?? 0;
})->purpose('Serve VPoint Care using APP_SERVE_HOST and APP_SERVE_PORT from .env');

Schedule::command('vpoint:kirim-notifikasi-chat-belum-terbalas')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
