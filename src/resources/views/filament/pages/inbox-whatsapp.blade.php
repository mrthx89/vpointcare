<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Chat baru</div>
                <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">18</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Menunggu CS</div>
                <div class="mt-2 text-2xl font-semibold text-amber-600">7</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Dalam proses</div>
                <div class="mt-2 text-2xl font-semibold text-blue-600">26</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Ticket dibuat</div>
                <div class="mt-2 text-2xl font-semibold text-emerald-600">9</div>
            </div>
        </div>

        <div class="grid min-h-[680px] gap-4 xl:grid-cols-[320px_minmax(0,1fr)_340px]">
            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Daftar Chat</div>
                    <div class="mt-3">
                        <input class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950" placeholder="Cari customer atau nomor WA">
                    </div>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ([
                        ['PT Maju Sistem', '0812 4400 1133', 'Tidak bisa login setelah update', 'Menunggu CS', '2m'],
                        ['RS Sentosa', '0821 9900 7701', 'Request reset password user kasir', 'Dalam Proses', '14m'],
                        ['CV Sinar Data', '0857 2020 9912', 'Laporan stok tidak sesuai', 'Butuh Ticket', '31m'],
                        ['Klinik Medika', '0878 5510 7772', 'Konfirmasi jadwal maintenance', 'Menunggu Customer', '1j'],
                    ] as $chat)
                        <button type="button" class="block w-full p-4 text-left hover:bg-gray-50 dark:hover:bg-gray-800/60">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $chat[0] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $chat[1] }}</div>
                                </div>
                                <div class="shrink-0 text-xs text-gray-400">{{ $chat[4] }}</div>
                            </div>
                            <div class="mt-2 line-clamp-2 text-sm text-gray-600 dark:text-gray-300">{{ $chat[2] }}</div>
                            <div class="mt-3 inline-flex rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ $chat[3] }}</div>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="flex min-h-[620px] flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
                    <div>
                        <div class="text-base font-semibold text-gray-950 dark:text-white">PT Maju Sistem</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">0812 4400 1133 · Ditangani oleh Rina CS</div>
                    </div>
                    <div class="inline-flex rounded-md bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">Menunggu CS</div>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto bg-gray-50 p-4 dark:bg-gray-950/60">
                    <div class="max-w-[82%] rounded-lg bg-white p-3 text-sm shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                        <div class="text-xs font-medium text-gray-500">Customer · 09:14</div>
                        <p class="mt-1 text-gray-800 dark:text-gray-100">Aplikasi tidak bisa login setelah update pagi ini. Muncul pesan session expired.</p>
                    </div>
                    <div class="ml-auto max-w-[82%] rounded-lg bg-blue-600 p-3 text-sm text-white shadow-sm">
                        <div class="text-xs font-medium text-blue-100">Rina CS · 09:16</div>
                        <p class="mt-1">Baik, kami cek. Mohon kirim username dan screenshot pesan errornya.</p>
                    </div>
                    <div class="max-w-[82%] rounded-lg bg-white p-3 text-sm shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                        <div class="text-xs font-medium text-gray-500">Customer · 09:18</div>
                        <p class="mt-1 text-gray-800 dark:text-gray-100">Sudah kami kirim. Ini terjadi di 4 user sekaligus.</p>
                    </div>
                </div>

                <div class="border-t border-gray-200 p-4 dark:border-gray-800">
                    <textarea class="min-h-24 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950" placeholder="Tulis balasan WhatsApp"></textarea>
                    <div class="mt-3 flex flex-wrap justify-end gap-2">
                        <button class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Catatan Internal</button>
                        <button class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Kirim Balasan</button>
                    </div>
                </div>
            </section>

            <aside class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Profil Customer</div>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-gray-500">Instansi</dt><dd class="font-medium text-gray-900 dark:text-white">PT Maju Sistem</dd></div>
                        <div><dt class="text-gray-500">Produk</dt><dd class="font-medium text-gray-900 dark:text-white">VPoint ERP</dd></div>
                        <div><dt class="text-gray-500">Status</dt><dd class="font-medium text-emerald-600">Aktif</dd></div>
                    </dl>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">AI Suggestion</div>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">Masalah berpotensi terkait session/token setelah update. Sarankan buat ticket kategori Bug Aplikasi dengan prioritas Tinggi.</p>
                    <button class="mt-4 w-full rounded-md bg-gray-950 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-950">Buat Draft Ticket</button>
                </div>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
