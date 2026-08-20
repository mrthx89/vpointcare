<?php

namespace App\Filament\Pages;

use App\Services\Waha\WahaSessionService;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\FilamentBreadcrumbs;
use App\Support\NavigationHelper;
use App\Support\WahaChatHelper;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class WahaConnectionCenter extends Page
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return NavigationHelper::iconFor(AccessPermissions::WAHA_SESSION_VIEW, 'heroicon-o-signal');
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::WAHA_SESSION_VIEW, __('ui.navigation.monitoring'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::WAHA_SESSION_VIEW, 20);
    }

    public function getTitle(): string|Htmlable
    {
        return __('ui.pages.waha_connection.title');
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::WAHA_SESSION_VIEW, __('ui.pages.waha_connection.navigation_label'));
    }

    public function getBreadcrumbs(): array
    {
        return FilamentBreadcrumbs::forMenu(AccessPermissions::WAHA_SESSION_VIEW, __('ui.pages.waha_connection.navigation_label'));
    }

    protected string $view = 'filament.pages.waha-connection-center';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::WAHA_SESSION_VIEW)
            && NavigationHelper::isActive(AccessPermissions::WAHA_SESSION_VIEW);
    }

    public function canManageSession(): bool
    {
        return FilamentAccess::can(AccessPermissions::WAHA_SESSION_MANAGE);
    }

    /** @var array<int, array<string, mixed>> */
    public array $sessions = [];

    public bool $isRefreshing = false;

    public bool $isSyncingWebhook = false;

    public bool $isTestingGateway = false;

    public string $webhookUrl = '';

    public ?string $lastWebhookReceivedAt = null;

    public int $totalWebhooksToday = 0;

    public ?int $gatewayLatencyMs = null;

    public ?string $activeModalSession = null;

    public ?string $activeModalSessionName = null;

    public string $activeModalTab = 'qr';

    public ?string $qrCodePayload = null;

    public ?string $qrCodeExpiresAt = null;

    public ?string $pairingPhoneNumber = null;

    public ?string $pairingCodePayload = null;

    public ?string $pairingCodeExpiresAt = null;

    public ?string $modalErrorMessage = null;

    public bool $modalLoading = false;

    public function mount(): void
    {
        $token = config('services.waha.webhook_token');
        $this->webhookUrl = url('/webhooks/waha'.($token ? '/'.$token : ''));

        $this->loadWebhookStats();
        $this->loadSessions(true);
    }

    public function loadWebhookStats(): void
    {
        try {
            if (Schema::hasTable('TLogWebhookWaha')) {
                $this->lastWebhookReceivedAt = DB::table('TLogWebhookWaha')
                    ->latest('TglDiterima')
                    ->value('TglDiterima');

                $this->totalWebhooksToday = DB::table('TLogWebhookWaha')
                    ->whereDate('TglDiterima', today())
                    ->count();
            }
        } catch (Throwable) {
            // Silently ignore stat loading failures
        }
    }

    public function loadSessions(bool $forceRefresh = false): void
    {
        $this->isRefreshing = true;

        try {
            $service = app(WahaSessionService::class);
            $rows = DB::table('MSesiWhatsapp')
                ->orderByRaw('CASE WHEN "KodeSesi" = \'default\' THEN 0 ELSE 1 END')
                ->orderBy('KodeSesi')
                ->get();

            $globalBaseUrl = rtrim((string) config('services.waha.base_url', 'http://127.0.0.1:3000'), '/');
            $sessionList = [];

            foreach ($rows as $row) {
                $sessionCode = (string) ($row->KodeSesi ?: 'default');
                $isConfiguredActive = ! (bool) ($row->NonAktif ?? false);
                $rowBaseUrl = rtrim((string) ($row->BaseUrlWaha ?? $globalBaseUrl), '/');
                $isBaseUrlMisconfigured = $rowBaseUrl !== '' && strtolower($rowBaseUrl) !== strtolower($globalBaseUrl);

                $liveStatus = $isConfiguredActive
                    ? $service->getSessionStatus($sessionCode, $forceRefresh)
                    : [
                        'ok' => false,
                        'status' => WahaSessionService::STATUS_STOPPED,
                        'session' => $sessionCode,
                        'connected_number' => null,
                        'connected_name' => null,
                        'capabilities' => ['qr' => false, 'pairing' => false, 'start' => false, 'stop' => false, 'restart' => false],
                        'checked_at' => now()->toIso8601String(),
                        'message' => __('ui.pages.waha_connection.disabled_in_wacs'),
                        'error_category' => 'disabled',
                        'http_status' => null,
                        'stale' => false,
                    ];

                $liveStatus = $this->safeLiveStatus($liveStatus);

                $sessionList[] = [
                    'id' => (string) $row->Id,
                    'code' => $sessionCode,
                    'name' => trim((string) ($row->NamaSesi ?: $sessionCode)),
                    'base_url' => $rowBaseUrl,
                    'misconfigured_base_url' => $isBaseUrlMisconfigured,
                    'configured_active' => $isConfiguredActive,
                    'db_status' => (string) ($row->StatusSesi ?: 'TidakAktif'),
                    'db_number' => (string) ($row->NomorTerhubung ?: ''),
                    'live' => $liveStatus,
                ];
            }

            $this->sessions = $sessionList;
            $this->loadWebhookStats();
            $this->clearAuthenticationArtifactsForRunningSession();
        } catch (Throwable $exception) {
            Notification::make()
                ->title(__('ui.pages.waha_connection.load_failed'))
                ->danger()
                ->send();
        } finally {
            $this->isRefreshing = false;
        }
    }

    public function testGatewayConnection(): void
    {
        $this->isTestingGateway = true;

        try {
            $service = app(WahaSessionService::class);
            $result = $service->pingGateway();

            $this->gatewayLatencyMs = $result['latency_ms'] ?? null;

            if ($result['ok'] ?? false) {
                Notification::make()
                    ->title(__('ui.pages.waha_connection.gateway_healthy_title'))
                    ->body(__('ui.pages.waha_connection.gateway_healthy', ['latency' => $this->gatewayLatencyMs]))
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title(__('ui.pages.waha_connection.gateway_unreachable_title'))
                    ->body(__('ui.waha.unavailable'))
                    ->danger()
                    ->send();
            }
        } catch (Throwable $e) {
            Notification::make()
                ->title(__('ui.pages.waha_connection.gateway_unreachable_title'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isTestingGateway = false;
        }
    }

    public function syncWebhookAuto(string $sessionCode): void
    {
        $this->authorizeManageSession($sessionCode);
        $this->isSyncingWebhook = true;

        try {
            $service = app(WahaSessionService::class);
            $result = $service->syncWebhook($sessionCode);

            $this->loadSessions(true);

            if ($result['ok'] ?? false) {
                Notification::make()
                    ->title(__('ui.pages.waha_connection.webhook_sync_success_title'))
                    ->body(__('ui.pages.waha_connection.webhook_sync_success', [
                        'session' => $sessionCode,
                        'url' => $result['webhook_url'] ?? $this->webhookUrl,
                    ]))
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title(__('ui.pages.waha_connection.webhook_sync_failed_title'))
                    ->body(__('ui.pages.waha_connection.webhook_sync_failed', [
                        'session' => $sessionCode,
                    ]))
                    ->danger()
                    ->send();
            }
        } catch (Throwable $e) {
            $this->loadSessions(true);
            Notification::make()
                ->title(__('ui.pages.waha_connection.webhook_sync_failed_title'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isSyncingWebhook = false;
        }
    }

    public function setModalTab(string $tab): void
    {
        $this->activeModalTab = in_array($tab, ['qr', 'pairing'], true) ? $tab : 'qr';

        if ($this->activeModalTab === 'qr' && ! $this->qrCodePayload && ! $this->modalLoading) {
            $this->fetchQrCode();
        }
    }

    public function openQrModal(string $sessionCode, string $sessionName): void
    {
        $this->authorizeManageSession($sessionCode);

        $this->activeModalSession = $sessionCode;
        $this->activeModalSessionName = $sessionName;
        $this->activeModalTab = 'qr';
        $this->qrCodePayload = null;
        $this->qrCodeExpiresAt = null;
        $this->pairingPhoneNumber = '';
        $this->pairingCodePayload = null;
        $this->pairingCodeExpiresAt = null;
        $this->modalErrorMessage = null;
        $this->modalLoading = true;

        $this->dispatch('open-modal', id: 'whatsapp-auth-modal');
        $this->fetchQrCode();
    }

    public function openPairingModal(string $sessionCode, string $sessionName): void
    {
        $this->authorizeManageSession($sessionCode);

        $this->activeModalSession = $sessionCode;
        $this->activeModalSessionName = $sessionName;
        $this->activeModalTab = 'pairing';
        $this->pairingPhoneNumber = '';
        $this->pairingCodePayload = null;
        $this->pairingCodeExpiresAt = null;
        $this->qrCodePayload = null;
        $this->qrCodeExpiresAt = null;
        $this->modalErrorMessage = null;
        $this->modalLoading = false;

        $this->dispatch('open-modal', id: 'whatsapp-auth-modal');
    }

    public function fetchQrCode(): void
    {
        if (! $this->activeModalSession) {
            return;
        }

        $this->authorizeManageSession($this->activeModalSession);

        $this->modalLoading = true;
        $this->modalErrorMessage = null;

        try {
            $service = app(WahaSessionService::class);
            $result = $service->getQrCode($this->activeModalSession);

            if (($result['ok'] ?? false) && isset($result['qr'])) {
                $this->qrCodePayload = (string) $result['qr'];
                $this->qrCodeExpiresAt = isset($result['expires_at']) ? (string) $result['expires_at'] : null;
            } else {
                $this->modalErrorMessage = $this->genericResultMessage($result, 'qr');
            }
        } catch (Throwable $exception) {
            $this->modalErrorMessage = __('ui.waha.qr_unavailable');
        } finally {
            $this->modalLoading = false;
        }
    }

    public function submitPairingCode(): void
    {
        if (! $this->activeModalSession) {
            return;
        }

        $this->authorizeManageSession($this->activeModalSession);

        $cleanPhone = WahaChatHelper::normalizePhoneNumber($this->pairingPhoneNumber);

        if ($cleanPhone === null || strlen($cleanPhone) < 8) {
            $this->modalErrorMessage = __('ui.waha.phone_invalid');

            return;
        }

        $this->modalLoading = true;
        $this->modalErrorMessage = null;

        try {
            $service = app(WahaSessionService::class);
            $result = $service->requestPairingCode($this->activeModalSession, $cleanPhone);

            if (($result['ok'] ?? false) && isset($result['code'])) {
                $this->pairingCodePayload = (string) $result['code'];
                $this->pairingCodeExpiresAt = isset($result['expires_at']) ? (string) $result['expires_at'] : null;
            } else {
                $this->modalErrorMessage = $this->genericResultMessage($result, 'pairing');
            }
        } catch (Throwable $exception) {
            $this->modalErrorMessage = __('ui.waha.pairing_unavailable');
        } finally {
            $this->modalLoading = false;
        }
    }

    public function startSession(string $sessionCode): void
    {
        $this->executeLifecycleAction('start', $sessionCode);
    }

    public function stopSession(string $sessionCode): void
    {
        $this->executeLifecycleAction('stop', $sessionCode);
    }

    public function restartSession(string $sessionCode): void
    {
        $this->executeLifecycleAction('restart', $sessionCode);
    }

    public function logoutSession(string $sessionCode): void
    {
        $this->authorizeManageSession($sessionCode);

        try {
            $service = app(WahaSessionService::class);
            $result = $service->logoutSession($sessionCode);

            $this->loadSessions(true);

            if ($result['ok'] ?? false) {
                Notification::make()
                    ->title(__('ui.pages.waha_connection.logout_success_title'))
                    ->body(__('ui.pages.waha_connection.logout_success', ['session' => $sessionCode]))
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title(__('ui.pages.waha_connection.logout_failed_title'))
                    ->danger()
                    ->send();
            }
        } catch (Throwable $exception) {
            $this->loadSessions(true);
            Notification::make()
                ->title(__('ui.pages.waha_connection.logout_failed_title'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function executeLifecycleAction(string $action, string $sessionCode): void
    {
        $this->authorizeManageSession($sessionCode);

        try {
            $service = app(WahaSessionService::class);
            $result = match ($action) {
                'start' => $service->startSession($sessionCode),
                'stop' => $service->stopSession($sessionCode),
                'restart' => $service->restartSession($sessionCode),
                default => ['ok' => false, 'message' => __('ui.waha.mutation_failed')],
            };

            $this->loadSessions(true);

            if ($result['ok'] ?? false) {
                Notification::make()
                    ->title(__('ui.pages.waha_connection.action_success', [
                        'action' => strtoupper($action),
                        'session' => $sessionCode,
                    ]))
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title(__('ui.pages.waha_connection.action_failed', [
                        'action' => strtoupper($action),
                        'session' => $sessionCode,
                    ]))
                    ->body($this->genericResultMessage($result, 'lifecycle'))
                    ->danger()
                    ->send();
            }
        } catch (Throwable $exception) {
            $this->loadSessions(true);
            Notification::make()
                ->title(__('ui.pages.waha_connection.action_failed', [
                    'action' => strtoupper($action),
                    'session' => $sessionCode,
                ]))
                ->danger()
                ->send();
        }
    }

    private function authorizeManage(): void
    {
        abort_unless($this->canManageSession(), 403);
    }

    public function clearExpiredAuthenticationArtifacts(): void
    {
        if ($this->artifactHasExpired($this->qrCodeExpiresAt)) {
            $this->qrCodePayload = null;
            $this->qrCodeExpiresAt = null;
            $this->modalErrorMessage = __('ui.waha.qr_unavailable');

            return;
        }

        if ($this->artifactHasExpired($this->pairingCodeExpiresAt)) {
            $this->pairingCodePayload = null;
            $this->pairingCodeExpiresAt = null;
            $this->modalErrorMessage = __('ui.waha.pairing_unavailable');

            return;
        }

        if ($this->activeModalSession) {
            // Check if active modal session is already connected
            $service = app(WahaSessionService::class);
            $liveStatus = $service->getSessionStatus($this->activeModalSession, true);

            if (($liveStatus['status'] ?? null) === WahaSessionService::STATUS_RUNNING) {
                $connectedNumber = $liveStatus['connected_number'] ?? '';
                $this->clearAuthenticationArtifacts();
                $this->dispatch('close-modal', id: 'whatsapp-auth-modal');
                $this->loadSessions(true);

                Notification::make()
                    ->title(__('ui.pages.waha_connection.authenticated_success_title'))
                    ->body(__('ui.pages.waha_connection.authenticated_success_body', ['number' => $connectedNumber]))
                    ->success()
                    ->send();
            }
        }
    }

    public function clearAuthenticationArtifacts(): void
    {
        $this->activeModalSession = null;
        $this->activeModalSessionName = null;
        $this->activeModalTab = 'qr';
        $this->qrCodePayload = null;
        $this->qrCodeExpiresAt = null;
        $this->pairingPhoneNumber = null;
        $this->pairingCodePayload = null;
        $this->pairingCodeExpiresAt = null;
        $this->modalErrorMessage = null;
        $this->modalLoading = false;
    }

    private function authorizeManageSession(string $sessionCode): void
    {
        $this->authorizeManage();

        $sessionCode = trim($sessionCode);
        $isActiveSession = $sessionCode !== '' && DB::table('MSesiWhatsapp')
            ->where('KodeSesi', $sessionCode)
            ->where(function ($query): void {
                $query->where('NonAktif', false)->orWhereNull('NonAktif');
            })
            ->exists();

        abort_unless($isActiveSession, 403);
    }

    private function clearAuthenticationArtifactsForRunningSession(): void
    {
        if (! $this->activeModalSession) {
            return;
        }

        foreach ($this->sessions as $session) {
            if ($session['code'] === $this->activeModalSession && ($session['live']['status'] ?? null) === WahaSessionService::STATUS_RUNNING) {
                $this->clearAuthenticationArtifacts();
                $this->dispatch('close-modal', id: 'whatsapp-auth-modal');

                return;
            }
        }
    }

    private function artifactHasExpired(?string $expiresAt): bool
    {
        return $expiresAt !== null && now()->greaterThanOrEqualTo($expiresAt);
    }

    /** @param array<string, mixed> $result */
    private function genericResultMessage(array $result, string $context): string
    {
        $errorCategory = (string) ($result['error_category'] ?? '');

        return match ($context) {
            'qr' => __('ui.waha.qr_unavailable'),
            'pairing' => $errorCategory === 'validation'
                ? __('ui.waha.phone_invalid')
                : __('ui.waha.pairing_unavailable'),
            'lifecycle' => match ($errorCategory) {
                'busy' => __('ui.waha.mutation_in_progress'),
                'unavailable', 'authentication', 'malformed_response' => __('ui.waha.unavailable'),
                'validation' => __('ui.waha.session_required'),
                default => __('ui.waha.mutation_failed'),
            },
            default => __('ui.waha.unavailable'),
        };
    }

    /** @param array<string, mixed> $status */
    private function safeLiveStatus(array $status): array
    {
        if (($status['error_category'] ?? null) === 'disabled') {
            $status['message'] = __('ui.pages.waha_connection.disabled_in_wacs');

            return $status;
        }

        $status['message'] = match ((string) ($status['status'] ?? 'unknown')) {
            WahaSessionService::STATUS_RUNNING => __('ui.waha.status_running'),
            WahaSessionService::STATUS_STARTING => __('ui.waha.status_starting'),
            WahaSessionService::STATUS_SCAN_REQUIRED => __('ui.waha.status_scan_required'),
            WahaSessionService::STATUS_STOPPED => __('ui.waha.status_stopped'),
            WahaSessionService::STATUS_FAILED => __('ui.waha.status_failed'),
            WahaSessionService::STATUS_UNAVAILABLE => __('ui.waha.unavailable'),
            default => __('ui.waha.status_unknown'),
        };

        return $status;
    }
}
