<?php

namespace App\Http\Controllers;

use App\Support\WahaMediaPayload;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

class WahaMediaController extends Controller
{
    public function __invoke(Request $request, string $message): Response
    {
        $row = DB::table('TChatD')
            ->where('Id', $message)
            ->select(
                'Id',
                'UrlMedia',
                'PayloadJson',
                'JenisPesan',
                Schema::hasColumn('TChatD', 'NamaFileMedia') ? 'NamaFileMedia' : DB::raw('NULL as "NamaFileMedia"'),
                Schema::hasColumn('TChatD', 'TipeMime') ? 'TipeMime' : DB::raw('NULL as "TipeMime"'),
            )
            ->first();

        abort_if(! $row, 404);

        $media = $this->mediaFromUrl($row, $message)
            ?? WahaMediaPayload::fromPayloadJson(
                $row->PayloadJson,
                $row->TipeMime,
                $row->NamaFileMedia,
                $row->JenisPesan,
            );

        if (! $media) {
            if (filled($row->PayloadJson)) {
                $this->logUnavailable($message, 'payload', 'embedded_invalid');
            }

            return $this->errorResponse();
        }

        return $this->mediaResponse($media, $request->boolean('download'));
    }

    /**
     * @return array{source: string, contents: string, mime_type: string, file_name: ?string, category: string, inline: bool}|null
     */
    private function mediaFromUrl(object $row, string $message): ?array
    {
        if (! filled($row->UrlMedia)) {
            return null;
        }

        $url = $this->mediaUrl((string) $row->UrlMedia);

        if (Str::startsWith($url, 'data:')) {
            $media = WahaMediaPayload::fromDataUri($url, $row->TipeMime, $row->NamaFileMedia, $row->JenisPesan);

            if (! $media) {
                $this->logUnavailable($message, 'url', 'invalid_data_uri');
            }

            return $media;
        }

        $localPath = $this->localPublicStoragePath($url);

        if ($localPath !== null) {
            if (! Storage::disk('public')->exists($localPath)) {
                $this->logUnavailable($message, 'url', 'storage_missing');

                return null;
            }

            return WahaMediaPayload::fromBinary(
                Storage::disk('public')->get($localPath),
                $row->TipeMime,
                Storage::disk('public')->mimeType($localPath),
                $row->NamaFileMedia,
                null,
                $row->JenisPesan,
                'storage',
            );
        }

        $http = Http::timeout(45);

        if (filled(config('services.waha.api_key'))) {
            $http = $http->withHeader('X-Api-Key', (string) config('services.waha.api_key'));
        }

        try {
            $response = $http->get($url);
        } catch (Throwable) {
            $this->logUnavailable($message, 'url', 'upstream_exception');

            return null;
        }

        if (! $response->successful()) {
            $this->logUnavailable($message, 'url', 'upstream_status', $response->status());

            return null;
        }

        $contents = $response->body();

        if ($contents === '') {
            $this->logUnavailable($message, 'url', 'upstream_empty');

            return null;
        }

        if ($this->looksLikeJson((string) $response->header('Content-Type'), $contents)) {
            $media = WahaMediaPayload::fromPayloadJson($contents, $row->TipeMime, $row->NamaFileMedia, $row->JenisPesan);

            if (! $media) {
                $this->logUnavailable($message, 'url', 'invalid_json_media');
            }

            return $media;
        }

        return WahaMediaPayload::fromBinary(
            $contents,
            $row->TipeMime,
            $response->header('Content-Type'),
            $row->NamaFileMedia,
            null,
            $row->JenisPesan,
            'url',
        );
    }

    /**
     * @param  array{contents: string, mime_type: string, file_name: ?string, inline: bool}  $media
     */
    private function mediaResponse(array $media, bool $download): Response
    {
        $disposition = $download || ! $media['inline']
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
            : ResponseHeaderBag::DISPOSITION_INLINE;

        return response($media['contents'], 200, [
            'Content-Type' => $media['mime_type'],
            'Content-Disposition' => (new ResponseHeaderBag)->makeDisposition(
                $disposition,
                $this->safeFileName($media['file_name'] ?? null),
                $this->safeAsciiFileName($media['file_name'] ?? null),
            ),
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function errorResponse(): Response
    {
        return response(__('ui.controllers.waha_media.unavailable'), 424, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function safeFileName(?string $fileName): string
    {
        $fileName = trim((string) preg_replace('/[:\\x00-\\x1F\\x7F].*$/', '', (string) $fileName));
        $fileName = (string) preg_replace('/[A-Z][A-Za-z]*(?:-[A-Za-z]+)+$/', '', $fileName);

        return $fileName !== '' ? $fileName : 'whatsapp-media';
    }

    private function safeAsciiFileName(?string $fileName): string
    {
        $fileName = $this->safeFileName($fileName);
        $fallback = preg_replace('/[^\x20-\x7E]/', '', $fileName) ?? '';
        $fallback = str_replace(['%', '/', '\\'], '-', $fallback);
        $fallback = trim($fallback, ' .-');

        if ($fallback === '') {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);

            return $extension !== '' ? 'whatsapp-media.'.$extension : 'whatsapp-media';
        }

        return $fallback;
    }

    private function logUnavailable(string $message, string $source, string $reasonCode, ?int $status = null): void
    {
        $context = [
            'message_id' => $message,
            'source' => $source,
        ];

        if ($status !== null) {
            $context['status'] = $status;
        }

        $context['reason_code'] = $reasonCode;

        Log::warning('WAHA media source unavailable.', $context);
    }

    private function mediaUrl(string $url): string
    {
        if (Str::startsWith($url, 'data:')) {
            return $url;
        }

        $baseUrl = rtrim((string) config('services.waha.media_base_url'), '/');

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $this->normalizeWahaAbsoluteUrl($url, $baseUrl);
        }

        return $baseUrl.'/'.ltrim($url, '/');
    }

    private function localPublicStoragePath(string $url): ?string
    {
        $path = Str::startsWith($url, ['http://', 'https://'])
            ? (string) parse_url($url, PHP_URL_PATH)
            : $url;

        if (! Str::startsWith($path, '/storage/')) {
            return null;
        }

        return ltrim(Str::after($path, '/storage/'), '/');
    }

    private function normalizeWahaAbsoluteUrl(string $url, string $baseUrl): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (! in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'], true)) {
            return $url;
        }

        $baseParts = parse_url($baseUrl);
        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = (string) parse_url($url, PHP_URL_QUERY);
        $scheme = $baseParts['scheme'] ?? 'http';
        $baseHost = $baseParts['host'] ?? '127.0.0.1';
        $port = isset($baseParts['port']) ? ':'.$baseParts['port'] : '';

        return $scheme.'://'.$baseHost.$port.$path.($query !== '' ? '?'.$query : '');
    }

    private function looksLikeJson(string $mimeType, string $body): bool
    {
        return str_contains(strtolower($mimeType), 'json')
            || Str::startsWith(ltrim($body), ['{', '[']);
    }
}
