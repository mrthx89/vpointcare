<x-filament-panels::page>
    <form wire:submit.prevent="simpanPengaturan" class="space-y-6">
        {{-- Hero Card --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white/90 p-5 shadow-sm backdrop-blur-md dark:border-gray-800/80 dark:bg-gray-900/90">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-950 dark:text-white">
                            {{ __('ui.pages.app_settings.hero_title') }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('ui.pages.app_settings.hero_subtitle') }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 font-semibold text-blue-700 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-300">
                        {{ $pengaturan['NamaAplikasi'] ?: config('app.name') }}
                    </span>
                    <span class="rounded-full border {{ !empty($pengaturan['MailHost']) ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300' }} px-3 py-1 font-medium">
                        {{ !empty($pengaturan['MailHost']) ? 'SMTP: ' . $pengaturan['MailHost'] : __('ui.pages.app_settings.mail_not_configured') }}
                    </span>
                    <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        {{ $pengaturan['ZonaWaktu'] }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="flex border-b border-gray-200 dark:border-gray-800">
            <button type="button"
                wire:click="setTab('branding')"
                class="flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition-colors {{ $activeTab === 'branding' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <x-filament::icon icon="heroicon-o-photo" class="h-4 w-4" />
                {{ __('ui.pages.app_settings.tab_branding') }}
            </button>
            <button type="button"
                wire:click="setTab('mail')"
                class="flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition-colors {{ $activeTab === 'mail' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <x-filament::icon icon="heroicon-o-at-symbol" class="h-4 w-4" />
                {{ __('ui.pages.app_settings.tab_mail') }}
            </button>
            <button type="button"
                wire:click="setTab('regional')"
                class="flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition-colors {{ $activeTab === 'regional' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <x-filament::icon icon="heroicon-o-globe-alt" class="h-4 w-4" />
                {{ __('ui.pages.app_settings.tab_regional') }}
            </button>
            <button type="button"
                wire:click="setTab('contact')"
                class="flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition-colors {{ $activeTab === 'contact' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <x-filament::icon icon="heroicon-o-envelope" class="h-4 w-4" />
                {{ __('ui.pages.app_settings.tab_contact') }}
            </button>
        </div>

        {{-- Tab 1: Branding & Identitas --}}
        @if ($activeTab === 'branding')
            <div class="grid gap-6 md:grid-cols-2">
                {{-- Nama & Teks Identitas --}}
                <div class="space-y-4 rounded-2xl border border-gray-200/80 bg-white p-5 dark:border-gray-800/80 dark:bg-gray-900">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('ui.pages.app_settings.identity_section') }}
                    </h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('ui.pages.app_settings.app_name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                            wire:model.defer="pengaturan.NamaAplikasi"
                            class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            placeholder="CareDesk" required />
                        @error('pengaturan.NamaAplikasi') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('ui.pages.app_settings.tagline') }}
                        </label>
                        <input type="text"
                            wire:model.defer="pengaturan.Tagline"
                            class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            placeholder="Integrated WhatsApp & AI Helpdesk System" />
                        @error('pengaturan.Tagline') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('ui.pages.app_settings.company_name') }}
                        </label>
                        <input type="text"
                            wire:model.defer="pengaturan.NamaPerusahaan"
                            class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            placeholder="PT Nama Perusahaan" />
                        @error('pengaturan.NamaPerusahaan') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('ui.pages.app_settings.footer_text') }}
                        </label>
                        <textarea
                            wire:model.defer="pengaturan.TeksFooter"
                            rows="2"
                            class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            placeholder="Care Desk System. All rights reserved."></textarea>
                        <p class="mt-1 text-xs text-gray-500">{{ __('ui.pages.app_settings.footer_text_help') }}</p>
                        @error('pengaturan.TeksFooter') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Upload & Preview Logo --}}
                <div class="space-y-4 rounded-2xl border border-gray-200/80 bg-white p-5 dark:border-gray-800/80 dark:bg-gray-900">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('ui.pages.app_settings.logo_section') }}
                    </h3>

                    {{-- Logo Utama (Light Mode) --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                            {{ __('ui.pages.app_settings.logo_primary') }}
                        </label>
                        <div class="mt-2 flex items-center gap-4">
                            <div class="flex h-12 w-32 items-center justify-center rounded-lg border border-gray-200 bg-white p-1 dark:border-gray-700">
                                <img src="{{ \App\Support\AppSettings::logoPrimaryUrl() }}" alt="Logo Primary" class="max-h-8 max-w-full object-contain" />
                            </div>
                            <div class="flex-1">
                                <input type="file" wire:model="logoUtamaFile" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-950/50 dark:file:text-blue-300" />
                                @if (!empty($pengaturan['LogoUtamaPath']))
                                    <button type="button" wire:click="hapusLogo('utama')" class="mt-1 text-xs text-red-600 hover:underline dark:text-red-400">
                                        {{ __('ui.pages.app_settings.reset_logo') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Logo Sekunder (Dark Mode) --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                            {{ __('ui.pages.app_settings.logo_dark') }}
                        </label>
                        <div class="mt-2 flex items-center gap-4">
                            <div class="flex h-12 w-32 items-center justify-center rounded-lg border border-gray-800 bg-slate-900 p-1">
                                <img src="{{ \App\Support\AppSettings::logoDarkUrl() }}" alt="Logo Dark" class="max-h-8 max-w-full object-contain" />
                            </div>
                            <div class="flex-1">
                                <input type="file" wire:model="logoSekunderFile" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-950/50 dark:file:text-blue-300" />
                                @if (!empty($pengaturan['LogoSekunderPath']))
                                    <button type="button" wire:click="hapusLogo('sekunder')" class="mt-1 text-xs text-red-600 hover:underline dark:text-red-400">
                                        {{ __('ui.pages.app_settings.reset_logo') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Favicon --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                            {{ __('ui.pages.app_settings.favicon') }}
                        </label>
                        <div class="mt-2 flex items-center gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white p-1 dark:border-gray-700 dark:bg-gray-800">
                                <img src="{{ \App\Support\AppSettings::faviconUrl() }}" alt="Favicon" class="h-6 w-6 object-contain" />
                            </div>
                            <div class="flex-1">
                                <input type="file" wire:model="faviconFile" accept=".ico,.png,.svg" class="block w-full text-xs text-gray-500 file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-950/50 dark:file:text-blue-300" />
                                @if (!empty($pengaturan['FaviconPath']))
                                    <button type="button" wire:click="hapusLogo('favicon')" class="mt-1 text-xs text-red-600 hover:underline dark:text-red-400">
                                        {{ __('ui.pages.app_settings.reset_logo') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tab 2: Email Sender / SMTP --}}
        @if ($activeTab === 'mail')
            <div class="grid gap-6 md:grid-cols-2">
                {{-- Form Konfigurasi SMTP --}}
                <div class="space-y-4 rounded-2xl border border-gray-200/80 bg-white p-5 dark:border-gray-800/80 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('ui.pages.app_settings.mail_section') }}
                        </h3>
                        <span class="rounded-full border {{ $mailPasswordTerisi ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300' }} px-2.5 py-0.5 text-xs font-semibold">
                            {{ $mailPasswordTerisi ? __('ui.pages.app_settings.mail_password_saved') : __('ui.pages.app_settings.mail_password_empty') }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('ui.pages.app_settings.mail_mailer') }} <span class="text-red-500">*</span>
                        </label>
                        <select
                            wire:model.defer="pengaturan.MailMailer"
                            class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="smtp">SMTP (Recommended for Production)</option>
                            <option value="sendmail">Sendmail</option>
                            <option value="log">Log File (Testing / Offline)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('ui.pages.app_settings.mail_host') }}
                            </label>
                            <input type="text"
                                wire:model.defer="pengaturan.MailHost"
                                class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="smtp.gmail.com / smtp.mailgun.org" />
                            @error('pengaturan.MailHost') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('ui.pages.app_settings.mail_port') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                wire:model.defer="pengaturan.MailPort"
                                class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="587" />
                            @error('pengaturan.MailPort') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('ui.pages.app_settings.mail_username') }}
                            </label>
                            <input type="text"
                                wire:model.defer="pengaturan.MailUsername"
                                class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="apikey / admin@company.com" />
                            @error('pengaturan.MailUsername') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('ui.pages.app_settings.mail_encryption') }}
                            </label>
                            <select
                                wire:model.defer="pengaturan.MailEncryption"
                                class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="tls">TLS (Port 587)</option>
                                <option value="ssl">SSL (Port 465)</option>
                                <option value="none">None / Plain (Port 25)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('ui.pages.app_settings.mail_password') }}
                        </label>
                        <input type="password"
                            wire:model.defer="mailPasswordBaru"
                            class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            placeholder="{{ $mailPasswordTerisi ? __('ui.pages.app_settings.mail_password_keep_placeholder') : '••••••••••••' }}" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('ui.pages.app_settings.mail_password_help') }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 dark:border-gray-800">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('ui.pages.app_settings.mail_from_address') }}
                            </label>
                            <input type="email"
                                wire:model.defer="pengaturan.MailFromAddress"
                                class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="noreply@company.com" />
                            @error('pengaturan.MailFromAddress') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('ui.pages.app_settings.mail_from_name') }}
                            </label>
                            <input type="text"
                                wire:model.defer="pengaturan.MailFromName"
                                class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="CareDesk Support" />
                            @error('pengaturan.MailFromName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Uji Coba Pengiriman Email --}}
                <div class="space-y-4 rounded-2xl border border-gray-200/80 bg-white p-5 dark:border-gray-800/80 dark:bg-gray-900">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('ui.pages.app_settings.mail_test_section') }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('ui.pages.app_settings.mail_test_subtitle') }}
                    </p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('ui.pages.app_settings.mail_test_recipient') }}
                        </label>
                        <div class="mt-1 flex gap-2">
                            <input type="email"
                                wire:model.defer="testEmailRecipient"
                                class="block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="test-user@domain.com" />
                            <button type="button"
                                wire:click="testKirimEmail"
                                wire:loading.attr="disabled"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-500">
                                <span wire:loading wire:target="testKirimEmail">
                                    <x-filament::loading-indicator class="h-4 w-4 text-white" />
                                </span>
                                <x-filament::icon icon="heroicon-m-paper-airplane" class="h-4 w-4" wire:loading.remove wire:target="testKirimEmail" />
                                {{ __('ui.pages.app_settings.mail_test_send_btn') }}
                            </button>
                        </div>
                        @error('testEmailRecipient') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    @if ($testEmailResult)
                        <div class="mt-3 rounded-xl p-3.5 text-xs font-mono {{ str_starts_with($testEmailResult, 'ERROR') ? 'border border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300' : 'border border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' }}">
                            {{ $testEmailResult }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Tab 3: Regional & Lokalisasi --}}
        @if ($activeTab === 'regional')
            <div class="max-w-2xl space-y-4 rounded-2xl border border-gray-200/80 bg-white p-5 dark:border-gray-800/80 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ __('ui.pages.app_settings.regional_section') }}
                </h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('ui.pages.app_settings.default_locale') }}
                    </label>
                    <select
                        wire:model.defer="pengaturan.BahasaDefault"
                        class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="id">Bahasa Indonesia (ID)</option>
                        <option value="en">English (EN)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('ui.pages.app_settings.timezone') }}
                    </label>
                    <select
                        wire:model.defer="pengaturan.ZonaWaktu"
                        class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="Asia/Jakarta">Asia/Jakarta (WIB - UTC+7)</option>
                        <option value="Asia/Makassar">Asia/Makassar (WITA - UTC+8)</option>
                        <option value="Asia/Jayapura">Asia/Jayapura (WIT - UTC+9)</option>
                        <option value="Asia/Singapore">Asia/Singapore (SGT - UTC+8)</option>
                        <option value="UTC">UTC (Universal Time)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('ui.pages.app_settings.date_format') }}
                    </label>
                    <select
                        wire:model.defer="pengaturan.FormatTanggal"
                        class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="d/m/Y">DD/MM/YYYY (contoh: 20/08/2026)</option>
                        <option value="d-m-Y">DD-MM-YYYY (contoh: 20-08-2026)</option>
                        <option value="Y-m-d">YYYY-MM-DD (contoh: 2026-08-20)</option>
                        <option value="d M Y">DD MMM YYYY (contoh: 20 Agu 2026)</option>
                    </select>
                </div>
            </div>
        @endif

        {{-- Tab 4: Kontak & Support --}}
        @if ($activeTab === 'contact')
            <div class="max-w-2xl space-y-4 rounded-2xl border border-gray-200/80 bg-white p-5 dark:border-gray-800/80 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ __('ui.pages.app_settings.contact_section') }}
                </h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('ui.pages.app_settings.support_email') }}
                    </label>
                    <input type="email"
                        wire:model.defer="pengaturan.EmailSupport"
                        class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        placeholder="support@company.com" />
                    @error('pengaturan.EmailSupport') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('ui.pages.app_settings.support_phone') }}
                    </label>
                    <input type="text"
                        wire:model.defer="pengaturan.NomorTeleponSupport"
                        class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        placeholder="+6281234567890" />
                    @error('pengaturan.NomorTeleponSupport') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('ui.pages.app_settings.office_address') }}
                    </label>
                    <textarea
                        wire:model.defer="pengaturan.AlamatKantor"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        placeholder="Jl. Jenderal Sudirman No. 123, Jakarta"></textarea>
                    @error('pengaturan.AlamatKantor') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
        @endif

        {{-- Bottom Actions Bar --}}
        <div class="flex items-center justify-end gap-3 border-t border-gray-200/80 pt-4 dark:border-gray-800/80">
            <x-filament::button type="submit" color="primary" size="lg" icon="heroicon-m-check">
                {{ __('ui.pages.app_settings.save_button') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
