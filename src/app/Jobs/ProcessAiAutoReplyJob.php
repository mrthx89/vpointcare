<?php

namespace App\Jobs;

use App\Services\Ai\AiAutoReplyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAiAutoReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 90;

    public function __construct(public string $chatId, public ?string $triggeredAt = null)
    {
        $this->onQueue('ai-replies');
    }

    public function handle(AiAutoReplyService $autoReply): void
    {
        if ($this->csAlreadyReplied()) {
            Log::info(__('ui.scalability.ai_reply_skipped_cs_replied'), [
                'chat_id' => $this->chatId,
            ]);

            return;
        }

        $result = $autoReply->handleIncomingChat($this->chatId);

        if (($result['ok'] ?? false) && empty($result['skipped'])) {
            SendBroadcastDebouncedJob::dispatchDebounced($this->chatId);
        }

        if (! empty($result['delivery_failed'])) {
            Log::warning('AI auto reply WAHA delivery failed.', [
                'chat_id' => $this->chatId,
                'reason_code' => 'waha_delivery_failed',
            ]);

            throw new \RuntimeException('AI auto reply WAHA delivery failed.');
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('AI auto reply job failed.', [
            'chat_id' => $this->chatId,
            'reason_code' => 'queue_job_failed',
        ]);
    }

    private function csAlreadyReplied(): bool
    {
        $latestIncomingAt = DB::table('TChatD')
            ->where('IdChat', $this->chatId)
            ->where('ArahPesan', 'Masuk')
            ->where('DikirimOlehCustomer', true)
            ->max('TglPesan');

        if (! $latestIncomingAt) {
            return false;
        }

        return DB::table('TChatD')
            ->where('IdChat', $this->chatId)
            ->where('ArahPesan', 'Keluar')
            ->where(function ($query): void {
                $query->whereNull('DihasilkanOlehAi')
                    ->orWhere('DihasilkanOlehAi', false);
            })
            ->where('TglPesan', '>=', $latestIncomingAt)
            ->exists();
    }
}
