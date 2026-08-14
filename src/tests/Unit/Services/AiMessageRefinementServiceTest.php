<?php

namespace Tests\Unit\Services;

use App\Services\Ai\AiAutoReplyService;
use App\Services\Ai\AiMessageRefinementService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AiMessageRefinementServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    public function test_refine_rejects_empty_input(): void
    {
        $autoReply = Mockery::mock(AiAutoReplyService::class);
        $service = new AiMessageRefinementService($autoReply);
        $result = $service->refine('   ');
        self::assertFalse($result['ok']);
        self::assertSame('Pesan tidak boleh kosong.', $result['error']);
    }

    public function test_refine_returns_error_when_provider_is_missing(): void
    {
        Cache::put('mpengaturan_ai_default_v2', []);
        $autoReply = Mockery::mock(AiAutoReplyService::class);
        $service = new AiMessageRefinementService($autoReply);
        $result = $service->refine('test');
        self::assertFalse($result['ok']);
        self::assertSame('Konfigurasi AI Agent belum diatur.', $result['error']);
    }

    public function test_refine_returns_refined_text_on_success(): void
    {
        $settings = $this->seedSettings();
        $autoReply = Mockery::mock(AiAutoReplyService::class);
        $autoReply->shouldReceive('generateManualRefinement')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->ProviderAi === 'OpenAI'), 'hallo')
            ->andReturn(['text' => '  Halo, apa kabar?  ']);

        $service = new AiMessageRefinementService($autoReply);
        $result = $service->refine('hallo');
        self::assertTrue($result['ok']);
        self::assertSame('Halo, apa kabar?', $result['text']);
    }

    public function test_refine_removes_ai_signature_from_manual_reply(): void
    {
        $settings = $this->seedSettings(['TandaTanganAi' => '~ Admin AI']);
        $autoReply = Mockery::mock(AiAutoReplyService::class);
        $autoReply->shouldReceive('generateManualRefinement')
            ->once()
            ->andReturn(['text' => "Halo, apa kabar?\n\n~ Admin AI"]);

        $service = new AiMessageRefinementService($autoReply);
        $result = $service->refine('hallo');

        self::assertTrue($result['ok']);
        self::assertSame('Halo, apa kabar?', $result['text']);
    }

    public function test_refine_removes_fallback_ai_signature_from_manual_reply(): void
    {
        $this->seedSettings();
        $autoReply = Mockery::mock(AiAutoReplyService::class);
        $autoReply->shouldReceive('generateManualRefinement')
            ->once()
            ->andReturn(['text' => "Halo, apa kabar?\n\n~ Auto Reply by VICA"]);

        $service = new AiMessageRefinementService($autoReply);
        $result = $service->refine('hallo');

        self::assertTrue($result['ok']);
        self::assertSame('Halo, apa kabar?', $result['text']);
    }

    public function test_refine_removes_short_ai_footer_from_manual_reply(): void
    {
        $this->seedSettings();
        $autoReply = Mockery::mock(AiAutoReplyService::class);
        $autoReply->shouldReceive('generateManualRefinement')
            ->once()
            ->andReturn(['text' => "Halo, apa kabar?\n\n~AI"]);

        $service = new AiMessageRefinementService($autoReply);
        $result = $service->refine('hallo');

        self::assertTrue($result['ok']);
        self::assertSame('Halo, apa kabar?', $result['text']);
    }

    public function test_refine_returns_error_on_empty_provider_response(): void
    {
        $settings = $this->seedSettings();
        $autoReply = Mockery::mock(AiAutoReplyService::class);
        $autoReply->shouldReceive('generateManualRefinement')->andReturn(null);

        $service = new AiMessageRefinementService($autoReply);
        $result = $service->refine('hallo');
        self::assertFalse($result['ok']);
        self::assertSame('AI tidak mengembalikan respons yang valid.', $result['error']);
    }

    public function test_refine_returns_sanitized_error_on_provider_exception(): void
    {
        $settings = $this->seedSettings();
        $autoReply = Mockery::mock(AiAutoReplyService::class);
        $autoReply->shouldReceive('generateManualRefinement')->andThrow(new RuntimeException('API Key OPENAI_API_KEY is invalid'));

        $service = new AiMessageRefinementService($autoReply);
        $result = $service->refine('hallo');
        self::assertFalse($result['ok']);
        self::assertSame('Gagal menghubungi AI provider.', $result['error']);
        self::assertFalse(isset($result['detail']));
    }

    private function seedSettings(array $overrides = []): object
    {
        $settings = array_merge([
            'Id' => 'default-settings',
            'KodePengaturan' => 'DEFAULT',
            'NamaPengaturan' => 'Default AI',
            'ProviderAi' => 'OpenAI',
            'ModelAi' => 'gpt-4',
            'ModelInstructAi' => 'gpt-4-instruct',
            'BaseUrl' => 'https://api.openai.com/v1',
            'NonAktif' => false,
            'AutoReplyAktif' => true,
            'TandaTanganAi' => null,
        ], $overrides);

        Cache::put('mpengaturan_ai_default_v2', $settings);

        return (object) $settings;
    }
}
