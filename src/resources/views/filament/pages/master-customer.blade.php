<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500">Instansi Aktif</div>
                <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">42</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500">Customer Aktif</div>
                <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">186</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500">Nomor WhatsApp</div>
                <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">231</div>
            </div>
        </div>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
                <div>
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Data Customer</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Master lokal VPoint Care, dapat disinkronkan dari API custom perusahaan.</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Sinkron API</button>
                    <button class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Customer Baru</button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Instansi</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">WhatsApp</th>
                            <th class="px-4 py-3">Sinkron Terakhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ([
                            ['CST-001', 'PT Maju Sistem', 'Dewi Kartika', '0812 4400 1133', '26 Apr 2026 09:00'],
                            ['CST-002', 'RS Sentosa', 'Agus Pratama', '0821 9900 7701', '26 Apr 2026 08:30'],
                            ['CST-003', 'CV Sinar Data', 'Lina Wijaya', '0857 2020 9912', '25 Apr 2026 16:45'],
                        ] as $customer)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $customer[0] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">{{ $customer[1] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">{{ $customer[2] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $customer[3] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ $customer[4] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
