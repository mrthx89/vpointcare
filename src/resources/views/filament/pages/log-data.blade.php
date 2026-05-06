<x-filament-panels::page>
    <div class="space-y-6" wire:poll.15s="loadLogs">
        <section
            x-data="{
                locale: @js(\App\Support\LocaleManager::browserLocale()),
                status: window.wahaGetReverbStatus ? window.wahaGetReverbStatus() : {
                    state: 'unknown',
                    message: 'Reverb client belum terdeteksi di browser ini.',
                    updatedAt: new Date().toISOString(),
                    wsUrl: '-',
                    reason: 'Asset Echo belum aktif atau halaman belum selesai memuat.',
                },
                logs: window.wahaGetReverbStatusLogs ? window.wahaGetReverbStatusLogs() : [],
                init() {
                    setTimeout(() => {
                        if (window.wahaGetReverbStatus) {
                            this.status = window.wahaGetReverbStatus();
                        }

                        if (window.wahaGetReverbStatusLogs) {
                            this.logs = window.wahaGetReverbStatusLogs();
                        }
                    }, 300);
                },
                applyStatus(event) {
                    this.status = event.detail;
                    this.logs = window.wahaGetReverbStatusLogs ? window.wahaGetReverbStatusLogs() : this.logs;
                },
                badgeClass(state) {
                    if (state === 'connected') return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30';
                    if (state === 'connecting' || state === 'initialized') return 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/30';
                    if (state === 'disconnected') return 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30';
                    return 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30';
                },
                dotClass(state) {
                    if (state === 'connected') return 'bg-emerald-500';
                    if (state === 'connecting' || state === 'initialized') return 'bg-blue-500';
                    if (state === 'disconnected') return 'bg-amber-500';
                    return 'bg-red-500';
                },
                formatDate(value) {
                    if (!value) return '-';

                    return new Intl.DateTimeFormat(this.locale, {
                        day: '2-digit',
                        month: 'short',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                    }).format(new Date(value));
                },
            }"
            @wacs-reverb-status-changed.window="applyStatus($event)"
            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-base font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.log_data.reverb_title') }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.log_data.reverb_desc') }}</div>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-md px-2.5 py-1.5 text-xs font-semibold ring-1" :class="badgeClass(status.state)">
                        <span class="h-2 w-2 rounded-full" :class="dotClass(status.state)"></span>
                        <span x-text="status.state"></span>
                    </span>
                </div>
            </div>
            <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_minmax(360px,520px)]">
                <div class="space-y-3">
                    <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-950">
                        <div class="text-sm font-semibold text-gray-950 dark:text-white" x-text="status.message || '-'"></div>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-300" x-show="status.reason" x-text="status.reason"></div>
                    </div>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.pages.log_data.websocket_endpoint') }}</dt>
                            <dd class="break-all font-mono text-xs text-gray-900 dark:text-gray-100" x-text="status.wsUrl || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.pages.log_data.last_update') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100" x-text="formatDate(status.updatedAt)"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.pages.log_data.host') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100" x-text="status.host || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('ui.pages.log_data.port_mode') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">
                                <span x-text="status.port || '-'"></span>
                                <span>&middot;</span>
                                <span x-text="status.secure ? 'WSS' : 'WS'"></span>
                            </dd>
                        </div>
                    </dl>
                </div>
                <div class="rounded-md border border-gray-200 dark:border-gray-800">
                    <div class="border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        {{ __('ui.pages.log_data.client_history') }}
                    </div>
                    <div class="max-h-64 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-800">
                        <template x-for="item in logs.slice(0, 12)" :key="`${item.at}-${item.state}-${item.reason || ''}`">
                            <div class="px-3 py-2 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="inline-flex items-center gap-2 font-medium text-gray-950 dark:text-white">
                                        <span class="h-2 w-2 rounded-full" :class="dotClass(item.state)"></span>
                                        <span x-text="item.state"></span>
                                    </span>
                                    <span class="shrink-0 text-xs text-gray-500" x-text="formatDate(item.at)"></span>
                                </div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="item.reason || item.message || '-'"></div>
                            </div>
                        </template>
                        <div x-show="logs.length === 0" class="px-3 py-8 text-center text-sm text-gray-500">
                            {{ __('ui.pages.log_data.no_reverb_status') }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @php
            $jobTone = match ($jobStatus['status'] ?? 'healthy') {
                'danger' => [
                    'badge' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30',
                    'dot' => 'bg-red-500',
                    'panel' => 'border-red-200 bg-red-50 dark:border-red-500/30 dark:bg-red-500/10',
                ],
                'warning' => [
                    'badge' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30',
                    'dot' => 'bg-amber-500',
                    'panel' => 'border-amber-200 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10',
                ],
                'info' => [
                    'badge' => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/30',
                    'dot' => 'bg-blue-500',
                    'panel' => 'border-blue-200 bg-blue-50 dark:border-blue-500/30 dark:bg-blue-500/10',
                ],
                'missing' => [
                    'badge' => 'bg-gray-100 text-gray-700 ring-gray-600/20 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-500/30',
                    'dot' => 'bg-gray-500',
                    'panel' => 'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950',
                ],
                default => [
                    'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30',
                    'dot' => 'bg-emerald-500',
                    'panel' => 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10',
                ],
            };

            $jobStateClass = fn (string $state): string => match ($state) {
                'reserved', 'running' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
                'delayed' => 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300',
                'finished' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                'cancelled' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                default => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            };
        @endphp

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-base font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.log_data.jobs_title') }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.log_data.jobs_desc') }}</div>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-md px-2.5 py-1.5 text-xs font-semibold ring-1 {{ $jobTone['badge'] }}">
                        <span class="h-2 w-2 rounded-full {{ $jobTone['dot'] }}"></span>
                        {{ $jobStatus['label'] ?? __('ui.pages.log_data.queue_status_missing') }}
                    </span>
                </div>
            </div>

            <div class="space-y-4 p-4">
                <div class="rounded-md border p-4 {{ $jobTone['panel'] }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ $jobStatus['description'] ?? '-' }}</div>
                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                {{ __('ui.pages.log_data.driver') }}: <span class="font-mono">{{ $jobStatus['driver'] ?? '-' }}</span>
                                &middot; {{ __('ui.pages.log_data.connection') }}: <span class="font-mono">{{ $jobStatus['connection'] ?? '-' }}</span>
                                &middot; {{ __('ui.pages.log_data.default_queue') }}: <span class="font-mono">{{ $jobStatus['defaultQueue'] ?? '-' }}</span>
                                &middot; {{ __('ui.pages.log_data.retry_after') }}: {{ \App\Support\LocaleFormatter::number($jobStatus['retryAfter'] ?? 0) }} {{ __('ui.pages.log_data.seconds') }}
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('ui.pages.log_data.update') }} {{ $jobStatus['updatedAt'] ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-md border border-gray-200 p-3 dark:border-gray-800">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('ui.pages.log_data.total_jobs') }}</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ \App\Support\LocaleFormatter::number($jobStatus['total'] ?? 0) }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ __('ui.pages.log_data.pending') }} {{ \App\Support\LocaleFormatter::number($jobStatus['pending'] ?? 0) }} &middot; {{ __('ui.pages.log_data.delayed') }} {{ \App\Support\LocaleFormatter::number($jobStatus['delayed'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-md border border-gray-200 p-3 dark:border-gray-800">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('ui.pages.log_data.processing') }}</div>
                        <div class="mt-1 text-2xl font-semibold text-blue-600 dark:text-blue-300">{{ \App\Support\LocaleFormatter::number($jobStatus['reserved'] ?? 0) }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ __('ui.pages.log_data.stale_reserved') }} {{ \App\Support\LocaleFormatter::number($jobStatus['staleReserved'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-md border border-gray-200 p-3 dark:border-gray-800">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('ui.pages.log_data.failed_jobs') }}</div>
                        <div class="mt-1 text-2xl font-semibold {{ ($jobStatus['failed'] ?? 0) > 0 ? 'text-red-600 dark:text-red-300' : 'text-emerald-600 dark:text-emerald-300' }}">{{ \App\Support\LocaleFormatter::number($jobStatus['failed'] ?? 0) }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ __('ui.pages.log_data.failed_batch_jobs') }} {{ \App\Support\LocaleFormatter::number($jobStatus['failedBatchJobs'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-md border border-gray-200 p-3 dark:border-gray-800">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('ui.pages.log_data.active_batches') }}</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ \App\Support\LocaleFormatter::number($jobStatus['activeBatches'] ?? 0) }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ __('ui.pages.log_data.pending_batch_jobs') }} {{ \App\Support\LocaleFormatter::number($jobStatus['pendingBatchJobs'] ?? 0) }}</div>
                    </div>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <div class="overflow-hidden rounded-md border border-gray-200 dark:border-gray-800">
                        <div class="border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            {{ __('ui.pages.log_data.queue_breakdown') }}
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                                    <tr>
                                        <th class="px-3 py-2">{{ __('ui.pages.log_data.queue') }}</th>
                                        <th class="px-3 py-2">{{ __('ui.pages.log_data.total') }}</th>
                                        <th class="px-3 py-2">{{ __('ui.pages.log_data.pending') }}</th>
                                        <th class="px-3 py-2">{{ __('ui.pages.log_data.reserved') }}</th>
                                        <th class="px-3 py-2">{{ __('ui.pages.log_data.delayed') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse ($queueRows as $row)
                                        <tr>
                                            <td class="px-3 py-2 font-mono text-xs text-gray-900 dark:text-gray-100">{{ $row['queue'] }}</td>
                                            <td class="px-3 py-2">{{ \App\Support\LocaleFormatter::number($row['total']) }}</td>
                                            <td class="px-3 py-2">{{ \App\Support\LocaleFormatter::number($row['pending']) }}</td>
                                            <td class="px-3 py-2">{{ \App\Support\LocaleFormatter::number($row['reserved']) }}</td>
                                            <td class="px-3 py-2">{{ \App\Support\LocaleFormatter::number($row['delayed']) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500">{{ __('ui.pages.log_data.no_queue_jobs') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-md border border-gray-200 dark:border-gray-800">
                        <div class="border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            {{ __('ui.pages.log_data.upcoming_jobs') }}
                        </div>
                        <div class="max-h-80 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-800">
                            @forelse ($pendingJobs as $job)
                                <div class="px-3 py-2 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0 truncate font-medium text-gray-950 dark:text-white">{{ $job['name'] }}</div>
                                        <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $jobStateClass($job['state']) }}">{{ $job['state'] }}</span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        #{{ \App\Support\LocaleFormatter::number($job['id']) }} &middot; {{ $job['queue'] }} &middot; attempts {{ \App\Support\LocaleFormatter::number($job['attempts']) }}
                                        &middot; available {{ $job['availableAt'] ?: '-' }}
                                    </div>
                                </div>
                            @empty
                                <div class="px-3 py-6 text-center text-sm text-gray-500">{{ __('ui.pages.log_data.no_pending_jobs') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                @if (! empty($failedJobs) || ! empty($jobBatches))
                    <div class="grid gap-4 xl:grid-cols-2">
                        <div class="overflow-hidden rounded-md border border-gray-200 dark:border-gray-800">
                            <div class="border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                {{ __('ui.pages.log_data.last_failed_jobs') }}
                            </div>
                            <div class="max-h-80 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-800">
                                @forelse ($failedJobs as $job)
                                    <div class="px-3 py-2 text-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="min-w-0 truncate font-medium text-gray-950 dark:text-white">{{ $job['name'] }}</div>
                                            <span class="shrink-0 text-xs text-gray-500">{{ \App\Support\LocaleFormatter::shortDateTime($job['failedAt']) }}</span>
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">{{ $job['connection'] }} / {{ $job['queue'] }}</div>
                                        <div class="mt-1 rounded bg-red-50 px-2 py-1 text-xs text-red-700 dark:bg-red-500/10 dark:text-red-300">{{ $job['exception'] }}</div>
                                    </div>
                                @empty
                                    <div class="px-3 py-6 text-center text-sm text-gray-500">{{ __('ui.pages.log_data.no_failed_jobs') }}</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-md border border-gray-200 dark:border-gray-800">
                            <div class="border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                {{ __('ui.pages.log_data.last_batch_jobs') }}
                            </div>
                            <div class="max-h-80 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-800">
                                @forelse ($jobBatches as $batch)
                                    <div class="px-3 py-2 text-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="min-w-0 truncate font-medium text-gray-950 dark:text-white">{{ $batch['name'] }}</div>
                                            <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $jobStateClass($batch['status']) }}">{{ $batch['status'] }}</span>
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            total {{ \App\Support\LocaleFormatter::number($batch['totalJobs']) }} &middot; pending {{ \App\Support\LocaleFormatter::number($batch['pendingJobs']) }} &middot; failed {{ \App\Support\LocaleFormatter::number($batch['failedJobs']) }}
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-3 py-6 text-center text-sm text-gray-500">{{ __('ui.pages.log_data.no_batch_jobs') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                <div class="text-base font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.log_data.integration_title') }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.log_data.integration_desc') }}</div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('ui.common.time') }}</th>
                            <th class="px-4 py-3">{{ __('ui.common.code') }}</th>
                            <th class="px-4 py-3">{{ __('ui.common.status') }}</th>
                            <th class="px-4 py-3">{{ __('ui.common.endpoint') }}</th>
                            <th class="px-4 py-3">{{ __('ui.common.request') }}</th>
                            <th class="px-4 py-3">{{ __('ui.pages.log_data.response_error') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($integrationLogs as $log)
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ \App\Support\LocaleFormatter::shortDateTime($log['TglRequest']) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $log['KodeIntegrasi'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="rounded-md px-2 py-1 text-xs font-semibold {{ $log['Berhasil'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300' }}">
                                        {{ $log['Berhasil'] ? __('ui.pages.log_data.success') : __('ui.pages.log_data.failed') }}
                                        @if ($log['StatusHttp'])
                                            &middot; HTTP {{ $log['StatusHttp'] }}
                                        @endif
                                    </span>
                                </td>
                                <td class="min-w-[260px] px-4 py-3 text-gray-600 dark:text-gray-300">
                                    <div class="font-medium">{{ $log['MetodeHttp'] }}</div>
                                    <div class="break-all text-xs">{{ $log['UrlEndpoint'] }}</div>
                                </td>
                                <td class="min-w-[300px] px-4 py-3">
                                    <pre class="max-h-32 overflow-auto whitespace-pre-wrap rounded-md bg-gray-50 p-3 text-xs text-gray-700 dark:bg-gray-950 dark:text-gray-300">{{ $log['RequestJson'] ?: '-' }}</pre>
                                </td>
                                <td class="min-w-[320px] px-4 py-3">
                                    <pre class="max-h-32 overflow-auto whitespace-pre-wrap rounded-md bg-gray-50 p-3 text-xs text-gray-700 dark:bg-gray-950 dark:text-gray-300">{{ $log['PesanError'] ?: $log['ResponseJson'] ?: '-' }}</pre>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('ui.pages.log_data.no_integration_logs') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                <div class="text-base font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.log_data.webhook_title') }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.log_data.webhook_desc') }}</div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('ui.common.time') }}</th>
                            <th class="px-4 py-3">{{ __('ui.common.event') }}</th>
                            <th class="px-4 py-3">{{ __('ui.common.status') }}</th>
                            <th class="px-4 py-3">{{ __('ui.common.error') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($webhookLogs as $log)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ \App\Support\LocaleFormatter::shortDateTime($log['TglDiterima']) }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $log['JenisEvent'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-md px-2 py-1 text-xs font-semibold {{ $log['SudahDiproses'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }}">
                                        {{ $log['SudahDiproses'] ? __('ui.pages.log_data.processed') : __('ui.pages.log_data.not_processed') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $log['PesanError'] ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('ui.pages.log_data.no_webhook_logs') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
