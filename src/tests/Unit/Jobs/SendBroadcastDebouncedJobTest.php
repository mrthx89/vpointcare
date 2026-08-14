<?php

namespace Tests\Unit\Jobs;

use App\Events\WahaInboxUpdated;
use App\Jobs\SendBroadcastDebouncedJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendBroadcastDebouncedJobTest extends TestCase
{
    public function test_it_preserves_is_incoming_true_with_or_semantics()
    {
        Queue::fake();

        SendBroadcastDebouncedJob::dispatchDebounced('chat-123', false);
        $this->assertEquals(['is_incoming' => false], Cache::get('broadcast:pending:chat-123'));

        SendBroadcastDebouncedJob::dispatchDebounced('chat-123', true);
        $this->assertEquals(['is_incoming' => true], Cache::get('broadcast:pending:chat-123'));

        // Dispatch again with false, should still be true (OR semantics)
        SendBroadcastDebouncedJob::dispatchDebounced('chat-123', false);
        $this->assertEquals(['is_incoming' => true], Cache::get('broadcast:pending:chat-123'));

        Queue::assertPushed(SendBroadcastDebouncedJob::class, 1);
    }

    public function test_it_broadcasts_with_correct_is_incoming()
    {
        Event::fake([WahaInboxUpdated::class]);

        Cache::put('broadcast:pending:chat-456', ['is_incoming' => true], now()->addSeconds(2));

        $job = new SendBroadcastDebouncedJob('chat-456');
        $job->handle();

        Event::assertDispatched(WahaInboxUpdated::class, function ($event) {
            return $event->chatId === 'chat-456' && $event->isIncoming === true;
        });

        $this->assertNull(Cache::get('broadcast:pending:chat-456'));
    }
}
