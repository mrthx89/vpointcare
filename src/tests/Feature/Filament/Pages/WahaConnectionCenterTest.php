<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\WahaConnectionCenter;
use App\Models\Master\Pengguna;
use App\Services\Waha\WahaSessionService;
use App\Support\AccessPermissions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class WahaConnectionCenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->name('waha-connection-center-test.livewire.update');
        });
        app('router')->getRoutes()->refreshNameLookups();
        $this->createSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        foreach ([
            'MSesiWhatsapp',
            'MPengguna',
            'TLogAktivitas',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Cache::flush();

        parent::tearDown();
    }

    public function test_user_without_view_permission_cannot_access_connection_center(): void
    {
        $user = $this->user([]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->assertForbidden();
    }

    public function test_user_with_view_permission_can_access_and_render_sessions(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'WORKING',
                'me' => ['id' => '628123456789@c.us', 'pushName' => 'CareDesk CS'],
            ], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->assertStatus(200)
            ->assertSee(__('ui.pages.waha_connection.title'))
            ->assertSee('default')
            ->assertSee(__('ui.pages.waha_connection.status_running'));
    }

    public function test_gateway_overview_displays_effective_url_without_exposing_api_key(): void
    {
        config()->set('services.waha.base_url', 'https://waha.internal.example');
        config()->set('services.waha.api_key', 'super-secret-waha-key');

        Http::fake([
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'WORKING',
            ], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->assertSee('https://waha.internal.example')
            ->assertSee(__('ui.pages.waha_connection.api_key_configured'))
            ->assertDontSee('super-secret-waha-key');
    }

    public function test_manager_can_initialize_default_session_without_persisting_global_api_key(): void
    {
        DB::table('MSesiWhatsapp')->delete();
        config()->set('services.waha.base_url', 'https://waha.internal.example');
        config()->set('services.waha.api_key', 'super-secret-waha-key');

        Http::fake([
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'SCAN_QR_CODE',
            ], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->assertSee(__('ui.pages.waha_connection.btn_initialize_default'))
            ->call('initializeDefaultSession')
            ->assertHasNoErrors()
            ->assertSee('default');

        $this->assertDatabaseHas('MSesiWhatsapp', [
            'KodeSesi' => 'default',
            'BaseUrlWaha' => 'https://waha.internal.example',
            'ApiKey' => null,
        ]);
    }

    public function test_viewer_cannot_initialize_default_session_directly(): void
    {
        DB::table('MSesiWhatsapp')->delete();

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('initializeDefaultSession')
            ->assertForbidden();

        $this->assertDatabaseMissing('MSesiWhatsapp', ['KodeSesi' => 'default']);
    }

    public function test_manager_can_align_session_url_without_changing_session_api_key(): void
    {
        config()->set('services.waha.base_url', 'https://waha.internal.example');
        DB::table('MSesiWhatsapp')->where('KodeSesi', 'default')->update([
            'BaseUrlWaha' => 'https://legacy-waha.example',
            'ApiKey' => 'stored-session-secret',
        ]);

        Http::fake([
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'SCAN_QR_CODE',
            ], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->assertSee(__('ui.pages.waha_connection.btn_align_base_url'))
            ->assertDontSee('stored-session-secret')
            ->call('alignSessionBaseUrl', 'default')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('MSesiWhatsapp', [
            'KodeSesi' => 'default',
            'BaseUrlWaha' => 'https://waha.internal.example',
            'ApiKey' => 'stored-session-secret',
        ]);
    }

    public function test_loading_translation_keys_are_available_in_both_locales(): void
    {
        $originalLocale = app()->getLocale();

        try {
            foreach (['id', 'en'] as $locale) {
                app()->setLocale($locale);

                foreach ([
                    'ui.common.loading',
                    'ui.common.refreshing',
                    'ui.common.never',
                    'ui.pages.waha_connection.testing_gateway',
                    'ui.pages.waha_connection.starting_session',
                    'ui.pages.waha_connection.disconnecting_device',
                ] as $key) {
                    $this->assertNotSame($key, __($key));
                }
            }
        } finally {
            app()->setLocale($originalLocale);
        }
    }

    public function test_running_session_shows_logout_control(): void
    {
        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Http::fake([
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'WORKING',
            ], 200),
        ]);

        Livewire::test(WahaConnectionCenter::class)
            ->assertSee(__('ui.pages.waha_connection.btn_logout'))
            ->assertDontSee(__('ui.pages.waha_connection.btn_qr'));
    }

    public function test_scan_required_session_shows_qr_control(): void
    {
        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Http::fake([
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'SCAN_QR_CODE',
            ], 200),
        ]);

        Livewire::test(WahaConnectionCenter::class)
            ->assertSee(__('ui.pages.waha_connection.btn_qr'))
            ->assertDontSee(__('ui.pages.waha_connection.btn_logout'));
    }

    public function test_operator_with_manage_permission_can_trigger_qr_modal_and_fetch_qr(): void
    {
        Http::fake([
            '*/api/default/auth/qr' => Http::response([
                'qr' => 'data:image/png;base64,TEST_QR_DATA',
            ], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('openQrModal', 'default', 'Session Default')
            ->assertSet('activeModalSession', 'default')
            ->assertSet('activeModalTab', 'qr')
            ->assertSet('qrCodePayload', 'data:image/png;base64,TEST_QR_DATA');
    }

    public function test_operator_can_switch_modal_tabs(): void
    {
        Http::fake([
            '*/api/default/auth/qr' => Http::response(['qr' => 'data:image/png;base64,TEST_QR_DATA'], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('openQrModal', 'default', 'Session Default')
            ->call('setModalTab', 'pairing')
            ->assertSet('activeModalTab', 'pairing')
            ->call('setModalTab', 'qr')
            ->assertSet('activeModalTab', 'qr');
    }

    public function test_operator_can_submit_pairing_code(): void
    {
        Http::fake([
            '*/api/default/auth/request-code' => Http::response([
                'code' => '1234-5678',
            ], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('openPairingModal', 'default', 'Session Default')
            ->set('pairingPhoneNumber', '628123456789')
            ->call('submitPairingCode')
            ->assertSet('pairingCodePayload', '1234-5678');
    }

    public function test_operator_can_auto_sync_webhook(): void
    {
        Http::fake([
            '*/api/sessions' => Http::response(['name' => 'default', 'status' => 'STARTING'], 201),
            '*/api/sessions/default' => Http::response(['name' => 'default', 'status' => 'WORKING'], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('syncWebhookAuto', 'default')
            ->assertHasNoErrors()
            ->assertNotified(
                Notification::make()
                    ->title(__('ui.pages.waha_connection.webhook_sync_success_title'))
                    ->body(__('ui.pages.waha_connection.webhook_sync_success', [
                        'session' => 'default',
                        'url' => config('app.url', 'http://127.0.0.1:8000').'/webhooks/waha',
                    ]))
                    ->success(),
            );
    }

    public function test_operator_can_logout_device(): void
    {
        Http::fake([
            '*/api/sessions/default/logout' => Http::response(['ok' => true], 200),
            '*/api/sessions/default' => Http::response(['name' => 'default', 'status' => 'SCAN_QR_CODE'], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('logoutSession', 'default')
            ->assertHasNoErrors()
            ->assertNotified(
                Notification::make()
                    ->title(__('ui.pages.waha_connection.logout_success_title'))
                    ->body(__('ui.pages.waha_connection.logout_success', ['session' => 'default']))
                    ->success(),
            );
    }

    public function test_operator_can_test_gateway_connection(): void
    {
        Http::fake([
            '*/api/sessions' => Http::response([['name' => 'default']], 200),
            '*/api/sessions/default' => Http::response(['name' => 'default', 'status' => 'WORKING'], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        $component = Livewire::test(WahaConnectionCenter::class)
            ->call('testGatewayConnection')
            ->assertHasNoErrors();

        $latency = $component->get('gatewayLatencyMs');

        $component->assertNotified(
            Notification::make()
                ->title(__('ui.pages.waha_connection.gateway_healthy_title'))
                ->body(__('ui.pages.waha_connection.gateway_healthy', ['latency' => $latency]))
                ->success(),
        );
    }

    public function test_operator_can_execute_start_session_action(): void
    {
        Http::fake([
            '*/api/sessions/start' => Http::response([
                'name' => 'default',
                'status' => 'STARTING',
            ], 200),
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'WORKING',
            ], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('startSession', 'default')
            ->assertHasNoErrors()
            ->assertNotified(
                Notification::make()
                    ->title(__('ui.pages.waha_connection.action_success', ['action' => 'START', 'session' => 'default']))
                    ->success(),
            );
    }

    public function test_user_without_manage_permission_cannot_trigger_mutations(): void
    {
        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('startSession', 'default')
            ->assertForbidden();
    }

    public function test_disabled_session_hides_actions_and_rejects_direct_manage_requests(): void
    {
        DB::table('MSesiWhatsapp')->where('KodeSesi', 'default')->update(['NonAktif' => true]);
        Http::fake();

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->assertSee(__('ui.pages.waha_connection.disabled_in_wacs'))
            ->assertDontSee(__('ui.pages.waha_connection.btn_start'))
            ->assertDontSee(__('ui.pages.waha_connection.btn_stop'))
            ->assertDontSee(__('ui.pages.waha_connection.btn_restart'))
            ->assertDontSee(__('ui.pages.waha_connection.btn_qr'))
            ->assertDontSee(__('ui.pages.waha_connection.btn_pairing'));

        foreach (['startSession', 'stopSession', 'restartSession', 'openQrModal', 'openPairingModal'] as $action) {
            $parameters = in_array($action, ['openQrModal', 'openPairingModal'], true)
                ? ['default', 'Default Session']
                : ['default'];

            Livewire::test(WahaConnectionCenter::class)
                ->call($action, ...$parameters)
                ->assertForbidden();
        }

        Http::assertNothingSent();
    }

    public function test_pairing_failure_shows_generic_message_without_sensitive_response(): void
    {
        $this->bindWahaService([
            'ok' => false,
            'status' => WahaSessionService::STATUS_SCAN_REQUIRED,
            'error_category' => 'unsupported',
            'message' => 'apiKey=private-value',
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('openPairingModal', 'default', 'Session Default')
            ->set('pairingPhoneNumber', '628123456789')
            ->call('submitPairingCode')
            ->assertSet('modalErrorMessage', __('ui.waha.pairing_unavailable'))
            ->assertSee(__('ui.waha.pairing_unavailable'))
            ->assertDontSee('private-value');
    }

    public function test_live_status_diagnostic_uses_a_generic_message_without_sensitive_response(): void
    {
        $service = \Mockery::mock(WahaSessionService::class);
        $service->shouldReceive('getSessionStatus')->andReturn([
            'ok' => false,
            'status' => WahaSessionService::STATUS_UNAVAILABLE,
            'capabilities' => ['qr' => false, 'pairing' => false, 'start' => false, 'stop' => false, 'restart' => false],
            'checked_at' => now()->toIso8601String(),
            'message' => 'X-Api-Key: private-value',
        ]);
        app()->instance(WahaSessionService::class, $service);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->assertSee(__('ui.waha.unavailable'))
            ->assertDontSee('private-value');
    }

    public function test_qr_expiry_clears_the_artifact_and_offers_a_safe_retry(): void
    {
        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->set('activeModalSession', 'default')
            ->set('activeModalSessionName', 'Session Default')
            ->set('qrCodePayload', 'data:image/png;base64,EXPIRED_QR')
            ->set('qrCodeExpiresAt', now()->subSecond()->toIso8601String())
            ->call('clearExpiredAuthenticationArtifacts')
            ->assertSet('qrCodePayload', null)
            ->assertSet('qrCodeExpiresAt', null)
            ->assertSet('modalErrorMessage', __('ui.waha.qr_unavailable'))
            ->assertSee(__('ui.waha.qr_unavailable'))
            ->assertSee(__('ui.common.retry'))
            ->assertDontSee('EXPIRED_QR');
    }

    public function test_pairing_expiry_clears_the_code_and_returns_to_the_form(): void
    {
        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->set('activeModalSession', 'default')
            ->set('activeModalSessionName', 'Session Default')
            ->set('activeModalTab', 'pairing')
            ->set('pairingCodePayload', 'EXPIRED_PAIRING_CODE')
            ->set('pairingCodeExpiresAt', now()->subSecond()->toIso8601String())
            ->call('clearExpiredAuthenticationArtifacts')
            ->assertSet('pairingCodePayload', null)
            ->assertSet('pairingCodeExpiresAt', null)
            ->assertSet('modalErrorMessage', __('ui.waha.pairing_unavailable'))
            ->assertSee(__('ui.waha.pairing_unavailable'))
            ->assertSee(__('ui.pages.waha_connection.phone_label'))
            ->assertDontSee('EXPIRED_PAIRING_CODE');
    }

    public function test_lifecycle_failure_notifies_with_a_generic_message_without_sensitive_response(): void
    {
        $this->bindWahaService([
            'ok' => false,
            'status' => WahaSessionService::STATUS_UNAVAILABLE,
            'error_category' => 'unavailable',
            'message' => 'Authorization: Bearer private-token',
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('startSession', 'default')
            ->assertNotified(
                Notification::make()
                    ->title(__('ui.pages.waha_connection.action_failed', ['action' => 'START', 'session' => 'default']))
                    ->body(__('ui.waha.unavailable'))
                    ->danger(),
            )
            ->assertDontSee('private-token');
    }

    public function test_pairing_action_is_not_rendered_when_waha_does_not_support_it(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'SCAN_QR_CODE',
                'capabilities' => ['qr' => true, 'pairing' => false],
            ], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->assertSee(__('ui.pages.waha_connection.status_scan_required'))
            ->assertSee(__('ui.pages.waha_connection.btn_qr'))
            ->assertDontSee(__('ui.pages.waha_connection.btn_pairing'));
    }

    public function test_pairing_requires_a_valid_phone_number_before_calling_waha(): void
    {
        Http::fake();

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('openPairingModal', 'default', 'Session Default')
            ->set('pairingPhoneNumber', '@lid')
            ->call('submitPairingCode')
            ->assertSet('modalErrorMessage', __('ui.waha.phone_invalid'));

        Http::assertNotSent(fn ($request) => str_contains((string) $request->url(), 'auth/request-code'));
    }

    private function createSchema(): void
    {
        Schema::create('MSesiWhatsapp', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodeSesi');
            $table->string('NamaSesi')->nullable();
            $table->string('BaseUrlWaha')->nullable();
            $table->string('ApiKey')->nullable();
            $table->string('NomorTerhubung')->nullable();
            $table->string('StatusSesi')->default('Aktif');
            $table->boolean('NonAktif')->default(false);
            $table->dateTime('TglBuat')->nullable();
            $table->string('DibuatOleh')->nullable();
            $table->dateTime('TglEdit')->nullable();
            $table->string('DieditOleh')->nullable();
        });

        Schema::create('MPengguna', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('NamaPengguna');
            $table->boolean('NonAktif')->default(false);
        });

        Schema::create('TLogAktivitas', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdPengguna')->nullable();
            $table->string('Modul');
            $table->string('Aksi');
            $table->text('Keterangan')->nullable();
            $table->string('IpAddress')->nullable();
            $table->text('UserAgent')->nullable();
            $table->text('DataSebelumJson')->nullable();
            $table->text('DataSesudahJson')->nullable();
            $table->dateTime('TglAktivitas');
            $table->dateTime('TglBuat');
            $table->string('DibuatOleh')->nullable();
            $table->dateTime('TglEdit')->nullable();
        });
    }

    private function seedFixtures(): void
    {
        DB::table('MSesiWhatsapp')->insert([
            'Id' => 'session-1',
            'KodeSesi' => 'default',
            'NamaSesi' => 'Default Session',
            'BaseUrlWaha' => config('services.waha.base_url', 'http://127.0.0.1:3000'),
            'StatusSesi' => 'Aktif',
            'NonAktif' => false,
        ]);
    }

    private function user(array $permissions = []): Pengguna
    {
        $user = new class extends Pengguna
        {
            /** @var array<int, string> */
            public array $testPermissions = [];

            public function hasPermissionCode(string $permission): bool
            {
                return in_array($permission, $this->testPermissions, true);
            }
        };

        $user->testPermissions = $permissions;

        return $user->forceFill([
            'Id' => 'user-test-1',
            'NamaPengguna' => 'Test Operator',
            'NonAktif' => false,
        ]);
    }

    /** @param array<string, mixed> $result */
    private function bindWahaService(array $result): void
    {
        $service = \Mockery::mock(WahaSessionService::class);
        $service->shouldReceive('getSessionStatus')->andReturn([
            'ok' => true,
            'status' => WahaSessionService::STATUS_SCAN_REQUIRED,
            'capabilities' => ['qr' => true, 'pairing' => true, 'start' => true, 'stop' => true, 'restart' => true],
            'checked_at' => now()->toIso8601String(),
        ]);
        $service->shouldReceive('getQrCode')->andReturn($result);
        $service->shouldReceive('requestPairingCode')->andReturn($result);
        $service->shouldReceive('startSession')->andReturn($result);
        $service->shouldReceive('stopSession')->andReturn($result);
        $service->shouldReceive('restartSession')->andReturn($result);

        app()->instance(WahaSessionService::class, $service);
    }
}
