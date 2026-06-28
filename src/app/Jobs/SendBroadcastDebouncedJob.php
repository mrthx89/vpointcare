<?php

namespace App\Jobs;

use App\Events\WahaInboxUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SendBroadcastDebouncedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public function __construct(public string $chatId)
    {
        $this->onQueue('broadcasts');
    }

    public static function dispatchDebounced(string $chatId): void
    {
        $key = self::cacheKey($chatId);

        if (Cache::has($key)) {
            return;
        }

        Cache::put($key, true, now()->addSeconds(2));
        self::dispatch($chatId)->delay(now()->addMilliseconds(500));
    }

    public function handle(): void
    {
        $key = self::cacheKey($this->chatId);

        if (! Cache::pull($key)) {
            return;
        }

        broadcast(new WahaInboxUpdated($this->chatId))->toOthers();
    }

    private static function cacheKey(string $chatId): string
    {
        return 'broadcast:pending:'.$chatId;
    }
}
