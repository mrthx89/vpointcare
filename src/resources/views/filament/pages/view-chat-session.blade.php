<x-filament-panels::page>
    @if ($errorMessage)
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <svg class="mx-auto mb-4 h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
            class="rounded-2xl border border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-3">

                {{-- Nama kontak & status --}}
                <div class="flex items-center gap-3 min-w-0">
                    <span
                        class="text-lg font-extrabold text-gray-900 dark:text-white truncate">{{ $session['NamaKontak'] }}</span>
                    <span
                        class="shrink-0 rounded-full px-3 py-1 text-xs font-bold
                    {{ str_contains(strtolower($session['Status']), 'selesai') ||
                    str_contains(strtolower($session['Status']), 'tutup')
                        ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
                        : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200' }}">
                        {{ $session['Status'] }}
                    </span>
                </div>

                <span class="text-gray-300 dark:text-gray-600 hidden sm:inline">&bull;</span>

                {{-- Info singkat --}}
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-mono">{{ $session['NomorWhatsapp'] }}</span>
                    @if ($session['NamaCustomer'])
                        <span class="hidden sm:inline text-gray-300 dark:text-gray-600">&bull;</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $session['NamaCustomer'] }}</span>
                    @endif
                    @if ($session['NamaInstansi'])
                        <span class="hidden sm:inline text-gray-300 dark:text-gray-600">&bull;</span>
                        <span>{{ $session['NamaInstansi'] }}</span>
                    @endif
                </div>

                {{-- Spacer --}}
                <div class="flex-1"></div>

                {{-- Meta kanan --}}
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <span class="text-emerald-700 dark:text-emerald-400 font-semibold">
                        &#x1F9D1; {{ __('ui.pages.view_chat.handled_by') }}: <strong class="text-emerald-800 dark:text-emerald-300">{{ $session['NamaCS'] }}</strong>
                    </span>
                    <span class="text-gray-500 dark:text-gray-400 font-medium">
                        &#x1F552; {{ $session['TglTerakhir'] }}
                    </span>
                    <span
                        class="rounded-2xl border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-bold text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        {{ __('ui.pages.view_chat.read_only') }}
                    </span>
                </div>

            </div>
        </div>


        {{-- ── Body: Chat + Notes ── --}}
        <div class="mt-6 flex gap-6" style="height: calc(100vh - 280px); min-height: 420px;">

            {{-- Kolom Kiri: Percakapan (2/3) --}}
            <div
                class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

                {{-- Header --}}
                <div
                    class="flex items-center border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                    <span class="text-base font-extrabold text-gray-800 dark:text-gray-100">
                        &#x1F4AC; {{ __('ui.pages.view_chat.conversation_history') }}
                        <span
                            class="ml-2 font-normal text-gray-500 text-sm">({{ \App\Support\LocaleFormatter::number(count($messages)) }}
                            {{ __('ui.common.messages') }})</span>
                    </span>
                </div>

                {{-- Pesan --}}
                <div
                    class="min-h-0 flex-1 space-y-5 overflow-y-auto overflow-x-hidden bg-gray-50 p-6 dark:bg-gray-950/70">
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
                            class="{{ $isOut ? 'ml-auto bg-indigo-600 text-white' : 'bg-white text-gray-800 border border-gray-200 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700' }} max-w-[88%] rounded-2xl p-4">

                            {{-- Label pengirim & waktu --}}
                            <div class="{{ $isOut ? 'text-indigo-100' : 'text-gray-500' }} text-xs font-semibold">
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
                                    class="mt-3 overflow-hidden rounded-2xl {{ $isOut ? 'bg-indigo-700/50' : 'bg-gray-100 dark:bg-gray-950' }}">
                                    @if ($mediaUrl && $isImage)
                                        <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="block">
                                            <img src="{{ $mediaUrl }}" alt="{{ $mediaLabel }}"
                                                class="max-h-96 w-full object-contain">
                                        </a>
                                    @elseif ($mediaUrl && $isVideo)
                                        <video controls preload="metadata" class="max-h-96 w-full">
                                            <source src="{{ $mediaUrl }}">
                                        </video>
                                    @elseif ($mediaUrl && $isAudio)
                                        <div class="p-4">
                                            <audio controls preload="metadata" class="w-full">
                                                <source src="{{ $mediaUrl }}">
                                            </audio>
                                        </div>
                                    @elseif ($mediaUrl)
                                        <a href="{{ $mediaUrl }}" target="_blank" rel="noopener"
                                            class="{{ $isOut ? 'text-indigo-50 hover:text-white' : 'text-indigo-700 hover:text-indigo-900 dark:text-indigo-300' }} block px-4 py-3 text-sm font-semibold underline underline-offset-4">
                                            {{ $mediaLabel }}
                                        </a>
                                    @else
                                        <div
                                            class="px-4 py-3 text-sm {{ $isOut ? 'text-indigo-50' : 'text-gray-600 dark:text-gray-300' }}">
                                            {{ __('ui.pages.view_chat.media_unavailable', ['file' => $mediaLabel]) }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Teks pesan --}}
                            @if ($msg['IsiPesan'])
                                <p class="mt-3 whitespace-pre-line wacs-chat-message">{{ $msg['IsiPesan'] }}</p>
                            @elseif (!$hasMedia)
                                <p class="mt-2 whitespace-pre-line text-xs opacity-70 wacs-chat-message">
                                    {{ __('ui.pages.view_chat.non_text_message') }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-gray-500">{{ __('ui.pages.view_chat.no_messages') }}
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Kolom Kanan: Catatan Internal --}}
            <div class="flex w-80 shrink-0 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                style="max-width: 340px;">

                {{-- Header --}}
                <div
                    class="flex items-center gap-3 border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                    <span class="text-base font-extrabold text-gray-800 dark:text-gray-100">&#9998;
                        {{ __('ui.pages.view_chat.internal_notes') }}</span>
                    @if (count($internalNotes) > 0)
                        <span class="ml-auto rounded-full bg-indigo-600 px-3 py-1 text-xs font-extrabold text-white">
                            {{ \App\Support\LocaleFormatter::number(count($internalNotes)) }}
                        </span>
                    @endif
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-4 space-y-4">
                    @forelse ($internalNotes as $note)
                        <div
                            class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-wrap leading-relaxed">
                                {{ $note['IsiCatatan'] }}</p>
                            <div class="mt-3 flex items-center justify-between text-xs">
                                <span
                                    class="font-bold text-indigo-700 dark:text-indigo-400">{{ $note['NamaPembuat'] }}</span>
                                <span class="text-gray-500 font-medium">{{ $note['TglFormatted'] }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-gray-500">
                            {{ __('ui.pages.view_chat.no_internal_notes') }}</p>
                    @endforelse
                </div>
            </div>

        </div>
    @endif
</x-filament-panels::page>
