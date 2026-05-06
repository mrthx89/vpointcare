<x-filament-panels::page>
    <div class="vc-dashboard">
        <section class="vc-stats-grid">
            @php
                $cards = [
                    ['label' => 'Klien', 'value' => $stats['instansi'] ?? 0, 'tone' => 'vc-tone-blue'],
                    ['label' => 'Kontak', 'value' => $stats['kontak'] ?? 0, 'tone' => 'vc-tone-emerald'],
                    ['label' => 'Nomor WA', 'value' => $stats['nomor'] ?? 0, 'tone' => 'vc-tone-amber'],
                    ['label' => 'Grup WA', 'value' => $stats['grup'] ?? 0, 'tone' => 'vc-tone-cyan'],
                    ['label' => 'Anggota Grup', 'value' => $stats['anggota'] ?? 0, 'tone' => 'vc-tone-rose'],
                ];
            @endphp

            @foreach ($cards as $card)
                <div class="vc-stat-card">
                    <div class="vc-stat-body">
                        <div>
                            <div class="vc-stat-label">{{ $card['label'] }}</div>
                            <div class="vc-stat-value">{{ \App\Support\LocaleFormatter::number($card['value']) }}</div>
                        </div>
                        <div class="vc-stat-tone {{ $card['tone'] }}"></div>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="vc-card">
            <div class="vc-section-head">
                <div>
                    <h2 class="vc-title">Alur Master Customer</h2>
                    <p class="vc-copy">
                        Mapping dibuat bertingkat agar chat pribadi dan chat grup bisa langsung dikenali asal kliennya.
                    </p>
                </div>
            </div>

            <div class="vc-flow-grid">
                @foreach ([['step' => '01', 'title' => 'Klien', 'body' => 'Daftarkan PT, instansi, atau perusahaan customer.'], ['step' => '02', 'title' => 'Kontak', 'body' => 'Masukkan PIC atau user di bawah klien tersebut.'], ['step' => '03', 'title' => 'Nomor WA', 'body' => 'Hubungkan nomor WhatsApp pribadi ke kontak customer.'], ['step' => '04', 'title' => 'Grup WA', 'body' => 'Mapping grup WAHA dan anggota grup ke klien.']] as $item)
                    <div class="vc-flow-card">
                        <div class="vc-step">{{ $item['step'] }}</div>
                        <div class="vc-flow-title">{{ $item['title'] }}</div>
                        <p class="vc-flow-body">{{ $item['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="vc-content-grid">
            <div class="vc-card">
                <div class="vc-section-head">
                    <div>
                        <h2 class="vc-title">Kelola Data Master</h2>
                        <p class="vc-copy">
                            Setiap menu memakai tabel Filament dengan search, sort, filter, footer, dan pagination
                            server-side.
                        </p>
                    </div>
                </div>

                <div class="vc-master-grid">
                    @foreach ($masterLinks as $link)
                        <a href="{{ $link['url'] }}" class="vc-link-card">
                            <div class="vc-link-row">
                                <div>
                                    <div class="vc-link-title">{{ $link['title'] }}</div>
                                    <p class="vc-link-description">{{ $link['description'] }}</p>
                                </div>
                                <span class="vc-link-badge">Buka</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="vc-card">
                <h2 class="vc-title">Mapping Nomor Terbaru</h2>
                <div class="vc-recent-list">
                    @forelse ($recentMappings as $row)
                        <div class="vc-recent-item">
                            <div class="vc-recent-client">
                                {{ $row['NamaInstansi'] ?? 'Tanpa klien' }}
                            </div>
                            <div class="vc-recent-contact">
                                {{ $row['NamaKontak'] ?: $row['NamaCustomer'] ?? '-' }}
                            </div>
                            <div class="vc-recent-number">
                                {{ $row['NomorWhatsapp'] ?? '-' }}
                            </div>
                        </div>
                    @empty
                        <div class="vc-empty-state">
                            Belum ada nomor WhatsApp yang dimapping.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
