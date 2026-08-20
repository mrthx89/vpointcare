<x-filament-panels::page>
    <div class="space-y-6" wire:poll.5s="clearExpiredAuthenticationArtifacts">
        {{-- Header & Live Quick Status Hub --}}
        <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-400">
                            <x-filament::icon icon="heroicon-o-signal" class="h-5 w-5" aria-hidden="true" />
                        </div>
                        <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                            {{ __('ui.pages.waha_connection.center_title') }}
                        </h2>
                        @if ($gatewayLatencyMs !== null)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 shadow-sm ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30">
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                </span>
                                {{ __('ui.pages.waha_connection.gateway_connected_pill') }} • {{ $gatewayLatencyMs }}ms
                            </span>
                        @elseif ($gatewayStatus === 'unreachable')
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 shadow-sm ring-1 ring-inset ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300">
                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                {{ __('ui.pages.waha_connection.gateway_offline_pill') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('ui.pages.waha_connection.center_subtitle') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <x-filament::button
                        type="button"
                        color="gray"
                        outlined
                        size="sm"
                        icon="heroicon-m-signal"
                        wire:click="testGatewayConnection"
                        wire:loading.attr="disabled"
                        wire:target="testGatewayConnection"
                    >
                        <span wire:loading.remove wire:target="testGatewayConnection">{{ __('ui.pages.waha_connection.btn_test_gateway') }}</span>
                        <span wire:loading wire:target="testGatewayConnection">{{ __('ui.pages.waha_connection.testing_gateway') }}</span>
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        color="primary"
                        size="sm"
                        icon="heroicon-m-arrow-path"
                        wire:click="loadSessions(true)"
                        wire:loading.attr="disabled"
                        wire:target="loadSessions"
                    >
                        <span wire:loading.remove wire:target="loadSessions">{{ __('ui.common.refresh') }}</span>
                        <span wire:loading wire:target="loadSessions">{{ __('ui.common.refreshing') }}</span>
                    </x-filament::button>
                </div>
            </div>
        </div>

        @php
            $gatewayBadgeColor = match ($gatewayStatus) {
                'reachable' => 'success',
                'authentication_failed' => 'warning',
                'unreachable' => 'danger',
                default => 'gray',
            };
        @endphp

        {{-- Gateway Overview Card --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900" aria-labelledby="waha-gateway-overview-title">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-server-stack" class="h-5 w-5 text-gray-500 dark:text-gray-400" aria-hidden="true" />
                        <h3 id="waha-gateway-overview-title" class="text-sm font-bold text-gray-950 dark:text-white">
                            {{ __('ui.pages.waha_connection.gateway_overview_title') }}
                        </h3>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                        {{ __('ui.pages.waha_connection.gateway_overview_desc') }}
                    </p>
                </div>

                <x-filament::badge color="{{ $gatewayBadgeColor }}" size="sm" class="shrink-0 font-semibold uppercase tracking-wider">
                    {{ __('ui.pages.waha_connection.gateway_status_' . $gatewayStatus) }}
                </x-filament::badge>
            </div>

            <div class="mt-4 grid gap-3.5 md:grid-cols-3">
                {{-- Effective Base URL --}}
                <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-gray-800/80 dark:bg-gray-950/60 md:col-span-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('ui.pages.waha_connection.effective_base_url') }}
                        </span>
                        <button
                            type="button"
                            x-data="{ copied: false }"
                            x-on:click="navigator.clipboard.writeText('{{ $gatewayBaseUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="inline-flex items-center gap-1 text-[11px] font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 transition"
                            title="{{ __('ui.pages.waha_connection.copy_url') }}"
                        >
                            <template x-if="!copied">
                                <span class="flex items-center gap-1"><x-filament::icon icon="heroicon-m-clipboard" class="h-3.5 w-3.5" aria-hidden="true" /> {{ __('ui.common.copy') }}</span>
                            </template>
                            <template x-if="copied">
                                <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold"><x-filament::icon icon="heroicon-m-check" class="h-3.5 w-3.5" aria-hidden="true" /> {{ __('ui.pages.waha_connection.copied') }}</span>
                            </template>
                        </button>
                    </div>
                    <code class="mt-1.5 block break-all font-mono text-xs font-semibold text-gray-900 dark:text-gray-100 bg-white/70 dark:bg-gray-900/70 p-2 rounded-lg border border-gray-200/60 dark:border-gray-800/60 select-all">{{ $gatewayBaseUrl }}</code>
                    <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ __('ui.pages.waha_connection.effective_base_url_help') }}
                    </p>
                </div>

                {{-- API Key Status --}}
                <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-gray-800/80 dark:bg-gray-950/60 flex flex-col justify-between">
                    <div>
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('ui.pages.waha_connection.api_key_status') }}
                        </span>
                        <div class="mt-1.5 flex items-center gap-2 text-xs font-bold {{ $gatewayApiKeyConfigured ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400' }}">
                            <div class="flex h-6 w-6 items-center justify-center rounded-md {{ $gatewayApiKeyConfigured ? 'bg-emerald-100 dark:bg-emerald-950/60' : 'bg-amber-100 dark:bg-amber-950/60' }}">
                                <x-filament::icon icon="{{ $gatewayApiKeyConfigured ? 'heroicon-m-shield-check' : 'heroicon-m-exclamation-triangle' }}" class="h-4 w-4" aria-hidden="true" />
                            </div>
                            <span>{{ $gatewayApiKeyConfigured ? __('ui.pages.waha_connection.api_key_configured') : __('ui.pages.waha_connection.api_key_not_configured') }}</span>
                        </div>
                    </div>
                    <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                        {{ __('ui.pages.waha_connection.api_key_hidden_help') }}
                    </p>
                </div>
            </div>

            @if ($gatewayLatencyMs !== null || $gatewayHttpStatus !== null)
                <div class="mt-3.5 flex flex-wrap items-center gap-x-5 gap-y-1.5 border-t border-gray-100 pt-3 text-[11px] text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    @if ($gatewayLatencyMs !== null)
                        <div class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            <span>{{ __('ui.pages.waha_connection.gateway_latency') }}: <strong class="font-mono text-gray-800 dark:text-gray-200">{{ $gatewayLatencyMs }} ms</strong></span>
                        </div>
                    @endif
                    @if ($gatewayHttpStatus !== null)
                        <div class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full {{ $gatewayHttpStatus >= 200 && $gatewayHttpStatus < 300 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                            <span>{{ __('ui.pages.waha_connection.gateway_http_status') }}: <strong class="font-mono text-gray-800 dark:text-gray-200">{{ $gatewayHttpStatus }}</strong></span>
                        </div>
                    @endif
                </div>
            @endif
        </section>

        {{-- Connection Grid --}}
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($sessions as $session)
                @php
                    $live = $session['live'] ?? [];
                    $status = $live['status'] ?? 'unknown';
                    $ok = (bool) ($live['ok'] ?? false);
                    $capabilities = $live['capabilities'] ?? [];
                    $connectedNumber = $live['connected_number'] ?? $session['db_number'] ?? null;
                    $connectedName = $live['connected_name'] ?? null;

                    // Color tokens, icons & border styling
                    $cardBorder = 'border-gray-200 dark:border-gray-800';
                    $statusBadgeColor = 'gray';
                    $glowBg = 'bg-gray-100 dark:bg-gray-800/40';
                    $iconColor = 'text-gray-600 dark:text-gray-400';

                    if ($session['configured_active']) {
                        if ($status === 'running') {
                            $cardBorder = 'border-emerald-300 dark:border-emerald-700/60 shadow-sm';
                            $statusBadgeColor = 'success';
                            $glowBg = 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400';
                            $iconColor = 'text-emerald-600 dark:text-emerald-400';
                        } elseif ($status === 'scan_required') {
                            $cardBorder = 'border-amber-300 dark:border-amber-700/60 shadow-sm';
                            $statusBadgeColor = 'warning';
                            $glowBg = 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400';
                            $iconColor = 'text-amber-600 dark:text-amber-400';
                        } elseif (in_array($status, ['stopped', 'starting'])) {
                            $cardBorder = 'border-blue-300 dark:border-blue-700/60 shadow-sm';
                            $statusBadgeColor = 'info';
                            $glowBg = 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400';
                            $iconColor = 'text-blue-600 dark:text-blue-400';
                        } else {
                            $cardBorder = 'border-rose-300 dark:border-rose-700/60 shadow-sm';
                            $statusBadgeColor = 'danger';
                            $glowBg = 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400';
                            $iconColor = 'text-rose-600 dark:text-rose-400';
                        }
                    } else {
                        $cardBorder = 'border-gray-200 dark:border-gray-800 opacity-70';
                    }
                @endphp

                <div class="flex flex-col justify-between rounded-2xl border bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-md dark:bg-gray-900 {{ $cardBorder }}">
                    <div class="space-y-5">
                        {{-- Card Header --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $glowBg }} border border-black/5 dark:border-white/5">
                                    <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-6 w-6 {{ $iconColor }}" aria-hidden="true" />
                                </div>
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-bold text-gray-950 dark:text-white" title="{{ $session['name'] }}">
                                        {{ $session['name'] }}
                                    </h3>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ __('ui.pages.waha_connection.session_code') }}:</span>
                                        <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ $session['code'] }}</code>
                                    </div>
                                </div>
                            </div>

                            <x-filament::badge color="{{ $statusBadgeColor }}" size="sm" class="shrink-0 font-semibold uppercase tracking-wider">
                                {{ __('ui.pages.waha_connection.status_' . $status) }}
                            </x-filament::badge>
                        </div>

                        {{-- Device Status Display Card --}}
                        <div class="rounded-xl border border-gray-100 bg-gray-50/90 p-3.5 dark:border-gray-800/80 dark:bg-gray-800/40">
                            @if ($status === 'running' && !empty($connectedNumber))
                                <div class="flex items-center justify-between gap-2">
                                    <div class="space-y-0.5 min-w-0">
                                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.connected_device') }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="relative flex h-2 w-2 shrink-0">
                                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                            </span>
                                            <span class="font-mono text-sm font-bold text-gray-950 dark:text-white truncate">+{{ $connectedNumber }}</span>
                                        </div>
                                    </div>
                                    @if ($connectedName)
                                        <span class="shrink-0 rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 shadow-sm border border-gray-200/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                            {{ $connectedName }}
                                        </span>
                                    @endif
                                </div>
                            @elseif ($status === 'scan_required')
                                <div class="flex items-center justify-between gap-2">
                                    <div class="space-y-0.5 min-w-0">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-3.5 w-3.5" aria-hidden="true" />
                                            {{ __('ui.pages.waha_connection.auth_required') }}
                                        </span>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-snug">{{ __('ui.pages.waha_connection.scan_to_connect') }}</p>
                                    </div>
                                    <span class="relative flex h-2.5 w-2.5 shrink-0">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                    </span>
                                </div>
                            @elseif (in_array($status, ['stopped', 'starting']))
                                <div class="flex items-center justify-between gap-2">
                                    <div class="space-y-0.5">
                                        <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.device_disconnected') }}</span>
                                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ __('ui.pages.waha_connection.status_' . $status) }}</p>
                                    </div>
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                </div>
                            @else
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.device_disconnected') }}</span>
                                    <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                                </div>
                            @endif
                        </div>

                        {{-- Metadata Details --}}
                        <div class="space-y-2 text-xs divide-y divide-gray-100 dark:divide-gray-800/80">
                            {{-- Webhook Sync Status --}}
                            <div class="flex items-center justify-between pt-1 text-gray-600 dark:text-gray-400">
                                <span>{{ __('ui.pages.waha_connection.webhook_status') }}</span>
                                <span class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                                    <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4" aria-hidden="true" />
                                    {{ __('ui.pages.waha_connection.webhook_synced') }}
                                </span>
                            </div>

                            {{-- Session Base URL --}}
                            <div class="flex items-center justify-between gap-3 pt-2 text-gray-600 dark:text-gray-400">
                                <span>{{ __('ui.pages.waha_connection.session_base_url') }}</span>
                                <code class="max-w-[65%] truncate text-right font-mono text-[11px] font-medium text-gray-800 dark:text-gray-200" title="{{ $session['base_url'] }}">{{ $session['base_url'] }}</code>
                            </div>

                            {{-- Last Checked --}}
                            @if (!empty($live['checked_at']))
                                <div class="flex items-center justify-between pt-2 text-gray-600 dark:text-gray-400">
                                    <span>{{ __('ui.pages.waha_connection.last_checked') }}</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">
                                        {{ \Carbon\Carbon::parse($live['checked_at'])->locale(app()->getLocale())->diffForHumans() }}
                                    </span>
                                </div>
                            @endif

                            {{-- Diagnostic Message if error --}}
                            @if (!$ok && !empty($live['message']))
                                <div class="pt-2">
                                    <div class="rounded-lg bg-rose-50/90 p-2.5 text-[11px] leading-relaxed text-rose-700 dark:bg-rose-950/30 dark:text-rose-300 border border-rose-200/60 dark:border-rose-900/60">
                                        {{ $live['message'] }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- URL Alignment Banner (If misconfigured) --}}
                    @if ($this->canManageSession() && $session['configured_active'] && $session['misconfigured_base_url'])
                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-3.5 dark:border-amber-900/50 dark:bg-amber-950/20">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">
                                        {{ __('ui.pages.waha_connection.misconfigured_url_warning') }}
                                    </p>
                                    <p class="text-[11px] text-amber-700/80 dark:text-amber-300/80">
                                        {{ __('ui.pages.waha_connection.align_base_url_help', ['url' => $gatewayBaseUrl]) }}
                                    </p>
                                </div>

                                <x-filament::button
                                    type="button"
                                    size="xs"
                                    color="warning"
                                    icon="heroicon-m-arrows-right-left"
                                    wire:click="alignSessionBaseUrl('{{ $session['code'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="alignSessionBaseUrl('{{ $session['code'] }}')"
                                    wire:confirm="{{ __('ui.pages.waha_connection.confirm_align_base_url') }}"
                                >
                                    <span wire:loading.remove wire:target="alignSessionBaseUrl('{{ $session['code'] }}')">{{ __('ui.pages.waha_connection.btn_align_base_url') }}</span>
                                    <span wire:loading wire:target="alignSessionBaseUrl('{{ $session['code'] }}')">{{ __('ui.pages.waha_connection.aligning_base_url') }}</span>
                                </x-filament::button>
                            </div>
                        </div>
                    @endif

                    {{-- Actions Toolbar --}}
                    @if ($this->canManageSession() && $session['configured_active'] && !$session['misconfigured_base_url'])
                        <div class="mt-6 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                            {{-- Left Action: Auto-Sync Webhook --}}
                            <x-filament::button
                                type="button"
                                size="xs"
                                color="gray"
                                outlined
                                icon="heroicon-m-bolt"
                                wire:click="syncWebhookAuto('{{ $session['code'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="syncWebhookAuto('{{ $session['code'] }}')"
                                title="{{ __('ui.pages.waha_connection.btn_sync_webhook_desc') }}"
                            >
                                <span wire:loading.remove wire:target="syncWebhookAuto('{{ $session['code'] }}')">{{ __('ui.pages.waha_connection.btn_sync_webhook') }}</span>
                                <span wire:loading wire:target="syncWebhookAuto('{{ $session['code'] }}')">{{ __('ui.pages.waha_connection.syncing_webhook') }}</span>
                            </x-filament::button>

                            {{-- Right Actions --}}
                            <div class="flex items-center gap-1.5">
                                {{-- If Scan Required: Show QR & PIN Buttons --}}
                                @if ($status === 'scan_required')
                                    @if ($capabilities['qr'] ?? true)
                                        <x-filament::button
                                            type="button"
                                            size="xs"
                                            color="primary"
                                            icon="heroicon-m-qr-code"
                                            wire:click="openQrModal('{{ $session['code'] }}', '{{ $session['name'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="openQrModal('{{ $session['code'] }}', '{{ $session['name'] }}')"
                                        >
                                            <span wire:loading.remove wire:target="openQrModal('{{ $session['code'] }}', '{{ $session['name'] }}')">{{ __('ui.pages.waha_connection.btn_qr') }}</span>
                                            <span wire:loading wire:target="openQrModal('{{ $session['code'] }}', '{{ $session['name'] }}')">{{ __('ui.pages.waha_connection.loading_artifact') }}</span>
                                        </x-filament::button>
                                    @else
                                        <x-filament::button type="button" size="xs" color="gray" outlined icon="heroicon-m-qr-code" disabled>
                                            {{ __('ui.pages.waha_connection.btn_qr') }}
                                        </x-filament::button>
                                    @endif

                                    @if ($capabilities['pairing'] ?? true)
                                        <x-filament::button
                                            type="button"
                                            size="xs"
                                            color="gray"
                                            outlined
                                            icon="heroicon-m-key"
                                            wire:click="openPairingModal('{{ $session['code'] }}', '{{ $session['name'] }}')"
                                        >
                                            {{ __('ui.pages.waha_connection.btn_pairing') }}
                                        </x-filament::button>
                                    @endif
                                @endif

                                @if ($status !== 'running' && $status !== 'scan_required')
                                    <x-filament::button
                                        type="button"
                                        size="xs"
                                        color="gray"
                                        outlined
                                        icon="heroicon-m-qr-code"
                                        disabled
                                        title="{{ __('ui.pages.waha_connection.qr_after_start_hint') }}"
                                    >
                                        {{ __('ui.pages.waha_connection.btn_qr') }}
                                    </x-filament::button>
                                @endif

                                {{-- If Running: Show Logout / Restart --}}
                                @if ($status === 'running')
                                    @if ($capabilities['restart'] ?? true)
                                        <x-filament::button
                                            type="button"
                                            size="xs"
                                            color="gray"
                                            outlined
                                            wire:click="restartSession('{{ $session['code'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="restartSession('{{ $session['code'] }}')"
                                            wire:confirm="{{ __('ui.pages.waha_connection.confirm_restart') }}"
                                        >
                                            <span wire:loading.remove wire:target="restartSession('{{ $session['code'] }}')">{{ __('ui.pages.waha_connection.btn_restart') }}</span>
                                            <span wire:loading wire:target="restartSession('{{ $session['code'] }}')">{{ __('ui.pages.waha_connection.restarting_session') }}</span>
                                        </x-filament::button>
                                    @endif

                                    <x-filament::button
                                        type="button"
                                        size="xs"
                                        color="danger"
                                        outlined
                                        wire:click="logoutSession('{{ $session['code'] }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="logoutSession('{{ $session['code'] }}')"
                                        wire:confirm="{{ __('ui.pages.waha_connection.confirm_logout') }}"
                                    >
                                        <span wire:loading.remove wire:target="logoutSession('{{ $session['code'] }}')">{{ __('ui.pages.waha_connection.btn_logout') }}</span>
                                        <span wire:loading wire:target="logoutSession('{{ $session['code'] }}')">{{ __('ui.pages.waha_connection.disconnecting_device') }}</span>
                                    </x-filament::button>
                                @endif

                                {{-- If Stopped/Failed: Show Start Session --}}
                                @if (in_array($status, ['stopped', 'failed', 'unknown']))
                                    <x-filament::button
                                        type="button"
                                        size="xs"
                                        color="success"
                                        icon="heroicon-m-play"
                                        wire:click="startSession('{{ $session['code'] }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="startSession('{{ $session['code'] }}')"
                                    >
                                        <span wire:loading.remove wire:target="startSession('{{ $session['code'] }}')">{{ __('ui.pages.waha_connection.btn_start') }}</span>
                                        <span wire:loading wire:target="startSession('{{ $session['code'] }}')">{{ __('ui.pages.waha_connection.starting_session') }}</span>
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>

                        @if ($status !== 'running' && $status !== 'scan_required')
                            <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                                {{ __('ui.pages.waha_connection.qr_after_start_hint') }}
                            </p>
                        @endif
                    @endif
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-800">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                        <x-filament::icon icon="heroicon-o-signal-slash" class="h-8 w-8" aria-hidden="true" />
                    </div>
                    <h3 class="mt-4 text-base font-bold text-gray-950 dark:text-white">{{ __('ui.pages.waha_connection.no_sessions') }}</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto leading-relaxed">{{ __('ui.pages.waha_connection.no_sessions_desc') }}</p>
                    @if ($this->canManageSession())
                        <x-filament::button
                            type="button"
                            class="mt-5"
                            size="sm"
                            icon="heroicon-m-plus"
                            wire:click="initializeDefaultSession"
                            wire:loading.attr="disabled"
                            wire:target="initializeDefaultSession"
                        >
                            <span wire:loading.remove wire:target="initializeDefaultSession">{{ __('ui.pages.waha_connection.btn_initialize_default') }}</span>
                            <span wire:loading wire:target="initializeDefaultSession">{{ __('ui.pages.waha_connection.initializing_default') }}</span>
                        </x-filament::button>
                    @endif
                </div>
            @endforelse
        </div>

        {{-- Webhook & Gateway Diagnostics Footer Panel --}}
        <div class="grid gap-6 md:grid-cols-2">
            {{-- Webhook Configuration Card --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400">
                            <x-filament::icon icon="heroicon-o-link" class="h-4 w-4" aria-hidden="true" />
                        </div>
                        <h3 class="text-sm font-bold text-gray-950 dark:text-white">{{ __('ui.pages.waha_connection.webhook_panel_title') }}</h3>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        {{ __('ui.pages.waha_connection.auto_configured') }}
                    </span>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('ui.pages.waha_connection.webhook_url_label') }}</label>
                    <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50/90 px-3 py-2 font-mono text-xs text-gray-800 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300 select-all">
                        <span class="truncate flex-1">{{ $webhookUrl }}</span>
                        <button
                            type="button"
                            x-data="{ copied: false }"
                            x-on:click="navigator.clipboard.writeText('{{ $webhookUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition shrink-0"
                            title="{{ __('ui.pages.waha_connection.copy_url') }}"
                        >
                            <template x-if="!copied">
                                <span class="flex items-center gap-1 text-[11px] text-primary-600 dark:text-primary-400 font-medium">
                                    <x-filament::icon icon="heroicon-m-clipboard-document" class="h-4 w-4" aria-hidden="true" />
                                    {{ __('ui.common.copy') }}
                                </span>
                            </template>
                            <template x-if="copied">
                                <span class="flex items-center gap-1 text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">
                                    <x-filament::icon icon="heroicon-m-check" class="h-4 w-4" aria-hidden="true" />
                                    {{ __('ui.pages.waha_connection.copied') }}
                                </span>
                            </template>
                        </button>
                    </div>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                    {{ __('ui.pages.waha_connection.webhook_panel_desc') }}
                </p>
            </div>

            {{-- Webhook Traffic Activity Card --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <x-filament::icon icon="heroicon-o-bolt" class="h-4 w-4" aria-hidden="true" />
                        </div>
                        <h3 class="text-sm font-bold text-gray-950 dark:text-white">{{ __('ui.pages.waha_connection.traffic_panel_title') }}</h3>
                    </div>
                    <span class="inline-flex items-center rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                        {{ $totalWebhooksToday }} {{ __('ui.pages.waha_connection.events_today') }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-gray-800/80 dark:bg-gray-800/40">
                        <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.last_event_received') }}</span>
                        <p class="mt-1 text-xs font-bold text-gray-900 dark:text-white">
                            @if ($lastWebhookReceivedAt)
                                {{ \Carbon\Carbon::parse($lastWebhookReceivedAt)->locale(app()->getLocale())->diffForHumans() }}
                            @else
                                <span class="text-gray-400 font-normal">{{ __('ui.common.never') }}</span>
                            @endif
                        </p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-gray-800/80 dark:bg-gray-800/40">
                        <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.gateway_health') }}</span>
                        <p class="mt-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            {{ __('ui.pages.waha_connection.listening') }}
                        </p>
                    </div>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                    {{ __('ui.pages.waha_connection.traffic_panel_desc') }}
                </p>
            </div>
        </div>
    </div>

    @if ($activeModalSession)
    {{-- Seamless WhatsApp Auth Modal (QR Code & Pairing PIN) --}}
    <x-filament::modal id="whatsapp-auth-modal" width="lg" alignment="center" x-on:close-modal.window="if ($event.detail.id === 'whatsapp-auth-modal') $wire.clearAuthenticationArtifacts()">
        <x-slot name="heading">
            <div class="flex items-center gap-2.5 text-base font-bold text-gray-950 dark:text-white">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-o-qr-code" class="h-4 w-4" aria-hidden="true" />
                </div>
                <span>{{ __('ui.pages.waha_connection.connect_heading', ['session' => $activeModalSessionName]) }}</span>
            </div>
        </x-slot>

        <div class="space-y-5" x-data>
            {{-- Tab Switcher --}}
            <div class="flex rounded-xl bg-gray-100/80 p-1 dark:bg-gray-800/80 border border-gray-200/50 dark:border-gray-700/50">
                <button
                    type="button"
                    wire:click="setModalTab('qr')"
                    class="flex-1 rounded-lg py-2 text-xs font-bold transition {{ $activeModalTab === 'qr' ? 'bg-white text-gray-950 shadow-sm dark:bg-gray-900 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
                >
                    <span class="flex items-center justify-center gap-1.5">
                        <x-filament::icon icon="heroicon-m-qr-code" class="h-4 w-4" aria-hidden="true" />
                        {{ __('ui.pages.waha_connection.tab_qr_scan') }}
                    </span>
                </button>
                <button
                    type="button"
                    wire:click="setModalTab('pairing')"
                    class="flex-1 rounded-lg py-2 text-xs font-bold transition {{ $activeModalTab === 'pairing' ? 'bg-white text-gray-950 shadow-sm dark:bg-gray-900 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
                >
                    <span class="flex items-center justify-center gap-1.5">
                        <x-filament::icon icon="heroicon-m-key" class="h-4 w-4" aria-hidden="true" />
                        {{ __('ui.pages.waha_connection.tab_pairing_code') }}
                    </span>
                </button>
            </div>

            {{-- QR Scanner Tab Content --}}
            @if ($activeModalTab === 'qr')
                <div class="flex flex-col items-center justify-center space-y-4 py-2">
                    @if ($modalLoading)
                        <div class="flex flex-col items-center space-y-3 py-12">
                            <x-filament::loading-indicator class="h-10 w-10 text-primary-600" />
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('ui.pages.waha_connection.loading_artifact') }}</span>
                        </div>
                    @elseif ($modalErrorMessage)
                        <div class="w-full rounded-xl border border-rose-200 bg-rose-50/90 p-5 text-center dark:border-rose-900/40 dark:bg-rose-950/20">
                            <p class="text-sm font-semibold text-rose-800 dark:text-rose-300">{{ $modalErrorMessage }}</p>
                            <x-filament::button
                                type="button"
                                size="sm"
                                color="gray"
                                outlined
                                class="mt-4"
                                wire:click="fetchQrCode"
                                wire:loading.attr="disabled"
                                wire:target="fetchQrCode"
                            >
                                <span wire:loading.remove wire:target="fetchQrCode">{{ __('ui.common.retry') }}</span>
                                <span wire:loading wire:target="fetchQrCode">{{ __('ui.pages.waha_connection.loading_artifact') }}</span>
                            </x-filament::button>
                        </div>
                    @elseif ($qrCodePayload)
                        {{-- QR Container with Modern Viewfinder Frame --}}
                        <div class="relative rounded-2xl border-2 border-primary-500/30 bg-white p-4 shadow-xl dark:border-primary-500/20 dark:bg-gray-900 ring-4 ring-primary-500/5">
                            @if (str_starts_with($qrCodePayload, 'data:image'))
                                <img src="{{ $qrCodePayload }}" alt="{{ __('ui.pages.waha_connection.qr_alt') }}" class="h-60 w-60 object-contain select-none rounded-lg" />
                            @else
                                <div class="flex h-60 w-60 items-center justify-center bg-gray-50 p-3 rounded-lg dark:bg-gray-800">
                                    <span class="break-all font-mono text-[9px] text-gray-600 dark:text-gray-300">{{ $qrCodePayload }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- 3-Step Scan Instructions --}}
                        <div class="w-full rounded-xl border border-gray-100 bg-gray-50/90 p-4 dark:border-gray-800 dark:bg-gray-800/40 space-y-2.5 text-xs">
                            <div class="font-bold text-gray-950 dark:text-white flex items-center gap-1.5">
                                <x-filament::icon icon="heroicon-m-device-phone-mobile" class="h-4 w-4 text-primary-600" aria-hidden="true" />
                                {{ __('ui.pages.waha_connection.scan_guide_title') }}
                            </div>
                            <ol class="space-y-2 text-gray-600 dark:text-gray-300 list-decimal list-inside leading-relaxed font-medium">
                                <li>{{ __('ui.pages.waha_connection.scan_guide_1') }}</li>
                                <li>{{ __('ui.pages.waha_connection.scan_guide_2') }}</li>
                                <li>{{ __('ui.pages.waha_connection.scan_guide_3') }}</li>
                            </ol>
                        </div>

                        <div class="flex items-center justify-between w-full pt-1">
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-1.5 font-medium">
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                </span>
                                {{ __('ui.pages.waha_connection.auto_detect_scan') }}
                            </span>

                            <x-filament::button
                                type="button"
                                color="gray"
                                outlined
                                size="xs"
                                icon="heroicon-m-arrow-path"
                                wire:click="fetchQrCode"
                                wire:loading.attr="disabled"
                                wire:target="fetchQrCode"
                            >
                                <span wire:loading.remove wire:target="fetchQrCode">{{ __('ui.common.refresh') }}</span>
                                <span wire:loading wire:target="fetchQrCode">{{ __('ui.common.refreshing') }}</span>
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            @endif

            {{-- 8-Digit Pairing Code Tab Content --}}
            @if ($activeModalTab === 'pairing')
                <div class="py-2 space-y-4">
                    @if ($modalLoading)
                        <div class="flex flex-col items-center justify-center space-y-3 py-12">
                            <x-filament::loading-indicator class="h-10 w-10 text-primary-600" />
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('ui.pages.waha_connection.generating_pairing_code') }}</span>
                        </div>
                    @elseif ($pairingCodePayload)
                        <div class="flex flex-col items-center justify-center space-y-4 text-center py-4">
                            <div class="rounded-2xl border-2 border-primary-500/40 bg-gradient-to-b from-primary-50 to-primary-100/40 px-8 py-5 shadow-sm dark:border-primary-500/30 dark:from-primary-950/40 dark:to-primary-900/20">
                                <span class="font-mono text-3xl font-extrabold tracking-widest text-primary-700 dark:text-primary-300 select-all">
                                    {{ $pairingCodePayload }}
                                </span>
                            </div>

                            <div class="space-y-1.5 max-w-sm">
                                <p class="text-xs font-bold text-gray-900 dark:text-gray-100">
                                    {{ __('ui.pages.waha_connection.pairing_code_guide') }}
                                </p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed">
                                    {{ __('ui.pages.waha_connection.pairing_code_desc') }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    x-data="{ copied: false }"
                                    x-on:click="navigator.clipboard.writeText('{{ $pairingCodePayload }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition"
                                >
                                    <template x-if="!copied">
                                        <span class="flex items-center gap-1"><x-filament::icon icon="heroicon-m-clipboard-document" class="h-4 w-4" aria-hidden="true" /> {{ __('ui.pages.waha_connection.copy_pairing_code') }}</span>
                                    </template>
                                    <template x-if="copied">
                                        <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400"><x-filament::icon icon="heroicon-m-check" class="h-4 w-4" aria-hidden="true" /> {{ __('ui.pages.waha_connection.copied') }}</span>
                                    </template>
                                </button>

                                <x-filament::button
                                    type="button"
                                    size="sm"
                                    color="gray"
                                    outlined
                                    wire:click="clearPairingPayload"
                                >
                                    {{ __('ui.pages.waha_connection.btn_input_other_number') }}
                                </x-filament::button>
                            </div>
                        </div>
                    @else
                        <form wire:submit.prevent="submitPairingCode" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                    {{ __('ui.pages.waha_connection.phone_label') }}
                                </label>
                                <x-filament::input.wrapper :valid="!$modalErrorMessage">
                                    <x-filament::input
                                        type="text"
                                        placeholder="628123456789"
                                        wire:model.defer="pairingPhoneNumber"
                                        required
                                        autofocus
                                    />
                                </x-filament::input.wrapper>
                                <p class="mt-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ __('ui.pages.waha_connection.phone_help') }}
                                </p>
                            </div>

                            @if ($modalErrorMessage)
                                <div class="rounded-xl border border-rose-200 bg-rose-50/90 p-3 text-xs font-medium text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/20 dark:text-rose-300">
                                    {{ $modalErrorMessage }}
                                </div>
                            @endif

                            <div class="flex items-center justify-end gap-2.5 pt-2">
                                <x-filament::button type="button" size="sm" color="gray" outlined x-on:click="$dispatch('close-modal', { id: 'whatsapp-auth-modal' })">
                                    {{ __('ui.common.cancel') }}
                                </x-filament::button>
                                
                                <x-filament::button type="submit" size="sm" color="primary" wire:loading.attr="disabled" wire:target="submitPairingCode">
                                    <span wire:loading.remove wire:target="submitPairingCode">{{ __('ui.pages.waha_connection.btn_generate_code') }}</span>
                                    <span wire:loading wire:target="submitPairingCode">{{ __('ui.pages.waha_connection.generating_pairing_code') }}</span>
                                </x-filament::button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </x-filament::modal>
    @endif
</x-filament-panels::page>
