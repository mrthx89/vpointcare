<?php

namespace App\Services\Waha;

use App\Support\WahaChatHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class WahaSender
{
    private static int $consecutiveFailures = 0;

    private static ?Carbon $circuitOpenUntil = null;

    private const CIRCUIT_FAILURE_THRESHOLD = 5;

    private const CIRCUIT_COOLDOWN_SECONDS = 120;

    /**
     * @return array{ok: bool, status?: int, body?: string, error?: string}
     */
    public function sendText(string $session, string $chatIdOrNumber, string $text, string $kodeIntegrasi = 'WAHA_SEND_TEXT'): array
    {
        $payload = [
            'session' => $session,
            'chatId' => WahaChatHelper::normalizeChatId($chatIdOrNumber),
            'text' => $text,
        ];

        return $this->postJson((string) config('services.waha.send_text_path', '/api/sendText'), $payload, $kodeIntegrasi);
    }

    /**
     * @return array{ok: bool, status?: int, body?: string, error?: string}
     */
    public function sendMedia(
        string $session,
        string $chatIdOrNumber,
        string $base64Data,
        string $mimeType,
        string $fileName,
        ?string $caption = null,
        string $kodeIntegrasi = 'WAHA_SEND_MEDIA'
    ): array {
        $path = match (true) {
            str_starts_with($mimeType, 'image/') => '/api/sendImage',
            str_starts_with($mimeType, 'video/') => '/api/sendVideo',
            default => '/api/sendFile',
        };

        $payload = [
            'session' => $session,
            'chatId' => WahaChatHelper::normalizeChatId($chatIdOrNumber),
            'file' => [
                'mimetype' => $mimeType,
                'filename' => $fileName,
                'data' => $base64Data,
            ],
        ];

        if ($caption !== null && trim($caption) !== '') {
            $payload['caption'] = $caption;
        }

        if (str_starts_with($mimeType, 'video/')) {
            $payload['convert'] = false;
            $payload['asNote'] = false;
        }

        return $this->postJson($path, $payload, $kodeIntegrasi);
    }

    /**
     * @return array{ok: bool, url?: ?string, status?: int, body?: string, error?: string}
     */
    public function getContactProfilePictureUrl(string $session, string $contactId, bool $refresh = false): array
    {
        $response = $this->getJson('/api/contacts/profile-picture', [
            'contactId' => WahaChatHelper::normalizeContactId($contactId),
            'session' => $session,
            'refresh' => $refresh ? 'true' : 'false',
        ], 'WAHA_CONTACT_PROFILE_PICTURE');

        if (! ($response['ok'] ?? false)) {
            return $response;
        }

        $payload = json_decode((string) ($response['body'] ?? ''), true);
        $url = is_array($payload)
            ? (Arr::get($payload, 'profilePictureURL') ?? Arr::get($payload, 'url'))
            : null;

        return array_merge($response, [
            'url' => is_string($url) && trim($url) !== '' ? trim($url) : null,
        ]);
    }

    /**
     * @return array{ok: bool, phone: ?string, pn: ?string, status: ?int, error: ?string}
     */
    public function getPhoneNumberByLid(string $session, string $lid): array
    {
        $response = $this->getJson('/api/'.rawurlencode($session).'/lids/'.$this->encodeWahaPathId(WahaChatHelper::normalizeContactId($lid)), [], 'WAHA_LID_TO_PHONE');

        if (! ($response['ok'] ?? false)) {
            return [
                'ok' => false,
                'phone' => null,
                'pn' => null,
                'status' => $response['status'] ?? null,
                'error' => 'WAHA LID lookup failed.',
            ];
        }

        $body = (string) ($response['body'] ?? '');
        $payload = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $payload = $body;
        }

        $pn = $this->firstPhoneContactId($payload);
        $phone = is_string($pn) ? WahaChatHelper::normalizePhoneNumber($pn) : null;

        return [
            'ok' => true,
            'phone' => $phone,
            'pn' => is_string($pn) ? $pn : null,
            'status' => $response['status'] ?? null,
            'error' => null,
        ];
    }

    /**
     * @return array{ok: bool, name: ?string, pushname: ?string, id: string, phone: ?string, status: ?int, error: ?string}
     */
    public function getContactInfo(string $session, string $contactId): array
    {
        $id = WahaChatHelper::normalizeContactId($contactId);
        $response = $this->getJson('/api/'.rawurlencode($session).'/contacts/'.$this->encodeWahaPathId($id), [], 'WAHA_CONTACT_INFO');

        if (! ($response['ok'] ?? false)) {
            return [
                'ok' => false,
                'name' => null,
                'pushname' => null,
                'id' => $id,
                'phone' => null,
                'status' => $response['status'] ?? null,
                'error' => 'WAHA contact metadata request failed.',
            ];
        }

        $payload = $this->responsePayload($response);
        $name = $this->metadataText($payload, ['name', 'subject', 'pushname', 'shortName'], 150);
        $pushname = $this->metadataText($payload, ['pushname'], 150);
        $phone = $this->metadataPhone($payload);

        if (str_ends_with(strtolower($id), '@lid')) {
            $lid = $this->getPhoneNumberByLid($session, $id);
            $phone = $lid['phone'] ?? $phone;
        }

        if ($name === null && $pushname === null && $phone === null) {
            return [
                'ok' => false,
                'name' => null,
                'pushname' => null,
                'id' => $id,
                'phone' => null,
                'status' => $response['status'] ?? null,
                'error' => 'WAHA contact metadata response is invalid.',
            ];
        }

        return [
            'ok' => true,
            'name' => $name,
            'pushname' => $pushname,
            'id' => $id,
            'phone' => $phone,
            'status' => $response['status'] ?? null,
            'error' => null,
        ];
    }

    /**
     * @return array{ok: bool, name: ?string, id: string, status: ?int, error: ?string}
     */
    public function getGroupInfo(string $session, string $groupId): array
    {
        $id = WahaChatHelper::normalizeContactId($groupId);
        $response = $this->getJson('/api/'.rawurlencode($session).'/groups/'.$this->encodeWahaPathId($id), [], 'WAHA_GROUP_INFO');

        if (! ($response['ok'] ?? false)) {
            return [
                'ok' => false,
                'name' => null,
                'id' => $id,
                'status' => $response['status'] ?? null,
                'error' => 'WAHA group metadata request failed.',
            ];
        }

        $name = $this->metadataText($this->responsePayload($response), ['subject', 'name', 'pushname', 'shortName'], 200);

        return [
            'ok' => $name !== null,
            'name' => $name,
            'id' => $id,
            'status' => $response['status'] ?? null,
            'error' => $name === null ? 'WAHA group metadata response is invalid.' : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, status?: int, body?: string, error?: string}
     */
    private function postJson(string $path, array $payload, string $kodeIntegrasi): array
    {
        $baseUrl = rtrim((string) config('services.waha.base_url'), '/');
        $url = $baseUrl.'/'.ltrim($path, '/');
        $logId = (string) Str::orderedUuid();

        DB::table('TLogIntegrasi')->insert([
            'Id' => $logId,
            'KodeIntegrasi' => $kodeIntegrasi,
            'UrlEndpoint' => $this->redactSensitiveValues($url),
            'MetodeHttp' => 'POST',
            'RequestJson' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'TglRequest' => now(),
            'TglBuat' => now(),
        ]);

        if ($this->isCircuitOpen()) {
            $this->markCircuitBreakerLog($logId);

            return [
                'ok' => false,
                'error' => __('ui.scalability.circuit_breaker_active'),
            ];
        }

        try {
            $request = Http::acceptJson()->asJson()->timeout(20);

            if (config('services.waha.api_key')) {
                $request = $request->withHeader('X-Api-Key', (string) config('services.waha.api_key'));
            }

            $response = $request->post($url, $payload);
            $this->recordCircuitResult($response->successful());

            $body = $response->body();
            $error = $response->successful() ? null : $this->sanitizeLogError($response->status(), 'http_error');

            DB::table('TLogIntegrasi')->where('Id', $logId)->update([
                'ResponseJson' => $this->sanitizeLogResponse($body, $response->status()),
                'StatusHttp' => $response->status(),
                'Berhasil' => $response->successful(),
                'PesanError' => $error,
                'TglResponse' => now(),
                'TglEdit' => now(),
            ]);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'body' => $body,
                'error' => $error,
            ];
        } catch (Throwable $exception) {
            $this->recordWahaFailure();
            $error = $this->sanitizeLogError(null, 'request_exception');

            DB::table('TLogIntegrasi')->where('Id', $logId)->update([
                'Berhasil' => false,
                'PesanError' => $error,
                'TglResponse' => now(),
                'TglEdit' => now(),
            ]);

            return [
                'ok' => false,
                'error' => $error,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{ok: bool, status?: int, body?: string, error?: string}
     */
    private function getJson(string $path, array $query, string $kodeIntegrasi): array
    {
        $baseUrl = rtrim((string) config('services.waha.base_url'), '/');
        $url = $baseUrl.'/'.ltrim($path, '/');
        $logId = (string) Str::orderedUuid();

        DB::table('TLogIntegrasi')->insert([
            'Id' => $logId,
            'KodeIntegrasi' => $kodeIntegrasi,
            'UrlEndpoint' => $this->redactSensitiveValues($url),
            'MetodeHttp' => 'GET',
            'RequestJson' => json_encode($query, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'TglRequest' => now(),
            'TglBuat' => now(),
        ]);

        if ($this->isCircuitOpen()) {
            $this->markCircuitBreakerLog($logId);

            return [
                'ok' => false,
                'error' => __('ui.scalability.circuit_breaker_active'),
            ];
        }

        try {
            $request = Http::acceptJson()->timeout(8);

            if (config('services.waha.api_key')) {
                $request = $request->withHeader('X-Api-Key', (string) config('services.waha.api_key'));
            }

            $response = $request->get($url, $query);
            $this->recordCircuitResult($response->successful());

            $body = $response->body();
            $error = $response->successful() ? null : $this->sanitizeLogError($response->status(), 'http_error');

            DB::table('TLogIntegrasi')->where('Id', $logId)->update([
                'ResponseJson' => $this->sanitizeLogResponse($body, $response->status()),
                'StatusHttp' => $response->status(),
                'Berhasil' => $response->successful(),
                'PesanError' => $error,
                'TglResponse' => now(),
                'TglEdit' => now(),
            ]);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'body' => $body,
                'error' => $response->successful() ? null : $error,
            ];
        } catch (Throwable $exception) {
            $this->recordWahaFailure();
            $error = $this->sanitizeLogError(null, 'request_exception');

            DB::table('TLogIntegrasi')->where('Id', $logId)->update([
                'Berhasil' => false,
                'PesanError' => $error,
                'TglResponse' => now(),
                'TglEdit' => now(),
            ]);

            return [
                'ok' => false,
                'error' => $error,
            ];
        }
    }

    private function firstPhoneContactId(mixed $payload): ?string
    {
        if (is_string($payload) && trim($payload) !== '') {
            return trim($payload);
        }

        if (! is_array($payload)) {
            return null;
        }

        foreach ([
            'pn',
            'phone',
            'phoneNumber',
            'number',
            'jid',
            'id',
            'contact.pn',
            'contact.phone',
            'contact.phoneNumber',
            'contact.id',
        ] as $key) {
            $value = Arr::get($payload, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /** @param array<string, mixed> $response @return array<string, mixed> */
    private function responsePayload(array $response): array
    {
        $payload = json_decode((string) ($response['body'] ?? ''), true);

        return is_array($payload) ? $payload : [];
    }

    /** @param array<string, mixed> $payload @param array<int, string> $keys */
    private function metadataText(array $payload, array $keys, int $maxLength): ?string
    {
        foreach ([$payload, Arr::get($payload, 'data', []), Arr::get($payload, 'contact', []), Arr::get($payload, 'group', [])] as $source) {
            if (! is_array($source)) {
                continue;
            }

            foreach ($keys as $key) {
                $value = Arr::get($source, $key);

                if (! is_string($value)) {
                    continue;
                }

                $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($value));
                $value = preg_replace('/\s+/u', ' ', (string) $value);

                if (is_string($value) && $value !== '') {
                    return $this->limitSqlServerText($value, $maxLength);
                }
            }
        }

        return null;
    }

    /** @param array<string, mixed> $payload */
    private function metadataPhone(array $payload): ?string
    {
        return WahaChatHelper::normalizePhoneNumber($this->firstPhoneContactId($payload));
    }

    private function limitSqlServerText(string $value, int $maxUtf16Units): string
    {
        if (! function_exists('mb_convert_encoding')) {
            return Str::limit($value, $maxUtf16Units, '');
        }

        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);

        if ($characters === false) {
            return Str::limit($value, $maxUtf16Units, '');
        }

        $limited = '';
        $usedUnits = 0;

        foreach ($characters as $character) {
            $units = intdiv(strlen(mb_convert_encoding($character, 'UTF-16LE', 'UTF-8')), 2);

            if ($usedUnits + $units > $maxUtf16Units) {
                break;
            }

            $limited .= $character;
            $usedUnits += $units;
        }

        return $limited;
    }

    private function redactSensitiveValues(string $value): string
    {
        $keys = 'credential|token|api_key|key|password|secret|access_token';
        $redacted = preg_replace('/([?&](?:'.$keys.')=)[^&#\s]*/i', '$1[REDACTED]', $value);
        $redacted = preg_replace('/(\b(?:'.$keys.')\b\s*[:=]\s*)(?:"[^"]*"|\'[^\']*\'|[^\s,;]+)/i', '$1[REDACTED]', (string) $redacted);

        return is_string($redacted) ? $redacted : '[REDACTED]';
    }

    private function sanitizeLogResponse(string $body, int $status): string
    {
        return json_encode([
            'received' => true,
            'status' => $status,
            'bytes' => min(strlen($body), 1048576),
        ], JSON_UNESCAPED_SLASHES) ?: '{"received":true}';
    }

    private function sanitizeLogError(?int $status, string $type): string
    {
        return json_encode(array_filter([
            'error' => $this->redactSensitiveValues($type),
            'status' => $status,
        ], static fn (mixed $value): bool => $value !== null), JSON_UNESCAPED_SLASHES) ?: '{"error":"request_failed"}';
    }

    private function encodeWahaPathId(string $id): string
    {
        return str_replace('%40', '@', rawurlencode($id));
    }

    private function isCircuitOpen(): bool
    {
        if (! self::$circuitOpenUntil) {
            return false;
        }

        if (now()->greaterThanOrEqualTo(self::$circuitOpenUntil)) {
            self::$circuitOpenUntil = null;
            self::$consecutiveFailures = 0;
            Log::info('WAHA circuit breaker reset.');

            return false;
        }

        return true;
    }

    private function markCircuitBreakerLog(string $logId): void
    {
        DB::table('TLogIntegrasi')->where('Id', $logId)->update([
            'Berhasil' => false,
            'PesanError' => __('ui.scalability.circuit_breaker_active'),
            'TglResponse' => now(),
            'TglEdit' => now(),
        ]);
    }

    private function recordCircuitResult(bool $success): void
    {
        if ($success) {
            $this->recordWahaSuccess();

            return;
        }

        $this->recordWahaFailure();
    }

    private function recordWahaSuccess(): void
    {
        if (self::$consecutiveFailures > 0 || self::$circuitOpenUntil) {
            Log::info('WAHA circuit breaker closed after successful response.');
        }

        self::$consecutiveFailures = 0;
        self::$circuitOpenUntil = null;
    }

    private function recordWahaFailure(): void
    {
        self::$consecutiveFailures++;

        if (self::$consecutiveFailures < self::CIRCUIT_FAILURE_THRESHOLD || self::$circuitOpenUntil) {
            return;
        }

        self::$circuitOpenUntil = now()->addSeconds(self::CIRCUIT_COOLDOWN_SECONDS);
        Log::critical('WAHA circuit breaker opened after consecutive failures.', [
            'failures' => self::$consecutiveFailures,
            'open_until' => self::$circuitOpenUntil?->toDateTimeString(),
        ]);
    }
}
