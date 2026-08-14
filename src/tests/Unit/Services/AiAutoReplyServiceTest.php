<?php

namespace Tests\Unit\Services;

use App\Services\Ai\AiAutoReplyService;
use App\Services\Waha\WahaSender;
use Illuminate\Support\Facades\DB;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class AiAutoReplyServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_ai_auto_reply_service_can_be_instantiated(): void
    {
        $wahaSender = Mockery::mock(WahaSender::class);
        $service = new AiAutoReplyService($wahaSender);

        $this->assertInstanceOf(AiAutoReplyService::class, $service);
    }

    public function test_store_reply_appends_configured_signature(): void
    {
        $this->assertStoredReply(
            settings: (object) [
                'KirimKeWaha' => false,
                'TandaTanganAi' => '~ Auto Reply by VICA',
            ],
            reply: 'Halo, ada yang bisa kami bantu?',
            expected: "Halo, ada yang bisa kami bantu?\n\n~ Auto Reply by VICA",
        );
    }

    public function test_store_reply_does_not_append_empty_signature(): void
    {
        $this->assertStoredReply(
            settings: (object) [
                'KirimKeWaha' => false,
                'TandaTanganAi' => '   ',
            ],
            reply: 'Halo, ada yang bisa kami bantu?',
            expected: 'Halo, ada yang bisa kami bantu?',
        );
    }

    public function test_store_reply_does_not_duplicate_existing_signature(): void
    {
        $this->assertStoredReply(
            settings: (object) [
                'KirimKeWaha' => false,
                'TandaTanganAi' => '~ Auto Reply by VICA',
            ],
            reply: "Halo, ada yang bisa kami bantu?\n\n~ Auto Reply by VICA",
            expected: "Halo, ada yang bisa kami bantu?\n\n~ Auto Reply by VICA",
        );
    }

    public function test_store_reply_sends_signature_to_waha(): void
    {
        $expected = "Halo, ada yang bisa kami bantu?\n\n~ Auto Reply by VICA";
        $query = Mockery::mock();
        $query->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(fn (array $data): bool => $data['IsiPesan'] === $expected))
            ->andReturnTrue();

        DB::shouldReceive('table')
            ->once()
            ->with('TChatD')
            ->andReturn($query);

        $wahaSender = Mockery::mock(WahaSender::class);
        $sentReply = null;
        $wahaSender->shouldReceive('sendText')
            ->once()
            ->withArgs(function (string $session, string $chatId, string $reply, string $operation) use (&$sentReply): bool {
                $sentReply = $reply;

                return $session === 'default'
                    && $chatId === '120363000000000000@g.us'
                    && $operation === 'WAHA_SEND_TEXT';
            })
            ->andReturn(['ok' => true]);

        $service = new AiAutoReplyService($wahaSender);
        $method = new ReflectionMethod($service, 'storeReply');
        $method->invoke(
            $service,
            (object) [
                'KirimKeWaha' => true,
                'TandaTanganAi' => '~ Auto Reply by VICA',
            ],
            (object) [
                'Id' => 'chat-1',
                'KodeSesi' => 'default',
                'JenisChat' => 'Grup',
                'IdGrupWaha' => '120363000000000000@g.us',
            ],
            'Halo, ada yang bisa kami bantu?',
            'response-1',
            'Berlanjut',
        );

        self::assertSame($expected, $sentReply);
    }

    private function assertStoredReply(object $settings, string $reply, string $expected): void
    {
        $query = Mockery::mock();
        $storedReply = null;
        $query->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function (array $data) use (&$storedReply): bool {
                $storedReply = $data['IsiPesan'];

                return true;
            }))
            ->andReturnTrue();

        DB::shouldReceive('table')
            ->once()
            ->with('TChatD')
            ->andReturn($query);

        $service = new AiAutoReplyService(Mockery::mock(WahaSender::class));
        $method = new ReflectionMethod($service, 'storeReply');
        $method->invoke(
            $service,
            $settings,
            (object) ['Id' => 'chat-1'],
            $reply,
            'response-1',
            'Berlanjut',
        );

        self::assertSame($expected, $storedReply);
    }
}
