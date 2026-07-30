<?php

namespace Tests\Feature\Ai;

use App\Jobs\ProcessAiAutoReplyJob;
use App\Services\Ai\AiAutoReplyService;
use App\Services\Waha\WahaSender;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class AiAutoReplySessionPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('TChatD', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdChat');
            $table->string('IdAiRespon')->nullable();
            $table->string('ArahPesan');
            $table->string('JenisPesan')->nullable();
            $table->text('IsiPesan')->nullable();
            $table->boolean('DikirimOlehCustomer')->default(false);
            $table->boolean('DihasilkanOlehAi')->default(false);
            $table->dateTime('TglPesan');
            $table->dateTime('TglDikirim')->nullable();
            $table->string('StatusKirim')->nullable();
            $table->text('PesanError')->nullable();
            $table->dateTime('TglBuat')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('TChatD');
        Mockery::close();

        parent::tearDown();
    }

    public function test_all_session_allows_every_incoming_message(): void
    {
        [$service, $latest] = $this->serviceWithIncomingMessages(1);

        $policy = $this->invoke($service, 'sessionPolicy', [
            (object) ['AutoReplyJamKerjaBerlanjut' => true, 'BatasSesiAutoReplyMenit' => 60],
            'chat-1',
            $latest,
        ]);

        self::assertTrue($policy['boleh']);
        self::assertSame('all_session', $policy['reason_code']);
    }

    public function test_first_message_and_idle_boundary_are_allowed(): void
    {
        [$service, $first] = $this->serviceWithIncomingMessages();
        $settings = (object) ['AutoReplyJamKerjaBerlanjut' => false, 'BatasSesiAutoReplyMenit' => 60];

        self::assertSame('first_message', $this->invoke($service, 'sessionPolicy', [$settings, 'chat-1', $first])['reason_code']);

        $latest = $this->insertIncoming('incoming-latest', now(), 'Pesan terbaru');
        DB::table('TChatD')->where('Id', 'incoming-first')->update(['TglPesan' => now()->subMinutes(60)]);
        $policy = $this->invoke($service, 'sessionPolicy', [$settings, 'chat-1', $latest]);

        self::assertTrue($policy['boleh']);
        self::assertSame('idle_session', $policy['reason_code']);
    }

    public function test_active_session_is_skipped_before_idle_boundary(): void
    {
        [$service] = $this->serviceWithIncomingMessages();
        DB::table('TChatD')->where('Id', 'incoming-first')->update(['TglPesan' => now()->subMinutes(30)]);
        $latest = $this->insertIncoming('incoming-latest', now(), 'Pesan terbaru');

        $policy = $this->invoke($service, 'sessionPolicy', [
            (object) ['AutoReplyJamKerjaBerlanjut' => false, 'BatasSesiAutoReplyMenit' => 60],
            'chat-1',
            $latest,
        ]);

        self::assertFalse($policy['boleh']);
        self::assertSame('active_session_skip', $policy['reason_code']);
    }

    public function test_failed_waha_reply_does_not_count_as_successful_first_reply(): void
    {
        $sender = Mockery::mock(WahaSender::class);
        $sender->shouldReceive('sendText')->once()->andReturn([
            'ok' => false,
            'error' => 'token=secret-value request failed',
        ]);
        $service = new AiAutoReplyService($sender);
        $chat = (object) [
            'Id' => 'chat-1',
            'JenisChat' => 'Pribadi',
            'NomorWhatsapp' => '628111111111',
            'KodeSesi' => 'default',
            'IdGrupWaha' => null,
        ];

        $delivery = $this->invoke($service, 'storeReply', [
            (object) ['KirimKeWaha' => true],
            $chat,
            'Balasan',
            'response-1',
            'Berlanjut',
        ]);

        self::assertSame('Gagal WAHA', $delivery['status']);
        self::assertStringNotContainsString('secret-value', (string) DB::table('TChatD')->value('PesanError'));
        self::assertTrue($this->invoke($service, 'isFirstInboxAiReply', ['chat-1']));
    }

    public function test_manual_reply_after_latest_incoming_skips_ai_job(): void
    {
        $latest = $this->insertIncoming('incoming-latest', now()->subMinute(), 'Pesan terbaru');
        DB::table('TChatD')->insert([
            'Id' => 'manual-reply',
            'IdChat' => 'chat-1',
            'ArahPesan' => 'Keluar',
            'JenisPesan' => 'Teks',
            'IsiPesan' => 'Sudah dijawab CS',
            'DikirimOlehCustomer' => false,
            'DihasilkanOlehAi' => false,
            'TglPesan' => now(),
            'TglBuat' => now(),
        ]);

        $job = new ProcessAiAutoReplyJob('chat-1', (string) $latest->TglPesan);

        self::assertTrue($this->invoke($job, 'csAlreadyReplied', []));
    }

    public function test_ai_job_throws_when_waha_delivery_fails_so_queue_can_retry(): void
    {
        $this->insertIncoming('incoming-latest', now(), 'Pesan terbaru');
        $service = Mockery::mock(AiAutoReplyService::class);
        $service->shouldReceive('handleIncomingChat')->once()->with('chat-1')->andReturn([
            'ok' => false,
            'delivery_failed' => true,
        ]);

        $this->expectException(\RuntimeException::class);

        (new ProcessAiAutoReplyJob('chat-1'))->handle($service);
    }

    /** @return array{AiAutoReplyService, object} */
    private function serviceWithIncomingMessages(int $additionalMessages = 0): array
    {
        $service = new AiAutoReplyService(Mockery::mock(WahaSender::class));
        $latest = $this->insertIncoming('incoming-first', now()->subMinutes(30), 'Pesan pertama');

        for ($index = 0; $index < $additionalMessages; $index++) {
            $latest = $this->insertIncoming('incoming-'.($index + 2), now()->subMinutes($additionalMessages - $index), 'Pesan lanjutan');
        }

        return [$service, $latest];
    }

    private function insertIncoming(string $id, mixed $sentAt, string $body): object
    {
        DB::table('TChatD')->insert([
            'Id' => $id,
            'IdChat' => 'chat-1',
            'ArahPesan' => 'Masuk',
            'JenisPesan' => 'Teks',
            'IsiPesan' => $body,
            'DikirimOlehCustomer' => true,
            'DihasilkanOlehAi' => false,
            'TglPesan' => $sentAt,
            'TglBuat' => $sentAt,
        ]);

        return DB::table('TChatD')->where('Id', $id)->first();
    }

    private function invoke(object $target, string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $arguments);
    }
}
