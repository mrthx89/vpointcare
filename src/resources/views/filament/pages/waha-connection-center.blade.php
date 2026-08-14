<x-filament-panels::page>
    <div class="space-y-6" wire:poll.15s="clearExpiredAuthenticationArtifacts">
        {{-- Header Status Summary --}}
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div>
                <h2 class="text-lg font-bold text-gray-950 dark:text-white">{{ __('ui.pages.waha_connection.center_title') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.center_subtitle') }}</p>
            </div>
            <div>
                <x-filament::button
                    type="button"
                    color="gray"
                    outlined
                    icon="heroicon-m-arrow-path"
                    wire:click="loadSessions(true)"
                    wire:loading.attr="disabled"
                    class="relative"
                >
                    <span wire:loading.remove wire:target="loadSessions">{{ __('ui.common.refresh') }}</span>
                    <span wire:loading wire:target="loadSessions">{{ __('ui.common.refreshing') }}</span>
                </x-filament::button>
            </div>
        </div>

        {{-- Connection Grid --}}
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($sessions as $session)
                @php
                    $live = $session['live'] ?? [];
                    $status = $live['status'] ?? 'unknown';
                    $ok = (bool) ($live['ok'] ?? false);
                    $capabilities = $live['capabilities'] ?? [];
                    
                    // Card State Colors
                    $borderClass = 'border-gray-200 dark:border-gray-800';
                    $statusBadgeColor = 'gray';
                    
                    if ($session['configured_active']) {
                        if ($status === 'running') {
                            $borderClass = 'border-emerald-200 dark:border-emerald-800/40';
                            $statusBadgeColor = 'success';
                        } elseif ($status === 'scan_required') {
                            $borderClass = 'border-amber-200 dark:border-amber-800/40';
                            $statusBadgeColor = 'warning';
                        } elseif (in_array($status, ['stopped', 'starting'])) {
                            $borderClass = 'border-blue-200 dark:border-blue-800/40';
                            $statusBadgeColor = 'info';
                        } else {
                            $borderClass = 'border-rose-200 dark:border-rose-800/40';
                            $statusBadgeColor = 'danger';
                        }
                    } else {
                        $borderClass = 'border-gray-300 dark:border-gray-800 opacity-75';
                    }
                @endphp

                <div class="flex flex-col justify-between rounded-2xl border bg-white p-6 shadow-sm dark:bg-gray-900 {{ $borderClass }} transition hover:shadow-md">
                    {{-- Card Body --}}
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-x-4">
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-bold text-gray-950 dark:text-white" title="{{ $session['name'] }}">
                                    {{ $session['name'] }}
                                </h3>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('ui.pages.waha_connection.session_code') }}: <code class="rounded bg-gray-100 px-1 py-0.5 text-[10px] text-gray-800 dark:bg-gray-800 dark:text-gray-300">{{ $session['code'] }}</code>
                                </p>
                            </div>
                            
                            <x-filament::badge color="{{ $statusBadgeColor }}" size="sm" class="shrink-0 font-semibold uppercase">
                                {{ __('ui.pages.waha_connection.status_' . $status) }}
                            </x-filament::badge>
                        </div>

                        {{-- Metadata List --}}
                        <div class="divide-y divide-gray-100 text-xs dark:divide-gray-800">
                            {{-- Connected Number --}}
                            @if ($status === 'running' && !empty($live['connected_number']))
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.connected_number') }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">+{{ $live['connected_number'] }}</span>
                                </div>
                            @elseif ($session['configured_active'] && !empty($session['db_number']))
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.last_number') }}</span>
                                    <span class="font-medium text-gray-400 dark:text-gray-500">+{{ $session['db_number'] }}</span>
                                </div>
                            @endif

                            {{-- Base URL --}}
                            <div class="flex flex-col gap-y-1 py-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.base_url') }}</span>
                                    <span class="truncate max-w-[180px] font-mono text-[10px] text-gray-900 dark:text-gray-300" title="{{ $session['base_url'] }}">
                                        {{ $session['base_url'] }}
                                    </span>
                                </div>
                                @if ($session['misconfigured_base_url'])
                                    <div class="rounded-md bg-rose-50 p-1.5 text-[10px] text-rose-800 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-200/50 dark:border-rose-900/50">
                                        {{ __('ui.pages.waha_connection.misconfigured_url_warning') }}
                                    </div>
                                @endif
                            </div>

                            {{-- Last Checked --}}
                            @if (!empty($live['checked_at']))
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.last_checked') }}</span>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        {{ \Carbon\Carbon::parse($live['checked_at'])->locale(app()->getLocale())->diffForHumans() }}
                                    </span>
                                </div>
                            @endif

                            {{-- Diagnostics --}}
                            @if (!$ok && !empty($live['message']))
                                <div class="py-2">
                                    <p class="text-[10px] leading-relaxed text-rose-600 dark:text-rose-400">
                                        {{ $live['message'] }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Actions Footer --}}
                    @if ($this->canManageSession() && $session['configured_active'] && !$session['misconfigured_base_url'])
                        <div class="mt-6 border-t border-gray-100 pt-4 dark:border-gray-800 flex flex-wrap gap-2 justify-end">
                            {{-- Start Action --}}
                            @if ($capabilities['start'] ?? false)
                                <x-filament::button
                                    type="button"
                                    size="xs"
                                    color="success"
                                    wire:click="startSession('{{ $session['code'] }}')"
                                    wire:loading.attr="disabled"
                                >
                                    {{ __('ui.pages.waha_connection.btn_start') }}
                                </x-filament::button>
                            @endif

                            {{-- Stop Action --}}
                            @if ($capabilities['stop'] ?? false)
                                <x-filament::button
                                    type="button"
                                    size="xs"
                                    color="danger"
                                    wire:click="stopSession('{{ $session['code'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="{{ __('ui.pages.waha_connection.confirm_stop') }}"
                                >
                                    {{ __('ui.pages.waha_connection.btn_stop') }}
                                </x-filament::button>
                            @endif

                            {{-- Restart Action --}}
                            @if ($capabilities['restart'] ?? false)
                                <x-filament::button
                                    type="button"
                                    size="xs"
                                    color="warning"
                                    wire:click="restartSession('{{ $session['code'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="{{ __('ui.pages.waha_connection.confirm_restart') }}"
                                >
                                    {{ __('ui.pages.waha_connection.btn_restart') }}
                                </x-filament::button>
                            @endif

                            {{-- Authenticate Drawer Buttons --}}
                            @if ($status === 'scan_required')
                                @if ($capabilities['qr'] ?? true)
                                    <x-filament::button
                                        type="button"
                                        size="xs"
                                        color="primary"
                                        outlined
                                        icon="heroicon-m-qr-code"
                                        wire:click="openQrModal('{{ $session['code'] }}', '{{ $session['name'] }}')"
                                    >
                                        {{ __('ui.pages.waha_connection.btn_qr') }}
                                    </x-filament::button>
                                @endif

                                @if ($capabilities['pairing'] ?? true)
                                    <x-filament::button
                                        type="button"
                                        size="xs"
                                        color="primary"
                                        outlined
                                        icon="heroicon-m-phone"
                                        wire:click="openPairingModal('{{ $session['code'] }}', '{{ $session['name'] }}')"
                                    >
                                        {{ __('ui.pages.waha_connection.btn_pairing') }}
                                    </x-filament::button>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-800">
                    <x-filament::icon icon="heroicon-o-signal-slash" class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" />
                    <h3 class="mt-4 text-sm font-bold text-gray-900 dark:text-white">{{ __('ui.pages.waha_connection.no_sessions') }}</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.no_sessions_desc') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Ephemeral QR Code Modal --}}
    <x-filament::modal id="waha-qr-modal" width="md" alignment="center" x-on:close-modal.window="if ($event.detail.id === 'waha-qr-modal') $wire.clearAuthenticationArtifacts()">
        <x-slot name="heading">
            {{ __('ui.pages.waha_connection.qr_heading', ['session' => $activeModalSessionName]) }}
        </x-slot>

        <div
            class="flex flex-col items-center justify-center p-6 space-y-4"
            x-data
            x-init="@if ($qrCodeExpiresAt) { const delay = Math.max(0, new Date(@js($qrCodeExpiresAt)).getTime() - Date.now()); setTimeout(() => $wire.clearExpiredAuthenticationArtifacts(), delay); } @endif"
        >
            @if ($modalLoading)
                <div class="flex flex-col items-center space-y-2 py-8">
                    <x-filament::loading-indicator class="h-10 w-10 text-primary-600" />
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.loading_artifact') }}</span>
                </div>
            @elseif ($modalErrorMessage)
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-center dark:border-rose-900/40 dark:bg-rose-950/10">
                    <p class="text-sm font-medium text-rose-800 dark:text-rose-400">{{ $modalErrorMessage }}</p>
                    <x-filament::button
                        type="button"
                        size="sm"
                        color="gray"
                        outlined
                        class="mt-4"
                        wire:click="fetchQrCode"
                    >
                        {{ __('ui.common.retry') }}
                    </x-filament::button>
                </div>
            @elseif ($qrCodePayload)
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-inner dark:border-gray-800">
                    @if (str_starts_with($qrCodePayload, 'data:image'))
                        <img src="{{ $qrCodePayload }}" alt="{{ __('ui.pages.waha_connection.qr_heading', ['session' => $activeModalSessionName]) }}" class="h-64 w-64 object-contain select-none" />
                    @else
                        {{-- Fallback renderer if raw text returned --}}
                        <div class="flex h-64 w-64 items-center justify-center bg-gray-50 p-2 dark:bg-gray-800">
                            <span class="break-all font-mono text-[8px] text-gray-500">{{ $qrCodePayload }}</span>
                        </div>
                    @endif
                </div>

                <div class="text-center space-y-2">
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        {{ __('ui.pages.waha_connection.qr_scan_desc') }}
                    </p>
                    <p class="text-[10px] text-rose-500 font-medium">
                        {{ __('ui.pages.waha_connection.ephemeral_warning') }}
                    </p>
                </div>

                <x-filament::button
                    type="button"
                    color="gray"
                    outlined
                    size="sm"
                    icon="heroicon-m-arrow-path"
                    wire:click="fetchQrCode"
                    wire:loading.attr="disabled"
                >
                    {{ __('ui.common.refresh') }}
                </x-filament::button>
            @endif
        </div>
    </x-filament::modal>

    {{-- Ephemeral Pairing Code Modal --}}
    <x-filament::modal id="waha-pairing-modal" width="md" alignment="center" x-on:close-modal.window="if ($event.detail.id === 'waha-pairing-modal') $wire.clearAuthenticationArtifacts()">
        <x-slot name="heading">
            {{ __('ui.pages.waha_connection.pairing_heading', ['session' => $activeModalSessionName]) }}
        </x-slot>

        <div
            class="p-6 space-y-6"
            x-data
            x-init="@if ($pairingCodeExpiresAt) { const delay = Math.max(0, new Date(@js($pairingCodeExpiresAt)).getTime() - Date.now()); setTimeout(() => $wire.clearExpiredAuthenticationArtifacts(), delay); } @endif"
        >
            @if ($modalLoading)
                <div class="flex flex-col items-center justify-center space-y-2 py-8">
                    <x-filament::loading-indicator class="h-10 w-10 text-primary-600" />
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.pages.waha_connection.loading_artifact') }}</span>
                </div>
            @elseif ($pairingCodePayload)
                <div class="flex flex-col items-center justify-center space-y-4 text-center">
                    <div class="rounded-2xl border border-primary-200 bg-primary-50 px-6 py-4 dark:border-primary-800/40 dark:bg-primary-950/10">
                        <span class="font-mono text-3xl font-extrabold tracking-widest text-primary-700 dark:text-primary-400">
                            {{ $pairingCodePayload }}
                        </span>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            {{ __('ui.pages.waha_connection.pairing_code_desc') }}
                        </p>
                        <p class="text-[10px] text-rose-500 font-medium">
                            {{ __('ui.pages.waha_connection.ephemeral_warning') }}
                        </p>
                    </div>

                    <x-filament::button
                        type="button"
                        color="gray"
                        outlined
                        size="sm"
                        wire:click="$set('pairingCodePayload', null)"
                    >
                        {{ __('ui.common.back') }}
                    </x-filament::button>
                </div>
            @else
                <form wire:submit="submitPairingCode" class="space-y-4">
                    @if ($modalErrorMessage)
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/40 dark:bg-rose-950/10">
                            <p class="text-xs font-medium text-rose-800 dark:text-rose-400">{{ $modalErrorMessage }}</p>
                        </div>
                    @endif

                    <div class="space-y-2">
                        <label for="pairing-phone" class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ __('ui.pages.waha_connection.phone_label') }}
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                id="pairing-phone"
                                type="text"
                                wire:model="pairingPhoneNumber"
                                placeholder="628123456789"
                                required
                            />
                        </x-filament::input.wrapper>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400">
                            {{ __('ui.pages.waha_connection.phone_help') }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <x-filament::button
                            type="button"
                            color="gray"
                            outlined
                            x-on:click="$dispatch('close-modal', { id: 'waha-pairing-modal' })"
                        >
                            {{ __('ui.common.cancel') }}
                        </x-filament::button>
                        
                        <x-filament::button type="submit">
                            {{ __('ui.pages.waha_connection.btn_generate_code') }}
                        </x-filament::button>
                    </div>
                </form>
            @endif
        </div>
    </x-filament::modal>
</x-filament-panels::page>
