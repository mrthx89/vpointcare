<x-filament-panels::page>
    <form wire:submit.prevent="simpanPengaturan" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.stats_auto_reply') }}
                </div>
                <div
                    class="mt-2 text-2xl font-semibold {{ $pengaturan['AutoReplyAktif'] ? 'text-emerald-600' : 'text-gray-400' }}">
                    {{ $pengaturan['AutoReplyAktif'] ? __('ui.common.active') : __('ui.common.inactive') }}
                </div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.stats_sessions') }}</div>
                <div class="mt-2 text-2xl font-semibold text-blue-600">{{ $stats['chat_auto'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.stats_replies') }}</div>
                <div class="mt-2 text-2xl font-semibold text-amber-600">{{ $stats['balasan_ai'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.stats_recipients') }}
                </div>
                <div class="mt-2 text-2xl font-semibold text-emerald-600">{{ $stats['penerima_notifikasi'] ?? 0 }}</div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                        <div class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('ui.pages.ai_agent.auto_reply_mode') }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('ui.pages.ai_agent.auto_reply_desc') }}</div>
                    </div>
                    <div class="grid gap-4 p-4 md:grid-cols-2">
                        <label class="flex gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <input type="checkbox" wire:model="pengaturan.AutoReplyAktif"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.ai_agent.enable_ai') }}</span>
                                <span
                                    class="mt-1 block text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.enable_ai_desc') }}</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <input type="checkbox" wire:model="pengaturan.AutoReplyDiluarJamKerja"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.ai_agent.reply_after_hours') }}</span>
                                <span
                                    class="mt-1 block text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.reply_after_hours_desc') }}</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <input type="checkbox" wire:model="pengaturan.AutoReplyHariLibur"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.ai_agent.reply_holiday') }}</span>
                                <span
                                    class="mt-1 block text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.reply_holiday_desc') }}</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <input type="checkbox" wire:model="pengaturan.AutoReplyJamKerjaSapaan"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.ai_agent.working_hours_greeting') }}</span>
                                <span
                                    class="mt-1 block text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.working_hours_greeting_desc') }}</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <input type="checkbox" wire:model="pengaturan.AutoReplyJamKerjaBerlanjut"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-950 dark:text-white">{{ __('ui.pages.ai_agent.continue_all_sessions') }}</span>
                                <span
                                    class="mt-1 block text-sm text-gray-500 dark:text-gray-400">{{ __('ui.pages.ai_agent.continue_all_sessions_desc') }}</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
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
                            <input type="time" wire:model="pengaturan.JamKerjaMulai"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            @error('pengaturan.JamKerjaMulai')
                                <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.end') }}</label>
                            <input type="time" wire:model="pengaturan.JamKerjaSelesai"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            @error('pengaturan.JamKerjaSelesai')
                                <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.timezone') }}</label>
                            <input type="text" wire:model="pengaturan.ZonaWaktu"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
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
                                    class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm dark:border-gray-800">
                                    <input type="checkbox" wire:model="pengaturan.HariKerja"
                                        value="{{ $value }}"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('pengaturan.HariKerja')
                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
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
                            <textarea wire:model="pengaturan.PromptSistem"
                                class="mt-2 min-h-28 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"></textarea>
                        </div>
                        <div class="grid gap-4 lg:grid-cols-4">
                            <div>
                                <label
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.after_hours_template') }}</label>
                                <textarea wire:model="pengaturan.TemplateDiluarJamKerja"
                                    class="mt-2 min-h-36 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"></textarea>
                            </div>
                            <div>
                                <label
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.holiday_template') }}</label>
                                <textarea wire:model="pengaturan.TemplateHariLibur"
                                    class="mt-2 min-h-36 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"></textarea>
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ __('ui.pages.ai_agent.holiday_placeholders') }}</div>
                            </div>
                            <div>
                                <label
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.working_greeting_template') }}</label>
                                <textarea wire:model="pengaturan.TemplateJamKerjaSapaan"
                                    class="mt-2 min-h-36 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"></textarea>
                            </div>
                            <div>
                                <label
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.fallback_template') }}</label>
                                <textarea wire:model="pengaturan.TemplateFallback"
                                    class="mt-2 min-h-36 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </section>

            <aside class="space-y-4">
                <div
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('ui.pages.ai_agent.provider_ai') }}</div>
                    <div class="mt-4 space-y-4">
                        <div class="grid gap-2">
                            @foreach ($providerPresets as $provider => $preset)
                                <button type="button" wire:click="applyProviderPreset('{{ $provider }}')"
                                    class="rounded-md border px-3 py-2 text-left text-sm transition {{ ($pengaturan['ProviderAi'] ?? 'OpenAI') === $provider ? 'border-blue-500 bg-blue-50 text-blue-900 dark:border-blue-500 dark:bg-blue-500/10 dark:text-blue-200' : 'border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800' }}">
                                    <span class="flex items-center justify-between gap-3">
                                        <span class="font-semibold">{{ $preset['label'] }}</span>
                                        <span
                                            class="text-xs text-gray-500 dark:text-gray-400">{{ $preset['key_label'] }}</span>
                                    </span>
                                    <span
                                        class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $preset['summary'] }}</span>
                                </button>
                            @endforeach
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.provider') }}</label>
                            <select wire:model="pengaturan.ProviderAi"
                                wire:change="applyProviderPreset($event.target.value)"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                                <option value="OpenAI">OpenAI / ChatGPT</option>
                                <option value="DeepSeek">DeepSeek</option>
                                <option value="OpenRouter">OpenRouter</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.model') }}</label>
                            <input type="text" wire:model="pengaturan.ModelAi"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            <div class="mt-1 text-xs text-gray-500">{{ __('ui.pages.ai_agent.preset') }}:
                                {{ $providerPresets[$pengaturan['ProviderAi'] ?? 'OpenAI']['model'] ?? '-' }}</div>
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.common.endpoint') }}</label>
                            <input type="url" wire:model="pengaturan.BaseUrl"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            <div class="mt-1 break-all text-xs text-gray-500">
                                {{ $providerPresets[$pengaturan['ProviderAi'] ?? 'OpenAI']['base_url'] ?? '' }}</div>
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.api_key_selected') }}</label>
                            <input type="password" wire:model="apiKeyBaru" autocomplete="new-password"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"
                                placeholder="{{ $apiKeyTerisi ? __('ui.pages.ai_agent.api_key_saved') : __('ui.pages.ai_agent.api_key_enter') }}">
                            <div class="mt-2 text-xs {{ $apiKeyTerisi ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $apiKeyInfo }}
                            </div>
                            @if ($apiKeyTerisi)
                                <button type="button" wire:click="hapusApiKey"
                                    class="mt-3 rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/40">{{ __('ui.pages.ai_agent.delete_api_key') }}</button>
                            @endif
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('ui.pages.ai_agent.delivery') }}</div>
                    <div class="mt-4 space-y-4">
                        <label
                            class="flex gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                            <input type="checkbox" wire:model="pengaturan.KirimKeWaha"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
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
                            <select wire:model="pengaturan.ModeKirim"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                                <option value="DraftLokal">{{ __('ui.pages.ai_agent.draft_local') }}</option>
                                <option value="KirimWaha">{{ __('ui.pages.ai_agent.send_waha') }}</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.history_limit') }}</label>
                            <input type="number" min="1" max="20"
                                wire:model="pengaturan.BatasRiwayatPesan"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('ui.pages.ai_agent.cs_notification') }}</div>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('ui.pages.ai_agent.cs_notification_desc') }}</div>
                    <div class="mt-4 space-y-4">
                        <label class="flex gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <input type="checkbox" wire:model="pengaturan.NotifikasiChatBelumTerbalasAktif"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
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
                                <input type="number" min="1" max="1440"
                                    wire:model="pengaturan.MenitTungguNotifikasi"
                                    class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            </div>
                            <div>
                                <label
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.reminder_interval') }}</label>
                                <input type="number" min="1" max="1440"
                                    wire:model="pengaturan.JedaNotifikasiMenit"
                                    class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            </div>
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.recipient_roles') }}</label>
                            <input type="text" wire:model="pengaturan.KodePeranPenerimaNotifikasi"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"
                                placeholder="ADMIN,SUPERVISOR_CS,CS">
                            <div class="mt-1 text-xs text-gray-500">{{ __('ui.pages.ai_agent.recipient_roles_help') }}
                            </div>
                        </div>
                        <div>
                            <label
                                class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.pages.ai_agent.notification_template') }}</label>
                            <textarea wire:model="pengaturan.TemplateNotifikasiChatBelumTerbalas"
                                class="mt-2 min-h-32 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"></textarea>
                            <div class="mt-1 text-xs text-gray-500">
                                {{ __('ui.pages.ai_agent.notification_placeholders') }}</div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">{{ __('ui.pages.ai_agent.save_settings') }}</button>
                </div>
            </aside>
        </div>
    </form>
</x-filament-panels::page>
