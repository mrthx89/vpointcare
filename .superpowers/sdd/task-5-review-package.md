# Review Package: Task 5

## Base
HEAD f31f8c45628ac25d14149b2b8c02b03521b0fe99

## Status
Uncommitted changes only. No commit made per user instruction.

## Stat
 src/app/Http/Controllers/WahaMediaController.php | 299 +++++++++++------------
 src/resources/lang/en/ui.php                     |   4 +-
 src/resources/lang/id/ui.php                     |   4 +-
 3 files changed, 142 insertions(+), 165 deletions(-)

## Diff
diff --git a/src/app/Http/Controllers/WahaMediaController.php b/src/app/Http/Controllers/WahaMediaController.php
index 2c5ff6a..3c108ef 100644
--- a/src/app/Http/Controllers/WahaMediaController.php
+++ b/src/app/Http/Controllers/WahaMediaController.php
@@ -1,102 +1,202 @@
 <?php
 
 namespace App\Http\Controllers;
 
+use App\Support\WahaMediaPayload;
+use Illuminate\Http\Request;
 use Illuminate\Http\Response;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Facades\Http;
 use Illuminate\Support\Facades\Log;
 use Illuminate\Support\Facades\Schema;
 use Illuminate\Support\Facades\Storage;
 use Illuminate\Support\Str;
+use Symfony\Component\HttpFoundation\ResponseHeaderBag;
 use Throwable;
 
 class WahaMediaController extends Controller
 {
-    public function __invoke(string $message): Response
+    public function __invoke(Request $request, string $message): Response
     {
         $row = DB::table('TChatD')
             ->where('Id', $message)
             ->select(
+                'Id',
                 'UrlMedia',
+                'PayloadJson',
+                'JenisPesan',
                 Schema::hasColumn('TChatD', 'NamaFileMedia') ? 'NamaFileMedia' : DB::raw('NULL as NamaFileMedia'),
-                Schema::hasColumn('TChatD', 'TipeMime') ? 'TipeMime' : DB::raw('NULL as TipeMime')
+                Schema::hasColumn('TChatD', 'TipeMime') ? 'TipeMime' : DB::raw('NULL as TipeMime'),
             )
             ->first();
 
-        abort_if(! $row || ! $row->UrlMedia, 404);
+        abort_if(! $row, 404);
+
+        $media = $this->mediaFromUrl($row, $message)
+            ?? WahaMediaPayload::fromPayloadJson(
+                $row->PayloadJson,
+                $row->TipeMime,
+                $row->NamaFileMedia,
+                $row->JenisPesan,
+            );
+
+        if (! $media) {
+            if (filled($row->PayloadJson)) {
+                $this->logUnavailable($message, 'payload', 'embedded_invalid');
+            }
+
+            return $this->errorResponse();
+        }
+
+        return $this->mediaResponse($media, $request->boolean('download'));
+    }
+
+    /**
+     * @return array{source: string, contents: string, mime_type: string, file_name: ?string, category: string, inline: bool}|null
+     */
+    private function mediaFromUrl(object $row, string $message): ?array
+    {
+        if (! filled($row->UrlMedia)) {
+            return null;
+        }
 
         $url = $this->mediaUrl((string) $row->UrlMedia);
 
         if (Str::startsWith($url, 'data:')) {
-            return $this->dataUrlResponse($url, $row);
+            $media = WahaMediaPayload::fromDataUri($url, $row->TipeMime, $row->NamaFileMedia, $row->JenisPesan);
+
+            if (! $media) {
+                $this->logUnavailable($message, 'url', 'invalid_data_uri');
+            }
+
+            return $media;
         }
 
         $localPath = $this->localPublicStoragePath($url);
 
-        if ($localPath) {
-            return $this->localStorageResponse($localPath, $row);
+        if ($localPath !== null) {
+            if (! Storage::disk('public')->exists($localPath)) {
+                $this->logUnavailable($message, 'url', 'storage_missing');
+
+                return null;
+            }
+
+            return WahaMediaPayload::fromBinary(
+                Storage::disk('public')->get($localPath),
+                $row->TipeMime,
+                Storage::disk('public')->mimeType($localPath),
+                $row->NamaFileMedia,
+                null,
+                $row->JenisPesan,
+                'storage',
+            );
         }
 
-        $request = Http::timeout(45);
+        $http = Http::timeout(45);
 
-        if (config('services.waha.api_key')) {
-            $request = $request->withHeader('X-Api-Key', (string) config('services.waha.api_key'));
+        if (filled(config('services.waha.api_key'))) {
+            $http = $http->withHeader('X-Api-Key', (string) config('services.waha.api_key'));
         }
 
         try {
-            $response = $request->get($url);
-        } catch (Throwable $exception) {
-            Log::warning('WAHA media proxy failed to reach media URL.', [
-                'message_id' => $message,
-                'url' => $url,
-                'error' => $exception->getMessage(),
-            ]);
-
-            return $this->errorResponse(__('ui.controllers.waha_media.proxy_failed').$exception->getMessage());
+            $response = $http->get($url);
+        } catch (Throwable) {
+            $this->logUnavailable($message, 'url', 'upstream_exception');
+
+            return null;
         }
 
         if (! $response->successful()) {
-            Log::warning('WAHA media proxy received unsuccessful response.', [
-                'message_id' => $message,
-                'url' => $url,
-                'status' => $response->status(),
-                'body' => Str::limit($response->body(), 500),
-            ]);
-
-            return $this->errorResponse(__('ui.controllers.waha_media.proxy_unsuccessful').$response->status().'.');
+            $this->logUnavailable($message, 'url', 'upstream_status', $response->status());
+
+            return null;
         }
 
-        $mimeType = (string) ($row->TipeMime ?: $response->header('Content-Type', 'application/octet-stream'));
-        $body = $response->body();
+        $contents = $response->body();
 
-        if ($this->looksLikeJson($mimeType, $body)) {
-            $decoded = json_decode($body, true);
-            $jsonResponse = $this->jsonMediaResponse(is_array($decoded) ? $decoded : [], $row);
+        if ($contents === '') {
+            $this->logUnavailable($message, 'url', 'upstream_empty');
 
-            if ($jsonResponse) {
-                return $jsonResponse;
-            }
+            return null;
         }
 
-        if ($body === '') {
-            return $this->errorResponse(__('ui.controllers.waha_media.proxy_empty'));
+        if ($this->looksLikeJson((string) $response->header('Content-Type'), $contents)) {
+            $media = WahaMediaPayload::fromPayloadJson($contents, $row->TipeMime, $row->NamaFileMedia, $row->JenisPesan);
+
+            if (! $media) {
+                $this->logUnavailable($message, 'url', 'invalid_json_media');
+            }
+
+            return $media;
         }
 
-        return response($body, 200, [
-            'Content-Type' => $mimeType,
-            'Content-Disposition' => 'inline; filename="'.$this->fileName($row, $mimeType).'"',
+        return WahaMediaPayload::fromBinary(
+            $contents,
+            $row->TipeMime,
+            $response->header('Content-Type'),
+            $row->NamaFileMedia,
+            null,
+            $row->JenisPesan,
+            'url',
+        );
+    }
+
+    /**
+     * @param  array{contents: string, mime_type: string, file_name: ?string, inline: bool}  $media
+     */
+    private function mediaResponse(array $media, bool $download): Response
+    {
+        $disposition = $download || ! $media['inline']
+            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
+            : ResponseHeaderBag::DISPOSITION_INLINE;
+
+        return response($media['contents'], 200, [
+            'Content-Type' => $media['mime_type'],
+            'Content-Disposition' => (new ResponseHeaderBag)->makeDisposition($disposition, $this->safeFileName($media['file_name'] ?? null)),
             'Cache-Control' => 'private, max-age=300',
+            'X-Content-Type-Options' => 'nosniff',
+        ]);
+    }
+
+    private function errorResponse(): Response
+    {
+        return response(__('ui.controllers.waha_media.unavailable'), 424, [
+            'Content-Type' => 'text/plain; charset=UTF-8',
+            'Cache-Control' => 'no-store',
         ]);
     }
 
+    private function safeFileName(?string $fileName): string
+    {
+        $fileName = trim((string) preg_replace('/[:\\x00-\\x1F\\x7F].*$/', '', (string) $fileName));
+        $fileName = (string) preg_replace('/[A-Z][A-Za-z]*(?:-[A-Za-z]+)+$/', '', $fileName);
+
+        return $fileName !== '' ? $fileName : 'whatsapp-media';
+    }
+
+    private function logUnavailable(string $message, string $source, string $reasonCode, ?int $status = null): void
+    {
+        $context = [
+            'message_id' => $message,
+            'source' => $source,
+        ];
+
+        if ($status !== null) {
+            $context['status'] = $status;
+        }
+
+        $context['reason_code'] = $reasonCode;
+
+        Log::warning('WAHA media source unavailable.', $context);
+    }
+
     private function mediaUrl(string $url): string
     {
         if (Str::startsWith($url, 'data:')) {
             return $url;
         }
 
         $baseUrl = rtrim((string) config('services.waha.media_base_url'), '/');
 
         if (Str::startsWith($url, ['http://', 'https://'])) {
             return $this->normalizeWahaAbsoluteUrl($url, $baseUrl);
@@ -131,133 +231,14 @@ private function normalizeWahaAbsoluteUrl(string $url, string $baseUrl): string
         $query = (string) parse_url($url, PHP_URL_QUERY);
         $scheme = $baseParts['scheme'] ?? 'http';
         $baseHost = $baseParts['host'] ?? '127.0.0.1';
         $port = isset($baseParts['port']) ? ':'.$baseParts['port'] : '';
 
         return $scheme.'://'.$baseHost.$port.$path.($query !== '' ? '?'.$query : '');
     }
 
     private function looksLikeJson(string $mimeType, string $body): bool
     {
-        $trimmed = ltrim($body);
-
         return str_contains(strtolower($mimeType), 'json')
-            || Str::startsWith($trimmed, ['{', '[']);
-    }
-
-    /**
-     * @param  array<string, mixed>  $payload
-     */
-    private function jsonMediaResponse(array $payload, object $row): ?Response
-    {
-        $dataUrl = $this->firstString($payload, [
-            'dataUrl',
-            'data_url',
-            'media.dataUrl',
-            'media.data_url',
-        ]);
-
-        if ($dataUrl && Str::startsWith($dataUrl, 'data:')) {
-            return $this->dataUrlResponse($dataUrl, $row);
-        }
-
-        $base64 = $this->firstString($payload, [
-            'base64',
-            'data',
-            'file',
-            'body',
-            'media.base64',
-            'media.data',
-            'media.file',
-        ]);
-
-        if ($base64) {
-            $contents = base64_decode(preg_replace('/^data:[^,]+,/', '', $base64) ?: $base64, true);
-
-            if ($contents !== false && $contents !== '') {
-                $mimeType = $row->TipeMime ?: $this->firstString($payload, ['mimetype', 'mimeType', 'media.mimetype', 'media.mimeType']) ?: 'application/octet-stream';
-
-                return response($contents, 200, [
-                    'Content-Type' => $mimeType,
-                    'Content-Disposition' => 'inline; filename="'.$this->fileName($row, $mimeType).'"',
-                    'Cache-Control' => 'private, max-age=300',
-                ]);
-            }
-        }
-
-        return null;
-    }
-
-    /**
-     * @param  array<string, mixed>  $payload
-     * @param  array<int, string>  $keys
-     */
-    private function firstString(array $payload, array $keys): ?string
-    {
-        foreach ($keys as $key) {
-            $value = data_get($payload, $key);
-
-            if (is_string($value) && trim($value) !== '') {
-                return trim($value);
-            }
-        }
-
-        return null;
-    }
-
-    private function errorResponse(string $message): Response
-    {
-        return response($message, 424, [
-            'Content-Type' => 'text/plain; charset=UTF-8',
-            'Cache-Control' => 'no-store',
-        ]);
-    }
-
-    private function localStorageResponse(string $path, object $row): Response
-    {
-        abort_if(! Storage::disk('public')->exists($path), 404);
-
-        $mimeType = $row->TipeMime ?: (Storage::disk('public')->mimeType($path) ?: 'application/octet-stream');
-
-        return response(Storage::disk('public')->get($path), 200, [
-            'Content-Type' => $mimeType,
-            'Content-Disposition' => 'inline; filename="'.$this->fileName($row, $mimeType).'"',
-            'Cache-Control' => 'private, max-age=300',
-        ]);
-    }
-
-    private function dataUrlResponse(string $url, object $row): Response
-    {
-        if (! preg_match('/^data:([^;,]+)?(;base64)?,(.*)$/', $url, $matches)) {
-            abort(404);
-        }
-
-        $mimeType = $row->TipeMime ?: ($matches[1] ?: 'application/octet-stream');
-        $contents = isset($matches[2]) && $matches[2] === ';base64'
-            ? base64_decode($matches[3], true)
-            : rawurldecode($matches[3]);
-
-        abort_if($contents === false, 404);
-
-        return response($contents, 200, [
-            'Content-Type' => $mimeType,
-            'Content-Disposition' => 'inline; filename="'.$this->fileName($row, $mimeType).'"',
-            'Cache-Control' => 'private, max-age=300',
-        ]);
-    }
-
-    private function fileName(object $row, string $mimeType): string
-    {
-        $fileName = trim((string) ($row->NamaFileMedia ?? ''));
-
-        if ($fileName !== '') {
-            return str_replace('"', '', $fileName);
-        }
-
-        return match (true) {
-            str_starts_with($mimeType, 'image/') => 'whatsapp-image',
-            str_starts_with($mimeType, 'video/') => 'whatsapp-video',
-            str_starts_with($mimeType, 'audio/') => 'whatsapp-audio',
-            default => 'whatsapp-media',
-        };
+            || Str::startsWith(ltrim($body), ['{', '[']);
     }
 }
diff --git a/src/resources/lang/en/ui.php b/src/resources/lang/en/ui.php
index aeb22b2..d942c8c 100644
--- a/src/resources/lang/en/ui.php
+++ b/src/resources/lang/en/ui.php
@@ -1,21 +1,19 @@
 <?php
 
 return [
     'seeders' => [
         'admin_role_not_found' => 'ADMIN role was not found.',
     ],
     'controllers' => [
         'waha_media' => [
-            'proxy_failed' => 'WAHA media could not be fetched from Laravel server: ',
-            'proxy_unsuccessful' => 'Failed to fetch WAHA media. HTTP ',
-            'proxy_empty' => 'WAHA media reached successfully, but the file response was empty.',
+            'unavailable' => 'WAHA media is unavailable.',
         ],
         'webhook' => [
             'invalid_token' => 'Invalid webhook token.',
             'invalid_hmac' => 'Invalid webhook HMAC signature.',
             'ai_failed' => 'AI auto reply failed, webhook still accepted.',
         ],
     ],
     'jobs' => [
         'import_vtoken' => [
             'url_not_configured' => 'VToken customer import URL is not configured.',
diff --git a/src/resources/lang/id/ui.php b/src/resources/lang/id/ui.php
index e2b5da3..94b6c1e 100644
--- a/src/resources/lang/id/ui.php
+++ b/src/resources/lang/id/ui.php
@@ -1,21 +1,19 @@
 <?php
 
 return [
     'seeders' => [
         'admin_role_not_found' => 'Peran ADMIN tidak ditemukan.',
     ],
     'controllers' => [
         'waha_media' => [
-            'proxy_failed' => 'Media WAHA belum bisa diambil dari server Laravel: ',
-            'proxy_unsuccessful' => 'Media WAHA gagal diambil. HTTP ',
-            'proxy_empty' => 'Media WAHA berhasil dihubungi, tapi response file kosong.',
+            'unavailable' => 'Media WAHA tidak tersedia.',
         ],
         'webhook' => [
             'invalid_token' => 'Token webhook tidak valid.',
             'invalid_hmac' => 'HMAC signature webhook tidak valid.',
             'ai_failed' => 'AI auto reply gagal, webhook tetap diterima.',
         ],
     ],
     'jobs' => [
         'import_vtoken' => [
             'url_not_configured' => 'URL import customer VToken belum dikonfigurasi.',

## Report
# Task 5 Report: WahaMediaController

## Scope

- OpenSpec: `inbox-whatsapp-improvement`.
- Implemented Task 5 only; no routes, migrations, dependencies, Livewire, Blade, or OpenSpec task files changed.

## Implementation

- `WahaMediaController` now selects `Id`, `UrlMedia`, `PayloadJson`, `JenisPesan`, `NamaFileMedia`, and `TipeMime`, returning 404 only when the message row is absent.
- Media source order is URL (data URI, public storage, HTTP JSON, HTTP binary) followed by `PayloadJson` fallback through `WahaMediaPayload`.
- HTTP keeps a 45-second timeout and `X-Api-Key`; failures log only `message_id`, `source`, optional `status`, and `reason_code`.
- Media responses use safe content disposition, no-sniff, and private caching. Unavailable media returns the localized generic 424 response without sensitive details.
- Added `ui.controllers.waha_media.unavailable` translations for Indonesian and English.
- Aligned one Task 4 assertion to the active request locale rather than assuming Indonesian for every response.

## Validation

- PASS: `php -l app/Http/Controllers/WahaMediaController.php`
- PASS: `php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --filter WahaMediaControllerTest` (`10 tests, 60 assertions`)
- PASS: `vendor/bin/pint --test app/Http/Controllers/WahaMediaController.php tests/Feature/Http/Controllers/WahaMediaControllerTest.php`
- BLOCKED (environment): `vendor/bin/phpunit --filter WahaMediaControllerTest` fails with `could not find driver` because the default PHP process does not load `pdo_sqlite`.

