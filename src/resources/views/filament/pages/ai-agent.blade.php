<x-filament-panels::page>
    <form wire:submit.prevent="simpanPengaturan" class="wacs-ai-agent space-y-6">
        <div
            class="overflow-hidden rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 via-white to-indigo-50 p-5 dark:border-blue-900 dark:from-blue-950/40 dark:via-gray-900 dark:to-indigo-950/40">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-blue-600/20">
                        <x-filament::icon icon="heroicon-o-sparkles" class="h-9 w-9" />
                    </div>
                    <div>
                        <div class="text-xl font-bold text-gray-950 dark:text-white">
                            {{ __('ui.pages.ai_agent.hero_title') }}</div>
                        <div class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-300">
                            {{ __('ui.pages.ai_agent.hero_subtitle') }}</div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span
                        class="rounded-full bg-blue-100 px-3 py-1 font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-200">{{ $pengaturan['ProviderAi'] ?? 'OpenAI' }}</span>
                    <span
                        class="rounded-full {{ $apiKeyTerisi ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200' }} px-3 py-1 font-semibold">{{ $apiKeyTerisi ? __('ui.pages.ai_agent.api_key_saved') : __('ui.pages.ai_agent.api_key_missing_badge') }}</span>
                    <span
                        class="rounded-full {{ $pengaturan['AutoReplyAktif'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }} px-3 py-1 font-semibold">{{ $pengaturan['AutoReplyAktif'] ? __('ui.common.active') : __('ui.common.inactive') }}</span>
                </div>
            </div>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.stats_auto_reply') }}
                </div>
                <div
                    class="mt-2 text-2xl font-semibold {{ $pengaturan['AutoReplyAktif'] ? 'text-emerald-600' : 'text-gray-400' }}">
                    {{ $pengaturan['AutoReplyAktif'] ? __('ui.common.active') : __('ui.common.inactive') }}
                </div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.stats_sessions') }}
                </div>
                <div class="mt-2 text-2xl font-semibold text-blue-600">{{ $stats['chat_auto'] ?? 0 }}</div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.stats_replies') }}</div>
                <div class="mt-2 text-2xl font-semibold text-amber-600">{{ $stats['balasan_ai'] ?? 0 }}</div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.stats_recipients') }}
                </div>
                <div class="mt-2 text-2xl font-semibold text-emerald-600">{{ $stats['penerima_notifikasi'] ?? 0 }}
                </div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="space-y-4">
                <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                        <div class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('ui.pages.ai_agent.auto_reply_mode') }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('ui.pages.ai_agent.auto_reply_desc') }}</div>
                    </div>
                    <div class="grid gap-4 p-4 md:grid-cols-2">
                        <label class="flex gap-3 rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                            <x-filament::input.checkbox wire:model="pengaturan.AutoReplyAktif" class="mt-1" />
                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.ai_agent.enable_ai') }}</span>
                                <span
                                    class="mt-1 block text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.enable_ai_desc') }}</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                            <x-filament::input.checkbox wire:model="pengaturan.AutoReplyDiluarJamKerja"
                                class="mt-1" />
                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.ai_agent.reply_after_hours') }}</span>
                                <span
                                    class="mt-1 block text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.reply_after_hours_desc') }}</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                            <x-filament::input.checkbox wire:model="pengaturan.AutoReplyHariLibur" class="mt-1" />
                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.ai_agent.reply_holiday') }}</span>
                                <span
                                    class="mt-1 block text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.reply_holiday_desc') }}</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                            <x-filament::input.checkbox wire:model="pengaturan.AutoReplyJamKerjaSapaan"
                                class="mt-1" />
                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.ai_agent.working_hours_greeting') }}</span>
                                <span
                                    class="mt-1 block text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.working_hours_greeting_desc') }}</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                            <x-filament::input.checkbox wire:model="pengaturan.AutoReplyJamKerjaBerlanjut"
                                class="mt-1" />
                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.ai_agent.continue_all_sessions') }}</span>
                                <span
                                    class="mt-1 block text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.continue_all_sessions_desc') }}</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                        <div class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('ui.pages.ai_agent.working_hours') }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('ui.pages.ai_agent.working_hours_desc') }}</div>
                    </div>
                    <div class="grid gap-4 p-4 md:grid-cols-3">
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.start') }}</label>
                            <x-filament::input.wrapper class="mt-2" :valid="!$errors->has('pengaturan.JamKerjaMulai')">
                                <x-filament::input type="time" wire:model="pengaturan.JamKerjaMulai" />
                            </x-filament::input.wrapper>
                            @error('pengaturan.JamKerjaMulai')
                                <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.end') }}</label>
                            <x-filament::input.wrapper class="mt-2" :valid="!$errors->has('pengaturan.JamKerjaSelesai')">
                                <x-filament::input type="time" wire:model="pengaturan.JamKerjaSelesai" />
                            </x-filament::input.wrapper>
                            @error('pengaturan.JamKerjaSelesai')
                                <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.timezone') }}</label>
                            <x-filament::input.wrapper class="mt-2" :valid="!$errors->has('pengaturan.ZonaWaktu')">
                                <x-filament::input type="text" wire:model="pengaturan.ZonaWaktu" />
                            </x-filament::input.wrapper>
                            @error('pengaturan.ZonaWaktu')
                                <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="border-t border-gray-200 p-4 dark:border-gray-800">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('ui.pages.ai_agent.working_days') }}</div>
                        <div class="mt-3 grid gap-2 sm:grid-cols-4 lg:grid-cols-7">
                            @foreach ([1, 2, 3, 4, 5, 6, 7] as $value)
                                @php($label = __('ui.pages.ai_agent.days.' . ($value - 1)))
                                <label
                                    class="flex items-center gap-2 rounded-2xl border border-gray-200 px-3 py-2 text-sm dark:border-gray-800">
                                    <x-filament::input.checkbox wire:model="pengaturan.HariKerja"
                                        value="{{ $value }}" />
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('pengaturan.HariKerja')
                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                        <div class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('ui.pages.ai_agent.phrases_prompts') }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('ui.pages.ai_agent.phrases_prompts_desc') }}</div>
                    </div>
                    <div class="grid gap-4 p-4">
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.system_prompt') }}</label>
                            <x-filament::input.wrapper class="mt-2">
                                <textarea wire:model="pengaturan.PromptSistem"
                                    class="min-h-120 w-full resize-y border-0 bg-transparent px-3 py-2 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"></textarea>
                            </x-filament::input.wrapper>
                        </div>
                        <div class="grid gap-4 lg:grid-cols-4">
                            <div>
                                <label
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.after_hours_template') }}</label>
                                <x-filament::input.wrapper class="mt-2">
                                    <textarea wire:model="pengaturan.TemplateDiluarJamKerja"
                                        class="min-h-120 w-full resize-y border-0 bg-transparent px-3 py-2 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"></textarea>
                                </x-filament::input.wrapper>
                            </div>
                            <div>
                                <label
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.holiday_template') }}</label>
                                <x-filament::input.wrapper class="mt-2">
                                    <textarea wire:model="pengaturan.TemplateHariLibur"
                                        class="min-h-120 w-full resize-y border-0 bg-transparent px-3 py-2 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"></textarea>
                                </x-filament::input.wrapper>
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ __('ui.pages.ai_agent.holiday_placeholders') }}</div>
                            </div>
                            <div>
                                <label
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.working_greeting_template') }}</label>
                                <x-filament::input.wrapper class="mt-2">
                                    <textarea wire:model="pengaturan.TemplateJamKerjaSapaan"
                                        class="min-h-120 w-full resize-y border-0 bg-transparent px-3 py-2 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"></textarea>
                                </x-filament::input.wrapper>
                            </div>
                            <div>
                                <label
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.fallback_template') }}</label>
                                <x-filament::input.wrapper class="mt-2">
                                    <textarea wire:model="pengaturan.TemplateFallback"
                                        class="min-h-120 w-full resize-y border-0 bg-transparent px-3 py-2 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"></textarea>
                                </x-filament::input.wrapper>
                            </div>
                        </div>
                    </div>
                </div>

            </section>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('ui.pages.ai_agent.provider_ai') }}</div>
                    <div class="mt-4 space-y-4">
                        <div class="grid gap-2">
                            @foreach ($providerPresets as $provider => $preset)
                                <button type="button" wire:click="applyProviderPreset('{{ $provider }}')"
                                    class="rounded-2xl border px-3 py-2 text-left text-sm transition {{ ($pengaturan['ProviderAi'] ?? 'OpenAI') === $provider ? 'border-blue-500 bg-blue-50 text-blue-900 dark:border-blue-500 dark:bg-blue-500/10 dark:text-blue-200' : 'border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800' }}">
                                    <span class="flex items-center justify-between gap-3">
                                        <span class="flex min-w-0 items-center gap-3">
                                            <span
                                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-2xl {{ $preset['icon_class'] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                                                @if (!empty($preset['icon_path']))
                                                    <img src="{{ asset($preset['icon_path']) }}"
                                                        alt="{{ $preset['label'] }}" class="h-7 w-7 object-contain"
                                                        loading="lazy" />
                                                @else
                                                    <x-filament::icon :icon="$preset['icon'] ?? 'heroicon-o-sparkles'" class="h-5 w-5" />
                                                @endif
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block font-semibold">{{ $preset['label'] }}</span>
                                                <span
                                                    class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $preset['summary'] }}</span>
                                            </span>
                                        </span>
                                        <span
                                            class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $preset['key_label'] }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.model') }}</label>
                            <x-filament::input.wrapper class="mt-2">
                                <x-filament::input type="text" wire:model="pengaturan.ModelAi" />
                            </x-filament::input.wrapper>
                            <div class="mt-1 text-xs text-gray-500">{{ __('ui.pages.ai_agent.preset') }}:
                                {{ $providerPresets[$pengaturan['ProviderAi'] ?? 'OpenAI']['model'] ?? '-' }}</div>
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.common.endpoint') }}</label>
                            <x-filament::input.wrapper class="mt-2">
                                <x-filament::input type="url" wire:model="pengaturan.BaseUrl" />
                            </x-filament::input.wrapper>
                            <div class="mt-1 break-all text-xs text-gray-500">
                                {{ $providerPresets[$pengaturan['ProviderAi'] ?? 'OpenAI']['base_url'] ?? '' }}</div>
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.api_key_selected') }}</label>
                            <x-filament::input.wrapper class="mt-2">
                                <x-filament::input type="password" wire:model="apiKeyBaru"
                                    autocomplete="new-password"
                                    placeholder="{{ $apiKeyTerisi ? __('ui.pages.ai_agent.api_key_saved') : __('ui.pages.ai_agent.api_key_enter') }}" />
                            </x-filament::input.wrapper>
                            <div class="mt-2 text-xs {{ $apiKeyTerisi ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $apiKeyInfo }}
                            </div>
                            @if ($apiKeyTerisi)
                                <button type="button" wire:click="hapusApiKey"
                                    class="mt-3 rounded-2xl border border-red-300 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/40">{{ __('ui.pages.ai_agent.delete_api_key') }}</button>
                            @endif
                        </div>
                        <div
                            class="rounded-2xl border border-blue-200 bg-blue-50 p-3 dark:border-blue-900 dark:bg-blue-950/30">
                            <div class="text-sm font-semibold text-blue-900 dark:text-blue-100">
                                {{ __('ui.pages.ai_agent.test_connection') }}</div>
                            <div class="mt-2">
                                <label
                                    class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('ui.pages.ai_agent.test_prompt') }}</label>
                                <x-filament::input.wrapper class="mt-1" :valid="!$errors->has('testPrompt')">
                                    <textarea wire:model="testPrompt"
                                        class="min-h-20 w-full resize-y border-0 bg-transparent px-3 py-2 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"></textarea>
                                </x-filament::input.wrapper>
                                @error('testPrompt')
                                    <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="button" wire:click="testKoneksiAi" wire:loading.attr="disabled"
                                wire:target="testKoneksiAi"
                                class="mt-3 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-wait disabled:opacity-70">
                                <span wire:loading.remove
                                    wire:target="testKoneksiAi">{{ __('ui.pages.ai_agent.test_connection') }}</span>
                                <span wire:loading
                                    wire:target="testKoneksiAi">{{ __('ui.pages.ai_agent.testing_connection') }}</span>
                            </button>
                            <div
                                class="mt-3 rounded-2xl border border-gray-200 bg-white p-3 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    {{ __('ui.pages.ai_agent.test_result') }}</div>
                                <div class="whitespace-pre-wrap">
                                    {{ $testResult !== '' ? $testResult : __('ui.pages.ai_agent.test_result_empty') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('ui.pages.ai_agent.delivery') }}</div>
                    <div class="mt-4 space-y-4">
                        <label
                            class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                            <x-filament::input.checkbox wire:model="pengaturan.KirimKeWaha" class="mt-1" />
                            <span>
                                <span
                                    class="block text-sm font-semibold text-amber-900 dark:text-amber-200">{{ __('ui.pages.ai_agent.send_direct_waha') }}</span>
                                <span
                                    class="mt-1 block text-sm text-amber-700 dark:text-amber-300">{{ __('ui.pages.ai_agent.send_direct_waha_desc') }}</span>
                            </span>
                        </label>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.delivery_mode') }}</label>
                            <x-filament::input.wrapper class="mt-2">
                                <x-filament::input.select wire:model="pengaturan.ModeKirim">
                                    <option value="DraftLokal">{{ __('ui.pages.ai_agent.draft_local') }}</option>
                                    <option value="KirimWaha">{{ __('ui.pages.ai_agent.send_waha') }}</option>
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.history_limit') }}</label>
                            <x-filament::input.wrapper class="mt-2">
                                <x-filament::input type="number" min="1" max="20"
                                    wire:model="pengaturan.BatasRiwayatPesan" />
                            </x-filament::input.wrapper>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('ui.pages.ai_agent.cs_notification') }}</div>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('ui.pages.ai_agent.cs_notification_desc') }}</div>
                    <div class="mt-4 space-y-4">
                        <label class="flex gap-3 rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                            <x-filament::input.checkbox wire:model="pengaturan.NotifikasiChatBelumTerbalasAktif"
                                class="mt-1" />
                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.ai_agent.enable_unanswered_notification') }}</span>
                                <span
                                    class="mt-1 block text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.enable_unanswered_notification_desc') }}</span>
                            </span>
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.send_after_minutes') }}</label>
                                <x-filament::input.wrapper class="mt-2">
                                    <x-filament::input type="number" min="1" max="1440"
                                        wire:model="pengaturan.MenitTungguNotifikasi" />
                                </x-filament::input.wrapper>
                            </div>
                            <div>
                                <label
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.reminder_interval') }}</label>
                                <x-filament::input.wrapper class="mt-2">
                                    <x-filament::input type="number" min="1" max="1440"
                                        wire:model="pengaturan.JedaNotifikasiMenit" />
                                </x-filament::input.wrapper>
                            </div>
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.recipient_roles') }}</label>
                            <x-filament::input.wrapper class="mt-2">
                                <x-filament::input type="text" wire:model="pengaturan.KodePeranPenerimaNotifikasi"
                                    placeholder="ADMIN,SUPERVISOR_CS,CS" />
                            </x-filament::input.wrapper>
                            <div class="mt-1 text-xs text-gray-500">{{ __('ui.pages.ai_agent.recipient_roles_help') }}
                            </div>
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.notification_template') }}</label>
                            <x-filament::input.wrapper class="mt-2">
                                <textarea wire:model="pengaturan.TemplateNotifikasiChatBelumTerbalas"
                                    class="min-h-32 w-full resize-y border-0 bg-transparent px-3 py-2 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"></textarea>
                            </x-filament::input.wrapper>
                            <div class="mt-1 text-xs text-gray-500">
                                {{ __('ui.pages.ai_agent.notification_placeholders') }}</div>
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.exclude_whatsapp_numbers') }}</label>
                            <x-filament::input.wrapper class="mt-2" :valid="!$errors->has('pengaturan.ExcludeNomorWhatsapp')">
                                <textarea wire:model="pengaturan.ExcludeNomorWhatsapp"
                                    class="min-h-28 w-full resize-y border-0 bg-transparent px-3 py-2 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                                    placeholder="62812xxxx&#10;62813xxxx"></textarea>
                            </x-filament::input.wrapper>
                            <div class="mt-1 text-xs text-gray-500">
                                {{ __('ui.pages.ai_agent.exclude_whatsapp_numbers_help') }}</div>
                            @error('pengaturan.ExcludeNomorWhatsapp')
                                <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('ui.pages.ai_agent.save_settings') }}</button>
                </div>
            </aside>
        </div>
    </form>
</x-filament-panels::page>
