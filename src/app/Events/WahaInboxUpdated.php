<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event ini di-broadcast ke WebSocket (Reverb) setiap kali
 * WAHA webhook menerima pesan baru yang berhasil diproses.
 *
 * Semua browser yang membuka halaman InboxWhatsapp akan
 * menerima event ini dan langsung memanggil loadInbox().
 */
class WahaInboxUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $chatId,
    ) {}

    /**
     * Channel publik 'waha-inbox' — semua user admin yang sedang
     * membuka halaman ini akan ter-subscribe ke channel ini.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('waha-inbox');
    }

    /**
     * Nama event yang akan diterima oleh Laravel Echo di browser.
     * Prefix '.' menghindari namespace otomatis Pusher.
     */
    public function broadcastAs(): string
    {
        return 'inbox.updated';
    }

    /**
     * Data yang dikirimkan bersama event (opsional).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chatId,
        ];
    }
}
