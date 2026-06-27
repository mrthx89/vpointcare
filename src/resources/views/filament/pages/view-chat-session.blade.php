<x-filament-panels::page>
    @if ($errorMessage)
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <svg class="mx-auto mb-4 h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200">{{ $errorMessage }}</h2>
        </div>
    @elseif ($session)
        @if (\App\Support\FilamentAccess::can(\App\Support\AccessPermissions::KNOWLEDGE_MANAGE))
            <div class="mb-4 flex justify-end">
                <x-filament::button type="button" color="info" wire:click="buatDraftKnowledge"
                    wire:loading.attr="disabled" wire:target="buatDraftKnowledge">
                    {{ __('ui.ai_learning.create_draft_button') }}
                </x-filament::button>
            </div>
        @endif
        {{-- Header Info Sesi --}}
        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">

                {{-- Nama kontak & status --}}
                <div class="flex items-center gap-2 min-w-0">
                    <span
                        class="text-base font-bold text-gray-900 dark:text-white truncate">{{ $session['NamaKontak'] }}</span>
                    <span
                        class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium
                    {{ str_contains(strtolower($session['Status']), 'selesai') ||
                    str_contains(strtolower($session['Status']), 'tutup')
                        ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                        : 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' }}">
                        {{ $session['Status'] }}
                    </span>
                </div>

                <span class="text-gray-300 dark:text-gray-600 hidden sm:inline">&bull;</span>

                {{-- Info singkat --}}
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                    <span>{{ $session['NomorWhatsapp'] }}</span>
                    @if ($session['NamaCustomer'])
                        <span class="hidden sm:inline text-gray-300 dark:text-gray-600">&bull;</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $session['NamaCustomer'] }}</span>
                    @endif
                    @if ($session['NamaInstansi'])
                        <span class="hidden sm:inline text-gray-300 dark:text-gray-600">&bull;</span>
                        <span>{{ $session['NamaInstansi'] }}</span>
                    @endif
                </div>

                {{-- Spacer --}}
                <div class="flex-1"></div>

                {{-- Meta kanan --}}
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <span class="text-emerald-600 dark:text-emerald-400">
                        &#x1F9D1; {{ __('ui.pages.view_chat.handled_by') }}: <strong>{{ $session['NamaCS'] }}</strong>
                    </span>
                    <span class="text-gray-400 dark:text-gray-500">
                        &#x1F552; {{ $session['TglTerakhir'] }}
                    </span>
                    <span
                        class="rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        {{ __('ui.pages.view_chat.read_only') }}
                    </span>
                </div>

            </div>
        </div>


        {{-- ── Body: Chat + Notes ── --}}
        <div class="mt-4 flex gap-4" style="height: calc(100vh - 260px); min-height: 400px;">

            {{-- Kolom Kiri: Percakapan (2/3) --}}
            <div
                class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">

                {{-- Header --}}
                <div
                    class="flex items-center border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        &#x1F4AC; {{ __('ui.pages.view_chat.conversation_history') }}
                        <span
                            class="ml-1 font-normal text-gray-400">({{ \App\Support\LocaleFormatter::number(count($messages)) }}
                            {{ __('ui.common.messages') }})</span>
                    </span>
                </div>

                {{-- Pesan --}}
                <div
                    class="min-h-0 flex-1 space-y-4 overflow-y-auto overflow-x-hidden bg-gray-50 p-4 dark:bg-gray-950/60">
                    @forelse ($messages as $msg)
                        @php
                            $isOut = $msg['IsOutgoing'];
                            $jenis = strtolower($msg['JenisPesan'] ?? '');
                            $hasMedia = !in_array($jenis, ['', 'text', 'teks', 'chat']);
                            $isImage =
                                str_contains($jenis, 'image') ||
                                str_contains($jenis, 'gambar') ||
                                str_contains($jenis, 'photo') ||
                                str_contains($jenis, 'stiker');
                            $isVideo = str_contains($jenis, 'video');
                            $isAudio =
                                str_contains($jenis, 'audio') ||
                                str_contains($jenis, 'voice') ||
                                str_contains($jenis, 'ptt');
                            $mediaUrl = $msg['UrlMedia']
                                ? route('admin.waha-media.show', ['message' => $msg['Id']])
                                : null;
                            $mediaLabel = $msg['NamaFileMedia'] ?: strtoupper($jenis ?: 'FILE');
                        @endphp

                        <div
                            class="{{ $isOut ? 'ml-auto bg-blue-600 text-white' : 'bg-white text-gray-800 ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-800' }} max-w-[86%] rounded-lg p-3 text-sm shadow-sm">

                            {{-- Label pengirim & waktu --}}
                            <div class="{{ $isOut ? 'text-blue-100' : 'text-gray-500' }} text-xs font-medium">
                                {{ $isOut ? $session['NamaCS'] : ($msg['PengirimNamaKontak'] ?: __('ui.common.customer')) }}
                                &middot;
                                {{ $msg['TglFormatted'] }}
                                @if ($msg['StatusKirim'])
                                    &middot; {{ $msg['StatusKirim'] }}
                                @endif
                            </div>

                            {{-- Media --}}
                            @if ($hasMedia)
                                <div
                                    class="mt-2 overflow-hidden rounded-md {{ $isOut ? 'bg-blue-700/40' : 'bg-gray-100 dark:bg-gray-950' }}">
                                    @if ($mediaUrl && $isImage)
                                        <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="block">
                                            <img src="{{ $mediaUrl }}" alt="{{ $mediaLabel }}"
                                                class="max-h-80 w-full object-contain">
                                        </a>
                                    @elseif ($mediaUrl && $isVideo)
                                        <video controls preload="metadata" class="max-h-80 w-full">
                                            <source src="{{ $mediaUrl }}">
                                        </video>
                                    @elseif ($mediaUrl && $isAudio)
                                        <div class="p-3">
                                            <audio controls preload="metadata" class="w-full">
                                                <source src="{{ $mediaUrl }}">
                                            </audio>
                                        </div>
                                    @elseif ($mediaUrl)
                                        <a href="{{ $mediaUrl }}" target="_blank" rel="noopener"
                                            class="{{ $isOut ? 'text-blue-50 hover:text-white' : 'text-blue-700 hover:text-blue-900 dark:text-blue-300' }} block px-3 py-2 text-sm font-medium underline underline-offset-2">
                                            {{ $mediaLabel }}
                                        </a>
                                    @else
                                        <div
                                            class="px-3 py-2 text-sm {{ $isOut ? 'text-blue-50' : 'text-gray-600 dark:text-gray-300' }}">
                                            {{ __('ui.pages.view_chat.media_unavailable', ['file' => $mediaLabel]) }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Teks pesan --}}
                            @if ($msg['IsiPesan'])
                                <p class="mt-2 whitespace-pre-line">{{ $msg['IsiPesan'] }}</p>
                            @elseif (!$hasMedia)
                                <p class="mt-1 whitespace-pre-line text-xs opacity-60">
                                    {{ __('ui.pages.view_chat.non_text_message') }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">{{ __('ui.pages.view_chat.no_messages') }}
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Kolom Kanan: Catatan Internal --}}
            <div class="flex w-72 shrink-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
                style="max-width: 320px;">

                {{-- Header --}}
                <div
                    class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">&#9998;
                        {{ __('ui.pages.view_chat.internal_notes') }}</span>
                    @if (count($internalNotes) > 0)
                        <span class="ml-auto rounded-full bg-indigo-500 px-2 py-0.5 text-xs font-bold text-white">
                            {{ \App\Support\LocaleFormatter::number(count($internalNotes)) }}
                        </span>
                    @endif
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-3 space-y-3">
                    @forelse ($internalNotes as $note)
                        <div
                            class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-wrap leading-relaxed">
                                {{ $note['IsiCatatan'] }}</p>
                            <div class="mt-2 flex items-center justify-between text-xs">
                                <span
                                    class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $note['NamaPembuat'] }}</span>
                                <span class="text-gray-400">{{ $note['TglFormatted'] }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-gray-400">
                            {{ __('ui.pages.view_chat.no_internal_notes') }}</p>
                    @endforelse
                </div>
            </div>

        </div>
    @endif
</x-filament-panels::page>
