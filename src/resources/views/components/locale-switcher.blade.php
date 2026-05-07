@props([
    'compact' => false,
    'alignment' => 'end',
])

@php
    $currentLocale = \App\Support\LocaleManager::current();
    $locales = \App\Support\LocaleManager::supported();
@endphp

<div class="wacs-locale-switcher {{ $compact ? 'wacs-locale-switcher-compact' : '' }} {{ $alignment === 'center' ? 'wacs-locale-switcher-center' : '' }}" translate="no">
    @unless ($compact)
        <span class="wacs-locale-label">{{ __('ui.language.label') }}</span>
    @endunless
    <div class="wacs-locale-options" role="group" aria-label="{{ __('ui.language.label') }}">
        @foreach ($locales as $locale => $meta)
            <a
                href="{{ route('locale.switch', ['locale' => $locale]) }}"
                class="wacs-locale-option {{ $currentLocale === $locale ? 'is-active' : '' }}"
                aria-current="{{ $currentLocale === $locale ? 'true' : 'false' }}"
                title="{{ $meta['label'] }}"
            >
                {{ $meta['short'] }}
            </a>
        @endforeach
    </div>
</div>
