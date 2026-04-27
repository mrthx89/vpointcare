<x-filament-panels::page>
    <form wire:submit.prevent="simpanPengaturan" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Auto reply aktif</div>
                <div
                    class="mt-2 text-2xl font-semibold {{ $pengaturan['AutoReplyAktif'] ? 'text-emerald-600' : 'text-gray-400' }}">
                    {{ $pengaturan['AutoReplyAktif'] ? 'Aktif' : 'Nonaktif' }}
                </div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Sesi auto reply</div>
                <div class="mt-2 text-2xl font-semibold text-blue-600">{{ $stats['chat_auto'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Balasan AI</div>
                <div class="mt-2 text-2xl font-semibold text-amber-600">{{ $stats['balasan_ai'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Penerima notif</div>
                <div class="mt-2 text-2xl font-semibold text-emerald-600">{{ $stats['penerima_notifikasi'] ?? 0 }}</div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                        <div class="text-base font-semibold text-gray-950 dark:text-white">Mode Auto Reply</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Atur kapan AI boleh menjawab chat
                            WhatsApp.</div>
                    </div>
                    <div class="grid gap-4 p-4 md:grid-cols-2">
                        <label class="flex gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <input type="checkbox" wire:model="pengaturan.AutoReplyAktif"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span class="block text-sm font-semibold text-gray-950 dark:text-white">Aktifkan AI
                                    Agent</span>
                                <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">Jika mati, webhook
                                    hanya menyimpan chat tanpa balasan AI.</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <input type="checkbox" wire:model="pengaturan.AutoReplyDiluarJamKerja"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span class="block text-sm font-semibold text-gray-950 dark:text-white">Balas di luar
                                    jam kerja</span>
                                <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">AI mengirim kalimat
                                    halus saat kantor tutup.</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <input type="checkbox" wire:model="pengaturan.AutoReplyJamKerjaSapaan"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span class="block text-sm font-semibold text-gray-950 dark:text-white">Sapaan awal jam
                                    kerja</span>
                                <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">AI menyapa satu kali,
                                    lalu CS melanjutkan.</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <input type="checkbox" wire:model="pengaturan.AutoReplyJamKerjaBerlanjut"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span class="block text-sm font-semibold text-gray-950 dark:text-white">Berlanjut untuk
                                    semua sesi</span>
                                <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">AI terus menjawab saat
                                    jam kerja. Lebih aman memakai kontrol per sesi di Inbox.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                        <div class="text-base font-semibold text-gray-950 dark:text-white">Jam Kerja</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Di luar jadwal ini AI memakai mode luar
                            jam kerja.</div>
                    </div>
                    <div class="grid gap-4 p-4 md:grid-cols-3">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Mulai</label>
                            <input type="time" wire:model="pengaturan.JamKerjaMulai"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            @error('pengaturan.JamKerjaMulai')
                                <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Selesai</label>
                            <input type="time" wire:model="pengaturan.JamKerjaSelesai"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            @error('pengaturan.JamKerjaSelesai')
                                <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Zona waktu</label>
                            <input type="text" wire:model="pengaturan.ZonaWaktu"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            @error('pengaturan.ZonaWaktu')
                                <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="border-t border-gray-200 p-4 dark:border-gray-800">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-200">Hari kerja</div>
                        <div class="mt-3 grid gap-2 sm:grid-cols-4 lg:grid-cols-7">
                            @foreach ([1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'] as $value => $label)
                                <label
                                    class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm dark:border-gray-800">
                                    <input type="checkbox" wire:model="pengaturan.HariKerja" value="{{ $value }}"
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
                        <div class="text-base font-semibold text-gray-950 dark:text-white">Kalimat dan Prompt</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Semua balasan dibuat singkat dan sopan
                            dengan konteks chat terakhir.</div>
                    </div>
                    <div class="grid gap-4 p-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Prompt sistem</label>
                            <textarea wire:model="pengaturan.PromptSistem"
                                class="mt-2 min-h-28 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"></textarea>
                        </div>
                        <div class="grid gap-4 lg:grid-cols-3">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Template luar jam
                                    kerja</label>
                                <textarea wire:model="pengaturan.TemplateDiluarJamKerja"
                                    class="mt-2 min-h-36 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"></textarea>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Template sapaan jam
                                    kerja</label>
                                <textarea wire:model="pengaturan.TemplateJamKerjaSapaan"
                                    class="mt-2 min-h-36 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"></textarea>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Template
                                    berlanjut</label>
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
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Provider AI</div>
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
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Provider</label>
                            <select wire:model="pengaturan.ProviderAi"
                                wire:change="applyProviderPreset($event.target.value)"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                                <option value="OpenAI">OpenAI / ChatGPT</option>
                                <option value="DeepSeek">DeepSeek</option>
                                <option value="OpenRouter">OpenRouter</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Model</label>
                            <input type="text" wire:model="pengaturan.ModelAi"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            <div class="mt-1 text-xs text-gray-500">Preset:
                                {{ $providerPresets[$pengaturan['ProviderAi'] ?? 'OpenAI']['model'] ?? '-' }}</div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Endpoint</label>
                            <input type="url" wire:model="pengaturan.BaseUrl"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            <div class="mt-1 break-all text-xs text-gray-500">
                                {{ $providerPresets[$pengaturan['ProviderAi'] ?? 'OpenAI']['base_url'] ?? '' }}</div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">API key provider
                                terpilih</label>
                            <input type="password" wire:model="apiKeyBaru" autocomplete="new-password"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"
                                placeholder="{{ $apiKeyTerisi ? 'API key sudah tersimpan' : 'Masukkan API key' }}">
                            <div class="mt-2 text-xs {{ $apiKeyTerisi ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $apiKeyInfo }}
                            </div>
                            @if ($apiKeyTerisi)
                                <button type="button" wire:click="hapusApiKey"
                                    class="mt-3 rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/40">Hapus
                                    API key provider ini</button>
                            @endif
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Pengiriman</div>
                    <div class="mt-4 space-y-4">
                        <label
                            class="flex gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                            <input type="checkbox" wire:model="pengaturan.KirimKeWaha"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span class="block text-sm font-semibold text-amber-900 dark:text-amber-200">Kirim
                                    langsung ke WAHA</span>
                                <span class="mt-1 block text-sm text-amber-700 dark:text-amber-300">Jika nonaktif,
                                    balasan AI disimpan sebagai draft lokal di chat.</span>
                            </span>
                        </label>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Mode kirim</label>
                            <select wire:model="pengaturan.ModeKirim"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                                <option value="DraftLokal">Draft lokal</option>
                                <option value="KirimWaha">Kirim WAHA</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Batas riwayat
                                pesan</label>
                            <input type="number" min="1" max="20"
                                wire:model="pengaturan.BatasRiwayatPesan"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Notifikasi CS</div>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Job akan mengirim WhatsApp ke user
                        internal jika chat klien belum terbalas.</div>
                    <div class="mt-4 space-y-4">
                        <label class="flex gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <input type="checkbox" wire:model="pengaturan.NotifikasiChatBelumTerbalasAktif"
                                class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span class="block text-sm font-semibold text-gray-950 dark:text-white">Aktifkan
                                    notifikasi chat belum terbalas</span>
                                <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">Nomor user diambil
                                    dari data pengguna aktif.</span>
                            </span>
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Kirim setelah
                                    menit</label>
                                <input type="number" min="1" max="1440"
                                    wire:model="pengaturan.MenitTungguNotifikasi"
                                    class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Jeda pengingat
                                    ulang</label>
                                <input type="number" min="1" max="1440"
                                    wire:model="pengaturan.JedaNotifikasiMenit"
                                    class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950">
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Role penerima</label>
                            <input type="text" wire:model="pengaturan.KodePeranPenerimaNotifikasi"
                                class="mt-2 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"
                                placeholder="ADMIN,SUPERVISOR_CS,CS">
                            <div class="mt-1 text-xs text-gray-500">Pisahkan kode role dengan koma. User harus memiliki
                                Nomor WhatsApp Internal.</div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Template
                                notifikasi</label>
                            <textarea wire:model="pengaturan.TemplateNotifikasiChatBelumTerbalas"
                                class="mt-2 min-h-32 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950"></textarea>
                            <div class="mt-1 text-xs text-gray-500">Placeholder: {nama_user}, {nama_instansi},
                                {jenis_chat}, {nama_kontak}, {nomor_whatsapp}, {pesan_terakhir}, {menit_menunggu},
                                {url_admin}</div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Simpan
                        Pengaturan</button>
                </div>
            </aside>
        </div>
    </form>
</x-filament-panels::page>
