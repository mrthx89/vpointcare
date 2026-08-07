<?php

namespace Tests\Unit\Services;

use App\Services\Ai\AiAutoReplyService;
use App\Services\Waha\WahaSender;
use Mockery;
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
}