<?php

namespace App\Jobs;

use App\Services\Waha\WahaWebhookProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @param array<string, mixed> $payload */
    public function __construct(public array $payload)
    {
        $this->onQueue('webhooks');
    }

    public function handle(WahaWebhookProcessor $processor): void
    {
        $result = $processor->process($this->payload);

        if (! (($result['ok'] ?? false) && empty($result['duplicate']) && ! empty($result['chat_id']))) {
            return;
        }

        $chatId = (string) $result['chat_id'];

        SendBroadcastDebouncedJob::dispatchDebounced($chatId);
        ProcessAiAutoReplyJob::dispatch($chatId, now()->toDateTimeString());
    }

    public function failed(Throwable $exception): void
    {
        Log::error('WAHA webhook processing job failed.', [
            'message' => $exception->getMessage(),
        ]);
    }
}
