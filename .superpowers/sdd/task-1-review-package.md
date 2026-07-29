# Review Package: Task 1 Re-review

## Base
HEAD f31f8c45628ac25d14149b2b8c02b03521b0fe99

## Status
Uncommitted changes only. No commit made per user instruction.

## Stat

## Diff

## Full New File
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
            'file data with media context' => ['file.data', ['hasMedia' => true, 'file' => ['data' => $encoded]], 'Teks'],
            'file base64 with media context' => ['file.base64', ['hasMedia' => true, 'file' => ['base64' => $encoded]], 'Teks'],
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
    ): void
    {
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
        self::assertNull(WahaMediaPayload::fromPayloadJson('{invalid-json', null, null, 'Dokumen'));
        self::assertNull(WahaMediaPayload::fromPayloadJson($malformed, null, null, 'Video'));
        self::assertNull(WahaMediaPayload::fromPayloadJson($empty, null, null, 'Dokumen'));
    }
}

