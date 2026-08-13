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

    public ?string $activeModalSession = null;

    public ?string $activeModalSessionName = null;

    public ?string $qrCodePayload = null;

    public ?string $pairingPhoneNumber = null;

    public ?string $pairingCodePayload = null;

    public ?string $modalErrorMessage = null;

    public bool $modalLoading = false;

    public function mount(): void
    {
        $this->loadSessions(true);
    }

    public function loadSessions(bool $forceRefresh = false): void
    {
        $this->isRefreshing = true;

        try {
            $service = app(WahaSessionService::class);
            $rows = DB::table('MSesiWhatsapp')
                ->orderByRaw("CASE WHEN KodeSesi = 'default' THEN 0 ELSE 1 END")
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
                        'capabilities' => ['qr' => false, 'pairing' => false, 'start' => false, 'stop' => false, 'restart' => false],
                        'checked_at' => now()->toIso8601String(),
                        'message' => __('ui.pages.waha_connection.disabled_in_wacs'),
                        'error_category' => 'disabled',
                        'http_status' => null,
                        'stale' => false,
                    ];

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
        } catch (Throwable $exception) {
            Notification::make()
                ->title(__('ui.pages.waha_connection.load_failed'))
                ->danger()
                ->send();
        } finally {
            $this->isRefreshing = false;
        }
    }

    public function openQrModal(string $sessionCode, string $sessionName): void
    {
        $this->authorizeManage();

        $this->activeModalSession = $sessionCode;
        $this->activeModalSessionName = $sessionName;
        $this->qrCodePayload = null;
        $this->modalErrorMessage = null;
        $this->modalLoading = true;

        $this->dispatch('open-modal', id: 'waha-qr-modal');
        $this->fetchQrCode();
    }

    public function fetchQrCode(): void
    {
        $this->authorizeManage();

        if (! $this->activeModalSession) {
            return;
        }

        $this->modalLoading = true;
        $this->modalErrorMessage = null;

        try {
            $service = app(WahaSessionService::class);
            $result = $service->getQrCode($this->activeModalSession);

            if (($result['ok'] ?? false) && isset($result['qr'])) {
                $this->qrCodePayload = (string) $result['qr'];
            } else {
                $this->modalErrorMessage = (string) ($result['message'] ?? __('ui.waha.qr_unavailable'));
            }
        } catch (Throwable $exception) {
            $this->modalErrorMessage = __('ui.waha.qr_unavailable');
        } finally {
            $this->modalLoading = false;
        }
    }

    public function openPairingModal(string $sessionCode, string $sessionName): void
    {
        $this->authorizeManage();

        $this->activeModalSession = $sessionCode;
        $this->activeModalSessionName = $sessionName;
        $this->pairingPhoneNumber = '';
        $this->pairingCodePayload = null;
        $this->modalErrorMessage = null;
        $this->modalLoading = false;

        $this->dispatch('open-modal', id: 'waha-pairing-modal');
    }

    public function submitPairingCode(): void
    {
        $this->authorizeManage();

        if (! $this->activeModalSession) {
            return;
        }

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
            } else {
                $this->modalErrorMessage = (string) ($result['message'] ?? __('ui.waha.pairing_unavailable'));
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

    private function executeLifecycleAction(string $action, string $sessionCode): void
    {
        $this->authorizeManage();

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
                    ->body((string) ($result['message'] ?? __('ui.waha.mutation_failed')))
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
}
