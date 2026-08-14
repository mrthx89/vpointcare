<?php

namespace Tests\Unit\Services\Waha;

use App\Services\Waha\WahaSessionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class WahaSessionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_get_session_status_normalizes_running_state(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'WORKING',
                'me' => [
                    'id' => '628123456789@c.us',
                    'pushName' => 'VPoint Care CS',
                ],
            ], 200),
        ]);

        $service = new WahaSessionService;
        $result = $service->getSessionStatus('default', true);

        self::assertTrue($result['ok']);
        self::assertSame('running', $result['status']);
        self::assertSame('628123456789', $result['connected_number']);
        self::assertTrue($result['capabilities']['start']);
        self::assertTrue($result['capabilities']['stop']);
        self::assertTrue($result['capabilities']['restart']);
    }

    public function test_get_session_status_normalizes_scan_required_state(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'SCAN_QR_CODE',
                'capabilities' => ['pairing' => true],
            ], 200),
        ]);

        $service = new WahaSessionService;
        $result = $service->getSessionStatus('default', true);

        self::assertTrue($result['ok']);
        self::assertSame('scan_required', $result['status']);
        self::assertTrue($result['capabilities']['qr']);
        self::assertTrue($result['capabilities']['pairing']);
    }

    public function test_get_session_status_normalizes_starting_failed_and_unknown_states(): void
    {
        Http::fake([
            '*/api/sessions/starting' => Http::response(['status' => 'INITIALIZING'], 200),
            '*/api/sessions/failed' => Http::response(['status' => 'CRASHED'], 200),
            '*/api/sessions/unknown' => Http::response(['status' => 'NEW_WAHA_STATE'], 200),
        ]);

        $service = new WahaSessionService;

        self::assertSame('starting', $service->getSessionStatus('starting', true)['status']);
        self::assertSame('failed', $service->getSessionStatus('failed', true)['status']);
        self::assertSame('unknown', $service->getSessionStatus('unknown', true)['status']);
    }

    public function test_scan_required_status_does_not_assume_pairing_is_supported(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'SCAN_QR_CODE',
            ], 200),
        ]);

        $result = (new WahaSessionService)->getSessionStatus('default', true);

        self::assertSame('scan_required', $result['status']);
        self::assertTrue($result['capabilities']['qr']);
        self::assertFalse($result['capabilities']['pairing']);
    }

    public function test_status_cache_is_bypassed_by_force_refresh(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::sequence()
                ->push(['status' => 'STOPPED'], 200)
                ->push(['status' => 'WORKING'], 200),
        ]);

        $service = new WahaSessionService;

        self::assertSame('stopped', $service->getSessionStatus('default', true)['status']);
        self::assertSame('stopped', $service->getSessionStatus('default')['status']);
        self::assertSame('running', $service->getSessionStatus('default', true)['status']);
    }

    public function test_session_codes_with_matching_slugs_do_not_share_status_cache_or_mutation_lock(): void
    {
        Http::fake([
            '*/api/sessions/ops%2Fa' => Http::response(['status' => 'WORKING'], 200),
            '*/api/sessions/opsa' => Http::response(['status' => 'STOPPED'], 200),
        ]);

        $service = new WahaSessionService;
        $first = $service->getSessionStatus('ops/a', true);
        $second = $service->getSessionStatus('opsa');
        $firstLock = Cache::lock($this->privateSessionKey($service, 'mutationLockKey', 'ops/a'), 30);

        self::assertSame('running', $first['status']);
        self::assertSame('stopped', $second['status']);
        self::assertTrue($firstLock->get());

        try {
            $secondResult = $service->startSession('opsa');
        } finally {
            $firstLock->release();
        }

        self::assertSame('stopped', $secondResult['status']);
        self::assertNotSame('busy', $secondResult['error_category']);
    }

    public function test_get_session_status_handles_unavailable_endpoint_without_stale_running_claim(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::response([], 500),
        ]);

        $service = new WahaSessionService;
        $result = $service->getSessionStatus('default', true);

        self::assertFalse($result['ok']);
        self::assertSame('unavailable', $result['status']);
        self::assertStringNotContainsString('500', $result['message']);
    }

    public function test_secret_redaction_prevents_qr_and_keys_from_being_logged(): void
    {
        Log::spy();

        $service = new WahaSessionService;
        $redacted = $service->redactSensitivePayload([
            'qr' => 'data:image/png;base64,SECRET_QR_DATA',
            'pairingCode' => '12345678',
            'api_key' => 'super-secret-key',
            'nested' => [
                'Authorization' => 'Bearer token-123',
                'password' => 'secret-password',
            ],
        ]);

        self::assertSame('[REDACTED]', $redacted['qr']);
        self::assertSame('[REDACTED]', $redacted['pairingCode']);
        self::assertSame('[REDACTED]', $redacted['api_key']);
        self::assertSame('[REDACTED]', $redacted['nested']['Authorization']);
        self::assertSame('[REDACTED]', $redacted['nested']['password']);

        $service->logSafeEvent('info', 'WAHA session test', [
            'qr' => 'data:image/png;base64,SECRET_QR_DATA',
            'session' => 'default',
        ]);

        Log::shouldHaveReceived('info')->once()->with('WAHA session test', [
            'qr' => '[REDACTED]',
            'session' => 'default',
        ]);
    }

    public function test_secret_redaction_removes_nested_api_and_webhook_headers_with_any_separator_or_casing(): void
    {
        $redacted = (new WahaSessionService)->redactSensitivePayload([
            'headers' => [
                'X-Api-Key' => 'api-secret',
                'x_webhook_token' => 'webhook-secret',
                'X Webhook Token' => 'second-webhook-secret',
            ],
        ]);

        self::assertSame('[REDACTED]', $redacted['headers']['X-Api-Key']);
        self::assertSame('[REDACTED]', $redacted['headers']['x_webhook_token']);
        self::assertSame('[REDACTED]', $redacted['headers']['X Webhook Token']);
    }

    public function test_secret_redaction_removes_sensitive_response_bodies(): void
    {
        $redacted = (new WahaSessionService)->redactSensitivePayload([
            'response_body' => '{"qr":"data:image/png;base64,SECRET_QR_DATA"}',
            'nested' => ['body' => '{"pairingCode":"12345678"}'],
        ]);

        self::assertSame('[REDACTED]', $redacted['response_body']);
        self::assertSame('[REDACTED]', $redacted['nested']['body']);
    }

    public function test_get_qr_code_returns_ephemeral_artifact_without_persisting(): void
    {
        Log::spy();
        Http::fake([
            '*/api/default/auth/qr' => Http::response([
                'qr' => 'data:image/png;base64,EPHEMERAL_QR_CODE',
            ], 200),
        ]);

        $service = new WahaSessionService;
        $result = $service->getQrCode('default');

        self::assertTrue($result['ok']);
        self::assertSame('data:image/png;base64,EPHEMERAL_QR_CODE', $result['qr']);
        self::assertNull(Cache::get('waha_session_status:'.hash('sha256', 'default')));
        $service->logSafeEvent('info', 'QR safety check', ['response_body' => $result['qr']]);
        Log::shouldHaveReceived('info')->once()->with('QR safety check', ['response_body' => '[REDACTED]']);
    }

    public function test_request_pairing_code_returns_code(): void
    {
        Log::spy();
        Http::fake([
            '*/api/default/auth/request-code' => Http::response([
                'code' => 'ABC-123',
            ], 200),
        ]);

        $service = new WahaSessionService;
        $result = $service->requestPairingCode('default', '628123456789');

        self::assertTrue($result['ok']);
        self::assertSame('ABC-123', $result['code']);
        self::assertNull(Cache::get('waha_session_status:'.hash('sha256', 'default')));
        $service->logSafeEvent('info', 'Pairing safety check', ['response_body' => $result['code']]);
        Log::shouldHaveReceived('info')->once()->with('Pairing safety check', ['response_body' => '[REDACTED]']);
    }

    public function test_qr_and_pairing_artifacts_do_not_enter_status_cache_logs_or_audit_metadata(): void
    {
        $auditEntries = [];
        $query = Mockery::mock();
        $query->shouldReceive('insert')->twice()->andReturnUsing(function (array $entry) use (&$auditEntries): bool {
            $auditEntries[] = $entry;

            return true;
        });
        Schema::shouldReceive('hasTable')->twice()->with('TLogAktivitas')->andReturnTrue();
        DB::shouldReceive('table')->twice()->with('TLogAktivitas')->andReturn($query);
        Log::spy();
        Http::fake([
            '*/api/default/auth/qr' => Http::response(['qr' => 'data:image/png;base64,EPHEMERAL_QR_CODE'], 200),
            '*/api/default/auth/request-code' => Http::response(['code' => 'EPHEMERAL-PAIRING-CODE'], 200),
        ]);

        $service = new WahaSessionService;
        $qr = $service->getQrCode('default');
        $pairing = $service->requestPairingCode('default', '628123456789');

        self::assertSame('data:image/png;base64,EPHEMERAL_QR_CODE', $qr['qr']);
        self::assertSame('EPHEMERAL-PAIRING-CODE', $pairing['code']);
        self::assertNull(Cache::get('waha_session_status:'.hash('sha256', 'default')));
        self::assertCount(2, $auditEntries);

        foreach ($auditEntries as $entry) {
            self::assertStringNotContainsString('EPHEMERAL_QR_CODE', (string) $entry['DataSesudahJson']);
            self::assertStringNotContainsString('EPHEMERAL-PAIRING-CODE', (string) $entry['DataSesudahJson']);
        }

        Log::shouldNotHaveReceived('info');
    }

    public function test_start_session_uses_mutation_lock_and_returns_refreshed_status(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::sequence()
                ->push(['name' => 'default', 'status' => 'STOPPED'], 200)
                ->push([
                    'name' => 'default',
                    'status' => 'WORKING',
                    'me' => ['id' => '628123456789@c.us'],
                ], 200),
            '*/api/sessions/start' => Http::response([
                'name' => 'default',
                'status' => 'STARTING',
            ], 200),
        ]);

        $service = new WahaSessionService;
        $result = $service->startSession('default');

        self::assertTrue($result['ok']);
        self::assertSame('running', $result['status']);
    }

    public function test_explicitly_unsupported_lifecycle_capability_returns_generic_failure_without_mutation(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::response([
                'status' => 'STOPPED',
                'capabilities' => ['start' => false],
            ], 200),
        ]);

        $result = (new WahaSessionService)->startSession('default');

        self::assertFalse($result['ok']);
        self::assertSame('unsupported', $result['error_category']);
        self::assertSame('stopped', $result['status']);
        Http::assertSentCount(1);
    }

    public function test_start_on_running_session_reports_live_status_without_mutating_again(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'WORKING',
            ], 200),
        ]);

        $result = (new WahaSessionService)->startSession('default');

        self::assertTrue($result['ok']);
        self::assertSame('running', $result['status']);
        Http::assertSentCount(1);
    }

    public function test_stop_on_stopped_session_reports_live_status_without_mutating_again(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::response([
                'status' => 'STOPPED',
            ], 200),
        ]);

        $result = (new WahaSessionService)->stopSession('default');

        self::assertTrue($result['ok']);
        self::assertSame('stopped', $result['status']);
        Http::assertSentCount(1);
    }

    public function test_mutation_failure_refreshes_status_once_without_retrying_the_mutation(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::sequence()
                ->push(['status' => 'STOPPED'], 200)
                ->push(['status' => 'FAILED'], 200),
            '*/api/sessions/start' => Http::response(['error' => 'do not expose this body'], 503),
        ]);

        $result = (new WahaSessionService)->startSession('default');

        self::assertFalse($result['ok']);
        self::assertSame('failed', $result['status']);
        self::assertSame('unavailable', $result['error_category']);
        self::assertStringNotContainsString('do not expose', $result['message']);
        Http::assertSentCount(3);
    }

    public function test_start_session_returns_busy_without_issuing_a_duplicate_mutation(): void
    {
        $service = new WahaSessionService;
        $lock = Cache::lock($this->privateSessionKey($service, 'mutationLockKey', 'default'), 30);
        self::assertTrue($lock->get());

        try {
            $result = (new WahaSessionService)->startSession('default');
        } finally {
            $lock->release();
        }

        self::assertFalse($result['ok']);
        self::assertSame('busy', $result['error_category']);
        Http::assertNothingSent();
    }

    public function test_malformed_status_response_maps_to_generic_failure(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::response('not-json: API_KEY=super-secret-key', 200),
        ]);

        $result = (new WahaSessionService)->getSessionStatus('default', true);

        self::assertFalse($result['ok']);
        self::assertSame('unknown', $result['status']);
        self::assertSame('malformed_response', $result['error_category']);
        self::assertStringNotContainsString('super-secret-key', $result['message']);
    }

    private function privateSessionKey(WahaSessionService $service, string $method, string $session): string
    {
        $reflection = new \ReflectionMethod($service, $method);

        return (string) $reflection->invoke($service, $session);
    }
}
