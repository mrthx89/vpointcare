<x-filament-panels::page>
    <div class="flex h-[calc(100dvh-8rem)] min-h-[680px] flex-col gap-4 overflow-hidden" wire:poll.15s="loadInbox">
        <div class="grid shrink-0 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total chat</div>
                <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $stats['baru'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Belum dibaca</div>
                <div class="mt-2 text-2xl font-semibold text-amber-600">{{ $stats['belum_dibaca'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Chat grup</div>
                <div class="mt-2 text-2xl font-semibold text-blue-600">{{ $stats['grup'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Belum dipetakan</div>
                <div class="mt-2 text-2xl font-semibold text-red-600">{{ $stats['unknown'] ?? 0 }}</div>
            </div>
        </div>

        <div class="grid min-h-0 flex-1 gap-4 xl:grid-cols-[340px_minmax(0,1fr)_340px]">
            <section
                class="flex min-h-0 flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="shrink-0 border-b border-gray-200 p-4 dark:border-gray-800">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Daftar Chat</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Data masuk dari webhook WAHA.</div>
                    <div class="mt-4 space-y-2">
                        <input type="text" wire:model.live.debounce.300ms="filterText"
                            class="h-11 w-full rounded-md border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"
                            placeholder="Filter nama, nomor WA, atau ID WAHA">
                        <div class="flex items-center gap-1.5 overflow-x-auto whitespace-nowrap text-[11px] font-medium">
                            <label
                                class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-md border border-gray-200 px-2 py-1.5 text-gray-700 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 dark:border-gray-700 dark:text-gray-200 dark:has-[:checked]:border-blue-500 dark:has-[:checked]:bg-blue-500/10 dark:has-[:checked]:text-blue-300">
                                <input type="radio" wire:model.live="filterType" value="pribadi"
                                    class="h-3 w-3 border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>Pribadi</span>
                            </label>
                            <label
                                class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-md border border-gray-200 px-2 py-1.5 text-gray-700 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 dark:border-gray-700 dark:text-gray-200 dark:has-[:checked]:border-blue-500 dark:has-[:checked]:bg-blue-500/10 dark:has-[:checked]:text-blue-300">
                                <input type="radio" wire:model.live="filterType" value="grup"
                                    class="h-3 w-3 border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>Grup</span>
                            </label>
                            <label
                                class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-md border border-gray-200 px-2 py-1.5 text-gray-700 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 dark:border-gray-700 dark:text-gray-200 dark:has-[:checked]:border-blue-500 dark:has-[:checked]:bg-blue-500/10 dark:has-[:checked]:text-blue-300">
                                <input type="radio" wire:model.live="filterType" value="keduanya"
                                    class="h-3 w-3 border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>Keduanya</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="min-h-0 flex-1 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-800">
                    @forelse ($chatRows as $chat)
                        <button type="button" wire:click="selectChat('{{ $chat['Id'] }}')"
                            class="block w-full p-4 text-left hover:bg-gray-50 dark:hover:bg-gray-800/60 {{ $selectedChatId === $chat['Id'] ? 'bg-blue-50 dark:bg-blue-950/30' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $chat['NamaInstansi'] }}</div>
                                    <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                                        @if ($chat['JenisChat'] === 'Grup')
                                            Grup: {{ $chat['NamaGrupWhatsapp'] ?: 'Belum dikenal' }}
                                        @else
                                            {{ $chat['NamaKontak'] }} &middot; {{ $chat['NomorWhatsapp'] }}
                                        @endif
                                    </div>
                                    @if ($chat['IdWaha'])
                                        <div class="truncate text-xs text-gray-400 dark:text-gray-500">
                                            ID WAHA: {{ $chat['IdWaha'] }}
                                        </div>
                                    @endif
                                </div>
                                @if ($chat['BelumDibaca'] > 0)
                                    <div
                                        class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                        {{ $chat['BelumDibaca'] }}</div>
                                @endif
                            </div>
                            <div class="mt-2 line-clamp-2 text-sm text-gray-600 dark:text-gray-300">
                                {{ $chat['PesanTerakhir'] }}</div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span
                                    class="rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ $chat['Status'] }}</span>
                                <span
                                    class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $chat['JenisChat'] }}</span>
                                @if ($chat['AutoReplyAiAktif'])
                                    <span
                                        class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">AI
                                        aktif</span>
                                @endif
                            </div>
                        </button>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">Belum ada chat. Kirim POST ke endpoint
                            webhook WAHA untuk mulai mengisi inbox.</div>
                    @endforelse
                </div>
            </section>

            <section
                class="flex min-h-0 flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                @if ($selectedChat)
                    <div
                        class="shrink-0 flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
                        <div>
                            <div class="text-base font-semibold text-gray-950 dark:text-white">
                                {{ $selectedChat['NamaInstansi'] }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                @if ($selectedChat['JenisChat'] === 'Grup')
                                    {{ $selectedChat['NamaGrupWhatsapp'] ?: 'Grup belum dipetakan' }} &middot;
                                    {{ $selectedChat['NomorWhatsapp'] }}
                                @else
                                    {{ $selectedChat['NamaKontak'] }} &middot; {{ $selectedChat['NomorWhatsapp'] }}
                                @endif
                                @if ($selectedChat['IdWaha'])
                                    &middot; ID WAHA: {{ $selectedChat['IdWaha'] }}
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($selectedChat['AutoReplyAiAktif'])
                                <div
                                    class="inline-flex rounded-md bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                    AI Auto Reply</div>
                            @endif
                            <div
                                class="inline-flex rounded-md bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                {{ $selectedChat['Status'] }}</div>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-gray-50 p-4 dark:bg-gray-950/60">
                        @forelse ($messages as $message)
                            @php($isOut = $message['ArahPesan'] === 'Keluar')
                            @php($hasMedia = $message['MediaCategory'] !== 'text')
                            <div
                                class="{{ $isOut ? 'ml-auto bg-blue-600 text-white' : 'bg-white text-gray-800 ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-800' }} max-w-[86%] rounded-lg p-3 text-sm shadow-sm">
                                <div class="{{ $isOut ? 'text-blue-100' : 'text-gray-500' }} text-xs font-medium">
                                    {{ $isOut ? ($message['DihasilkanOlehAi'] ? 'AI Agent' : 'CS') : ($message['PengirimNamaKontak'] ?: $message['PengirimNomorWhatsapp'] ?: 'Customer') }}
                                    &middot;
                                    {{ \Illuminate\Support\Carbon::parse($message['TglPesan'])->format('d M H:i') }}
                                    @if ($message['StatusKirim'])
                                        &middot; {{ $message['StatusKirim'] }}
                                    @endif
                                </div>
                                @if ($hasMedia)
                                    <div class="mt-2 overflow-hidden rounded-md {{ $isOut ? 'bg-blue-700/40' : 'bg-gray-100 dark:bg-gray-950' }}">
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
                                        @elseif ($message['MediaUrl'])
                                            <a href="{{ $message['MediaUrl'] }}" target="_blank" rel="noopener"
                                                class="{{ $isOut ? 'text-blue-50 hover:text-white' : 'text-blue-700 hover:text-blue-900 dark:text-blue-300 dark:hover:text-blue-100' }} block px-3 py-2 text-sm font-medium underline underline-offset-2">
                                                {{ $message['MediaLabel'] }}
                                            </a>
                                        @else
                                            <div class="px-3 py-2 text-sm {{ $isOut ? 'text-blue-50' : 'text-gray-600 dark:text-gray-300' }}">
                                                {{ $message['MediaLabel'] }} diterima, URL media belum tersedia.
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                @if ($message['IsiPesan'])
                                    <p class="mt-2 whitespace-pre-line">{{ $message['IsiPesan'] }}</p>
                                @elseif (! $hasMedia)
                                    <p class="mt-1 whitespace-pre-line">[pesan non-teks]</p>
                                @endif
                                @if ($message['PesanError'])
                                    <div
                                        class="mt-2 rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-200">
                                        Error: {{ $message['PesanError'] }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="p-6 text-center text-sm text-gray-500">Belum ada pesan di chat ini.</div>
                        @endforelse
                    </div>

                    <form wire:submit.prevent="kirimBalasanWaha"
                        x-data="{
                            handlePaste(event) {
                                const items = event.clipboardData?.items || [];

                                for (const item of items) {
                                    if (item.kind !== 'file') {
                                        continue;
                                    }

                                    const file = item.getAsFile();

                                    if (! file) {
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
                        class="shrink-0 border-t border-gray-200 p-4 dark:border-gray-800">
                        <input x-ref="attachmentInput" type="file" wire:model="attachment"
                            accept="image/*,video/*,audio/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.zip,.rar"
                            class="hidden">
                        <textarea wire:model="replyText"
                            class="min-h-24 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"
                            placeholder="Tulis balasan WhatsApp. Paste gambar/video dengan Ctrl+V."></textarea>
                        @error('replyText')
                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                        @enderror
                        @error('attachment')
                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                        @enderror
                        <div wire:loading wire:target="attachment" class="mt-2 text-xs font-medium text-blue-600 dark:text-blue-300">
                            Mengunggah lampiran...
                        </div>
                        @if ($attachment)
                            <div
                                class="mt-2 flex items-center justify-between gap-3 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                                <div class="min-w-0 truncate">
                                    Lampiran: {{ $attachment->getClientOriginalName() }}
                                </div>
                                <button type="button" wire:click="removeAttachment"
                                    class="shrink-0 font-semibold text-blue-700 hover:text-blue-900 dark:text-blue-200 dark:hover:text-white">Hapus</button>
                            </div>
                        @endif
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <button type="button" @click="$refs.attachmentInput.click()"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Lampirkan
                                File</button>
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button"
                                    class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Catatan
                                    Internal</button>
                                <button type="button" wire:click="simpanBalasanLokal"
                                    class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Simpan
                                    Draft</button>
                                <button
                                    class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-70"
                                    wire:loading.attr="disabled"
                                    wire:target="attachment,kirimBalasanWaha">Kirim
                                    ke WhatsApp</button>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="flex flex-1 items-center justify-center p-6 text-sm text-gray-500">Pilih chat untuk
                        melihat percakapan.</div>
                @endif
            </section>

            <aside class="min-h-0 space-y-4 overflow-y-auto">
                <div
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-base font-semibold text-gray-950 dark:text-white">Profil Mapping</div>
                        @if ($selectedChat)
                            <button type="button" wire:click="refreshMappingChat"
                                class="rounded-md border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Refresh</button>
                        @endif
                    </div>
                    @if ($selectedChat)
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Klien</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">
                                    {{ $selectedChat['NamaInstansi'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Jenis Chat</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $selectedChat['JenisChat'] }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Kontak</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $selectedChat['NamaKontak'] }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Grup</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">
                                    {{ $selectedChat['NamaGrupWhatsapp'] ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">ID WAHA</dt>
                                <dd class="break-all font-medium text-gray-900 dark:text-white">
                                    {{ $selectedChat['IdWaha'] ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">ID terdeteksi</dt>
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
                    @else
                        <div class="mt-3 text-sm text-gray-500">Belum ada chat dipilih.</div>
                    @endif
                </div>
                <div
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Kontrol AI Chat</div>
                    @if ($selectedChat)
                        <div class="mt-4 space-y-3 text-sm">
                            <div
                                class="rounded-md {{ $selectedChat['AutoReplyAiAktif'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }} px-3 py-2 font-medium">
                                {{ $selectedChat['AutoReplyAiAktif'] ? 'AI akan terus menjawab sesi ini.' : 'AI hanya mengikuti setting global.' }}
                            </div>
                            <div class="grid gap-2 text-gray-600 dark:text-gray-300">
                                <div>Sapaan AI: <span
                                        class="font-medium text-gray-950 dark:text-white">{{ $selectedChat['AiSudahMenyapa'] ? 'Sudah' : 'Belum' }}</span>
                                </div>
                                <div>Terakhir AI: <span
                                        class="font-medium text-gray-950 dark:text-white">{{ $selectedChat['TglAutoReplyAiTerakhir'] ? \Illuminate\Support\Carbon::parse($selectedChat['TglAutoReplyAiTerakhir'])->format('d M Y H:i') : '-' }}</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="toggleAutoReplyAi"
                                    class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                    {{ $selectedChat['AutoReplyAiAktif'] ? 'Matikan Auto Reply' : 'Aktifkan Auto Reply' }}
                                </button>
                                <button type="button" wire:click="resetSapaanAi"
                                    class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Reset
                                    Sapaan</button>
                            </div>
                        </div>
                    @else
                        <div class="mt-3 text-sm text-gray-500">Pilih chat untuk mengatur AI per sesi.</div>
                    @endif
                </div>
                <div
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Webhook WAHA</div>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">Endpoint lokal:</p>
                    <code
                        class="mt-2 block rounded-md bg-gray-100 p-3 text-xs text-gray-700 dark:bg-gray-950 dark:text-gray-300">POST
                        /webhooks/waha/{token}</code>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">Jika token dikosongkan di .env, endpoint
                        menerima request tanpa token.</p>
                </div>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
