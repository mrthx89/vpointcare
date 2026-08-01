<?php

namespace Tests\Unit\Services\Waha;

use App\Services\Waha\WahaSender;
use App\Services\Waha\WahaWebhookProcessor;
use ReflectionMethod;
use Tests\TestCase;

class WahaWebhookProcessorTest extends TestCase
{
    public function test_group_jid_is_prioritized_over_an_earlier_participant_chat_id(): void
    {
        $processor = new WahaWebhookProcessor(new WahaSender);
        $parseMessage = new ReflectionMethod($processor, 'parseMessage');

        $parsed = $parseMessage->invoke($processor, [], [
            'chatId' => '628123456789@c.us',
            'participant' => '628123456789@c.us',
            '_data' => [
                'Info' => [
                    'Chat' => '120363777777777777@g.us',
                ],
            ],
            'id' => [
                '_serialized' => 'message-group-priority-1',
            ],
        ]);

        self::assertSame('Grup', $parsed['jenis_chat']);
        self::assertSame('120363777777777777@g.us', $parsed['group_jid']);
        self::assertSame('628123456789@c.us', $parsed['pengirim_jid']);
    }
}
