<x-filament-panels::page>
    <div class="space-y-6" wire:poll.15s="loadDashboard">
        <x-filament::section>
            <div>
                <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Ringkasan Layanan WhatsApp
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Realtime setiap 15 detik. Update terakhir: {{ $lastUpdated ?: '-' }}
                </p>

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <x-filament::button color="gray" size="sm" variant="outline" wire:click="setQuickRange('today')">
                        Hari ini
                    </x-filament::button>
                    <x-filament::button color="gray" size="sm" variant="outline" wire:click="setQuickRange('7d')">
                        7 hari
                    </x-filament::button>
                    <x-filament::button color="gray" size="sm" variant="outline"
                        wire:click="setQuickRange('30d')">
                        30 hari
                    </x-filament::button>
                    <x-filament::button color="gray" size="sm" variant="outline"
                        wire:click="setQuickRange('month')">
                        Bulan ini
                    </x-filament::button>

                    <x-filament::modal id="custom-period-modal" width="md">
                        <x-slot name="trigger">
                            <x-filament::button color="gray" size="sm" variant="outline"
                                icon="heroicon-m-calendar"
                                class="ring-1 ring-primary-600 bg-primary-50 text-primary-600 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-400 dark:hover:bg-primary-500/20">
                                {{ $this->filters['date_range'] ?? 'Custom Periode' }}
                            </x-filament::button>
                        </x-slot>

                        <x-slot name="heading">
                            Filter Periode Data
                        </x-slot>

                        <form wire:submit.prevent="loadDashboard"
                            x-on:submit="$dispatch('close-modal', { id: 'custom-period-modal' })">
                            <div class="py-4 full-width">
                                {{ $this->getFiltersForm() }}
                            </div>
                            <div class="mt-4 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                                <x-filament::button color="gray"
                                    x-on:click="$dispatch('close-modal', { id: 'custom-period-modal' })">
                                    Batal
                                </x-filament::button>
                                <x-filament::button type="submit" color="primary">
                                    Terapkan
                                </x-filament::button>
                            </div>
                        </form>
                    </x-filament::modal>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <section
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Pesan masuk</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">
                    {{ $summary['incoming_messages'] ?? 0 }}</div>
                <div class="mt-1 text-sm text-gray-500">{{ $summary['incoming_chats'] ?? 0 }} sesi chat</div>
            </section>
            <section
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Balasan CS / AI</div>
                <div class="mt-2 text-3xl font-semibold text-blue-600">{{ $summary['outgoing_cs'] ?? 0 }} /
                    {{ $summary['outgoing_ai'] ?? 0 }}</div>
                <div class="mt-1 text-sm text-gray-500">Kontribusi manusia dan auto reply</div>
            </section>
            <section
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Belum terjawab</div>
                <div
                    class="mt-2 text-3xl font-semibold {{ ($summary['unanswered_chats'] ?? 0) > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                    {{ $summary['unanswered_chats'] ?? 0 }}</div>
                <div class="mt-1 text-sm text-gray-500">{{ $summary['unread_messages'] ?? 0 }} pesan belum dibaca total
                </div>
            </section>
            <section
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Rata-rata waktu balas</div>
                <div class="mt-2 text-3xl font-semibold text-amber-600">
                    {{ isset($summary['avg_response_minutes']) && $summary['avg_response_minutes'] !== null ? $summary['avg_response_minutes'] . 'm' : '-' }}
                </div>
                <div class="mt-1 text-sm text-gray-500">{{ $summary['tickets_created'] ?? 0 }} ticket dibuat</div>
            </section>
        </div>

        <div class="grid gap-4 xl:grid-cols-[360px_minmax(0,1fr)]">
            <section
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-base font-semibold text-gray-950 dark:text-white">Indeks Kepuasan Pelanggan</div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Estimasi operasional dari kecepatan dan
                    kualitas penanganan.</div>
                <div class="mt-5 flex items-end gap-3">
                    <div
                        class="text-5xl font-semibold {{ ($satisfaction['score'] ?? 0) >= 70 ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ $satisfaction['score'] ?? '-' }}
                    </div>
                    <div class="pb-1 text-sm font-medium text-gray-600 dark:text-gray-300">/
                        100<br>{{ $satisfaction['label'] ?? 'Belum ada data' }}</div>
                </div>
                <div class="mt-5 space-y-3">
                    @foreach ([['Response rate', $satisfaction['response_rate'] ?? 0], ['Kirim WAHA sukses', $satisfaction['delivery_rate'] ?? 0], ['Kecepatan balas', $satisfaction['speed_score'] ?? 0], ['Mapping customer', $satisfaction['mapping_rate'] ?? 0]] as [$label, $value])
                        <div>
                            <div class="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-300">
                                <span>{{ $label }}</span>
                                <span>{{ $value }}%</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full rounded-full bg-blue-600"
                                    style="width: {{ min(100, max(0, (int) $value)) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-base font-semibold text-gray-950 dark:text-white">Tren Pesan Harian</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Masuk, balasan CS, dan balasan AI dalam
                            periode.</div>
                    </div>
                    <div class="text-sm text-gray-500">Gagal WAHA: <span
                            class="font-semibold text-red-600">{{ $summary['failed_waha'] ?? 0 }}</span></div>
                </div>
                <div class="mt-4 grid gap-3">
                    @forelse ($dailyRows as $row)
                        @php($max = max(1, $row['incoming'], $row['cs'], $row['ai']))
                        <div class="grid gap-2 md:grid-cols-[70px_minmax(0,1fr)] md:items-center">
                            <div class="text-xs font-medium text-gray-500">{{ $row['date'] }}</div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 rounded-full bg-gray-900 dark:bg-gray-100"
                                        style="width: {{ max(3, ($row['incoming'] / $max) * 100) }}%"></div>
                                    <span class="w-10 text-xs text-gray-500">M {{ $row['incoming'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-2 rounded-full bg-blue-600"
                                        style="width: {{ max(3, ($row['cs'] / $max) * 100) }}%"></div>
                                    <span class="w-10 text-xs text-gray-500">CS {{ $row['cs'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-2 rounded-full bg-emerald-600"
                                        style="width: {{ max(3, ($row['ai'] / $max) * 100) }}%"></div>
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
            <section
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Performa Tim dan AI</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Kontribusi balasan, sesi ditangani, dan
                        status kirim.</div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        <thead
                            class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
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
                                        <div class="font-semibold text-gray-950 dark:text-white">{{ $row['name'] }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $row['email'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold">{{ $row['replies'] }}</td>
                                    <td class="px-4 py-3">{{ $row['chats'] }}</td>
                                    <td class="px-4 py-3 text-emerald-600">{{ $row['sent'] }}</td>
                                    <td class="px-4 py-3 text-red-600">{{ $row['failed'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada
                                        balasan pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Customer Teraktif</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sumber volume pesan tertinggi dalam periode.
                    </div>
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
                        <div class="p-6 text-center text-sm text-gray-500">Belum ada pesan masuk pada periode ini.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
