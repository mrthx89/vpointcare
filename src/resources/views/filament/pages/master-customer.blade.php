<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-master.stat title="Klien / Instansi" :value="$stats['instansi'] ?? 0" />
            <x-master.stat title="Kontak Orang" :value="$stats['kontak'] ?? 0" />
            <x-master.stat title="Nomor WhatsApp" :value="$stats['nomor'] ?? 0" />
            <x-master.stat title="Grup WhatsApp" :value="$stats['grup'] ?? 0" />
        </div>

        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-100">
            Struktur master dibuat bertingkat: satu klien memiliki banyak kontak, setiap kontak bisa punya beberapa nomor WhatsApp, dan nomor tersebut bisa dimasukkan ke grup WhatsApp milik klien. Chat pribadi dikenali dari nomor, chat grup dikenali dari ID grup WAHA.
        </div>

        <div x-data="{ tab: 'ringkasan' }" class="space-y-6">
            <x-filament::tabs label="Master Customer">
                <x-filament::tabs.item alpine-active="tab === 'ringkasan'" x-on:click="tab = 'ringkasan'">Ringkasan</x-filament::tabs.item>
                <x-filament::tabs.item alpine-active="tab === 'klien'" x-on:click="tab = 'klien'">Klien</x-filament::tabs.item>
                <x-filament::tabs.item alpine-active="tab === 'kontak'" x-on:click="tab = 'kontak'">Kontak</x-filament::tabs.item>
                <x-filament::tabs.item alpine-active="tab === 'nomor'" x-on:click="tab = 'nomor'">Nomor WA</x-filament::tabs.item>
                <x-filament::tabs.item alpine-active="tab === 'grup'" x-on:click="tab = 'grup'">Grup WA</x-filament::tabs.item>
            </x-filament::tabs>

            <section x-show="tab === 'ringkasan'" x-cloak class="grid gap-4 xl:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Alur Mapping</div>
                    <div class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                        <div class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-blue-100 text-xs font-semibold text-blue-700">1</span><span>Daftarkan klien, misalnya PT A.</span></div>
                        <div class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-blue-100 text-xs font-semibold text-blue-700">2</span><span>Masukkan kontak orang di bawah PT A.</span></div>
                        <div class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-blue-100 text-xs font-semibold text-blue-700">3</span><span>Daftarkan nomor WhatsApp tiap kontak.</span></div>
                        <div class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-blue-100 text-xs font-semibold text-blue-700">4</span><span>Daftarkan grup WhatsApp PT A dan anggota grupnya.</span></div>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Contoh</div>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div><dt class="text-gray-500">Klien</dt><dd class="font-medium text-gray-950 dark:text-white">PT A</dd></div>
                        <div><dt class="text-gray-500">Kontak</dt><dd class="font-medium text-gray-950 dark:text-white">Budi, Sari, Andi</dd></div>
                        <div><dt class="text-gray-500">Nomor pribadi</dt><dd class="font-medium text-gray-950 dark:text-white">62812..., 62821...</dd></div>
                        <div><dt class="text-gray-500">Grup</dt><dd class="font-medium text-gray-950 dark:text-white">Support PT A - 120363...@g.us</dd></div>
                    </dl>
                </div>
            </section>

            <section x-show="tab === 'klien'" x-cloak class="grid gap-4 xl:grid-cols-[420px_minmax(0,1fr)]">
                <x-master.panel title="Tambah Klien">
                    <form wire:submit.prevent="simpanInstansi" class="space-y-3">
                        <x-master.input label="Kode" model="formInstansi.KodeInstansi" placeholder="PTA" />
                        <x-master.input label="Nama Klien" model="formInstansi.NamaInstansi" placeholder="PT A" />
                        <x-master.input label="Kota" model="formInstansi.Kota" placeholder="Jakarta" />
                        <x-master.input label="Telepon" model="formInstansi.Telepon" placeholder="021..." />
                        <x-master.button>Simpan Klien</x-master.button>
                    </form>
                </x-master.panel>

                <x-master.panel title="Daftar Klien">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                                <tr><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Kota</th><th class="px-4 py-3">Telepon</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($instansiRows as $row)
                                    <tr><td class="px-4 py-3 font-medium">{{ $row['KodeInstansi'] }}</td><td class="px-4 py-3">{{ $row['NamaInstansi'] }}</td><td class="px-4 py-3">{{ $row['Kota'] }}</td><td class="px-4 py-3">{{ $row['Telepon'] }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada klien.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-master.panel>
            </section>

            <section x-show="tab === 'kontak'" x-cloak class="grid gap-4 xl:grid-cols-[420px_minmax(0,1fr)]">
                <x-master.panel title="Tambah Kontak">
                    <form wire:submit.prevent="simpanKontak" class="space-y-3">
                        <x-master.select label="Klien" model="formKontak.IdInstansi">
                            <option value="">Pilih klien</option>
                            @foreach ($instansiRows as $instansi)
                                <option value="{{ $instansi['Id'] }}">{{ $instansi['NamaInstansi'] }}</option>
                            @endforeach
                        </x-master.select>
                        <x-master.input label="Kode Kontak" model="formKontak.KodeCustomer" placeholder="PTA-001" />
                        <x-master.input label="Nama Kontak" model="formKontak.NamaCustomer" placeholder="Budi" />
                        <x-master.input label="Jabatan" model="formKontak.Jabatan" placeholder="IT Support" />
                        <x-master.input label="Email" model="formKontak.Email" placeholder="nama@pta.co.id" />
                        <x-master.input label="Telepon" model="formKontak.Telepon" placeholder="021..." />
                        <x-master.button>Simpan Kontak</x-master.button>
                    </form>
                </x-master.panel>

                <x-master.panel title="Daftar Kontak">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                                <tr><th class="px-4 py-3">Klien</th><th class="px-4 py-3">Kontak</th><th class="px-4 py-3">Jabatan</th><th class="px-4 py-3">Email</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($kontakRows as $row)
                                    <tr><td class="px-4 py-3 font-medium">{{ $row['NamaInstansi'] }}</td><td class="px-4 py-3">{{ $row['NamaCustomer'] }}</td><td class="px-4 py-3">{{ $row['Jabatan'] }}</td><td class="px-4 py-3">{{ $row['Email'] }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada kontak.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-master.panel>
            </section>

            <section x-show="tab === 'nomor'" x-cloak class="grid gap-4 xl:grid-cols-[420px_minmax(0,1fr)]">
                <x-master.panel title="Tambah Nomor WhatsApp">
                    <form wire:submit.prevent="simpanNomor" class="space-y-3">
                        <x-master.select label="Kontak" model="formNomor.IdCustomer">
                            <option value="">Pilih kontak</option>
                            @foreach ($kontakRows as $kontak)
                                <option value="{{ $kontak['Id'] }}">{{ $kontak['NamaInstansi'] }} - {{ $kontak['NamaCustomer'] }}</option>
                            @endforeach
                        </x-master.select>
                        <x-master.input label="Nomor WhatsApp" model="formNomor.NomorWhatsapp" placeholder="62812..." />
                        <x-master.input label="Nama di WhatsApp" model="formNomor.NamaKontak" placeholder="Budi PT A" />
                        <x-master.input label="Jabatan Kontak" model="formNomor.JabatanKontak" placeholder="PIC" />
                        <x-master.button>Simpan Nomor</x-master.button>
                    </form>
                </x-master.panel>

                <x-master.panel title="Daftar Nomor WhatsApp">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                                <tr><th class="px-4 py-3">Klien</th><th class="px-4 py-3">Kontak</th><th class="px-4 py-3">Nama WA</th><th class="px-4 py-3">Nomor</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($nomorRows as $row)
                                    <tr><td class="px-4 py-3 font-medium">{{ $row['NamaInstansi'] }}</td><td class="px-4 py-3">{{ $row['NamaCustomer'] }}</td><td class="px-4 py-3">{{ $row['NamaKontak'] }}</td><td class="px-4 py-3">{{ $row['NomorWhatsapp'] }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada nomor.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-master.panel>
            </section>

            <section x-show="tab === 'grup'" x-cloak class="space-y-4">
                <div class="grid gap-4 xl:grid-cols-2">
                    <x-master.panel title="Tambah Grup WhatsApp">
                        <form wire:submit.prevent="simpanGrup" class="space-y-3">
                            <x-master.select label="Klien" model="formGrup.IdInstansi">
                                <option value="">Pilih klien</option>
                                @foreach ($instansiRows as $instansi)
                                    <option value="{{ $instansi['Id'] }}">{{ $instansi['NamaInstansi'] }}</option>
                                @endforeach
                            </x-master.select>
                            <x-master.input label="Kode Grup" model="formGrup.KodeGrup" placeholder="GRP-PTA" />
                            <x-master.input label="Nama Grup" model="formGrup.NamaGrup" placeholder="Support PT A" />
                            <x-master.input label="ID Grup WAHA" model="formGrup.IdGrupWaha" placeholder="1203630...@g.us" />
                            <x-master.input label="Deskripsi" model="formGrup.Deskripsi" placeholder="Grup support operasional" />
                            <x-master.button>Simpan Grup</x-master.button>
                        </form>
                    </x-master.panel>

                    <x-master.panel title="Tambah Anggota Grup">
                        <form wire:submit.prevent="tambahAnggotaGrup" class="space-y-3">
                            <x-master.select label="Grup" model="formAnggotaGrup.IdGrupWhatsapp">
                                <option value="">Pilih grup</option>
                                @foreach ($grupRows as $grup)
                                    <option value="{{ $grup['Id'] }}">{{ $grup['NamaInstansi'] }} - {{ $grup['NamaGrup'] }}</option>
                                @endforeach
                            </x-master.select>
                            <x-master.select label="Nomor WhatsApp" model="formAnggotaGrup.IdNomorWhatsapp">
                                <option value="">Pilih nomor</option>
                                @foreach ($nomorRows as $nomor)
                                    <option value="{{ $nomor['Id'] }}">{{ $nomor['NamaInstansi'] }} - {{ $nomor['NamaKontak'] }} - {{ $nomor['NomorWhatsapp'] }}</option>
                                @endforeach
                            </x-master.select>
                            <x-master.input label="Peran Anggota" model="formAnggotaGrup.PeranAnggota" placeholder="PIC / IT / Owner" />
                            <x-master.button>Tambah Anggota</x-master.button>
                        </form>
                    </x-master.panel>
                </div>

                <x-master.panel title="Daftar Grup dan Anggota">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                                <tr><th class="px-4 py-3">Klien</th><th class="px-4 py-3">Grup</th><th class="px-4 py-3">ID WAHA</th><th class="px-4 py-3">Anggota</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($grupRows as $grup)
                                    <tr>
                                        <td class="px-4 py-3 font-medium">{{ $grup['NamaInstansi'] }}</td>
                                        <td class="px-4 py-3">{{ $grup['NamaGrup'] }}</td>
                                        <td class="px-4 py-3">{{ $grup['IdGrupWaha'] }}</td>
                                        <td class="px-4 py-3">
                                            @php($anggota = collect($anggotaGrupRows)->where('NamaInstansi', $grup['NamaInstansi'])->where('NamaGrup', $grup['NamaGrup']))
                                            @forelse ($anggota as $item)
                                                <div>{{ $item['NamaKontak'] }} - {{ $item['NomorWhatsapp'] }}</div>
                                            @empty
                                                <span class="text-gray-500">Belum ada anggota</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada grup.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-master.panel>
            </section>
        </div>
    </div>
</x-filament-panels::page>
