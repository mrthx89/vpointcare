<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class WahaMediaController extends Controller
{
    public function __invoke(string $message): Response
    {
        $row = DB::table('TChatD')
            ->where('Id', $message)
            ->select(
                'UrlMedia',
                Schema::hasColumn('TChatD', 'NamaFileMedia') ? 'NamaFileMedia' : DB::raw('NULL as NamaFileMedia'),
                Schema::hasColumn('TChatD', 'TipeMime') ? 'TipeMime' : DB::raw('NULL as TipeMime')
            )
            ->first();

        abort_if(! $row || ! $row->UrlMedia, 404);

        $url = $this->mediaUrl((string) $row->UrlMedia);

        if (Str::startsWith($url, 'data:')) {
            return $this->dataUrlResponse($url, $row);
        }

        $localPath = $this->localPublicStoragePath($url);

        if ($localPath) {
            return $this->localStorageResponse($localPath, $row);
        }

        $request = Http::timeout(45);

        if (config('services.waha.api_key')) {
            $request = $request->withHeader('X-Api-Key', (string) config('services.waha.api_key'));
        }

        try {
            $response = $request->get($url);
        } catch (Throwable $exception) {
            Log::warning('WAHA media proxy failed to reach media URL.', [
                'message_id' => $message,
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            return $this->errorResponse(__('ui.controllers.waha_media.proxy_failed').$exception->getMessage());
        }

        if (! $response->successful()) {
            Log::warning('WAHA media proxy received unsuccessful response.', [
                'message_id' => $message,
                'url' => $url,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return $this->errorResponse(__('ui.controllers.waha_media.proxy_unsuccessful').$response->status().'.');
        }

        $mimeType = (string) ($row->TipeMime ?: $response->header('Content-Type', 'application/octet-stream'));
        $body = $response->body();

        if ($this->looksLikeJson($mimeType, $body)) {
            $decoded = json_decode($body, true);
            $jsonResponse = $this->jsonMediaResponse(is_array($decoded) ? $decoded : [], $row);

            if ($jsonResponse) {
                return $jsonResponse;
            }
        }

        if ($body === '') {
            return $this->errorResponse(__('ui.controllers.waha_media.proxy_empty'));
        }

        return response($body, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$this->fileName($row, $mimeType).'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
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
        $trimmed = ltrim($body);

        return str_contains(strtolower($mimeType), 'json')
            || Str::startsWith($trimmed, ['{', '[']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function jsonMediaResponse(array $payload, object $row): ?Response
    {
        $dataUrl = $this->firstString($payload, [
            'dataUrl',
            'data_url',
            'media.dataUrl',
            'media.data_url',
        ]);

        if ($dataUrl && Str::startsWith($dataUrl, 'data:')) {
            return $this->dataUrlResponse($dataUrl, $row);
        }

        $base64 = $this->firstString($payload, [
            'base64',
            'data',
            'file',
            'body',
            'media.base64',
            'media.data',
            'media.file',
        ]);

        if ($base64) {
            $contents = base64_decode(preg_replace('/^data:[^,]+,/', '', $base64) ?: $base64, true);

            if ($contents !== false && $contents !== '') {
                $mimeType = $row->TipeMime ?: $this->firstString($payload, ['mimetype', 'mimeType', 'media.mimetype', 'media.mimeType']) ?: 'application/octet-stream';

                return response($contents, 200, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="'.$this->fileName($row, $mimeType).'"',
                    'Cache-Control' => 'private, max-age=300',
                ]);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function firstString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function errorResponse(string $message): Response
    {
        return response($message, 424, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function localStorageResponse(string $path, object $row): Response
    {
        abort_if(! Storage::disk('public')->exists($path), 404);

        $mimeType = $row->TipeMime ?: (Storage::disk('public')->mimeType($path) ?: 'application/octet-stream');

        return response(Storage::disk('public')->get($path), 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$this->fileName($row, $mimeType).'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function dataUrlResponse(string $url, object $row): Response
    {
        if (! preg_match('/^data:([^;,]+)?(;base64)?,(.*)$/', $url, $matches)) {
            abort(404);
        }

        $mimeType = $row->TipeMime ?: ($matches[1] ?: 'application/octet-stream');
        $contents = isset($matches[2]) && $matches[2] === ';base64'
            ? base64_decode($matches[3], true)
            : rawurldecode($matches[3]);

        abort_if($contents === false, 404);

        return response($contents, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$this->fileName($row, $mimeType).'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function fileName(object $row, string $mimeType): string
    {
        $fileName = trim((string) ($row->NamaFileMedia ?? ''));

        if ($fileName !== '') {
            return str_replace('"', '', $fileName);
        }

        return match (true) {
            str_starts_with($mimeType, 'image/') => 'whatsapp-image',
            str_starts_with($mimeType, 'video/') => 'whatsapp-video',
            str_starts_with($mimeType, 'audio/') => 'whatsapp-audio',
            default => 'whatsapp-media',
        };
    }
}
