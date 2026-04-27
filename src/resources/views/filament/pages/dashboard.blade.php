<x-filament-panels::page>
    <div class="space-y-6" wire:poll.15s="loadDashboard">
        <form wire:submit.prevent="loadDashboard" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Ringkasan Layanan WhatsApp</div>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Realtime setiap 15 detik. Update terakhir: {{ $lastUpdated ?: '-' }}</div>
                </div>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Dari</label>
                        <input type="date" wire:model="startDate" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Sampai</label>
                        <input type="date" wire:model="endDate" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                    </div>
                    <button type="submit" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Terapkan</button>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" wire:click="setQuickRange('today')" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Hari ini</button>
                <button type="button" wire:click="setQuickRange('7d')" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">7 hari</button>
                <button type="button" wire:click="setQuickRange('30d')" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">30 hari</button>
                <button type="button" wire:click="setQuickRange('month')" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Bulan ini</button>
            </div>
        </form>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Pesan masuk</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $summary['incoming_messages'] ?? 0 }}</div>
                <div class="mt-1 text-sm text-gray-500">{{ $summary['incoming_chats'] ?? 0 }} sesi chat</div>
            </section>
            <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Balasan CS / AI</div>
                <div class="mt-2 text-3xl font-semibold text-blue-600">{{ $summary['outgoing_cs'] ?? 0 }} / {{ $summary['outgoing_ai'] ?? 0 }}</div>
                <div class="mt-1 text-sm text-gray-500">Kontribusi manusia dan auto reply</div>
            </section>
            <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Belum terjawab</div>
                <div class="mt-2 text-3xl font-semibold {{ ($summary['unanswered_chats'] ?? 0) > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $summary['unanswered_chats'] ?? 0 }}</div>
                <div class="mt-1 text-sm text-gray-500">{{ $summary['unread_messages'] ?? 0 }} pesan belum dibaca total</div>
            </section>
            <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Rata-rata waktu balas</div>
                <div class="mt-2 text-3xl font-semibold text-amber-600">{{ isset($summary['avg_response_minutes']) && $summary['avg_response_minutes'] !== null ? $summary['avg_response_minutes'] . 'm' : '-' }}</div>
                <div class="mt-1 text-sm text-gray-500">{{ $summary['tickets_created'] ?? 0 }} ticket dibuat</div>
            </section>
        </div>

        <div class="grid gap-4 xl:grid-cols-[360px_minmax(0,1fr)]">
            <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-base font-semibold text-gray-950 dark:text-white">Indeks Kepuasan Pelanggan</div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Estimasi operasional dari kecepatan dan kualitas penanganan.</div>
                <div class="mt-5 flex items-end gap-3">
                    <div class="text-5xl font-semibold {{ ($satisfaction['score'] ?? 0) >= 70 ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ $satisfaction['score'] ?? '-' }}
                    </div>
                    <div class="pb-1 text-sm font-medium text-gray-600 dark:text-gray-300">/ 100<br>{{ $satisfaction['label'] ?? 'Belum ada data' }}</div>
                </div>
                <div class="mt-5 space-y-3">
                    @foreach ([
                        ['Response rate', $satisfaction['response_rate'] ?? 0],
                        ['Kirim WAHA sukses', $satisfaction['delivery_rate'] ?? 0],
                        ['Kecepatan balas', $satisfaction['speed_score'] ?? 0],
                        ['Mapping customer', $satisfaction['mapping_rate'] ?? 0],
                    ] as [$label, $value])
                        <div>
                            <div class="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-300">
                                <span>{{ $label }}</span>
                                <span>{{ $value }}%</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full rounded-full bg-blue-600" style="width: {{ min(100, max(0, (int) $value)) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-base font-semibold text-gray-950 dark:text-white">Tren Pesan Harian</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Masuk, balasan CS, dan balasan AI dalam periode.</div>
                    </div>
                    <div class="text-sm text-gray-500">Gagal WAHA: <span class="font-semibold text-red-600">{{ $summary['failed_waha'] ?? 0 }}</span></div>
                </div>
                <div class="mt-4 grid gap-3">
                    @forelse ($dailyRows as $row)
                        @php($max = max(1, $row['incoming'], $row['cs'], $row['ai']))
                        <div class="grid gap-2 md:grid-cols-[70px_minmax(0,1fr)] md:items-center">
                            <div class="text-xs font-medium text-gray-500">{{ $row['date'] }}</div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 rounded-full bg-gray-900 dark:bg-gray-100" style="width: {{ max(3, ($row['incoming'] / $max) * 100) }}%"></div>
                                    <span class="w-10 text-xs text-gray-500">M {{ $row['incoming'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-2 rounded-full bg-blue-600" style="width: {{ max(3, ($row['cs'] / $max) * 100) }}%"></div>
                                    <span class="w-10 text-xs text-gray-500">CS {{ $row['cs'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-2 rounded-full bg-emerald-600" style="width: {{ max(3, ($row['ai'] / $max) * 100) }}%"></div>
                                    <span class="w-10 text-xs text-gray-500">AI {{ $row['ai'] }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">Belum ada data pesan pada periode ini.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Performa Tim dan AI</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Kontribusi balasan, sesi ditangani, dan status kirim.</div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3">Balasan</th>
                                <th class="px-4 py-3">Chat</th>
                                <th class="px-4 py-3">Terkirim</th>
                                <th class="px-4 py-3">Gagal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($teamRows as $row)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-950 dark:text-white">{{ $row['name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $row['email'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold">{{ $row['replies'] }}</td>
                                    <td class="px-4 py-3">{{ $row['chats'] }}</td>
                                    <td class="px-4 py-3 text-emerald-600">{{ $row['sent'] }}</td>
                                    <td class="px-4 py-3 text-red-600">{{ $row['failed'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada balasan pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Customer Teraktif</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sumber volume pesan tertinggi dalam periode.</div>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($topClients as $client)
                        <div class="flex items-center justify-between gap-4 p-4">
                            <div>
                                <div class="font-semibold text-gray-950 dark:text-white">{{ $client['name'] }}</div>
                                <div class="text-sm text-gray-500">{{ $client['chats'] }} sesi chat</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-semibold text-blue-600">{{ $client['messages'] }}</div>
                                <div class="text-xs text-gray-500">pesan</div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">Belum ada pesan masuk pada periode ini.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
