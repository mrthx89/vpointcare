<x-filament-panels::page>
    <div class="grid gap-4 xl:grid-cols-3">
        @foreach ([
            ['Webhook WAHA', 'TLogWebhookWaha', 'Payload pesan masuk dari WAHA, status proses, dan error webhook.'],
            ['Integrasi API', 'TLogIntegrasi', 'Request dan response API custom master customer.'],
            ['AI Request', 'TAiPermintaan / TAiRespon', 'Prompt, response, token, biaya estimasi, dan approval draft.'],
            ['Aktivitas User', 'TLogAktivitas', 'Login, assignment chat, perubahan ticket, dan aktivitas admin panel.'],
            ['Error Aplikasi', 'TLogError', 'Exception, stack trace, context, dan level error aplikasi.'],
            ['Chat Detail', 'TChatD', 'Pesan masuk/keluar, pengirim, status kirim, dan payload mentah.'],
        ] as $log)
            <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-base font-semibold text-gray-950 dark:text-white">{{ $log[0] }}</div>
                <div class="mt-1 text-xs font-medium uppercase tracking-wide text-blue-600">{{ $log[1] }}</div>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $log[2] }}</p>
                <div class="mt-4 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-500 dark:bg-gray-950 dark:text-gray-400">Belum ada data live. Tabel siap sesuai DATABASE_SCHEMA_WACS.sql.</div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
