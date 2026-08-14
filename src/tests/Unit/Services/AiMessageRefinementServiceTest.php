<?php

namespace Tests\Unit\Services;

use App\Services\Ai\AiMessageRefinementService;
use App\Services\Ai\AiAutoReplyService;
use App\Support\AiSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Mockery;
use RuntimeException;

class AiMessageRefinementServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('MPengaturanAi');
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

    private function createSchema(): void
    {
        Schema::create('MPengaturanAi', function ($table): void {
            $table->string('Id')->primary();
            $table->string('KodePengaturan')->unique();
            $table->string('NamaPengaturan');
            $table->string('ProviderAi');
            $table->string('ModelAi')->nullable();
            $table->string('ModelInstructAi')->nullable();
            $table->string('BaseUrl')->nullable();
            $table->boolean('NonAktif')->default(false);
            $table->boolean('AutoReplyAktif')->default(false);
        });
    }

    private function seedSettings(): object
    {
        DB::table('MPengaturanAi')->insert([
            'Id' => 'default-settings',
            'KodePengaturan' => 'DEFAULT',
            'NamaPengaturan' => 'Default AI',
            'ProviderAi' => 'OpenAI',
            'ModelAi' => 'gpt-4',
            'ModelInstructAi' => 'gpt-4-instruct',
            'BaseUrl' => 'https://api.openai.com/v1',
            'NonAktif' => false,
            'AutoReplyAktif' => true,
        ]);
        return (object) DB::table('MPengaturanAi')->where('KodePengaturan', 'DEFAULT')->first();
    }
}