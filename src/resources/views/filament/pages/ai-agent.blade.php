<x-filament-panels::page>
    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                <div class="text-base font-semibold text-gray-950 dark:text-white">AI Agent Workspace</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Draft balasan, ringkasan chat, dan rekomendasi ticket sebelum dikirim oleh CS.</div>
            </div>
            <div class="grid gap-4 p-4 lg:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Konteks Chat</label>
                    <textarea class="mt-2 min-h-64 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">Customer melaporkan tidak bisa login setelah update. Error session expired pada 4 user.</textarea>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Draft AI</label>
                    <div class="mt-2 min-h-64 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200">
                        <p class="font-medium text-gray-950 dark:text-white">Ringkasan</p>
                        <p class="mt-2">Masalah login massal setelah update, kemungkinan berkaitan dengan session/token.</p>
                        <p class="mt-4 font-medium text-gray-950 dark:text-white">Rekomendasi</p>
                        <p class="mt-2">Buat ticket kategori Bug Aplikasi dengan prioritas Tinggi dan minta screenshot serta username terdampak.</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap justify-end gap-2 border-t border-gray-200 p-4 dark:border-gray-800">
                <button class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Ringkas Chat</button>
                <button class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Buat Draft Balasan</button>
            </div>
        </section>

        <aside class="space-y-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-base font-semibold text-gray-950 dark:text-white">Guardrail</div>
                <ul class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    <li>AI hanya membuat draft.</li>
                    <li>CS tetap approve sebelum pesan dikirim.</li>
                    <li>Prompt dan response disimpan ke log.</li>
                    <li>Data sensitif dapat dibatasi per provider.</li>
                </ul>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-base font-semibold text-gray-950 dark:text-white">Provider</div>
                <div class="mt-3 rounded-md bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">OpenAI / ChatGPT API siap dikonfigurasi</div>
            </div>
        </aside>
    </div>
</x-filament-panels::page>
