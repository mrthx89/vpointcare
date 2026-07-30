<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Master\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WahaMediaControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('TChatD', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->text('IsiPesan')->nullable();
            $table->string('UrlMedia')->nullable();
            $table->text('PayloadJson')->nullable();
            $table->string('NamaFileMedia')->nullable();
            $table->string('TipeMime')->nullable();
            $table->string('JenisPesan')->nullable();
        });

        config()->set('services.waha.media_base_url', 'https://waha.test');
        config()->set('services.waha.api_key', 'test-key');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('TChatD');

        parent::tearDown();
    }

    public function test_serves_embedded_media_when_url_is_empty(): void
    {
        $contents = "%PDF-1.4\n%%EOF";
        $payload = $this->embeddedPayload($contents, 'application/pdf', 'invoice.pdf');

        $this->insertMessage('message-embedded', [
            'PayloadJson' => $payload,
            'JenisPesan' => 'Dokumen',
        ]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-embedded']));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        self::assertStringStartsWith('inline;', (string) $response->headers->get('Content-Disposition'));
        self::assertSame($contents, $response->getContent());
        self::assertStringNotContainsString(base64_encode($contents), $response->getContent());
        self::assertStringNotContainsString($payload, $response->getContent());
    }

    public function test_download_parameter_forces_attachment_with_independent_fixture(): void
    {
        $this->insertMessage('message-download', [
            'PayloadJson' => $this->embeddedPayload('PDF-1', 'application/pdf', 'test.pdf'),
            'JenisPesan' => 'Dokumen',
        ]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', [
            'message' => 'message-download',
            'download' => 1,
        ]));

        $response->assertOk();
        self::assertStringStartsWith('attachment;', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_serves_data_uri_from_url_media(): void
    {
        $contents = 'tiny image';

        $this->insertMessage('message-data-uri', [
            'UrlMedia' => 'data:image/png;base64,'.base64_encode($contents),
            'NamaFileMedia' => 'photo.png',
            'JenisPesan' => 'Gambar',
        ]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-data-uri']));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        self::assertStringStartsWith('inline;', (string) $response->headers->get('Content-Disposition'));
        self::assertSame($contents, $response->getContent());
    }

    public function test_serves_storage_media_from_public_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('waha/report.pdf', '%PDF-1.4 storage');

        $this->insertMessage('message-storage', [
            'UrlMedia' => '/storage/waha/report.pdf',
            'NamaFileMedia' => 'report.pdf',
            'JenisPesan' => 'Dokumen',
        ]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-storage']));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        self::assertSame('%PDF-1.4 storage', $response->getContent());
    }

    public function test_serves_base64_media_from_waha_json_response(): void
    {
        $contents = 'remote json binary';
        $payload = $this->embeddedPayload($contents, 'application/pdf', 'remote.pdf');

        $this->insertMessage('message-json', [
            'UrlMedia' => '/api/files/json-response',
            'JenisPesan' => 'Dokumen',
        ]);
        Http::fake(['https://waha.test/*' => Http::response($payload, 200, ['Content-Type' => 'application/json'])]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-json']));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        self::assertSame($contents, $response->getContent());
        self::assertStringNotContainsString(base64_encode($contents), $response->getContent());
    }

    public function test_falls_back_to_payload_when_media_url_fails_without_logging_sensitive_values(): void
    {
        $contents = 'payload fallback binary';
        $payload = $this->embeddedPayload($contents, 'application/pdf', 'fallback.pdf');

        $this->insertMessage('message-url-fallback', [
            'UrlMedia' => '/api/files/missing?token=signed-url-token',
            'PayloadJson' => $payload,
            'JenisPesan' => 'Dokumen',
        ]);
        Http::fake(['https://waha.test/*' => Http::response('', 404)]);
        $warningEntries = [];
        $this->captureSingleWarning($warningEntries);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-url-fallback']));

        Http::assertSent(fn ($request): bool => $request->hasHeader('X-Api-Key', 'test-key'));
        $this->assertSafeWarning($warningEntries, 'message-url-fallback', 'upstream_status', 404);
        $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        self::assertSame($contents, $response->getContent());
        self::assertStringNotContainsString(base64_encode($contents), $response->getContent());
        self::assertStringNotContainsString($payload, $response->getContent());
    }

    public function test_external_media_host_is_rejected_without_sending_waha_api_key(): void
    {
        $this->insertMessage('message-external-host', [
            'UrlMedia' => 'https://evil.test/private.pdf',
            'JenisPesan' => 'Dokumen',
        ]);
        Http::fake();
        $warningEntries = [];
        $this->captureSingleWarning($warningEntries);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-external-host']));

        $response->assertStatus(424);
        Http::assertNothingSent();
        $this->assertSafeWarning($warningEntries, 'message-external-host', 'untrusted_host');
    }

    public function test_connection_exception_logs_only_safe_context(): void
    {
        $this->insertMessage('message-url-exception', [
            'UrlMedia' => '/api/files/missing?token=signed-url-token',
        ]);
        Http::fake(fn (): never => throw new ConnectionException('secret signed-url-token'));
        $warningEntries = [];
        $this->captureSingleWarning($warningEntries);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-url-exception']));

        $this->assertSafeWarning($warningEntries, 'message-url-exception', 'upstream_exception');
        $response->assertStatus(424);
        self::assertStringNotContainsString('signed-url-token', $response->getContent());
    }

    public function test_missing_storage_file_logs_safe_reason(): void
    {
        Storage::fake('public');
        $warningEntries = [];
        $this->captureSingleWarning($warningEntries);

        $this->insertMessage('message-storage-missing', [
            'UrlMedia' => '/storage/missing/private-token.pdf',
            'JenisPesan' => 'Dokumen',
        ]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-storage-missing']));

        $this->assertSafeWarning($warningEntries, 'message-storage-missing', 'storage_missing');
        $response->assertStatus(424);
        self::assertStringNotContainsString('private-token', $response->getContent());
    }

    public function test_empty_upstream_body_logs_safe_reason(): void
    {
        $warningEntries = [];
        $this->captureSingleWarning($warningEntries);

        $this->insertMessage('message-upstream-empty', [
            'UrlMedia' => '/api/files/empty?token=signed-url-token',
            'JenisPesan' => 'Dokumen',
        ]);
        Http::fake(['https://waha.test/*' => Http::response('', 200, ['Content-Type' => 'application/pdf'])]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-upstream-empty']));

        $this->assertSafeWarning($warningEntries, 'message-upstream-empty', 'upstream_empty');
        $response->assertStatus(424);
        self::assertStringNotContainsString('signed-url-token', $response->getContent());
    }

    public function test_invalid_json_media_logs_safe_reason(): void
    {
        $warningEntries = [];
        $this->captureSingleWarning($warningEntries);

        $this->insertMessage('message-invalid-json-media', [
            'UrlMedia' => '/api/files/json?token=signed-url-token',
            'JenisPesan' => 'Dokumen',
        ]);
        Http::fake(['https://waha.test/*' => Http::response('{"media":{"data":"%%%not-base64%%%"}}', 200, ['Content-Type' => 'application/json'])]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-invalid-json-media']));

        $this->assertSafeWarning($warningEntries, 'message-invalid-json-media', 'invalid_json_media');
        $response->assertStatus(424);
        self::assertStringNotContainsString('signed-url-token', $response->getContent());
        self::assertStringNotContainsString('not-base64', $response->getContent());
    }

    public function test_guest_cannot_open_media(): void
    {
        $this->insertMessage('message-guest', [
            'PayloadJson' => $this->embeddedPayload('private media'),
        ]);

        $response = $this->getJson(route('admin.waha-media.show', ['message' => 'message-guest']));

        $response->assertUnauthorized();
        self::assertStringNotContainsString('private media', $response->getContent());
    }

    public function test_missing_message_returns_not_found(): void
    {
        $this->actingAgent()
            ->get(route('admin.waha-media.show', ['message' => 'missing-message']))
            ->assertNotFound();
    }

    public function test_invalid_media_sources_return_generic_unavailable_response(): void
    {
        $encoded = '%%%not-base64%%%';
        $mediaUrl = '/api/files/missing?token=signed-url-token';
        $payload = json_encode([
            'hasMedia' => true,
            'media' => [
                'data' => $encoded,
                'mimetype' => 'application/pdf',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->insertMessage('message-invalid', [
            'UrlMedia' => $mediaUrl,
            'PayloadJson' => $payload,
            'JenisPesan' => 'Dokumen',
        ]);
        Http::fake(['https://waha.test/*' => Http::response('upstream failure detail', 500)]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-invalid']));

        $response->assertStatus(424)
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $translations = [];
        foreach (['id', 'en'] as $locale) {
            $translations[$locale] = __('ui.controllers.waha_media.unavailable', [], $locale);
            self::assertNotSame('ui.controllers.waha_media.unavailable', $translations[$locale]);
        }
        self::assertSame(__('ui.controllers.waha_media.unavailable'), $response->getContent());
        foreach ([$mediaUrl, 'signed-url-token', 'upstream failure detail', $encoded, $payload, 'payload', 'base64', 'exception', 'internal', 'error'] as $forbidden) {
            self::assertStringNotContainsString(strtolower($forbidden), strtolower($response->getContent()));
        }
    }

    public function test_serves_raw_base64_text_body_as_media(): void
    {
        $contents = str_repeat('image-bytes-', 10);

        $this->insertMessage('message-raw-body-base64', [
            'IsiPesan' => base64_encode($contents),
            'JenisPesan' => 'Gambar',
            'NamaFileMedia' => 'photo.png',
            'TipeMime' => 'image/png',
        ]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-raw-body-base64']));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        self::assertSame($contents, $response->getContent());
    }

    #[DataProvider('unsafeInlineMimeProvider')]
    public function test_active_media_types_are_forced_to_attachment(string $mimeType, string $body, string $fileName): void
    {
        $messageId = 'message-unsafe-'.str_replace('/', '-', $mimeType);

        $this->insertMessage($messageId, [
            'UrlMedia' => '/api/files/'.$fileName,
            'NamaFileMedia' => $fileName,
            'TipeMime' => $mimeType,
        ]);
        Http::fake(['https://waha.test/*' => Http::response($body, 200, ['Content-Type' => $mimeType])]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => $messageId]));

        $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        self::assertStringStartsWith('attachment;', (string) $response->headers->get('Content-Disposition'));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function unsafeInlineMimeProvider(): array
    {
        return [
            'HTML' => ['text/html', '<html><body>unsafe</body></html>', 'unsafe.html'],
            'SVG' => ['image/svg+xml', '<svg xmlns="http://www.w3.org/2000/svg"/>', 'unsafe.svg'],
        ];
    }

    public function test_filename_crlf_is_not_emitted_in_content_disposition(): void
    {
        $this->insertMessage('message-filename', [
            'UrlMedia' => '/api/files/report.pdf',
            'NamaFileMedia' => "..\\..\\report.pdf\r\nX-Injected: true",
            'TipeMime' => 'application/pdf',
        ]);
        Http::fake(['https://waha.test/*' => Http::response('%PDF-1.4', 200, ['Content-Type' => 'application/pdf'])]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-filename']));

        $response->assertOk();
        self::assertStringNotContainsString("\r", (string) $response->headers->get('Content-Disposition'));
        self::assertStringNotContainsString("\n", (string) $response->headers->get('Content-Disposition'));
        self::assertStringNotContainsString('X-Injected', (string) $response->headers->get('Content-Disposition'));
        self::assertStringNotContainsString('..', (string) $response->headers->get('Content-Disposition'));
        self::assertStringNotContainsString('\\', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_unicode_filename_uses_ascii_fallback_without_error(): void
    {
        $this->insertMessage('message-unicode-filename', [
            'PayloadJson' => $this->embeddedPayload('%PDF-1.4 unicode', 'application/pdf', 'laporan-Δ.pdf'),
            'JenisPesan' => 'Dokumen',
        ]);

        $response = $this->actingAgent()->get(route('admin.waha-media.show', [
            'message' => 'message-unicode-filename',
            'download' => 1,
        ]));

        $response->assertOk();
        $disposition = (string) $response->headers->get('Content-Disposition');
        self::assertStringStartsWith('attachment;', $disposition);
        self::assertStringContainsString('filename=laporan-.pdf', $disposition);
        self::assertStringContainsString("filename*=utf-8''laporan-%CE%94.pdf", $disposition);
    }

    private function actingAgent(): static
    {
        return $this->actingAs(new Pengguna([
            'Id' => 'agent-1',
            'NamaPengguna' => 'Agent Test',
            'NonAktif' => false,
        ]));
    }

    /**
     * @param  array<int, array{event: string, context: array<string, mixed>}>  $warningEntries
     */
    private function captureSingleWarning(array &$warningEntries): void
    {
        Log::spy();
        Log::shouldReceive('warning')->once()->withArgs(function (string $event, array $context) use (&$warningEntries): bool {
            $warningEntries[] = compact('event', 'context');

            return true;
        });
    }

    /**
     * @param  array<int, array{event: string, context: array<string, mixed>}>  $warningEntries
     */
    private function assertSafeWarning(array $warningEntries, string $messageId, string $reasonCode, ?int $status = null): void
    {
        $context = [
            'message_id' => $messageId,
            'source' => 'url',
        ];

        if ($status !== null) {
            $context['status'] = $status;
        }

        $context['reason_code'] = $reasonCode;

        self::assertCount(1, $warningEntries);
        self::assertSame('WAHA media source unavailable.', $warningEntries[0]['event']);
        self::assertSame($context, $warningEntries[0]['context']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertMessage(string $id, array $attributes): void
    {
        DB::table('TChatD')->insert([
            'Id' => $id,
            'UrlMedia' => null,
            'PayloadJson' => null,
            'NamaFileMedia' => null,
            'TipeMime' => null,
            'JenisPesan' => null,
            ...$attributes,
        ]);
    }

    private function embeddedPayload(string $contents, string $mimeType = 'application/octet-stream', string $fileName = 'media.bin'): string
    {
        return json_encode([
            'hasMedia' => true,
            'media' => [
                'data' => base64_encode($contents),
                'mimetype' => $mimeType,
                'filename' => $fileName,
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
