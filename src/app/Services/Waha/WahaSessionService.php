<?php

namespace App\Services\Waha;

use App\Support\WahaChatHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class WahaSessionService
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_STARTING = 'starting';

    public const STATUS_SCAN_REQUIRED = 'scan_required';

    public const STATUS_STOPPED = 'stopped';

    public const STATUS_FAILED = 'failed';

    public const STATUS_UNAVAILABLE = 'unavailable';

    public const STATUS_UNKNOWN = 'unknown';

    private const STATUS_CACHE_PREFIX = 'waha_session_status:';

    private const STATUS_CACHE_TTL = 10;

    private const MUTATION_LOCK_TTL = 30;

    /**
     * @return array<string, mixed>
     */
    public function getSessionStatus(string $session, bool $forceRefresh = false): array
    {
        $session = trim($session);

        if ($session === '') {
            return $this->failureResult(self::STATUS_UNKNOWN, 'validation', __('ui.waha.session_required'));
        }

        $cacheKey = $this->statusCacheKey($session);

        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                return $cached;
            }
        }

        $result = $this->requestJson(
            'GET',
            $this->statusPath($session),
            [],
            'status',
        );

        if (! ($result['ok'] ?? false)) {
            $errorCategory = (string) ($result['error_category'] ?? 'unavailable');
            $failure = $this->failureResult(
                $errorCategory === 'malformed_response' ? self::STATUS_UNKNOWN : self::STATUS_UNAVAILABLE,
                $errorCategory,
                (string) ($result['message'] ?? __('ui.waha.unavailable')),
                $result['http_status'] ?? null,
                $session,
            );

            return $failure;
        }

        $payload = is_array($result['payload'] ?? null) ? $result['payload'] : [];
        $normalizedStatus = $this->normalizeStatus($payload);
        $status = $this->successStatusResult($session, $normalizedStatus, $payload, $result['http_status'] ?? null);

        Cache::put($cacheKey, $status, self::STATUS_CACHE_TTL);

        return $status;
    }

    /**
     * @param  iterable<string>  $sessions
     * @return array<string, array<string, mixed>>
     */
    public function getSessionStatuses(iterable $sessions, bool $forceRefresh = false): array
    {
        $statuses = [];

        foreach ($sessions as $session) {
            $sessionCode = trim((string) $session);

            if ($sessionCode === '') {
                continue;
            }

            $statuses[$sessionCode] = $this->getSessionStatus($sessionCode, $forceRefresh);
        }

        return $statuses;
    }

    /**
     * @return array<string, mixed>
     */
    public function getQrCode(string $session): array
    {
        $session = trim($session);

        if ($session === '') {
            return $this->failureResult(self::STATUS_UNKNOWN, 'validation', __('ui.waha.session_required'));
        }

        $result = $this->requestJson('GET', $this->qrPath($session), [], 'qr');

        if (! ($result['ok'] ?? false)) {
            $failure = $this->failureResult(
                self::STATUS_SCAN_REQUIRED,
                (string) ($result['error_category'] ?? 'qr_unavailable'),
                (string) ($result['message'] ?? __('ui.waha.qr_unavailable')),
                $result['http_status'] ?? null,
                $session,
            );
            $this->auditAction('qr', $session, $failure);

            return $failure;
        }

        $payload = is_array($result['payload'] ?? null) ? $result['payload'] : [];
        $qr = $this->extractString($payload, [
            'qr',
            'qrCode',
            'qrcode',
            'data.qr',
            'data.qrCode',
            'data',
        ]);

        if ($qr === null && isset($result['raw_body']) && is_string($result['raw_body'])) {
            $contentType = strtolower((string) ($result['content_type'] ?? ''));

            if (str_starts_with($contentType, 'image/')) {
                $qr = 'data:'.$contentType.';base64,'.base64_encode($result['raw_body']);
            }
        }

        if ($qr === null) {
            $failure = $this->failureResult(self::STATUS_SCAN_REQUIRED, 'qr_missing', __('ui.waha.qr_unavailable'), null, $session);
            $this->auditAction('qr', $session, $failure);

            return $failure;
        }

        $response = [
            'ok' => true,
            'status' => self::STATUS_SCAN_REQUIRED,
            'session' => $session,
            'qr' => $qr,
            'expires_at' => now()->addSeconds($this->artifactTtl())->toIso8601String(),
            'message' => __('ui.waha.qr_ready'),
            'http_status' => $result['http_status'] ?? null,
        ];
        $this->auditAction('qr', $session, $response);

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    public function requestPairingCode(string $session, string $phoneNumber): array
    {
        $session = trim($session);
        $phoneNumber = WahaChatHelper::normalizePhoneNumber($phoneNumber);

        if ($session === '' || $phoneNumber === null || strlen($phoneNumber) < 8) {
            return $this->failureResult(self::STATUS_SCAN_REQUIRED, 'validation', __('ui.waha.phone_invalid'));
        }

        $result = $this->requestJson(
            'POST',
            $this->pairingPath($session),
            ['phoneNumber' => $phoneNumber],
            'pairing',
        );

        if (! ($result['ok'] ?? false)) {
            $failure = $this->failureResult(
                self::STATUS_SCAN_REQUIRED,
                (string) ($result['error_category'] ?? 'pairing_unavailable'),
                (string) ($result['message'] ?? __('ui.waha.pairing_unavailable')),
                $result['http_status'] ?? null,
                $session,
            );
            $this->auditAction('pairing', $session, $failure);

            return $failure;
        }

        $payload = is_array($result['payload'] ?? null) ? $result['payload'] : [];
        $code = $this->extractString($payload, [
            'code',
            'pairingCode',
            'pairing_code',
            'data.code',
            'data.pairingCode',
        ]);

        if ($code === null) {
            $failure = $this->failureResult(self::STATUS_SCAN_REQUIRED, 'pairing_missing', __('ui.waha.pairing_unavailable'), null, $session);
            $this->auditAction('pairing', $session, $failure);

            return $failure;
        }

        $response = [
            'ok' => true,
            'status' => self::STATUS_SCAN_REQUIRED,
            'session' => $session,
            'code' => $code,
            'expires_at' => now()->addSeconds($this->artifactTtl())->toIso8601String(),
            'message' => __('ui.waha.pairing_ready'),
            'http_status' => $result['http_status'] ?? null,
        ];
        $this->auditAction('pairing', $session, $response);

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    public function startSession(string $session): array
    {
        return $this->mutateSession('start', $session, $this->startPath(), ['name' => trim($session), 'session' => trim($session)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function stopSession(string $session): array
    {
        return $this->mutateSession('stop', $session, $this->stopPath(), ['name' => trim($session), 'session' => trim($session)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function restartSession(string $session): array
    {
        return $this->mutateSession('restart', $session, $this->restartPath(), ['name' => trim($session), 'session' => trim($session)]);
    }

    /**
     * @param  array<int, string>  $events
     * @return array<string, mixed>
     */
    public function createOrUpdateSession(string $session, ?string $webhookUrl = null, array $events = [], ?string $hmacKey = null): array
    {
        $session = trim($session);

        if ($session === '') {
            return $this->failureResult(self::STATUS_UNKNOWN, 'validation', __('ui.waha.session_required'));
        }

        if ($webhookUrl === null || trim($webhookUrl) === '') {
            $token = config('services.waha.webhook_token');
            $webhookUrl = url('/webhooks/waha'.($token ? '/'.$token : ''));
        }

        $defaultEvents = [
            'message',
            'message.any',
            'message.ack',
            'session.status',
            'group.join',
            'group.leave',
        ];

        $webhookConfig = [
            'url' => $webhookUrl,
            'events' => ! empty($events) ? $events : $defaultEvents,
        ];

        $hmacKey = $hmacKey ?? config('services.waha.webhook_hmac_key');
        if (! empty($hmacKey)) {
            $webhookConfig['hmac'] = ['key' => $hmacKey];
        }

        $payload = [
            'name' => $session,
            'start' => true,
            'config' => [
                'webhooks' => [$webhookConfig],
            ],
        ];

        $result = $this->requestJson('POST', (string) config('services.waha.control_plane.sessions_path', '/api/sessions'), $payload, 'create_session');

        if (! ($result['ok'] ?? false) && in_array($result['http_status'] ?? 0, [400, 409, 422], true)) {
            $this->startSession($session);
        }

        $status = $this->getSessionStatus($session, true);
        $status['webhook_url'] = $webhookUrl;

        $this->auditAction('sync_webhook', $session, $status);

        return $status;
    }

    /**
     * @param  array<int, string>  $events
     * @return array<string, mixed>
     */
    public function syncWebhook(string $session, ?string $webhookUrl = null, ?string $hmacKey = null, array $events = []): array
    {
        return $this->createOrUpdateSession($session, $webhookUrl, $events, $hmacKey);
    }

    /**
     * @return array<string, mixed>
     */
    public function logoutSession(string $session): array
    {
        $session = trim($session);

        if ($session === '') {
            return $this->failureResult(self::STATUS_UNKNOWN, 'validation', __('ui.waha.session_required'));
        }

        $path = str_replace('{session}', rawurlencode($session), (string) config('services.waha.control_plane.logout_path', '/api/sessions/{session}/logout'));
        $payload = ['name' => $session, 'session' => $session];

        $result = $this->requestJson('POST', $path, $payload, 'logout');

        if (! ($result['ok'] ?? false)) {
            $fallbackPath = str_replace('{session}', rawurlencode($session), '/api/{session}/auth/logout');
            $result = $this->requestJson('POST', $fallbackPath, $payload, 'logout');
        }

        $status = $this->getSessionStatus($session, true);
        $this->auditAction('logout', $session, $status);

        return $status;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProfileMe(string $session): ?array
    {
        $session = trim($session);

        if ($session === '') {
            return null;
        }

        $path = str_replace('{session}', rawurlencode($session), '/api/{session}/me');
        $result = $this->requestJson('GET', $path, [], 'me');

        if (! ($result['ok'] ?? false) || ! is_array($result['payload'] ?? null)) {
            return null;
        }

        return $result['payload'];
    }

    /**
     * @return array<string, mixed>
     */
    public function pingGateway(): array
    {
        $startTime = microtime(true);
        $baseUrl = rtrim((string) config('services.waha.base_url', 'http://127.0.0.1:3000'), '/');

        $result = $this->requestJson('GET', (string) config('services.waha.control_plane.sessions_path', '/api/sessions'), [], 'ping');
        $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'latency_ms' => $latencyMs,
            'base_url' => $baseUrl,
            'http_status' => $result['http_status'] ?? null,
            'message' => ($result['ok'] ?? false) ? __('ui.pages.waha_connection.gateway_healthy', ['latency' => $latencyMs]) : __('ui.waha.unavailable'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function redactSensitivePayload(array $payload): array
    {
        $redacted = [];

        foreach ($payload as $key => $value) {
            $keyString = (string) $key;

            if ($this->isSensitiveKey($keyString)) {
                $redacted[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $redacted[$key] = $this->redactSensitivePayload($value);

                continue;
            }

            if (is_string($value) && $this->containsSensitiveValue($value)) {
                $redacted[$key] = '[REDACTED]';

                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logSafeEvent(string $level, string $message, array $context = []): void
    {
        $context = $this->redactSensitivePayload($context);

        match (strtolower($level)) {
            'debug' => Log::debug($message, $context),
            'warning' => Log::warning($message, $context),
            'error' => Log::error($message, $context),
            'critical' => Log::critical($message, $context),
            default => Log::info($message, $context),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mutateSession(string $action, string $session, string $path, array $payload): array
    {
        $session = trim($session);

        if ($session === '') {
            return $this->failureResult(self::STATUS_UNKNOWN, 'validation', __('ui.waha.session_required'));
        }

        $lock = Cache::lock($this->mutationLockKey($session), self::MUTATION_LOCK_TTL);

        if (! $lock->get()) {
            $busy = $this->failureResult(self::STATUS_UNKNOWN, 'busy', __('ui.waha.mutation_in_progress'));
            $this->auditAction($action, $session, $busy);

            return $busy;
        }

        try {
            $currentStatus = $this->getSessionStatus($session, true);

            if ($this->isIdempotentMutation($action, $currentStatus)) {
                $this->auditAction($action, $session, $currentStatus);

                return $currentStatus;
            }

            if (($currentStatus['ok'] ?? false) && ($currentStatus['capabilities'][$action] ?? true) === false) {
                $unsupported = array_merge($currentStatus, [
                    'ok' => false,
                    'message' => __('ui.waha.mutation_failed'),
                    'error_category' => 'unsupported',
                    'session' => $session,
                ]);
                $this->auditAction($action, $session, $unsupported);

                return $unsupported;
            }

            $mutation = $this->requestJson('POST', $path, $payload, $action);

            if (! ($mutation['ok'] ?? false)) {
                $refreshed = $this->getSessionStatus($session, true);
                $failure = array_merge($refreshed, [
                    'ok' => false,
                    'message' => (string) ($mutation['message'] ?? __('ui.waha.mutation_failed')),
                    'error_category' => (string) ($mutation['error_category'] ?? 'mutation_failed'),
                    'http_status' => $mutation['http_status'] ?? null,
                    'session' => $session,
                ]);
                $this->auditAction($action, $session, $failure);

                return $failure;
            }

            $refreshed = $this->getSessionStatus($session, true);
            $this->auditAction($action, $session, $refreshed);

            return $refreshed;
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * @param  array<string, mixed>  $queryOrPayload
     * @return array<string, mixed>
     */
    private function requestJson(string $method, string $path, array $queryOrPayload, string $action): array
    {
        $baseUrl = rtrim((string) config('services.waha.base_url', 'http://127.0.0.1:3000'), '/');
        $url = $baseUrl.'/'.ltrim($path, '/');

        try {
            $request = Http::acceptJson()
                ->timeout((int) config('services.waha.control_plane.timeout', 8));

            $apiKey = config('services.waha.api_key');

            if (is_string($apiKey) && trim($apiKey) !== '') {
                $request = $request->withHeader('X-Api-Key', $apiKey);
            }

            $response = match (strtoupper($method)) {
                'POST' => $request->asJson()->post($url, $queryOrPayload),
                default => $request->get($url, $queryOrPayload),
            };

            if (! $response->successful()) {
                $this->logSafeEvent('warning', 'WAHA control-plane request failed.', [
                    'action' => $action,
                    'http_status' => $response->status(),
                    'url' => $url,
                ]);

                return [
                    'ok' => false,
                    'http_status' => $response->status(),
                    'error_category' => in_array($response->status(), [401, 403], true) ? 'authentication' : 'unavailable',
                    'message' => __('ui.waha.unavailable'),
                ];
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            $payload = $response->json();

            if (! is_array($payload) && ! ($action === 'qr' && str_starts_with($contentType, 'image/'))) {
                return [
                    'ok' => false,
                    'http_status' => $response->status(),
                    'error_category' => 'malformed_response',
                    'message' => __('ui.waha.unavailable'),
                ];
            }

            return [
                'ok' => true,
                'http_status' => $response->status(),
                'payload' => is_array($payload) ? $payload : [],
                'raw_body' => $action === 'qr' ? $response->body() : null,
                'content_type' => $contentType,
            ];
        } catch (Throwable $exception) {
            $this->logSafeEvent('warning', 'WAHA control-plane request unavailable.', [
                'action' => $action,
                'exception' => $exception::class,
            ]);

            return [
                'ok' => false,
                'error_category' => 'unavailable',
                'message' => __('ui.waha.unavailable'),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function normalizeStatus(array $payload): string
    {
        $rawStatus = Arr::get($payload, 'status')
            ?? Arr::get($payload, 'state')
            ?? Arr::get($payload, 'session.status')
            ?? Arr::get($payload, 'data.status');
        $status = Str::of((string) $rawStatus)->trim()->upper()->replace(['-', ' ', '.'], '_')->value();

        return match ($status) {
            'WORKING', 'RUNNING', 'CONNECTED', 'AUTHENTICATED', 'READY', 'ONLINE' => self::STATUS_RUNNING,
            'STARTING', 'INITIALIZING', 'INITIALIZED', 'LOADING', 'RECONNECTING' => self::STATUS_STARTING,
            'SCAN_QR_CODE', 'SCAN_REQUIRED', 'NEED_SCAN', 'UNPAIRED', 'PAIRING', 'AUTHENTICATING' => self::STATUS_SCAN_REQUIRED,
            'STOPPED', 'STOPPING', 'NOT_STARTED', 'DISCONNECTED', 'OFFLINE' => self::STATUS_STOPPED,
            'FAILED', 'ERROR', 'CRASHED', 'AUTHENTICATION_FAILURE' => self::STATUS_FAILED,
            default => self::STATUS_UNKNOWN,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function successStatusResult(string $session, string $status, array $payload, ?int $httpStatus): array
    {
        $capabilities = $this->capabilitiesFor($status, $payload);

        return [
            'ok' => true,
            'status' => $status,
            'session' => $session,
            'connected_number' => $this->connectedNumber($payload),
            'connected_name' => $this->connectedName($payload),
            'capabilities' => $capabilities,
            'checked_at' => now()->toIso8601String(),
            'message' => $this->statusMessage($status),
            'error_category' => $status === self::STATUS_UNKNOWN ? 'unknown_status' : null,
            'http_status' => $httpStatus,
            'stale' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function failureResult(string $status, string $errorCategory, string $message, ?int $httpStatus = null, ?string $session = null): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'session' => $session,
            'connected_number' => null,
            'capabilities' => [
                'qr' => false,
                'pairing' => false,
                'start' => false,
                'stop' => false,
                'restart' => false,
            ],
            'checked_at' => now()->toIso8601String(),
            'message' => $message,
            'error_category' => $errorCategory,
            'http_status' => $httpStatus,
            'stale' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, bool>
     */
    private function capabilitiesFor(string $status, array $payload): array
    {
        $capabilities = [
            'qr' => $status === self::STATUS_SCAN_REQUIRED,
            'pairing' => false,
            'start' => ! in_array($status, [self::STATUS_UNAVAILABLE, self::STATUS_UNKNOWN], true),
            'stop' => ! in_array($status, [self::STATUS_UNAVAILABLE, self::STATUS_UNKNOWN], true),
            'restart' => ! in_array($status, [self::STATUS_UNAVAILABLE, self::STATUS_UNKNOWN], true),
        ];

        foreach (['qr', 'pairing', 'start', 'stop', 'restart'] as $capability) {
            $value = Arr::get($payload, 'capabilities.'.$capability);

            if (is_bool($value)) {
                $capabilities[$capability] = $value;
            }
        }

        return $capabilities;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function connectedNumber(array $payload): ?string
    {
        foreach ([
            'me.id',
            'me._serialized',
            'me.phone',
            'me.number',
            'phoneNumber',
            'number',
            'phone',
            'data.me.id',
            'data.phoneNumber',
        ] as $key) {
            $value = Arr::get($payload, $key);

            if (is_string($value) && ($normalized = WahaChatHelper::normalizePhoneNumber($value)) !== null) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function connectedName(array $payload): ?string
    {
        foreach ([
            'me.pushName',
            'me.name',
            'pushName',
            'name',
            'data.me.pushName',
            'data.me.name',
        ] as $key) {
            $value = Arr::get($payload, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function extractString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($payload, $key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function statusMessage(string $status): string
    {
        return match ($status) {
            self::STATUS_RUNNING => __('ui.waha.status_running'),
            self::STATUS_STARTING => __('ui.waha.status_starting'),
            self::STATUS_SCAN_REQUIRED => __('ui.waha.status_scan_required'),
            self::STATUS_STOPPED => __('ui.waha.status_stopped'),
            self::STATUS_FAILED => __('ui.waha.status_failed'),
            default => __('ui.waha.status_unknown'),
        };
    }

    private function statusCacheKey(string $session): string
    {
        return self::STATUS_CACHE_PREFIX.hash('sha256', $session);
    }

    private function mutationLockKey(string $session): string
    {
        return 'waha_session_mutation:'.hash('sha256', $session);
    }

    private function statusPath(string $session): string
    {
        return str_replace('{session}', rawurlencode($session), (string) config('services.waha.control_plane.status_path', '/api/sessions/{session}'));
    }

    private function startPath(): string
    {
        return (string) config('services.waha.control_plane.start_path', '/api/sessions/start');
    }

    private function stopPath(): string
    {
        return (string) config('services.waha.control_plane.stop_path', '/api/sessions/stop');
    }

    private function restartPath(): string
    {
        return (string) config('services.waha.control_plane.restart_path', '/api/sessions/restart');
    }

    private function qrPath(string $session): string
    {
        return str_replace('{session}', rawurlencode($session), (string) config('services.waha.control_plane.qr_path', '/api/{session}/auth/qr'));
    }

    private function pairingPath(string $session): string
    {
        return str_replace('{session}', rawurlencode($session), (string) config('services.waha.control_plane.pairing_path', '/api/{session}/auth/request-code'));
    }

    private function artifactTtl(): int
    {
        return max(30, min(300, (int) config('services.waha.control_plane.artifact_ttl', 90)));
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $key));

        return in_array($normalized, [
            'qr',
            'qrcode',
            'pairingcode',
            'body',
            'rawbody',
            'response',
            'responsebody',
            'rawresponse',
            'apikey',
            'xapikey',
            'authorization',
            'accesstoken',
            'webhooktoken',
            'xwebhooktoken',
            'password',
            'secret',
            'cookie',
        ], true);
    }

    private function containsSensitiveValue(string $value): bool
    {
        $lower = strtolower($value);

        return str_contains($lower, 'bearer ')
            || str_starts_with($lower, 'data:image/')
            || str_contains($lower, 'base64,');
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function isIdempotentMutation(string $action, array $status): bool
    {
        return ($action === 'start' && ($status['status'] ?? null) === self::STATUS_RUNNING)
            || ($action === 'stop' && ($status['status'] ?? null) === self::STATUS_STOPPED);
    }

    private function auditAction(string $action, string $session, array $result): void
    {
        try {
            if (! Schema::hasTable('TLogAktivitas')) {
                return;
            }

            $status = (string) ($result['status'] ?? self::STATUS_UNKNOWN);
            $metadata = [
                'session_code' => $session,
                'action' => $action,
                'outcome' => $status,
            ];

            DB::table('TLogAktivitas')->insert([
                'Id' => (string) Str::orderedUuid(),
                'IdPengguna' => auth()->id(),
                'Modul' => 'WAHA_SESSION',
                'Aksi' => Str::limit($action, 100, ''),
                'Keterangan' => Str::limit(__('ui.waha.audit_message', [
                    'session' => $session,
                    'action' => $action,
                    'status' => $status,
                ]), 1000, ''),
                'IpAddress' => null,
                'UserAgent' => null,
                'DataSebelumJson' => null,
                'DataSesudahJson' => json_encode($this->redactSensitivePayload($metadata), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'TglAktivitas' => now(),
                'TglBuat' => now(),
                'DibuatOleh' => auth()->id(),
                'TglEdit' => now(),
            ]);
        } catch (Throwable $exception) {
            $this->logSafeEvent('warning', 'WAHA session audit storage unavailable.', [
                'action' => $action,
                'session' => $session,
                'exception' => $exception::class,
            ]);
        }
    }
}
