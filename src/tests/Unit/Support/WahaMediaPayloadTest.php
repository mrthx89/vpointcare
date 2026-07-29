<?php

namespace Tests\Unit\Support;

use App\Support\WahaMediaPayload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WahaMediaPayloadTest extends TestCase
{
    public function test_inspects_nested_media_without_exposing_base64(): void
    {
        $encoded = base64_encode('ID3audio');
        $payload = json_encode([
            'hasMedia' => true,
            'type' => 'ptt',
            'media' => [
                'data' => $encoded,
                'mimetype' => 'audio/ogg; codecs=opus',
                'filename' => 'voice-note.ogg',
            ],
        ], JSON_THROW_ON_ERROR);

        $media = WahaMediaPayload::inspectPayload($payload, null, null, 'Audio');

        self::assertSame([
            'source',
            'mime_type',
            'file_name',
            'category',
            'inline',
        ], array_keys($media));
        self::assertSame('media.data', $media['source']);
        self::assertSame('audio/ogg', $media['mime_type']);
        self::assertSame('voice-note.ogg', $media['file_name']);
        self::assertSame('audio', $media['category']);
        self::assertTrue($media['inline']);
        self::assertStringNotContainsString($encoded, json_encode($media, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('ID3audio', json_encode($media, JSON_THROW_ON_ERROR));
    }

    public function test_decodes_supported_root_base64_with_media_context(): void
    {
        $contents = "%PDF-1.4\n%%EOF";
        $payload = json_encode([
            'hasMedia' => true,
            'type' => 'document',
            'base64' => base64_encode($contents),
            'mimetype' => 'application/pdf',
            'filename' => 'invoice.pdf',
        ], JSON_THROW_ON_ERROR);

        $media = WahaMediaPayload::fromPayloadJson($payload, null, null, 'Dokumen');

        self::assertSame('base64', $media['source']);
        self::assertSame($contents, $media['contents']);
        self::assertSame('application/pdf', $media['mime_type']);
        self::assertSame('invoice.pdf', $media['file_name']);
        self::assertSame('pdf', $media['category']);
        self::assertTrue($media['inline']);
    }

    /** @param array<string, mixed> $payloadData */
    #[DataProvider('explicitCandidateProvider')]
    public function test_inspects_each_explicit_candidate_key(string $source, array $payloadData, string $messageType): void
    {
        $media = WahaMediaPayload::inspectPayload(
            json_encode($payloadData, JSON_THROW_ON_ERROR),
            null,
            null,
            $messageType,
        );

        self::assertSame([
            'source',
            'mime_type',
            'file_name',
            'category',
            'inline',
        ], array_keys($media));
        self::assertSame($source, $media['source']);
        $inspected = json_encode($media, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString(base64_encode('binary-content'), $inspected);
        self::assertStringNotContainsString('binary-content', $inspected);
    }

    /**
     * @return array<string, array{string, array<string, mixed>, string}>
     */
    public static function explicitCandidateProvider(): array
    {
        $encoded = base64_encode('binary-content');
        $dataUrl = 'data:image/png;base64,'.$encoded;

        return [
            'root dataUrl' => ['dataUrl', ['dataUrl' => $dataUrl], 'Teks'],
            'root data_url' => ['data_url', ['data_url' => $dataUrl], 'Teks'],
            'nested media dataUrl' => ['media.dataUrl', ['media' => ['dataUrl' => $dataUrl]], 'Teks'],
            'nested media data_url' => ['media.data_url', ['media' => ['data_url' => $dataUrl]], 'Teks'],
            'root base64' => ['base64', ['hasMedia' => true, 'base64' => $encoded], 'Teks'],
            'nested media base64' => ['media.base64', ['media' => ['base64' => $encoded]], 'Teks'],
            'nested media data' => ['media.data', ['media' => ['data' => $encoded]], 'Teks'],
            'nested media file' => ['media.file', ['media' => ['file' => $encoded]], 'Teks'],
            'nested file data' => ['file.data', ['file' => ['data' => $encoded]], 'Teks'],
            'nested file base64' => ['file.base64', ['file' => ['base64' => $encoded]], 'Teks'],
            'body with media context' => ['body', ['hasMedia' => true, 'body' => $encoded], 'Teks'],
        ];
    }

    #[DataProvider('rawRootCandidateProvider')]
    /** @param array<string, mixed> $context */
    public function test_accepts_raw_root_candidates_only_with_media_context(
        string $source,
        string $key,
        array $context,
        string $messageType,
    ): void {
        $encoded = base64_encode('binary-content');

        $withContext = WahaMediaPayload::inspectPayload(
            json_encode([...$context, $key => $encoded], JSON_THROW_ON_ERROR),
            null,
            null,
            $messageType,
        );

        self::assertSame([
            'source',
            'mime_type',
            'file_name',
            'category',
            'inline',
        ], array_keys($withContext));
        self::assertSame($source, $withContext['source']);
        $inspected = json_encode($withContext, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString($encoded, $inspected);
        self::assertStringNotContainsString('binary-content', $inspected);
        self::assertNull(WahaMediaPayload::inspectPayload(
            json_encode([$key => $encoded], JSON_THROW_ON_ERROR),
            null,
            null,
            'Teks',
        ));
    }

    /**
     * @return array<string, array{string, string, array<string, mixed>, string}>
     */
    public static function rawRootCandidateProvider(): array
    {
        $candidates = [
            'data' => 'data',
            'file' => 'file',
            'body' => 'body',
        ];
        $contexts = [
            'hasMedia' => [['hasMedia' => true], 'Teks'],
            'mime' => [['mimetype' => 'image/png'], 'Teks'],
            'filename' => [['filename' => 'photo.png'], 'Teks'],
            'message type' => [[], 'Image'],
            'Indonesian message type' => [[], 'Gambar'],
        ];
        $cases = [];

        foreach ($candidates as $source => $key) {
            foreach ($contexts as $contextName => [$context, $messageType]) {
                $cases[$source.' with '.$contextName] = [$source, $key, $context, $messageType];
            }
        }

        return $cases;
    }

    public function test_rejects_plain_text_invalid_json_malformed_base64_and_empty_decode(): void
    {
        $plainText = json_encode(['body' => 'Halo customer'], JSON_THROW_ON_ERROR);
        $malformed = json_encode([
            'hasMedia' => true,
            'body' => '%%%not-base64%%%',
            'mimetype' => 'video/mp4',
        ], JSON_THROW_ON_ERROR);
        $empty = json_encode([
            'hasMedia' => true,
            'base64' => base64_encode(''),
        ], JSON_THROW_ON_ERROR);

        self::assertNull(WahaMediaPayload::inspectPayload($plainText, null, null, 'Teks'));
        self::assertNull(WahaMediaPayload::inspectPayload($malformed, null, null, 'Video'));
        self::assertNull(WahaMediaPayload::fromPayloadJson('{invalid-json', null, null, 'Dokumen'));
        self::assertNull(WahaMediaPayload::fromPayloadJson($malformed, null, null, 'Video'));
        self::assertNull(WahaMediaPayload::fromPayloadJson($empty, null, null, 'Dokumen'));
    }

    public function test_decodes_payload_data_uri_with_payload_metadata(): void
    {
        $payload = json_encode([
            'media' => [
                'dataUrl' => 'data:image/png;base64,'.base64_encode('png-data'),
                'mimetype' => 'image/png',
                'filename' => 'photo.png',
            ],
        ], JSON_THROW_ON_ERROR);

        $media = WahaMediaPayload::fromPayloadJson($payload, null, null, 'Gambar');

        self::assertSame('media.dataUrl', $media['source']);
        self::assertSame('png-data', $media['contents']);
        self::assertSame('image/png', $media['mime_type']);
        self::assertSame('photo.png', $media['file_name']);
    }

    public function test_decodes_payload_data_uri_without_mime_using_payload_metadata(): void
    {
        $payload = json_encode([
            'media' => [
                'dataUrl' => 'data:;base64,'.base64_encode('png-data'),
                'mimetype' => 'image/png',
                'filename' => 'photo.png',
            ],
        ], JSON_THROW_ON_ERROR);

        $media = WahaMediaPayload::fromPayloadJson($payload, null, null, 'Gambar');

        self::assertSame('media.dataUrl', $media['source']);
        self::assertSame('png-data', $media['contents']);
        self::assertSame('image/png', $media['mime_type']);
        self::assertSame('photo.png', $media['file_name']);
        self::assertTrue($media['inline']);
    }

    public function test_decodes_base64_data_uri(): void
    {
        $media = WahaMediaPayload::fromDataUri(
            'data:audio/ogg;base64,'.base64_encode('ID3audio'),
            null,
            'voice.ogg',
            'Audio',
        );

        self::assertSame('ID3audio', $media['contents']);
        self::assertSame('audio/ogg', $media['mime_type']);
        self::assertSame('voice.ogg', $media['file_name']);
    }

    public function test_decodes_non_base64_data_uri(): void
    {
        $media = WahaMediaPayload::fromDataUri('data:text/plain,Halo%20customer', null, null, 'Dokumen');

        self::assertSame('Halo customer', $media['contents']);
        self::assertSame('text/plain', $media['mime_type']);
    }

    public function test_declared_mime_wins_and_is_normalized(): void
    {
        $media = WahaMediaPayload::fromBinary(
            contents: "%PDF-1.4\n%%EOF",
            declaredMime: 'application/pdf; charset=binary',
            payloadMime: 'application/octet-stream',
            declaredFileName: 'report',
            payloadFileName: 'wrong.bin',
            messageType: 'Dokumen',
            source: 'payload.base64',
        );

        self::assertSame('application/pdf', $media['mime_type']);
        self::assertSame('report.pdf', $media['file_name']);
        self::assertSame('pdf', $media['category']);
        self::assertTrue($media['inline']);
    }

    public function test_mime_uses_filename_then_signature_when_metadata_is_invalid(): void
    {
        $fromFilename = WahaMediaPayload::fromBinary(
            contents: 'not a real image',
            declaredMime: "image/png\r\nX-Evil: yes",
            payloadMime: 'invalid mime',
            declaredFileName: 'photo.jpeg',
        );
        $fromSignature = WahaMediaPayload::fromBinary("%PDF-1.4\n%%EOF");

        self::assertSame('image/jpeg', $fromFilename['mime_type']);
        self::assertSame('application/pdf', $fromSignature['mime_type']);
        self::assertSame('pdf', $fromSignature['category']);
    }

    public function test_webp_sticker_and_voice_note_are_categorized_with_safe_defaults(): void
    {
        $sticker = WahaMediaPayload::fromBinary(
            contents: 'RIFF'.pack('V', 4).'WEBPVP8 ',
            messageType: 'Sticker',
        );
        $voice = WahaMediaPayload::fromBinary(
            contents: 'OggS'.str_repeat("\0", 32),
            declaredMime: 'audio/ogg',
            messageType: 'PTT',
        );

        self::assertSame('image/webp', $sticker['mime_type']);
        self::assertSame('image', $sticker['category']);
        self::assertSame('whatsapp-image.webp', $sticker['file_name']);
        self::assertTrue($sticker['inline']);
        self::assertSame('audio/ogg', $voice['mime_type']);
        self::assertSame('audio', $voice['category']);
        self::assertSame('whatsapp-audio.ogg', $voice['file_name']);
        self::assertTrue($voice['inline']);
    }

    public function test_unknown_binary_uses_safe_fallback_filename(): void
    {
        $media = WahaMediaPayload::fromBinary("\x01\x02\x03");

        self::assertSame('application/octet-stream', $media['mime_type']);
        self::assertSame('file', $media['category']);
        self::assertSame('whatsapp-media.bin', $media['file_name']);
        self::assertFalse($media['inline']);
    }

    public function test_filename_is_safe_for_content_disposition_and_preserves_unicode(): void
    {
        $media = WahaMediaPayload::fromBinary(
            contents: 'plain document',
            declaredMime: 'application/pdf',
            declaredFileName: "..\\..\\invoice\r\n\" X-Evil: yes Документ",
        );

        self::assertSame('invoice X-Evil: yes Документ.pdf', $media['file_name']);
        self::assertStringNotContainsString('..', $media['file_name']);
        self::assertStringNotContainsString("\r", $media['file_name']);
        self::assertStringNotContainsString("\n", $media['file_name']);
        self::assertStringNotContainsString('"', $media['file_name']);
    }

    public function test_long_filename_without_extension_reserves_space_for_mime_extension(): void
    {
        $media = WahaMediaPayload::fromBinary(
            contents: 'plain document',
            declaredMime: 'application/pdf',
            declaredFileName: str_repeat('a', 180),
        );

        self::assertLessThanOrEqual(180, mb_strlen($media['file_name']));
        self::assertStringEndsWith('.pdf', $media['file_name']);
    }

    public function test_active_and_unknown_content_is_not_inline(): void
    {
        self::assertFalse(WahaMediaPayload::canPreviewInline('image/svg+xml'));
        self::assertFalse(WahaMediaPayload::canPreviewInline('text/html'));
        self::assertFalse(WahaMediaPayload::canPreviewInline('application/octet-stream'));
        self::assertTrue(WahaMediaPayload::canPreviewInline('image/webp'));
        self::assertTrue(WahaMediaPayload::canPreviewInline('audio/ogg'));
        self::assertTrue(WahaMediaPayload::canPreviewInline('video/mp4'));
        self::assertTrue(WahaMediaPayload::canPreviewInline('application/pdf'));
    }
}
