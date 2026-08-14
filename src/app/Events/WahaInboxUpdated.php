<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WahaInboxUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $chatId,
        public readonly bool $isIncoming = false,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('waha-inbox');
    }

    public function broadcastAs(): string
    {
        return 'inbox.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chatId,
            'is_incoming' => $this->isIncoming,
        ];
    }
}
