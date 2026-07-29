<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Symfony\Component\Mime\MimeTypes;

final class WahaMediaPayload
{
    /**
     * @return array{source: string, mime_type: string, file_name: ?string, category: string, inline: bool}|null
     */
    public static function inspectPayload(
        ?string $payloadJson,
        ?string $declaredMime = null,
        ?string $declaredFileName = null,
        ?string $messageType = null,
    ): ?array {
        $media = self::fromPayloadJson(
            $payloadJson,
            $declaredMime,
            $declaredFileName,
            $messageType,
        );

        if (! $media) {
            return null;
        }

        unset($media['contents']);

        return $media;
    }

    /**
     * @return array{source: string, contents: string, mime_type: string, file_name: ?string, category: string, inline: bool}|null
     */
    public static function fromPayloadJson(
        ?string $payloadJson,
        ?string $declaredMime = null,
        ?string $declaredFileName = null,
        ?string $messageType = null,
    ): ?array {
        $payload = self::payload($payloadJson);
        $candidate = $payload ? self::candidate($payload, $messageType) : null;

        if (! $candidate) {
            return null;
        }

        if (str_starts_with(strtolower($candidate['value']), 'data:')) {
            $dataUri = self::dataUri($candidate['value']);

            if (! $dataUri) {
                return null;
            }

            if ($dataUri['base64']) {
                $encoded = preg_replace('/\s+/', '', $dataUri['data']);
                $contents = $encoded === null || $encoded === '' ? false : base64_decode($encoded, true);
            } else {
                $contents = rawurldecode($dataUri['data']);
            }

            if ($contents === false || $contents === '') {
                return null;
            }

            $media = self::fromBinary(
                $contents,
                $declaredMime,
                $dataUri['mime'] ?? self::metadata($payload, self::MIME_KEYS),
                $declaredFileName,
                self::metadata($payload, self::FILE_NAME_KEYS),
                $messageType,
                $candidate['source'],
            );
        } else {
            $encoded = preg_replace('/\s+/', '', $candidate['value']);
            $contents = $encoded === null || $encoded === '' ? false : base64_decode($encoded, true);

            if ($contents === false || $contents === '') {
                return null;
            }

            $media = self::fromBinary(
                $contents,
                $declaredMime,
                self::metadata($payload, self::MIME_KEYS),
                $declaredFileName,
                self::metadata($payload, self::FILE_NAME_KEYS),
                $messageType,
                $candidate['source'],
            );
        }

        if (! $media) {
            return null;
        }

        $media['source'] = $candidate['source'];

        return $media;
    }

    /**
     * @return array{source: string, contents: string, mime_type: string, file_name: ?string, category: string, inline: bool}|null
     */
    public static function fromDataUri(
        string $dataUri,
        ?string $declaredMime = null,
        ?string $declaredFileName = null,
        ?string $messageType = null,
    ): ?array {
        $parsed = self::dataUri($dataUri);

        if (! $parsed) {
            return null;
        }

        if ($parsed['base64']) {
            $encoded = preg_replace('/\s+/', '', $parsed['data']);
            $contents = $encoded === null || $encoded === '' ? false : base64_decode($encoded, true);
        } else {
            $contents = rawurldecode($parsed['data']);
        }

        if ($contents === false || $contents === '') {
            return null;
        }

        return self::fromBinary(
            $contents,
            $declaredMime,
            $parsed['mime'],
            $declaredFileName,
            null,
            $messageType,
            'data-uri',
        );
    }

    /**
     * @return array{source: string, contents: string, mime_type: string, file_name: ?string, category: string, inline: bool}
     */
    public static function fromBinary(
        string $contents,
        ?string $declaredMime = null,
        ?string $payloadMime = null,
        ?string $declaredFileName = null,
        ?string $payloadFileName = null,
        ?string $messageType = null,
        string $source = 'binary',
    ): array {
        $fileName = self::firstNonEmpty($declaredFileName, $payloadFileName);
        $mimeType = self::normalizeMime($declaredMime)
            ?? self::normalizeMime($payloadMime)
            ?? self::mimeFromFilename($fileName)
            ?? self::mimeFromContents($contents)
            ?? 'application/octet-stream';
        $category = self::category($mimeType, $messageType);

        return [
            'source' => $source,
            'contents' => $contents,
            'mime_type' => $mimeType,
            'file_name' => self::fileName($fileName, $mimeType, $category),
            'category' => $category,
            'inline' => self::canPreviewInline($mimeType),
        ];
    }

    public static function canPreviewInline(string $mimeType): bool
    {
        $mimeType = self::normalizeMime($mimeType);

        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'], true)
            || str_starts_with((string) $mimeType, 'audio/')
            || str_starts_with((string) $mimeType, 'video/');
    }

    /** @var array<int, string> */
    private const MIME_KEYS = ['media.mimetype', 'media.mimeType', 'media.contentType', 'mimetype', 'mimeType', 'contentType', 'file.mimetype', 'file.mimeType', '_data.mimetype'];

    /** @var array<int, string> */
    private const FILE_NAME_KEYS = ['media.filename', 'media.fileName', 'filename', 'fileName', 'file.filename', 'file.fileName', '_data.filename'];

    /**
     * @return array<string, mixed>|null
     */
    private static function payload(?string $payloadJson): ?array
    {
        if (! is_string($payloadJson) || trim($payloadJson) === '') {
            return null;
        }

        $payload = json_decode($payloadJson, true);

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{source: string, value: string}|null
     */
    private static function candidate(array $payload, ?string $messageType): ?array
    {
        $keys = ['dataUrl', 'data_url', 'media.dataUrl', 'media.data_url', 'base64', 'media.base64', 'media.data', 'media.file', 'file.data', 'file.base64', 'data', 'file', 'body'];

        foreach ($keys as $key) {
            $value = Arr::get($payload, $key);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            if (in_array($key, ['data', 'file', 'body'], true) && ! self::hasMediaContext($payload, $messageType)) {
                continue;
            }

            return ['source' => $key, 'value' => trim($value)];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private static function metadata(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($payload, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function hasMediaContext(array $payload, ?string $messageType): bool
    {
        if (Arr::get($payload, 'hasMedia') === true || self::metadata($payload, self::MIME_KEYS) || self::metadata($payload, self::FILE_NAME_KEYS)) {
            return true;
        }

        return in_array(self::messageType($messageType), [
            'image', 'gambar', 'sticker', 'stiker', 'audio', 'ptt', 'voice', 'video', 'document', 'dokumen', 'file',
        ], true);
    }

    /**
     * @return array{mime: ?string, base64: bool, data: string}|null
     */
    private static function dataUri(string $value): ?array
    {
        if (! preg_match('/^data:([^;,]+)?(;base64)?,(.*)$/is', $value, $matches)) {
            return null;
        }

        return [
            'mime' => $matches[1] !== '' ? $matches[1] : null,
            'base64' => isset($matches[2]) && $matches[2] !== '',
            'data' => $matches[3],
        ];
    }

    private static function normalizeMime(?string $mime): ?string
    {
        if (! is_string($mime) || preg_match('/[\x00-\x1F\x7F]/', $mime)) {
            return null;
        }

        $mime = strtolower(trim((string) preg_replace('/;.*/', '', (string) $mime)));

        return preg_match('/^[a-z0-9!#$&^_.+-]+\/[a-z0-9!#$&^_.+-]+$/', $mime) ? $mime : null;
    }

    private static function mimeFromFilename(?string $fileName): ?string
    {
        $extension = pathinfo(str_replace('\\', '/', (string) $fileName), PATHINFO_EXTENSION);

        if ($extension === '') {
            return null;
        }

        return self::normalizeMime(MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? null);
    }

    private static function mimeFromContents(string $contents): ?string
    {
        if ($contents === '') {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return null;
        }

        $mimeType = finfo_buffer($finfo, $contents);

        return self::normalizeMime($mimeType ?: null);
    }

    private static function fileName(?string $fileName, string $mimeType, string $category): string
    {
        $fileName = basename(str_replace('\\', '/', (string) $fileName));
        $fileName = preg_replace('/[\x00-\x1F\x7F"]/', '', $fileName) ?? '';
        $fileName = preg_replace('/\s+/', ' ', $fileName) ?? '';
        $fileName = trim($fileName, ' .');

        if ($fileName === '') {
            $fileName = match ($category) {
                'image' => 'whatsapp-image',
                'video' => 'whatsapp-video',
                'audio' => 'whatsapp-audio',
                'pdf' => 'whatsapp-document',
                default => $mimeType === 'application/octet-stream' ? 'whatsapp-media' : 'whatsapp-document',
            };
        }

        if (pathinfo($fileName, PATHINFO_EXTENSION) === '') {
            $extension = MimeTypes::getDefault()->getExtensions($mimeType)[0] ?? null;

            if ($extension) {
                $fileName = mb_substr($fileName, 0, 180 - mb_strlen($extension) - 1);
                $fileName .= '.'.$extension;
            }
        }

        return mb_substr($fileName, 0, 180);
    }

    private static function category(string $mimeType, ?string $messageType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'audio/') => 'audio',
            str_starts_with($mimeType, 'video/') => 'video',
            $mimeType === 'application/pdf' => 'pdf',
            in_array(self::messageType($messageType), ['image', 'gambar', 'sticker', 'stiker'], true) => 'image',
            in_array(self::messageType($messageType), ['audio', 'ptt', 'voice'], true) => 'audio',
            self::messageType($messageType) === 'video' => 'video',
            in_array(self::messageType($messageType), ['document', 'dokumen'], true) => 'file',
            default => 'file',
        };
    }

    private static function firstNonEmpty(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private static function messageType(?string $messageType): string
    {
        return strtolower(trim((string) $messageType));
    }
}
