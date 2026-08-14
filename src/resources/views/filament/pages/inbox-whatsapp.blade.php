<x-filament-panels::page>
    {{-- Komponen utama: mengelola sound notifikasi + WS status --}}
    <div x-data="{
        soundOn: localStorage.getItem('wacs_sound') !== 'false',
        mobilePane: '{{ $selectedChatId ? 'conversation' : 'list' }}',
        detailsOpen: false,
        notificationPermission: 'default',
        checkNotificationPermission() {
            if ('Notification' in window) {
                this.notificationPermission = Notification.permission;
            } else {
                this.notificationPermission = 'unsupported';
            }
        },
        requestBrowserNotification() {
            if (!('Notification' in window)) return;
            Notification.requestPermission().then(permission => {
                this.notificationPermission = permission;
            });
        },
        wsOnline: false,
        reverbStatus: window.wahaGetReverbStatus ? window.wahaGetReverbStatus() : {
            state: 'unknown',
            message: @js(__('ui.pages.inbox.reverb_reason_client_missing')),
            reason: @js(__('ui.pages.inbox.reverb_reason_asset_inactive')),
            updatedAt: new Date().toISOString(),
        },
        toggleSound() {
            this.soundOn = !this.soundOn;
            localStorage.setItem('wacs_sound', String(this.soundOn));
        },
        updateReverbStatus(status) {
            this.reverbStatus = status;
            this.wsOnline = status.state === 'connected';
        },
        reverbBadgeClass() {
            if (this.reverbStatus.state === 'connected') return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300';
            if (this.reverbStatus.state === 'connecting' || this.reverbStatus.state === 'initialized') return 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300';
            if (this.reverbStatus.state === 'disconnected') return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300';
            return 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300';
        },
        reverbDotClass() {
            if (this.reverbStatus.state === 'connected') return 'bg-emerald-500';
            if (this.reverbStatus.state === 'connecting' || this.reverbStatus.state === 'initialized') return 'bg-blue-500';
            if (this.reverbStatus.state === 'disconnected') return 'bg-amber-500';
            return 'bg-red-500';
        },
        playSound() {
            if (!this.soundOn) return;
            try {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                if (!Ctx) return;
                const ctx = new Ctx();
                [
                    [880, 0],
                    [1100, 0.15]
                ].forEach(([freq, delay]) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.25, ctx.currentTime + delay);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + 0.35);
                    osc.start(ctx.currentTime + delay);
                    osc.stop(ctx.currentTime + delay + 0.35);
                });
            } catch (e) {}
        }
    }" x-init="window.wacsNotificationBodyCopy = @js(__('ui.pages.inbox.new_message_notification'));
    checkNotificationPermission();
    wsOnline = Boolean(window.wahaWsOnline);
    if (window.wahaGetReverbStatus) updateReverbStatus(window.wahaGetReverbStatus());
    setTimeout(() => {
        if (window.wahaGetReverbStatus) updateReverbStatus(window.wahaGetReverbStatus());
    }, 300);"
        @waha-new-message.window="if ($event.detail.isIncoming) playSound()" @waha-ws-connected.window="wsOnline = true"
        @waha-ws-disconnected.window="wsOnline = false"
        @wacs-reverb-status-changed.window="updateReverbStatus($event.detail)"
        class="wacs-inbox-shell flex flex-col gap-4" wire:poll.60s="loadInbox">


        {{-- Banner izin suara: muncul sekali sampai user klik --}}
        <div x-data="{
            shown: !localStorage.getItem('wacs_audio_allowed'),
            allowAudio() {
                try {
                    const Ctx = window.AudioContext || window.webkitAudioContext;
                    if (Ctx) {
                        const ctx = new Ctx();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        gain.gain.setValueAtTime(0.001, ctx.currentTime);
                        osc.start();
                        osc.stop(ctx.currentTime + 0.05);
                    }
                } catch (e) {}
                localStorage.setItem('wacs_audio_allowed', '1');
                this.shown = false;
            }
        }" x-show="shown" x-cloak
            class="flex shrink-0 flex-wrap items-center justify-between gap-x-6 gap-y-2 rounded-2xl border border-gray-300 bg-gray-100 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                <x-heroicon-o-bell class="h-5 w-5 shrink-0" aria-hidden="true" />
                <span>{{ __('ui.pages.inbox.sound_permission') }}</span>
            </div>
            <button type="button" x-on:click="allowAudio()"
                class="rounded-2xl border border-gray-400 bg-white px-4 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 active:scale-95 transition dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                {{ __('ui.pages.inbox.allow_sound') }}
            </button>
        </div>
        <div x-show="reverbStatus.state !== 'connected'" x-cloak
            class="flex shrink-0 flex-wrap items-center justify-between gap-x-6 gap-y-2 rounded-2xl border px-4 py-3 text-sm"
            :class="reverbBadgeClass()">
            <div class="min-w-0">
                <div class="flex items-center gap-2 font-semibold">
                    <span class="h-2.5 w-2.5 rounded-full" :class="reverbDotClass()"></span>
                    <span x-text="reverbStatus.message || @js(__('ui.pages.inbox.reverb_default_changed'))"></span>
                </div>
                <div class="mt-1 break-all text-xs opacity-80">
                    <span x-text="reverbStatus.reason || @js(__('ui.pages.inbox.reverb_default_reason'))"></span>
                    <template x-if="reverbStatus.wsUrl">
                        <span> &middot; <span x-text="reverbStatus.wsUrl"></span></span>
                    </template>
                </div>
            </div>
            <a href="{{ route('filament.admin.pages.log-data') }}"
                class="rounded-2xl border border-current px-3 py-1.5 text-xs font-semibold hover:bg-white/40 dark:hover:bg-white/10">
                {{ __('ui.pages.inbox.open_log_data') }}
            </a>
        </div>

        {{-- Embedded WAHA Live Session Statuses --}}
        <div
            class="flex shrink-0 flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center gap-3">
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                    {{ __('ui.pages.waha_connection.title') }}:
                </div>
                @forelse ($wahaStatuses as $sCode => $sInfo)
                    @php
                        $st = $sInfo['status'] ?? 'unknown';
                        $badgeStyle = match ($st) {
                            'running'
                                => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
                            'starting'
                                => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
                            'scan_required'
                                => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
                            'stopped'
                                => 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
                            'failed'
                                => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300',
                            default
                                => 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
                        };
                        $dotStyle = match ($st) {
                            'running' => 'bg-emerald-500',
                            'starting' => 'bg-blue-500',
                            'scan_required' => 'bg-amber-500',
                            'stopped' => 'bg-slate-400',
                            'failed' => 'bg-rose-500',
                            default => 'bg-red-500',
                        };
                        $statusLabel = match ($st) {
                            'running' => __('ui.pages.waha_connection.status_running'),
                            'starting' => __('ui.pages.waha_connection.status_starting'),
                            'scan_required' => __('ui.pages.waha_connection.status_scan_required'),
                            'stopped' => __('ui.pages.waha_connection.status_stopped'),
                            'failed' => __('ui.pages.waha_connection.status_failed'),
                            'unavailable' => __('ui.pages.waha_connection.status_unavailable'),
                            default => __('ui.pages.waha_connection.status_unknown'),
                        };
                    @endphp
                    <div
                        class="flex items-center gap-2 rounded-xl border px-3 py-1.5 text-xs font-semibold {{ $badgeStyle }}">
                        <span class="relative flex h-2.5 w-2.5">
                            @if ($st === 'running')
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            @endif
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full {{ $dotStyle }}"></span>
                        </span>
                        <span>{{ strtoupper($sCode) }}: {{ $statusLabel }}</span>

                        @if (in_array($st, ['scan_required', 'stopped', 'failed', 'unavailable'], true) &&
                                \App\Support\FilamentAccess::can(\App\Support\AccessPermissions::WAHA_SESSION_MANAGE))
                            <a href="{{ route('filament.admin.pages.waha-connection-center') }}"
                                class="ml-1 text-[11px] underline hover:opacity-80">
                                @if ($st === 'scan_required')
                                    {{ __('ui.pages.waha_connection.btn_qr') }}
                                @else
                                    {{ __('ui.pages.waha_connection.btn_reconnect') }}
                                @endif
                            </a>
                        @endif
                    </div>
                @empty
                    <div
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                        <span>{{ __('ui.pages.waha_connection.no_sessions') }}</span>
                    </div>
                @endforelse
            </div>
            @if (\App\Support\FilamentAccess::can(\App\Support\AccessPermissions::WAHA_SESSION_VIEW))
                <a href="{{ route('filament.admin.pages.waha-connection-center') }}"
                    class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                    {{ __('ui.pages.waha_connection.center_title') }} &rarr;
                </a>
            @endif
        </div>
        <div class="wacs-inbox-stats grid shrink-0 grid-cols-2 gap-2 sm:gap-3 md:grid-cols-3 xl:grid-cols-5">
            <div
                class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900 sm:rounded-2xl sm:p-4">
                <div class="truncate text-[11px] leading-4 text-gray-500 dark:text-gray-400 sm:text-sm">
                    {{ __('ui.pages.inbox.active_team') }}</div>
                <div class="mt-1 text-lg font-semibold text-emerald-600 sm:mt-2 sm:text-xl">{{ $activeAgents }}</div>
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900 sm:rounded-2xl sm:p-4">
                <div class="truncate text-[11px] leading-4 text-gray-500 dark:text-gray-400 sm:text-sm">
                    {{ __('ui.pages.inbox.total_chat') }}</div>
                <div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white sm:mt-2 sm:text-xl">
                    {{ $stats['baru'] ?? 0 }}</div>
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900 sm:rounded-2xl sm:p-4">
                <div class="truncate text-[11px] leading-4 text-gray-500 dark:text-gray-400 sm:text-sm">
                    {{ __('ui.pages.inbox.unread') }}</div>
                <div class="mt-1 text-lg font-semibold text-amber-600 sm:mt-2 sm:text-xl">
                    {{ $stats['belum_dibaca'] ?? 0 }}</div>
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900 sm:rounded-2xl sm:p-4">
                <div class="truncate text-[11px] leading-4 text-gray-500 dark:text-gray-400 sm:text-sm">
                    {{ __('ui.pages.inbox.group_chat') }}</div>
                <div class="mt-1 text-lg font-semibold text-blue-600 sm:mt-2 sm:text-xl">{{ $stats['grup'] ?? 0 }}
                </div>
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900 sm:rounded-2xl sm:p-4">
                <div class="truncate text-[11px] leading-4 text-gray-500 dark:text-gray-400 sm:text-sm">
                    {{ __('ui.pages.inbox.unmapped') }}</div>
                <div class="mt-1 text-lg font-semibold text-red-600 sm:mt-2 sm:text-xl">{{ $stats['unknown'] ?? 0 }}
                </div>
            </div>
        </div>

        {{-- Grid chat: mobile satu kolom, desktop tiga kolom --}}
        <div class="wacs-inbox-layout flex-1 min-h-0">
            {{-- Backdrop Drawer Kiri (Daftar Chat) di Mobile --}}
            <div x-show="mobilePane === 'list'" x-cloak @click="mobilePane = 'conversation'"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" aria-hidden="true"
                class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm md:hidden"></div>

            {{-- KOLOM KIRI / DRAWER KIRI: Daftar Chat --}}
            <section :class="mobilePane === 'list' ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
                aria-label="{{ __('ui.pages.inbox.chat_list') }}"
                class="wacs-inbox-chat-list wacs-inbox-chat-list--drawer flex min-h-0 flex-col overflow-hidden rounded-r-2xl md:rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-900 md:shadow-none">
                {{-- Header Daftar Chat --}}
                <div class="shrink-0 border-b border-gray-200 p-3 dark:border-gray-800">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <div class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ __('ui.pages.inbox.chat_list') }}</div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span x-show="wsOnline"
                                    class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span x-show="!wsOnline" class="inline-block w-2 h-2 rounded-full"
                                    :class="reverbDotClass()"></span>
                                <span class="text-xs text-gray-400"
                                    x-text="wsOnline ? @js(__('ui.pages.inbox.realtime_active')) : `${reverbStatus.state || @js(__('ui.pages.inbox.reverb_state_unknown'))} / ${@js(__('ui.pages.inbox.polling'))}`"></span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5 justify-end">
                            <button @click="toggleSound()" type="button"
                                title="{{ __('ui.pages.inbox.sound_toggle') }}"
                                class="shrink-0 flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-primary-500 active:scale-95"
                                :class="soundOn ?
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' :
                                    'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'">
                                <x-heroicon-o-bell x-show="soundOn" class="w-4 h-4" />
                                <x-heroicon-o-bell-slash x-show="!soundOn" class="w-4 h-4" x-cloak />
                                <span
                                    x-text="soundOn ? @js(__('ui.pages.inbox.sound_on')) : @js(__('ui.pages.inbox.sound_off'))"></span>
                            </button>

                            <button @click="requestBrowserNotification()" type="button"
                                title="{{ __('ui.pages.inbox.notifications_toggle') }}"
                                class="shrink-0 flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-primary-500 active:scale-95"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300': notificationPermission === 'granted',
                                    'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300': notificationPermission === 'default',
                                    'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300': notificationPermission === 'denied',
                                    'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400': notificationPermission === 'unsupported'
                                }">
                                <x-heroicon-o-chat-bubble-bottom-center-text class="w-4 h-4" />
                                <span
                                    x-text="notificationPermission === 'granted' ? @js(__('ui.pages.inbox.notifications_active')) : (notificationPermission === 'denied' ? @js(__('ui.pages.inbox.notifications_denied')) : (notificationPermission === 'unsupported' ? @js(__('ui.pages.inbox.notifications_unsupported')) : @js(__('ui.pages.inbox.notifications_enable'))))"></span>
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 space-y-3">
                        {{ $this->form }}
                        <div role="group" aria-label="{{ __('ui.pages.inbox.identity_mode') }}"
                            class="grid grid-cols-2 rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
                            <button type="button" wire:click="$set('identityDisplayMode', 'whatsapp')"
                                aria-pressed="{{ $identityDisplayMode === 'whatsapp' ? 'true' : 'false' }}"
                                class="rounded-md px-2 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 {{ $identityDisplayMode === 'whatsapp' ? 'bg-white text-primary-700 shadow-sm dark:bg-gray-700 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ __('ui.pages.inbox.identity_whatsapp') }}
                            </button>
                            <button type="button" wire:click="$set('identityDisplayMode', 'internal')"
                                aria-pressed="{{ $identityDisplayMode === 'internal' ? 'true' : 'false' }}"
                                class="rounded-md px-2 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 {{ $identityDisplayMode === 'internal' ? 'bg-white text-primary-700 shadow-sm dark:bg-gray-700 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ __('ui.pages.inbox.identity_internal') }}
                            </button>
                        </div>
                    </div>
                </div>
                {{-- List Chat: Scrollable --}}
                <div
                    class="min-h-0 flex-1 divide-y divide-gray-100 overflow-y-auto overflow-x-hidden dark:divide-gray-800">
                    @forelse ($chatRows as $chat)
                        @php
                            $identity = $chat['Identity'][$identityDisplayMode] ?? $chat['Identity']['whatsapp'];
                        @endphp
                        <button type="button" wire:click="selectChat('{{ $chat['Id'] }}')"
                            @click="mobilePane = 'conversation'"
                            class="block w-full p-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/60
                                    {{ $selectedChatId === $chat['Id'] ? 'bg-blue-50 dark:bg-blue-950/30 border-l-[3px] border-l-blue-500' : 'border-l-[3px] border-l-transparent' }}">
                            {{-- Layout item chat: Avatar + Info --}}
                            <div class="flex items-start gap-3">
                                {{-- Avatar inisial --}}
                                @if ($chat['FotoProfilUrl'] ?? null)
                                    <img src="{{ $chat['FotoProfilUrl'] }}" alt=""
                                        class="shrink-0 h-9 w-9 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                                @else
                                    <div
                                        class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold
                                            {{ $chat['BelumDibaca'] > 0 ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ mb_strtoupper(mb_substr($identity['PrimaryName'] ?: '?', 0, 2)) }}
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-1">
                                        <div
                                            class="truncate text-sm font-semibold text-gray-950 dark:text-white leading-tight">
                                            {{ $identity['PrimaryName'] ?: '-' }}
                                        </div>
                                        @if ($chat['BelumDibaca'] > 0)
                                            <div
                                                class="shrink-0 min-w-[1.2rem] h-5 rounded-full bg-emerald-500 px-1.5 flex items-center justify-center text-xs font-bold text-white">
                                                {{ min($chat['BelumDibaca'], 99) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="truncate text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $chat['JenisChat'] === 'Grup' ? __('ui.pages.inbox.whatsapp_group') : __('ui.pages.inbox.personal_chat') }}
                                    </div>
                                    <div class="truncate font-mono text-[11px] text-gray-400 dark:text-gray-500">
                                        {{ $chat['JenisChat'] === 'Grup' ? ($identity['GroupId'] ?: '-') : ($identity['ContactNumber'] ?: $identity['ChatId'] ?: '-') }}
                                    </div>
                                    <div class="mt-1 line-clamp-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $chat['PesanTerakhir'] }}
                                    </div>
                                    <div class="mt-2 flex flex-wrap items-center gap-1">
                                        {{-- Status badge --}}
                                        @php
                                            $statusCode = strtoupper((string) ($chat['StatusCode'] ?? ''));
                                            $statusColor = match ($statusCode) {
                                                'DALAM_PROSES' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
                                                'SELESAI', 'DITUTUP' => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                                                'MENUNGGU_CS', 'MENUNGGU_CUSTOMER' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                                                default
                                                    => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                                            };
                                        @endphp
                                        <span
                                            class="rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $statusColor }}">{{ $chat['Status'] }}</span>
                                        {{-- Handler badge --}}
                                        @if ($chat['DiambilNamaCS'] ?? null)
                                            <span
                                                class="rounded px-1.5 py-0.5 text-[10px] font-semibold
                                                    {{ $chat['DiambilOlehSaya'] ?? false ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300' }}">
                                                <x-heroicon-o-user class="mr-1 inline h-3 w-3 align-[-2px]"
                                                    aria-hidden="true" />
                                                {{ $chat['DiambilOlehSaya'] ?? false ? __('ui.pages.inbox.you') : $chat['DiambilNamaCS'] }}
                                            </span>
                                        @endif
                                        {{-- AI badge --}}
                                        @if ($chat['AutoReplyAiAktif'])
                                            <span
                                                class="rounded px-1.5 py-0.5 text-[10px] font-semibold bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">
                                                AI</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="p-8 text-center">
                            <x-heroicon-o-chat-bubble-bottom-center-text class="mx-auto mb-2 h-7 w-7 text-gray-400"
                                aria-hidden="true" />
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ __('ui.pages.inbox.no_chat') }}</div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                {{ __('ui.pages.inbox.send_waha_webhook') }}</div>
                        </div>
                    @endforelse
                </div>
                @if ($this->canReplyInbox())
                    <button type="button" wire:click="openStartChatDialog"
                        title="{{ __('ui.pages.inbox.create_chat') }}"
                        aria-label="{{ __('ui.pages.inbox.create_chat') }}"
                        class="absolute bottom-4 right-4 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-primary-600 text-white ring-1 ring-primary-500/40 transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:ring-primary-400/30">
                        <x-heroicon-o-plus class="h-6 w-6" />
                    </button>
                @endif
            </section>

            {{-- KOLOM TENGAH: Ruang Percakapan --}}
            <section :class="{ 'hidden': mobilePane !== 'conversation', 'flex': mobilePane === 'conversation' }"
                class="wacs-inbox-conversation relative md:!flex flex min-h-0 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                @if ($selectedChat)
                    @php
                        $selectedIdentity =
                            $selectedChat['Identity'][$identityDisplayMode] ?? $selectedChat['Identity']['whatsapp'];
                    @endphp
                    {{-- Header Chat: Tidak Ikut Scroll --}}
                    <div
                        class="shrink-0 flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
                        <div class="flex min-w-0 items-center gap-3">
                            <button type="button" @click="mobilePane = 'list'"
                                aria-label="{{ __('ui.pages.inbox.chat_list') }}"
                                class="md:hidden flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                                title="{{ __('ui.pages.inbox.chat_list') }}">
                                <x-heroicon-o-bars-3 class="w-6 h-6" />
                            </button>
                            @if ($selectedChat['FotoProfilUrl'] ?? null)
                                <img src="{{ $selectedChat['FotoProfilUrl'] }}" alt=""
                                    class="h-11 w-11 shrink-0 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                            @else
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ mb_strtoupper(mb_substr($selectedIdentity['PrimaryName'] ?: '?', 0, 2)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="truncate text-base font-semibold text-gray-950 dark:text-white">
                                    {{ $selectedIdentity['PrimaryName'] ?: '-' }}</div>
                                <div
                                    class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="rounded bg-gray-100 px-1.5 py-0.5 font-semibold dark:bg-gray-800">
                                        {{ $selectedChat['JenisChat'] === 'Grup' ? __('ui.pages.inbox.whatsapp_group') : __('ui.pages.inbox.personal_chat') }}
                                    </span>
                                    @if ($selectedChat['JenisChat'] === 'Grup')
                                        <span>{{ __('ui.pages.inbox.group_name') }}:
                                            {{ $selectedIdentity['GroupName'] ?: '-' }}</span>
                                        <span class="break-all font-mono">{{ __('ui.pages.inbox.group_id') }}:
                                            {{ $selectedIdentity['GroupId'] ?: '-' }}</span>
                                    @else
                                        <span>{{ $selectedIdentity['ContactName'] ?: '-' }}</span>
                                        <span
                                            class="break-all font-mono">{{ $selectedIdentity['ContactNumber'] ?: $selectedIdentity['ChatId'] ?: '-' }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($selectedChat['AutoReplyAiAktif'])
                                <div
                                    class="inline-flex rounded-md bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                    {{ __('ui.pages.inbox.ai_auto_reply') }}</div>
                            @endif
                            <div
                                class="inline-flex rounded-md bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                {{ $selectedChat['Status'] }}</div>

                            {{-- Dropdown Pengaturan Chat (Mobile & Tablet) --}}
                            <div x-data="{ menuOpen: false }" class="relative z-30 shrink-0">
                                <button type="button" @click="menuOpen = !menuOpen"
                                    @click.outside="menuOpen = false" :aria-expanded="menuOpen.toString()"
                                    aria-haspopup="menu" aria-label="{{ __('ui.pages.inbox.details') }}"
                                    class="2xl:hidden flex h-10 w-10 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-300 transition-colors focus:outline-none"
                                    title="{{ __('ui.pages.inbox.chat_settings') }}">
                                    <x-heroicon-o-ellipsis-vertical class="w-6 h-6" />
                                </button>

                                <div x-cloak x-show="menuOpen" x-transition.origin.top.right
                                    class="absolute right-0 top-full mt-1.5 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl dark:border-gray-800 dark:bg-gray-900 text-sm">

                                    {{-- Lihat Detail / Modal --}}
                                    <button type="button" @click="detailsOpen = true; menuOpen = false"
                                        aria-label="{{ __('ui.pages.inbox.open_details') }}" aria-haspopup="dialog"
                                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800">
                                        <x-heroicon-o-information-circle class="w-4 h-4 text-gray-400" />
                                        <span>{{ __('ui.pages.inbox.open_details') }}</span>
                                    </button>

                                    @if ($this->canManageInbox())
                                        <div class="my-1 border-t border-gray-100 dark:border-gray-800"></div>

                                        {{-- Fetch Profile --}}
                                        <button type="button" wire:click="refreshProfilWaha"
                                            @click="menuOpen = false"
                                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800">
                                            <x-heroicon-o-arrow-path class="w-4 h-4 text-gray-400" />
                                            <span>{{ __('ui.pages.inbox.fetch_profile') }}</span>
                                        </button>

                                        {{-- Refresh Mapping --}}
                                        <button type="button" wire:click="refreshMappingChat"
                                            @click="menuOpen = false"
                                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800">
                                            <x-heroicon-o-arrow-path class="w-4 h-4 text-gray-400" />
                                            <span>{{ __('ui.pages.inbox.refresh_mapping') }}</span>
                                        </button>
                                    @endif

                                    @if ($this->canManageInbox() && strtoupper((string) ($selectedChat['StatusCode'] ?? '')) !== 'DITUTUP')
                                        <div class="my-1 border-t border-gray-100 dark:border-gray-800"></div>

                                        {{-- Tutup Percakapan --}}
                                        <button type="button"
                                            x-on:click="
                                                Swal.fire({
                                                    title: '{{ __('ui.pages.inbox.close_chat_confirm_title') }}',
                                                    text: '{{ __('ui.pages.inbox.close_chat_confirm_text') }}',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#dc2626',
                                                    cancelButtonColor: '#6b7280',
                                                    confirmButtonText: '{{ __('ui.pages.inbox.close_chat_confirm_button') }}',
                                                    cancelButtonText: '{{ __('ui.common.cancel') }}'
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        $wire.tutupPercakapan();
                                                    }
                                                })
                                            "
                                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30">
                                            <x-heroicon-o-x-circle class="w-4 h-4 text-red-500" />
                                            <span>{{ __('ui.pages.inbox.close_chat_confirm_button') }}</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Tombol Tutup Chat Desktop (Tetap Tampil di Kanan luar menu jika layar besar >= 2xl) --}}
                            @if ($this->canManageInbox() && strtoupper((string) ($selectedChat['StatusCode'] ?? '')) !== 'DITUTUP')
                                <x-filament::button color="danger" size="sm" class="hidden 2xl:inline-flex"
                                    x-on:click="
                                            Swal.fire({
                                                title: '{{ __('ui.pages.inbox.close_chat_confirm_title') }}',
                                                text: '{{ __('ui.pages.inbox.close_chat_confirm_text') }}',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#dc2626',
                                                cancelButtonColor: '#6b7280',
                                                confirmButtonText: '{{ __('ui.pages.inbox.close_chat_confirm_button') }}',
                                                cancelButtonText: '{{ __('ui.common.cancel') }}'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    $wire.tutupPercakapan();
                                                }
                                            })
                                        "
                                    icon="heroicon-o-x-circle">
                                    {{ __('ui.pages.inbox.close_chat_confirm_button') }}
                                </x-filament::button>
                            @endif
                        </div>
                    </div>

                    {{-- Riwayat Pesan: Scrollable, Auto-scroll ke bawah --}}
                    <div x-data="{
                        scrollToBottom() {
                            this.$nextTick(() => {
                                this.$el.scrollTop = this.$el.scrollHeight;
                            });
                        }
                    }" x-init="scrollToBottom();
                    $wire.$hook('morph', () => { scrollToBottom(); });"
                        class="wacs-inbox-messages min-h-0 flex-1 space-y-4 overflow-y-auto overflow-x-hidden bg-gray-50 p-4 pb-36 dark:bg-gray-950/60">
                        @if ($hasOlderMessages)
                            <div>
                                <button type=button wire:click=loadOlderMessages wire:loading.attr=disabled
                                    wire:target=loadOlderMessages>
                                    <span wire:loading.remove
                                        wire:target=loadOlderMessages>{{ __('ui.pages.inbox.load_older_messages') }}</span>
                                    <span wire:loading
                                        wire:target=loadOlderMessages>{{ __('ui.pages.inbox.loading_older_messages') }}</span>
                                </button>
                            </div>
                        @endif
                        @forelse ($messages as $message)
                            @php($isOut = $message['ArahPesan'] === 'Keluar')
                            @php($hasMedia = $message['MediaCategory'] !== 'text')
                            @php($senderAvatar = $message['SenderAvatarUrl'] ?? null)
                            <div class="flex items-end gap-2 {{ $isOut ? 'justify-end' : 'justify-start' }}">
                                @if (!$isOut)
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        {{ mb_strtoupper(mb_substr($message['SenderName'] ?: 'C', 0, 1)) }}
                                    </div>
                                @endif
                                <div
                                    class="{{ $isOut ? 'bg-blue-600 text-white' : 'bg-white text-gray-800 ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-800' }} max-w-[86%] rounded-lg p-3 text-sm">
                                    <div class="{{ $isOut ? 'text-blue-100' : 'text-gray-500' }} text-xs font-medium">
                                        @if (!$isOut && $selectedChat['JenisChat'] === 'Grup')
                                            <span class="sr-only">{{ __('ui.pages.inbox.sender_name') }}:</span>
                                        @endif
                                        {{ $message['SenderName'] }}
                                        &middot;
                                        {{ \App\Support\LocaleFormatter::shortDate($message['TglPesan']) }}
                                        {{ \App\Support\LocaleFormatter::time($message['TglPesan']) }}
                                        @if ($message['StatusKirim'])
                                            &middot; {{ $message['StatusKirim'] }}
                                        @endif
                                    </div>
                                    @if (!$isOut && $selectedChat['JenisChat'] === 'Grup' && ($message['SenderNumber'] ?? null))
                                        <div class="mt-0.5 font-mono text-[11px] text-gray-400 dark:text-gray-500">
                                            <span class="sr-only">{{ __('ui.pages.inbox.sender_number') }}:</span>
                                            {{ $message['SenderNumber'] }}
                                        </div>
                                    @endif
                                    @if ($hasMedia)
                                        <div
                                            class="mt-2 overflow-hidden rounded-md {{ $isOut ? 'bg-blue-700/40' : 'bg-gray-100 dark:bg-gray-950' }}">
                                            @if ($message['MediaUrl'] && $message['MediaCategory'] === 'image')
                                                <a href="{{ $message['MediaUrl'] }}" target="_blank" rel="noopener"
                                                    class="block">
                                                    <img src="{{ $message['MediaUrl'] }}"
                                                        alt="{{ $message['MediaLabel'] }}"
                                                        class="max-h-80 w-full object-contain">
                                                </a>
                                            @elseif ($message['MediaUrl'] && $message['MediaCategory'] === 'video')
                                                <video controls preload="metadata" class="max-h-80 w-full">
                                                    <source src="{{ $message['MediaUrl'] }}"
                                                        @if ($message['TipeMime']) type="{{ $message['TipeMime'] }}" @endif>
                                                </video>
                                            @elseif ($message['MediaUrl'] && $message['MediaCategory'] === 'audio')
                                                <div class="p-3">
                                                    <audio controls preload="metadata" class="w-full">
                                                        <source src="{{ $message['MediaUrl'] }}"
                                                            @if ($message['TipeMime']) type="{{ $message['TipeMime'] }}" @endif>
                                                    </audio>
                                                </div>
                                            @elseif ($message['MediaUrl'] && $message['MediaCategory'] === 'pdf')
                                                <object data="{{ $message['MediaUrl'] }}" type="application/pdf"
                                                    title="{{ __('ui.pages.inbox.preview_media') }}"
                                                    class="h-80 w-full">
                                                    <a href="{{ $message['MediaUrl'] }}" target="_blank"
                                                        rel="noopener"
                                                        class="block px-3 py-2 text-sm font-medium underline underline-offset-2">
                                                        {{ __('ui.pages.inbox.preview_media') }}
                                                    </a>
                                                </object>
                                            @elseif ($message['MediaUrl'])
                                                <a href="{{ $message['MediaUrl'] }}" target="_blank" rel="noopener"
                                                    class="{{ $isOut ? 'text-blue-50 hover:text-white' : 'text-blue-700 hover:text-blue-900 dark:text-blue-300 dark:hover:text-blue-100' }} block px-3 py-2 text-sm font-medium underline underline-offset-2">
                                                    {{ $message['MediaLabel'] ?: __('ui.pages.inbox.unknown_media') }}
                                                </a>
                                            @else
                                                <div
                                                    class="px-3 py-2 text-sm {{ $isOut ? 'text-blue-50' : 'text-gray-600 dark:text-gray-300' }}">
                                                    {{ $message['MediaLabel'] ?: __('ui.pages.inbox.unknown_media') }}
                                                    {{ __('ui.pages.inbox.media_unavailable') }}
                                                </div>
                                            @endif
                                            @if ($message['MediaDownloadUrl'])
                                                <a href="{{ $message['MediaDownloadUrl'] }}" target="_blank"
                                                    rel="noopener"
                                                    class="block border-t border-gray-200 px-3 py-2 text-xs font-semibold underline underline-offset-2 dark:border-gray-800">
                                                    {{ __('ui.pages.inbox.download_media') }}
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($message['IsiPesan'])
                                        <p class="mt-2 whitespace-pre-line">{{ $message['IsiPesan'] }}</p>
                                    @elseif (!$hasMedia)
                                        <p class="mt-1 whitespace-pre-line">
                                            {{ __('ui.pages.view_chat.non_text_message') }}</p>
                                    @endif
                                    @if ($message['PesanError'])
                                        <div
                                            class="mt-2 rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-200">
                                            Error: {{ $message['PesanError'] }}
                                        </div>
                                    @endif
                                </div>
                                @if ($isOut)
                                    @if ($senderAvatar)
                                        <img src="{{ $senderAvatar }}" alt=""
                                            class="h-8 w-8 shrink-0 rounded-full bg-white object-cover ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-700">
                                    @else
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 ring-1 ring-blue-200 dark:bg-blue-500/20 dark:text-blue-100 dark:ring-blue-500/30">
                                            {{ mb_strtoupper(mb_substr($message['SenderName'] ?: 'CS', 0, 2)) }}
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @empty
                            <div class="p-6 text-center text-sm text-gray-500">{{ __('ui.pages.inbox.no_messages') }}
                            </div>
                        @endforelse
                    </div>

                    {{-- Form Balasan: Selalu Tampil di Bawah (Sticky) --}}
                    @if ($this->canReplyInbox())
                        <form wire:submit.prevent="kirimBalasanWaha" x-data="{
                            handlePaste(event) {
                                const items = event.clipboardData?.items || [];
                        
                                for (const item of items) {
                                    if (item.kind !== 'file') {
                                        continue;
                                    }
                        
                                    const file = item.getAsFile();
                        
                                    if (!file) {
                                        continue;
                                    }
                        
                                    const files = new DataTransfer();
                                    files.items.add(file);
                                    this.$refs.attachmentInput.files = files.files;
                                    this.$refs.attachmentInput.dispatchEvent(new Event('change', { bubbles: true }));
                                    event.preventDefault();
                                    break;
                                }
                            }
                        }"
                            @paste="handlePaste($event)"
                            class="wacs-inbox-reply-form absolute bottom-0 inset-x-0 z-20 pointer-events-none p-3 sm:p-4 bg-gradient-to-t from-white via-white/85 to-transparent dark:from-gray-900 dark:via-gray-900/85">
                            <div
                                class="pointer-events-auto mx-auto max-w-4xl rounded-2xl border border-gray-200/80 bg-white/95 p-3 shadow-xl backdrop-blur-md dark:border-gray-700/60 dark:bg-gray-900/95 space-y-2">
                                <input x-ref="attachmentInput" type="file" wire:model="attachment"
                                    accept="image/*,video/*,audio/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.zip,.rar"
                                    class="hidden">

                                @if ($attachment)
                                    <div
                                        class="flex items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs text-blue-800 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                                        <div class="min-w-0 truncate flex items-center gap-1.5">
                                            <x-heroicon-o-paper-clip class="h-4 w-4 shrink-0" />
                                            <span class="truncate">{{ __('ui.pages.inbox.attachment') }}:
                                                {{ $attachment->getClientOriginalName() }}</span>
                                        </div>
                                        <button type="button" wire:click="removeAttachment"
                                            class="shrink-0 font-semibold text-blue-700 hover:text-blue-900 dark:text-blue-200 dark:hover:text-white">{{ __('ui.common.delete') }}</button>
                                    </div>
                                @endif

                                @error('replyText')
                                    <div class="px-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</div>
                                @enderror
                                @error('attachment')
                                    <div class="px-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</div>
                                @enderror

                                <div wire:loading wire:target="attachment"
                                    class="px-1 text-xs font-medium text-blue-600 dark:text-blue-300">
                                    {{ __('ui.pages.inbox.uploading_attachment') }}
                                </div>

                                <div class="flex items-end gap-2">
                                    <button type="button" x-on:click="$refs.attachmentInput.click()"
                                        class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                                        title="{{ __('ui.pages.inbox.attach_file') }}">
                                        <x-heroicon-o-paper-clip class="h-5 w-5" />
                                    </button>

                                    <div class="flex-1 min-w-0">
                                        <textarea wire:model="replyText"
                                            x-on:keydown.enter="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.kirimBalasanWaha() }"
                                            x-on:input="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight, 180)+'px'"
                                            x-effect="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight, 180)+'px'"
                                            class="block max-h-[180px] min-h-[42px] w-full resize-none border-none bg-transparent px-2 py-2 text-sm leading-6 text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                                            rows="1" placeholder="{{ __('ui.pages.inbox.reply_placeholder') }}"></textarea>
                                    </div>

                                    <div class="flex items-center gap-1.5 shrink-0">
                                        {{-- <x-filament::button type="button" color="gray" size="sm" outlined
                                            wire:click="simpanBalasanLokal"
                                            title="Simpan Draft">
                                            {{ __('ui.common.save') }} Draft
                                        </x-filament::button> --}}

                                        <button type="submit" wire:loading.attr="disabled"
                                            wire:target="attachment,kirimBalasanWaha"
                                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-600 text-white transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-50 dark:ring-offset-gray-900"
                                            title="{{ __('ui.pages.inbox.send_to_whatsapp') }}">
                                            <x-heroicon-m-paper-airplane wire:loading.remove
                                                wire:target="attachment,kirimBalasanWaha" class="h-5 w-5" />
                                            <svg wire:loading wire:target="attachment,kirimBalasanWaha"
                                                class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none"
                                                aria-hidden="true">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @endif
                @else
                    <div class="flex flex-1 items-center justify-center p-6 text-sm text-gray-500">
                        {{ __('ui.pages.inbox.select_chat_to_view') }}</div>
                @endif
            </section>

            {{-- Backdrop untuk Aside di Mobile/Tablet --}}
            <div x-show="detailsOpen" x-transition.opacity @click="detailsOpen = false" x-cloak
                class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm 2xl:hidden"></div>

            {{-- Aside (Sidebar Kanan) / Modal di Mobile --}}
            <aside
                :class="detailsOpen ? 'scale-100 opacity-100' :
                    'scale-95 opacity-0 pointer-events-none 2xl:scale-100 2xl:opacity-100 2xl:pointer-events-auto'"
                :role="detailsOpen ? 'dialog' : 'complementary'" :aria-modal="detailsOpen ? 'true' : null"
                aria-labelledby="wacs-inbox-details-title"
                class="wacs-inbox-aside wacs-inbox-aside--details min-h-0 space-y-4 overflow-y-auto overflow-x-hidden">
                <div
                    class="2xl:hidden flex items-center justify-between mb-2 border-b border-gray-200 pb-3 dark:border-gray-800">
                    <h3 id="wacs-inbox-details-title" class="font-semibold text-lg text-gray-950 dark:text-white">
                        {{ __('ui.pages.inbox.open_details') }}</h3>
                    <button type="button" @click="detailsOpen = false" aria-label="{{ __('ui.common.cancel') }}"
                        class="flex h-11 w-11 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:hover:bg-gray-800 dark:hover:text-gray-300">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('ui.pages.inbox.profile_mapping') }}</div>
                        @if ($selectedChat && $this->canManageInbox())
                            <div class="flex flex-wrap justify-end gap-2">
                                <x-filament::button type="button" color="info" size="xs" outlined
                                    wire:click="refreshProfilWaha">
                                    {{ __('ui.pages.inbox.fetch_profile') }}
                                </x-filament::button>
                                <x-filament::button type="button" color="gray" size="xs" outlined
                                    wire:click="refreshMappingChat">
                                    {{ __('ui.common.refresh') }}
                                </x-filament::button>
                                @if (($selectedChat['JenisChat'] ?? '') === 'Grup')
                                    <x-filament::button type="button" color="warning" size="xs" outlined
                                        wire:click="syncSelectedGroupName" wire:loading.attr="disabled">
                                        {{ __('ui.pages.inbox.sync_group_name') }}
                                    </x-filament::button>
                                    <x-filament::button type="button" color="warning" size="xs" outlined
                                        wire:click="syncMissingGroupNames" wire:loading.attr="disabled">
                                        {{ __('ui.pages.inbox.sync_all_group_names') }}
                                    </x-filament::button>
                                @endif
                            </div>
                        @endif
                    </div>
                    @if ($selectedChat)
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">{{ __('ui.pages.inbox.client') }}</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">
                                    {{ $selectedIdentity['Instansi'] ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('ui.pages.inbox.chat_type') }}</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">
                                    {{ $selectedChat['JenisChat'] === 'Grup' ? __('ui.pages.inbox.whatsapp_group') : __('ui.pages.inbox.personal_chat') }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('ui.pages.inbox.contact') }}</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">
                                    {{ $selectedIdentity['ContactName'] ?: '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('ui.pages.inbox.wa_number') }}</dt>
                                <dd class="break-all font-mono text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $selectedIdentity['ContactNumber'] ?: ($selectedChat['JenisChat'] === 'Pribadi' ? ($selectedIdentity['ChatId'] ?: '-') : '-') }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('ui.pages.inbox.group_name') }}</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">
                                    {{ $selectedIdentity['GroupName'] ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('ui.pages.inbox.group_id') }}</dt>
                                <dd class="break-all font-mono font-medium text-gray-900 dark:text-white">
                                    {{ $selectedIdentity['GroupId'] ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('ui.pages.inbox.detected_id') }}</dt>
                                <dd class="space-y-1 font-mono text-xs text-gray-700 dark:text-gray-200">
                                    @forelse (array_slice($selectedChat['MappingIdentifiers'] ?? [], 0, 6) as $identifier)
                                        <div class="break-all rounded-md bg-gray-100 px-2 py-1 dark:bg-gray-950">
                                            {{ $identifier }}</div>
                                    @empty
                                        <div>-</div>
                                    @endforelse
                                </dd>
                            </div>
                        </dl>
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800 space-y-2">
                            @if ($this->canManageInbox())
                                <x-filament::button color="warning" size="sm" outlined class="w-full"
                                    x-on:click="$dispatch('open-modal', { id: 'internal-notes-modal' })"
                                    :badge="count($internalNotes) > 0 ? count($internalNotes) : null" badge-color="warning">
                                    {{ __('ui.pages.view_chat.internal_notes') }}
                                </x-filament::button>
                            @endif

                            <x-filament::button color="gray" size="sm" outlined class="w-full"
                                x-on:click="$dispatch('open-modal', { id: 'history-chat-modal' })">
                                {{ __('ui.pages.inbox.previous_history') }}
                            </x-filament::button>
                        </div>
                    @else
                        <div class="mt-3 text-sm text-gray-500">{{ __('ui.pages.inbox.no_chat_selected') }}</div>
                    @endif
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('ui.pages.inbox.ai_control') }}</div>
                    @if ($selectedChat)
                        <div class="mt-4 space-y-3 text-sm">
                            <div
                                class="rounded-md {{ $selectedChat['AutoReplyAiAktif'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }} px-3 py-2 font-medium">
                                {{ $selectedChat['AutoReplyAiAktif'] ? __('ui.pages.inbox.ai_reply_session_on') : __('ui.pages.inbox.ai_reply_global_only') }}
                            </div>
                            <div class="grid gap-2 text-gray-600 dark:text-gray-300">
                                <div>{{ __('ui.pages.inbox.ai_greeting') }}: <span
                                        class="font-medium text-gray-950 dark:text-white">{{ $selectedChat['AiSudahMenyapa'] ? __('ui.pages.inbox.already') : __('ui.pages.inbox.not_yet') }}</span>
                                </div>
                                <div>{{ __('ui.pages.inbox.last_ai') }}: <span
                                        class="font-medium text-gray-950 dark:text-white">{{ \App\Support\LocaleFormatter::dateTime($selectedChat['TglAutoReplyAiTerakhir'] ?? null) }}</span>
                                </div>
                            </div>
                            @if ($this->canManageInbox())
                                <div>
                                    <label
                                        class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('ui.ai_learning.mode_label') }}</label>
                                    <x-filament::input.wrapper class="mt-1">
                                        <x-filament::input.select
                                            wire:change="updateModeKnowledgeAi($event.target.value)">
                                            <option value="Ringan" @selected(($selectedChat['ModeKnowledgeAi'] ?? 'Ringan') === 'Ringan')>
                                                {{ __('ui.ai_learning.mode_light') }}</option>
                                            <option value="AllKnowledge" @selected(($selectedChat['ModeKnowledgeAi'] ?? 'Ringan') === 'AllKnowledge')>
                                                {{ __('ui.ai_learning.mode_all') }}</option>
                                            <option value="Nonaktif" @selected(($selectedChat['ModeKnowledgeAi'] ?? 'Ringan') === 'Nonaktif')>
                                                {{ __('ui.ai_learning.mode_off') }}</option>
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('ui.ai_learning.mode_help') }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <x-filament::button type="button" wire:click="toggleAutoReplyAi">
                                        {{ $selectedChat['AutoReplyAiAktif'] ? __('ui.pages.inbox.disable_auto_reply') : __('ui.pages.inbox.enable_auto_reply') }}
                                    </x-filament::button>
                                    <x-filament::button type="button" color="gray" outlined
                                        wire:click="resetSapaanAi">
                                        {{ __('ui.pages.inbox.reset_greeting') }}
                                    </x-filament::button>
                                    
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('ui.pages.ai_agent.refine_whatsapp_replies') }}</label>
                                    <div class="mt-1.5 flex rounded-lg shadow-sm">
                                        <button type="button" wire:click="setReplyRefinementPreference('follow')" class="relative flex-1 rounded-l-lg border px-2 py-1.5 text-xs font-medium focus:z-10 focus:ring-2 focus:ring-primary-500 transition-colors {{ $replyRefinementPreference === 'follow' ? 'bg-gray-100 border-gray-300 text-gray-900 dark:bg-gray-800 dark:border-gray-600 dark:text-white z-10' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800' }}">
                                            {{ __('ui.pages.inbox.refinement_follow') }}
                                        </button>
                                        <button type="button" wire:click="setReplyRefinementPreference('active')" class="relative flex-1 -ml-px border px-2 py-1.5 text-xs font-medium focus:z-10 focus:ring-2 focus:ring-primary-500 transition-colors {{ $replyRefinementPreference === 'active' ? 'bg-primary-50 border-primary-300 text-primary-700 dark:bg-primary-500/10 dark:border-primary-500/30 dark:text-primary-300 z-10' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800' }}">
                                            {{ __('ui.common.active') }}
                                        </button>
                                        <button type="button" wire:click="setReplyRefinementPreference('inactive')" class="relative flex-1 -ml-px rounded-r-lg border px-2 py-1.5 text-xs font-medium focus:z-10 focus:ring-2 focus:ring-primary-500 transition-colors {{ $replyRefinementPreference === 'inactive' ? 'bg-gray-100 border-gray-300 text-gray-900 dark:bg-gray-800 dark:border-gray-600 dark:text-white z-10' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800' }}">
                                            {{ __('ui.common.inactive') }}
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('ui.pages.inbox.refinement_desc') }}
                                    </p>
                                </div>

                                </div>
                            @endif
                        </div>
                    @else
                        <div class="mt-3 text-sm text-gray-500">{{ __('ui.pages.inbox.select_chat_ai') }}</div>
                    @endif
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('ui.pages.inbox.waha_webhook') }}</div>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('ui.pages.inbox.local_endpoint') }}</p>
                    <code
                        class="mt-2 block rounded-md bg-gray-100 p-3 text-xs text-gray-700 dark:bg-gray-950 dark:text-gray-300">POST
                        /webhooks/waha/{token}</code>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('ui.pages.inbox.webhook_token_info') }}</p>
                </div>
            </aside>
        </div>{{-- end grid 3-kolom --}}
    </div>{{-- end outer flex-col --}}

    @if ($this->canReplyInbox())
        <x-filament::modal id="start-chat-modal" width="2xl">
            <x-slot name="heading">{{ __('ui.pages.inbox.create_chat') }}</x-slot>
            <x-slot name="description">{{ __('ui.pages.inbox.create_chat_desc') }}</x-slot>

            <form wire:submit.prevent="buatChat" class="space-y-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('ui.pages.inbox.contact_search') }}
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" wire:model.live.debounce.300ms="startChatContactSearch"
                                placeholder="{{ __('ui.pages.inbox.contact_search_placeholder') }}" />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('ui.pages.inbox.target_contact') }}
                        </label>
                        <x-filament::input.wrapper :valid="!$errors->has('startChatNomorWhatsappId')">
                            <x-filament::input.select wire:model.live="startChatNomorWhatsappId">
                                <option value="">{{ __('ui.pages.inbox.select_contact_optional') }}</option>
                                @foreach ($this->startChatContactOptions() as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        @error('startChatNomorWhatsappId')
                            <div class="text-xs text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('ui.pages.inbox.manual_number') }}
                        </label>
                        <x-filament::input.wrapper :valid="!$errors->has('startChatManualNumber')">
                            <x-filament::input type="text" wire:model="startChatManualNumber"
                                placeholder="628xxxxxxxxxx" />
                        </x-filament::input.wrapper>
                        @error('startChatManualNumber')
                            <div class="text-xs text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('ui.pages.inbox.manual_contact_name') }}
                        </label>
                        <x-filament::input.wrapper :valid="!$errors->has('startChatManualName')">
                            <x-filament::input type="text" wire:model="startChatManualName"
                                placeholder="{{ __('ui.pages.inbox.manual_contact_name_placeholder') }}" />
                        </x-filament::input.wrapper>
                        @error('startChatManualName')
                            <div class="text-xs text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('ui.pages.inbox.waha_session') }}
                        </label>
                        <x-filament::input.wrapper :valid="!$errors->has('startChatSessionId')">
                            <x-filament::input.select wire:model="startChatSessionId">
                                @forelse ($this->startChatSessionOptions() as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @empty
                                    <option value="">{{ __('ui.pages.inbox.default_session') }}</option>
                                @endforelse
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        @error('startChatSessionId')
                            <div class="text-xs text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('ui.pages.inbox.delivery_mode') }}
                        </label>
                        <x-filament::input.wrapper :valid="!$errors->has('startChatDeliveryMode')">
                            <x-filament::input.select wire:model="startChatDeliveryMode">
                                <option value="send">{{ __('ui.pages.inbox.send_now') }}</option>
                                <option value="draft">{{ __('ui.pages.inbox.save_as_draft') }}</option>
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        @error('startChatDeliveryMode')
                            <div class="text-xs text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('ui.pages.inbox.initial_message') }}
                    </label>
                    <x-filament::input.wrapper :valid="!$errors->has('startChatMessage')">
                        <textarea wire:model="startChatMessage" rows="5"
                            class="w-full resize-y border-0 bg-transparent px-3 py-2 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                            placeholder="{{ __('ui.pages.inbox.initial_message_placeholder') }}"></textarea>
                    </x-filament::input.wrapper>
                    @error('startChatMessage')
                        <div class="text-xs text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <x-filament::button type="button" color="gray" outlined
                        x-on:click="$dispatch('close-modal', { id: 'start-chat-modal' })">
                        {{ __('ui.common.cancel') }}
                    </x-filament::button>
                    <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="buatChat">
                        {{ __('ui.pages.inbox.create_chat') }}
                    </x-filament::button>
                </div>
            </form>
        </x-filament::modal>
    @endif

    {{-- History Chat Modal --}}
    <x-filament::modal id="history-chat-modal" width="2xl">
        <x-slot name="heading">
            {{ __('ui.pages.inbox.history_chat') }}
        </x-slot>
        <div class="space-y-4" wire:init="loadHistoryChats">
            @if (empty($historyChats))
                <div class="text-sm text-gray-500 text-center py-8">{{ __('ui.pages.inbox.history_loading_empty') }}
                </div>
            @else
                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($historyChats as $history)
                        <div class="py-3 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ \App\Support\LocaleFormatter::dateTime($history['TglChatTerakhir']) }}</div>
                                <div class="text-sm text-gray-500">{{ $history['NamaStatusChat'] }} &middot;
                                    {{ $history['JumlahPesanBelumDibaca'] }} {{ __('ui.pages.inbox.unread_messages') }}</div>
                            </div>
                            <a href="{{ route('filament.admin.pages.view-chat-session') . '?id=' . $history['Id'] }}"
                                target="_blank"
                                class="inline-flex items-center gap-1 rounded-2xl border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                                <x-heroicon-s-arrow-top-right-on-square class="w-3.5 h-3.5" />
                                {{ __('ui.pages.inbox.open_session') }}
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::modal>

    @if ($this->canManageInbox())
        <x-filament::modal id="internal-notes-modal" width="xl">
            <x-slot name="heading">{{ __('ui.pages.view_chat.internal_notes') }}</x-slot>
            <x-slot name="description">{{ __('ui.pages.inbox.internal_notes_desc') }}</x-slot>

            <div class="space-y-4">
                <div class="max-h-96 overflow-y-auto space-y-3 pr-2">
                    @forelse ($internalNotes as $note)
                        <div
                            class="rounded-lg bg-yellow-50 p-3 text-sm dark:bg-yellow-500/10 border border-yellow-200 dark:border-yellow-500/20">
                            <div class="font-medium text-yellow-800 dark:text-yellow-400 mb-1 flex justify-between">
                                <span>{{ $note['DibuatOlehNama'] }}</span>
                                <span
                                    class="text-xs opacity-70">{{ \App\Support\LocaleFormatter::dateTime($note['TglBuat']) }}</span>
                            </div>
                            <div class="text-yellow-900 dark:text-yellow-300 whitespace-pre-wrap">
                                {{ $note['IsiCatatan'] }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500 text-center py-4">
                            {{ __('ui.pages.inbox.no_internal_notes') }}</div>
                    @endforelse
                </div>

                <div class="pt-4 border-t border-gray-200 dark:border-gray-800">
                    <x-filament::input.wrapper>
                        <textarea wire:model="newInternalNote" rows="3"
                            class="w-full resize-y border-0 bg-transparent px-3 py-2 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                            placeholder="{{ __('ui.pages.inbox.new_internal_note_placeholder') }}"></textarea>
                    </x-filament::input.wrapper>
                    <div class="mt-3 flex justify-end">
                        <x-filament::button color="warning" wire:click="saveInternalNote"
                            wire:loading.attr="disabled">
                            {{ __('ui.pages.inbox.save_note') }}
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </x-filament::modal>
    @endif

    {{-- Modal Review Refinement --}}
    <div x-data="{ open: @entangle('reviewModalOpen') }" x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="refinement-modal-title">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="$wire.cancelRefinedReply()"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
            <div x-show="open" x-transition.scale.95
                class="inline-block transform overflow-hidden rounded-2xl border border-gray-200/80 bg-white/95 text-left align-bottom shadow-2xl backdrop-blur-md transition-all dark:border-gray-700/60 dark:bg-gray-900/95 sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white" id="refinement-modal-title">
                        {{ __('ui.pages.inbox.refinement_modal_title') }}
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div wire:loading wire:target="kirimBalasanWaha" class="w-full text-center py-8 space-y-3">
                        <svg class="mx-auto h-8 w-8 animate-spin text-primary-600" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                        </svg>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('ui.pages.inbox.refining_loader') }}</p>
                    </div>
                    <div wire:loading.remove wire:target="kirimBalasanWaha" class="space-y-4">
                        @if ($refinementError)
                            <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-500/30 dark:bg-red-500/10">
                                <div class="flex gap-3">
                                    <x-heroicon-o-x-circle class="h-5 w-5 text-red-600 dark:text-red-400 shrink-0" />
                                    <div>
                                        <h4 class="text-sm font-semibold text-red-800 dark:text-red-300">{{ __('ui.pages.inbox.refinement_failed_title') }}</h4>
                                        <p class="mt-1 text-xs text-red-700 dark:text-red-400">{{ $refinementError }}</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('ui.pages.inbox.refinement_original') }}</h4>
                                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $originalDraft }}</p>
                                </div>
                                <div class="rounded-xl border border-primary-200 bg-primary-50/20 p-4 dark:border-primary-800/30 dark:bg-primary-950/20">
                                    <h4 class="text-xs font-semibold text-primary-500 uppercase tracking-wider">{{ __('ui.pages.inbox.refinement_suggested') }}</h4>
                                    <p class="mt-2 text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $refinedDraft }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div wire:loading.remove wire:target="kirimBalasanWaha" class="px-6 py-4 bg-gray-50 dark:bg-gray-950/30 border-t border-gray-100 dark:border-gray-800 flex flex-wrap justify-between items-center gap-3">
                    <div>
                        @if ($refinementError)
                            <x-filament::button type="button" color="danger" size="sm" wire:click="sendOriginalAfterRefinementFailure" wire:loading.attr="disabled">
                                {{ __('ui.pages.inbox.refinement_send_original') }}
                            </x-filament::button>
                        @else
                            <x-filament::button type="button" color="primary" size="sm" wire:click="confirmRefinedReply" wire:loading.attr="disabled">
                                {{ __('ui.pages.inbox.refinement_send_refined') }}
                            </x-filament::button>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        @if (! $refinementError)
                            <x-filament::button type="button" color="gray" size="sm" outlined wire:click="editRefinedReply" wire:loading.attr="disabled">
                                {{ __('ui.pages.inbox.refinement_edit') }}
                            </x-filament::button>
                        @endif
                        <x-filament::button type="button" color="gray" size="sm" outlined wire:click="cancelRefinedReply" wire:loading.attr="disabled">
                            {{ __('ui.common.cancel') }}
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-filament-panels::page>
