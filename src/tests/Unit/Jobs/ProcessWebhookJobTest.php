<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessAiAutoReplyJob;
use App\Jobs\ProcessWebhookJob;
use App\Jobs\RefreshWahaGroupMetadataJob;
use App\Jobs\SendBroadcastDebouncedJob;
use App\Services\Waha\WahaWebhookProcessor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProcessWebhookJobTest extends TestCase
{
    public function test_success_logs_persistence_and_queued_follow_up_work_without_payload(): void
    {
        Cache::flush();
        Queue::fake();
        Log::spy();

        $processor = Mockery::mock(WahaWebhookProcessor::class);
        $processor->expects('process')->andReturn([
            'ok' => true,
            'chat_id' => 'chat-1',
            'webhook_id' => 'webhook-1',
            'jenis_chat' => 'Grup',
            'session' => 'session-1',
            'group_jid' => '120363700000000001@g.us',
        ]);

        (new ProcessWebhookJob(['secret' => 'must-not-log']))->handle($processor);

        Queue::assertPushed(SendBroadcastDebouncedJob::class);
        Queue::assertPushed(RefreshWahaGroupMetadataJob::class);
        Queue::assertPushed(ProcessAiAutoReplyJob::class);
        Log::shouldHaveReceived('info')->with('WAHA webhook message persisted.', Mockery::on(
            fn (array $context): bool => $context['chat_id'] === 'chat-1'
                && $context['webhook_id'] === 'webhook-1'
                && ! isset($context['payload'])
        ));
        Log::shouldHaveReceived('info')->with('WAHA inbox broadcast queued.', ['chat_id' => 'chat-1']);
        Log::shouldHaveReceived('info')->with('WAHA group metadata queued.', Mockery::on(
            fn (array $context): bool => $context['session'] === 'session-1'
                && $context['group_jid'] === '120363700000000001@g.us'
                && ! isset($context['payload'])
        ));
    }

    public function test_failed_logging_excludes_exception_message(): void
    {
        Log::spy();

        (new ProcessWebhookJob([]))->failed(new RuntimeException('secret payload body'));

        Log::shouldHaveReceived('error')->with('WAHA webhook processing job failed.', Mockery::on(
            fn (array $context): bool => ($context['exception'] ?? null) === RuntimeException::class
                && ! isset($context['message'])
        ));
    }
}
