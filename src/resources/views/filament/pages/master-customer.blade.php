<x-filament-panels::page>
    <div class="vc-dashboard">
        <section class="vc-stats-grid">
            @php
                $cards = [
                    [
                        'label' => __('ui.pages.master_customer.clients'),
                        'value' => $stats['instansi'] ?? 0,
                        'tone' => 'vc-tone-blue',
                    ],
                    [
                        'label' => __('ui.pages.master_customer.contacts'),
                        'value' => $stats['kontak'] ?? 0,
                        'tone' => 'vc-tone-emerald',
                    ],
                    [
                        'label' => __('ui.pages.master_customer.wa_numbers'),
                        'value' => $stats['nomor'] ?? 0,
                        'tone' => 'vc-tone-amber',
                    ],
                    [
                        'label' => __('ui.pages.master_customer.wa_groups'),
                        'value' => $stats['grup'] ?? 0,
                        'tone' => 'vc-tone-cyan',
                    ],
                    [
                        'label' => __('ui.pages.master_customer.group_members'),
                        'value' => $stats['anggota'] ?? 0,
                        'tone' => 'vc-tone-rose',
                    ],
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
                    <h2 class="vc-title">{{ __('ui.pages.master_customer.flow_title') }}</h2>
                    <p class="vc-copy">
                        {{ __('ui.pages.master_customer.flow_desc') }}
                    </p>
                </div>
            </div>

            <div class="vc-flow-grid">
                @foreach ([['step' => '01', 'title' => __('ui.pages.master_customer.flow_client_title'), 'body' => __('ui.pages.master_customer.flow_client_body')], ['step' => '02', 'title' => __('ui.pages.master_customer.flow_contact_title'), 'body' => __('ui.pages.master_customer.flow_contact_body')], ['step' => '03', 'title' => __('ui.pages.master_customer.flow_number_title'), 'body' => __('ui.pages.master_customer.flow_number_body')], ['step' => '04', 'title' => __('ui.pages.master_customer.flow_group_title'), 'body' => __('ui.pages.master_customer.flow_group_body')]] as $item)
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
                        <h2 class="vc-title">{{ __('ui.pages.master_customer.manage_master') }}</h2>
                        <p class="vc-copy">
                            {{ __('ui.pages.master_customer.manage_master_desc') }}
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
                                <span class="vc-link-badge">{{ __('ui.common.open') }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="vc-card">
                <h2 class="vc-title">{{ __('ui.pages.master_customer.latest_mapping') }}</h2>
                <div class="vc-recent-list">
                    @forelse ($recentMappings as $row)
                        <div class="vc-recent-item">
                            <div class="vc-recent-client">
                                {{ $row['NamaInstansi'] ?? __('ui.pages.master_customer.no_client') }}
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
                            {{ __('ui.pages.master_customer.no_mapping') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
