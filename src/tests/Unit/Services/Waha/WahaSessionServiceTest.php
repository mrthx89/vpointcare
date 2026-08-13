<?php

namespace Tests\Unit\Services\Waha;

use App\Services\Waha\WahaSessionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        $service = new WahaSessionService();
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
            ], 200),
        ]);

        $service = new WahaSessionService();
        $result = $service->getSessionStatus('default', true);

        self::assertTrue($result['ok']);
        self::assertSame('scan_required', $result['status']);
        self::assertTrue($result['capabilities']['qr']);
        self::assertTrue($result['capabilities']['pairing']);
    }

    public function test_get_session_status_handles_unavailable_endpoint_without_stale_running_claim(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::response([], 500),
        ]);

        $service = new WahaSessionService();
        $result = $service->getSessionStatus('default', true);

        self::assertFalse($result['ok']);
        self::assertSame('unavailable', $result['status']);
        self::assertStringNotContainsString('500', $result['message']);
    }

    public function test_secret_redaction_prevents_qr_and_keys_from_being_logged(): void
    {
        Log::spy();

        $service = new WahaSessionService();
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

    public function test_get_qr_code_returns_ephemeral_artifact_without_persisting(): void
    {
        Http::fake([
            '*/api/default/auth/qr' => Http::response([
                'qr' => 'data:image/png;base64,EPHEMERAL_QR_CODE',
            ], 200),
        ]);

        $service = new WahaSessionService();
        $result = $service->getQrCode('default');

        self::assertTrue($result['ok']);
        self::assertSame('data:image/png;base64,EPHEMERAL_QR_CODE', $result['qr']);
        self::assertNull(Cache::get('waha_session_status:default:qr'));
    }

    public function test_request_pairing_code_returns_code(): void
    {
        Http::fake([
            '*/api/default/auth/request-code' => Http::response([
                'code' => 'ABC-123',
            ], 200),
        ]);

        $service = new WahaSessionService();
        $result = $service->requestPairingCode('default', '628123456789');

        self::assertTrue($result['ok']);
        self::assertSame('ABC-123', $result['code']);
    }

    public function test_start_session_uses_mutation_lock_and_returns_refreshed_status(): void
    {
        Http::fake([
            '*/api/sessions/start' => Http::response([
                'name' => 'default',
                'status' => 'STARTING',
            ], 200),
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'WORKING',
                'me' => ['id' => '628123456789@c.us'],
            ], 200),
        ]);

        $service = new WahaSessionService();
        $result = $service->startSession('default');

        self::assertTrue($result['ok']);
        self::assertSame('running', $result['status']);
    }
}
