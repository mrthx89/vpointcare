<?php

namespace Tests\Unit\Events;

use App\Events\WahaInboxUpdated;
use Tests\TestCase;

class WahaInboxUpdatedTest extends TestCase
{
    public function test_it_broadcasts_is_incoming_flag()
    {
        $eventIncoming = new WahaInboxUpdated('chat-123', true);
        $this->assertEquals([
            'chat_id' => 'chat-123',
            'is_incoming' => true,
        ], $eventIncoming->broadcastWith());

        $eventOutgoing = new WahaInboxUpdated('chat-123', false);
        $this->assertEquals([
            'chat_id' => 'chat-123',
            'is_incoming' => false,
        ], $eventOutgoing->broadcastWith());
    }
}
