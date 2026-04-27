<x-filament-panels::page>
    <div class="space-y-6" wire:poll.15s="loadLogs">
        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                <div class="text-base font-semibold text-gray-950 dark:text-white">Log Kirim WAHA / Integrasi API</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Diambil dari TLogIntegrasi, termasuk HTTP status, request, response, dan pesan error.</div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Endpoint</th>
                            <th class="px-4 py-3">Request</th>
                            <th class="px-4 py-3">Response / Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($integrationLogs as $log)
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ \Illuminate\Support\Carbon::parse($log['TglRequest'])->format('d M H:i:s') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $log['KodeIntegrasi'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="rounded-md px-2 py-1 text-xs font-semibold {{ $log['Berhasil'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300' }}">
                                        {{ $log['Berhasil'] ? 'Berhasil' : 'Gagal' }}
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
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada log integrasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                <div class="text-base font-semibold text-gray-950 dark:text-white">Log Webhook WAHA Masuk</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Diambil dari TLogWebhookWaha untuk memastikan pesan dari WAHA diproses atau gagal.</div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Event</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($webhookLogs as $log)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ \Illuminate\Support\Carbon::parse($log['TglDiterima'])->format('d M H:i:s') }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $log['JenisEvent'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-md px-2 py-1 text-xs font-semibold {{ $log['SudahDiproses'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }}">
                                        {{ $log['SudahDiproses'] ? 'Diproses' : 'Belum diproses' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $log['PesanError'] ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada log webhook WAHA.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
