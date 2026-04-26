<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Baru', '12', 'text-blue-600'],
                ['Diteruskan Developer', '8', 'text-amber-600'],
                ['Dalam Pengerjaan', '15', 'text-indigo-600'],
                ['Overdue SLA', '3', 'text-red-600'],
            ] as $stat)
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $stat[0] }}</div>
                    <div class="mt-2 text-2xl font-semibold {{ $stat[2] }}">{{ $stat[1] }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
                    <div>
                        <div class="text-base font-semibold text-gray-950 dark:text-white">Antrian Ticket</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Ticket dari chat customer sampai developer queue</div>
                    </div>
                    <button class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Ticket Baru</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Nomor</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Masalah</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">PIC</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ([
                                ['TCK-20260426-001', 'PT Maju Sistem', 'Session expired setelah update', 'Diteruskan Developer', 'Andi Dev'],
                                ['TCK-20260426-002', 'RS Sentosa', 'Reset password massal', 'Dianalisa CS', 'Rina CS'],
                                ['TCK-20260425-014', 'CV Sinar Data', 'Selisih laporan stok', 'Dalam Pengerjaan', 'Budi Dev'],
                            ] as $ticket)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $ticket[0] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">{{ $ticket[1] }}</td>
                                    <td class="min-w-64 px-4 py-3 text-gray-600 dark:text-gray-300">{{ $ticket[2] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ $ticket[3] }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $ticket[4] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-base font-semibold text-gray-950 dark:text-white">Timeline Ticket</div>
                <div class="mt-4 space-y-4">
                    @foreach ([
                        ['09:20', 'Ticket dibuat dari chat WhatsApp'],
                        ['09:26', 'AI membuat ringkasan dan prioritas'],
                        ['09:32', 'Supervisor meneruskan ke developer'],
                        ['09:45', 'Developer mulai investigasi'],
                    ] as $item)
                        <div class="flex gap-3">
                            <div class="mt-1 h-2 w-2 rounded-full bg-blue-600"></div>
                            <div>
                                <div class="text-xs text-gray-500">{{ $item[0] }}</div>
                                <div class="text-sm text-gray-800 dark:text-gray-100">{{ $item[1] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
