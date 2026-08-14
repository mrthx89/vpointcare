<?php

namespace App\Jobs;

use App\Events\WahaInboxUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBroadcastDebouncedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public int $tries = 3;

    public function __construct(public string $chatId)
    {
        $this->onQueue('broadcasts');
    }

    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public static function dispatchDebounced(string $chatId, bool $isIncoming = false): void
    {
        $key = self::cacheKey($chatId);
        $existing = Cache::get($key);

        if (is_array($existing)) {
            $newIncoming = (bool) ($existing['is_incoming'] ?? false) || $isIncoming;
            Cache::put($key, ['is_incoming' => $newIncoming], now()->addSeconds(2));

            return;
        }

        Cache::put($key, ['is_incoming' => $isIncoming], now()->addSeconds(2));
        self::dispatch($chatId)->delay(now()->addMilliseconds(500));
    }

    public function handle(): void
    {
        $key = self::cacheKey($this->chatId);
        $metadata = Cache::pull($key);

        if (! is_array($metadata)) {
            return;
        }

        $isIncoming = (bool) ($metadata['is_incoming'] ?? false);

        broadcast(new WahaInboxUpdated($this->chatId, $isIncoming))->toOthers();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendBroadcastDebouncedJob failed.', [
            'chat_id' => $this->chatId,
            'exception' => $exception->getMessage(),
        ]);
    }

    private static function cacheKey(string $chatId): string
    {
        return 'broadcast:pending:'.$chatId;
    }
}
