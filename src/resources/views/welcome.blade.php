<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" translate="no" class="notranslate" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="google" content="notranslate">
    <meta name="robots" content="notranslate">

    <title>{{ __('ui.seo.title') }}</title>
    @include('components.seo-meta')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700,800,900|playfair-display:400,600,700,800,900&display=swap" rel="stylesheet" />

    <script>
        (() => {
            const key = 'vpoint-care-landing-theme';
            const saved = localStorage.getItem(key) || 'system';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const resolved = saved === 'system' ? (prefersDark ? 'dark' : 'light') : saved;

            document.documentElement.dataset.theme = resolved;
            document.documentElement.dataset.themeChoice = saved;
        })();
    </script>

    <style>
        :root {
            color-scheme: light;
            --ink: #14212a;
            --muted: #64727f;
            --line: #d9e4ea;
            --surface: #ffffff;
            --surface-soft: #f3f8f7;
            --surface-strong: #e9f3f1;
            --brand: #0f766e;
            --brand-strong: #115e59;
            --accent: #c2410c;
            --blue: #2563eb;
            --violet: #6d28d9;
            --shadow: none;
            --card-radius: 1rem;
            --hero-overlay: linear-gradient(90deg, rgba(246, 250, 249, .98) 0%, rgba(246, 250, 249, .9) 39%, rgba(246, 250, 249, .28) 74%);
        }

        :root[data-theme="dark"] {
            color-scheme: dark;
            --ink: #eef6f6;
            --muted: #a8b8c1;
            --line: #263843;
            --surface: #0f1a21;
            --surface-soft: #0b141a;
            --surface-strong: #14262c;
            --brand: #2dd4bf;
            --brand-strong: #5eead4;
            --accent: #fb923c;
            --blue: #60a5fa;
            --violet: #a78bfa;
            --shadow: none;
            --hero-overlay: linear-gradient(90deg, rgba(8, 15, 20, .98) 0%, rgba(8, 15, 20, .9) 42%, rgba(8, 15, 20, .34) 78%);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: "DM Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background: var(--surface-soft);
        }
        h1, h2, h3, h4, h5, h6, th, .brand span {
            font-family: "Playfair Display", ui-serif, Georgia, Cambria, "Times New Roman", Times, serif !important;
            letter-spacing: 0.01em;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button {
            font: inherit;
        }

        .page {
            min-height: 100vh;
            overflow: hidden;
        }

        .nav {
            position: fixed;
            inset: 16px 0 auto;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 10px 12px;
            border: 1px solid color-mix(in srgb, var(--line), transparent 18%);
            border-radius: var(--card-radius);
            background: color-mix(in srgb, var(--surface), transparent 8%);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            font-size: 24px;
            line-height: 1;
            font-weight: 700;
        }

        .brand img {
            width: 1em;
            height: 1em;
            display: block;
        }

        .brand span {
            white-space: nowrap;
            line-height: 1;
        }

        .nav-links,
        .nav-actions,
        .segmented {
            display: flex;
            align-items: center;
        }

        .nav-links {
            gap: 18px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        .nav-links a:hover {
            color: var(--ink);
        }

        .nav-actions {
            gap: 8px;
        }

        .mobile-menu-button,
        .mobile-menu {
            display: none;
        }

        .segmented {
            gap: 3px;
            padding: 3px;
            border: 1px solid var(--line);
            border-radius: var(--card-radius);
            background: color-mix(in srgb, var(--surface-soft), var(--surface) 35%);
        }

        .segmented a,
        .segmented button {
            min-width: 36px;
            min-height: 32px;
            border: 0;
            border-radius: 0.75rem;
            color: var(--muted);
            background: transparent;
            cursor: pointer;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            text-align: center;
        }

        .segmented a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 9px;
        }

        .segmented button {
            padding: 0 8px;
        }

        .segmented svg,
        .mobile-menu-button svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }

        .segmented a.active,
        .segmented button.is-active {
            color: #fff;
            background: var(--brand-strong);
        }

        .mobile-menu-button {
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid var(--line);
            border-radius: var(--card-radius);
            color: var(--ink);
            background: color-mix(in srgb, var(--surface), transparent 8%);
            cursor: pointer;
        }

        .mobile-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: min(320px, calc(100vw - 32px));
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: var(--card-radius);
            background: color-mix(in srgb, var(--surface), transparent 2%);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }

        .mobile-menu.is-open {
            display: grid;
            gap: 12px;
        }

        .mobile-menu-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--line);
        }

        .mobile-menu-title {
            font-size: 13px;
            font-weight: 800;
            color: var(--muted);
        }

        .mobile-menu-links,
        .mobile-menu-section {
            display: grid;
            gap: 8px;
        }

        .mobile-menu-links a {
            padding: 10px 12px;
            border-radius: var(--card-radius);
            color: var(--ink);
            background: var(--surface-soft);
            font-size: 14px;
            font-weight: 800;
        }

        .mobile-menu-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 10px 16px;
            border-radius: var(--card-radius);
            font-size: 14px;
            font-weight: 800;
            transition: transform .16s ease, background .16s ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            color: #fff;
            background: #0f766e;
            box-shadow: none;
        }

        :root[data-theme="dark"] .button-primary {
            color: #06201d;
            background: #5eead4;
        }

        .button-secondary {
            color: var(--ink);
            border: 1px solid var(--line);
            background: color-mix(in srgb, var(--surface), transparent 8%);
        }

        .hero {
            position: relative;
            min-height: 90vh;
            display: grid;
            align-items: end;
            padding: 126px 0 70px;
            background:
                var(--hero-overlay),
                url("{{ asset('seo-banner.jpg') }}") center right / cover no-repeat;
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: auto 0 0;
            height: 130px;
            background: linear-gradient(180deg, transparent, var(--surface-soft) 82%);
        }

        .shell {
            position: relative;
            z-index: 1;
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 660px) minmax(300px, 420px);
            gap: 52px;
            align-items: end;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            margin: 0 0 18px;
            padding: 7px 10px;
            border: 1px solid color-mix(in srgb, var(--brand), transparent 68%);
            border-radius: var(--card-radius);
            color: var(--brand-strong);
            background: color-mix(in srgb, var(--surface), transparent 16%);
            font-size: 13px;
            font-weight: 800;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        h1 {
            margin-bottom: 0;
            max-width: 680px;
            color: var(--ink);
            font-size: clamp(44px, 7vw, 82px);
            line-height: .95;
            letter-spacing: 0;
        }

        .lead {
            max-width: 620px;
            margin: 24px 0 0;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.7;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 30px;
        }

        .ops-panel {
            border: 1px solid var(--line);
            border-radius: var(--card-radius);
            background: color-mix(in srgb, var(--surface), transparent 4%);
            box-shadow: var(--shadow);
        }

        .ops-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid var(--line);
        }

        .ops-panel-title {
            font-size: 14px;
            font-weight: 800;
        }

        .ops-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--brand-strong);
            font-size: 12px;
            font-weight: 800;
        }

        .ops-status::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--brand);
        }

        .ops-list {
            display: grid;
            gap: 10px;
            padding: 14px;
        }

        .ops-item {
            display: grid;
            grid-template-columns: 42px 1fr auto;
            gap: 12px;
            align-items: center;
            min-height: 68px;
            padding: 12px;
            border: 1px solid color-mix(in srgb, var(--line), transparent 26%);
            border-radius: var(--card-radius);
            background: color-mix(in srgb, var(--surface-soft), var(--surface) 45%);
        }

        .ops-icon {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: var(--card-radius);
            color: #fff;
            background: var(--brand-strong);
            font-size: 12px;
            font-weight: 900;
        }

        .ops-item:nth-child(2) .ops-icon {
            background: var(--blue);
        }

        .ops-item:nth-child(3) .ops-icon {
            background: var(--accent);
        }

        .ops-copy strong,
        .ops-copy span {
            display: block;
        }

        .ops-copy strong {
            margin-bottom: 4px;
            font-size: 14px;
        }

        .ops-copy span,
        .ops-count {
            color: var(--muted);
            font-size: 12px;
        }

        .ops-count {
            font-weight: 800;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 28px;
        }

        .metric {
            min-height: 112px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: var(--card-radius);
            background: var(--surface);
            box-shadow: none;
        }

        .metric strong {
            display: block;
            color: var(--brand-strong);
            font-size: 28px;
            line-height: 1;
        }

        .metric span {
            display: block;
            margin-top: 10px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .section {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 58px 0;
        }

        .section-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 30px;
            margin-bottom: 24px;
        }

        .section h2 {
            max-width: 680px;
            margin-bottom: 0;
            color: var(--ink);
            font-size: clamp(28px, 4vw, 42px);
            line-height: 1.08;
            letter-spacing: 0;
        }

        .section-head p {
            max-width: 440px;
            margin-bottom: 0;
            color: var(--muted);
            line-height: 1.65;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .feature {
            min-height: 220px;
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: var(--card-radius);
            background: var(--surface);
        }

        .feature-mark {
            display: inline-grid;
            place-items: center;
            width: 40px;
            height: 40px;
            margin-bottom: 20px;
            border-radius: var(--card-radius);
            color: #fff;
            background: var(--brand-strong);
            font-size: 12px;
            font-weight: 900;
        }

        .feature:nth-child(2) .feature-mark {
            background: var(--blue);
        }

        .feature:nth-child(3) .feature-mark {
            background: var(--accent);
        }

        .feature:nth-child(4) .feature-mark {
            background: var(--violet);
        }

        .feature h3 {
            margin-bottom: 10px;
            font-size: 17px;
            line-height: 1.25;
        }

        .feature p {
            margin-bottom: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .workflow {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 18px;
            align-items: stretch;
        }

        .workflow-main,
        .admin-panel {
            border: 1px solid var(--line);
            border-radius: var(--card-radius);
            background: var(--surface);
        }

        .workflow-main {
            padding: 28px;
        }

        .workflow-list {
            display: grid;
            gap: 16px;
            margin-top: 28px;
        }

        .workflow-item {
            display: grid;
            grid-template-columns: 36px 1fr;
            gap: 14px;
        }

        .workflow-number {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border-radius: var(--card-radius);
            color: var(--brand-strong);
            background: var(--surface-strong);
            font-weight: 900;
        }

        .workflow-item strong {
            display: block;
            margin-bottom: 5px;
        }

        .workflow-item span {
            color: var(--muted);
            line-height: 1.58;
        }

        .admin-panel {
            display: grid;
            align-content: center;
            padding: 32px;
            color: #f8fbfb;
            background:
                linear-gradient(135deg, rgba(15, 118, 110, .88), rgba(20, 33, 42, .96)),
                url("{{ asset('seo-banner.jpg') }}") center / cover no-repeat;
        }

        .admin-panel p {
            color: #d2e0e0;
            line-height: 1.65;
        }

        .footer {
            border-top: 1px solid var(--line);
            background: var(--surface);
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 24px 0;
            color: var(--muted);
            font-size: 14px;
        }

        @media (max-width: 1020px) {
            .nav-links {
                display: none;
            }

            .hero-grid,
            .workflow {
                grid-template-columns: 1fr;
            }

            .ops-panel {
                max-width: 620px;
            }

            .feature-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .nav {
                position: absolute;
                align-items: center;
            }

            .nav>.nav-actions {
                display: none;
            }

            .mobile-menu-button {
                display: inline-flex;
            }

            .brand span {
                max-width: 136px;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .hero {
                min-height: auto;
                padding-top: 124px;
                background:
                    linear-gradient(180deg, color-mix(in srgb, var(--surface-soft), transparent 2%) 0%, color-mix(in srgb, var(--surface-soft), transparent 18%) 58%, var(--surface-soft) 100%),
                    url("{{ asset('seo-banner.jpg') }}") center top / cover no-repeat;
            }

            .metrics,
            .feature-grid {
                grid-template-columns: 1fr;
            }

            .section-head {
                display: block;
            }

            .section-head p {
                margin-top: 12px;
            }

            .hero-actions,
            .admin-panel .hero-actions {
                display: grid;
            }

            .button {
                width: 100%;
            }

            .ops-item {
                grid-template-columns: 42px 1fr;
            }

            .ops-count {
                grid-column: 2;
            }

            .footer-inner {
                display: grid;
            }
        }
    </style>
</head>

<body translate="no" class="notranslate">
    @php
        $currentLocale = \App\Support\LocaleManager::current();
        $supportedLocales = \App\Support\LocaleManager::supported();
    @endphp

    <div class="page">
        <header class="nav">
            <a class="brand" href="{{ url('/') }}" aria-label="{{ config('app.name', 'VPoint Care') }}">
                <img src="{{ asset('images/logo_primary.svg') }}" alt="{{ config('app.name', 'VPoint Care') }}">
                <span>{{ config('app.name', 'VPoint Care') }}</span>
            </a>

            <nav class="nav-links" aria-label="{{ __('ui.landing.nav_label') }}">
                <a href="#features">{{ __('ui.landing.nav_features') }}</a>
                <a href="#workflow">{{ __('ui.landing.nav_workflow') }}</a>
            </nav>

            <div class="nav-actions">
                <div class="segmented" aria-label="{{ __('ui.landing.theme_label') }}">
                    <button type="button" data-theme-choice="light" title="{{ __('ui.landing.theme_light') }}"
                        aria-label="{{ __('ui.landing.theme_light') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="4"></circle>
                            <path
                                d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41">
                            </path>
                        </svg>
                    </button>
                    <button type="button" data-theme-choice="dark" title="{{ __('ui.landing.theme_dark') }}"
                        aria-label="{{ __('ui.landing.theme_dark') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20.99 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 20.99 12.79Z"></path>
                        </svg>
                    </button>
                    <button type="button" data-theme-choice="system" title="{{ __('ui.landing.theme_system') }}"
                        aria-label="{{ __('ui.landing.theme_system') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="12" rx="2"></rect>
                            <path d="M8 20h8M12 16v4"></path>
                        </svg>
                    </button>
                </div>

                <div class="segmented" aria-label="{{ __('ui.language.label') }}">
                    @foreach ($supportedLocales as $locale => $data)
                        <a href="{{ route('locale.switch', ['locale' => $locale]) }}"
                            class="{{ $currentLocale === $locale ? 'active' : '' }}">
                            {{ $data['short'] ?? strtoupper($locale) }}
                        </a>
                    @endforeach
                </div>

                <a class="button button-primary" href="{{ url('/admin') }}">{{ __('ui.landing.login') }}</a>
            </div>

            <button type="button" class="mobile-menu-button" data-mobile-menu-toggle
                aria-label="{{ __('ui.landing.open_menu') }}" aria-expanded="false">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16"></path>
                </svg>
            </button>

            <div class="mobile-menu" data-mobile-menu>
                <div class="mobile-menu-head">
                    <span class="mobile-menu-title">{{ __('ui.landing.menu_label') }}</span>
                    <button type="button" class="mobile-menu-button" data-mobile-menu-close
                        aria-label="{{ __('ui.landing.close_menu') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <nav class="mobile-menu-links" aria-label="{{ __('ui.landing.nav_label') }}">
                    <a href="#features" data-mobile-menu-link>{{ __('ui.landing.nav_features') }}</a>
                    <a href="#workflow" data-mobile-menu-link>{{ __('ui.landing.nav_workflow') }}</a>
                </nav>

                <div class="mobile-menu-section">
                    <div class="mobile-menu-label">{{ __('ui.landing.theme_label') }}</div>
                    <div class="segmented" aria-label="{{ __('ui.landing.theme_label') }}">
                        <button type="button" data-theme-choice="light" title="{{ __('ui.landing.theme_light') }}"
                            aria-label="{{ __('ui.landing.theme_light') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="4"></circle>
                                <path
                                    d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41">
                                </path>
                            </svg>
                        </button>
                        <button type="button" data-theme-choice="dark" title="{{ __('ui.landing.theme_dark') }}"
                            aria-label="{{ __('ui.landing.theme_dark') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20.99 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 20.99 12.79Z"></path>
                            </svg>
                        </button>
                        <button type="button" data-theme-choice="system" title="{{ __('ui.landing.theme_system') }}"
                            aria-label="{{ __('ui.landing.theme_system') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="12" rx="2"></rect>
                                <path d="M8 20h8M12 16v4"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="mobile-menu-section">
                    <div class="mobile-menu-label">{{ __('ui.language.label') }}</div>
                    <div class="segmented" aria-label="{{ __('ui.language.label') }}">
                        @foreach ($supportedLocales as $locale => $data)
                            <a href="{{ route('locale.switch', ['locale' => $locale]) }}"
                                class="{{ $currentLocale === $locale ? 'active' : '' }}">
                                {{ $data['short'] ?? strtoupper($locale) }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <a class="button button-primary" href="{{ url('/admin') }}">{{ __('ui.landing.login') }}</a>
            </div>
        </header>

        <main>
            <section class="hero">
                <div class="shell">
                    <div class="hero-grid">
                        <div>
                            <div class="eyebrow">{{ __('ui.landing.eyebrow') }}</div>
                            <h1>{{ __('ui.landing.title') }}</h1>
                            <p class="lead">{{ __('ui.landing.subtitle') }}</p>
                            <div class="hero-actions">
                                <a class="button button-primary"
                                    href="{{ url('/admin') }}">{{ __('ui.landing.login') }}</a>
                                <a class="button button-secondary"
                                    href="#features">{{ __('ui.landing.explore') }}</a>
                            </div>

                            <div class="metrics" aria-label="{{ __('ui.landing.metrics_label') }}">
                                <div class="metric">
                                    <strong>{{ __('ui.landing.metric_1_value') }}</strong>
                                    <span>{{ __('ui.landing.metric_1_label') }}</span>
                                </div>
                                <div class="metric">
                                    <strong>{{ __('ui.landing.metric_2_value') }}</strong>
                                    <span>{{ __('ui.landing.metric_2_label') }}</span>
                                </div>
                                <div class="metric">
                                    <strong>{{ __('ui.landing.metric_3_value') }}</strong>
                                    <span>{{ __('ui.landing.metric_3_label') }}</span>
                                </div>
                            </div>
                        </div>

                        <aside class="ops-panel" aria-label="{{ __('ui.landing.ops_title') }}">
                            <div class="ops-panel-header">
                                <div class="ops-panel-title">{{ __('ui.landing.ops_title') }}</div>
                                <div class="ops-status">{{ __('ui.landing.ops_status') }}</div>
                            </div>
                            <div class="ops-list">
                                @foreach ([['icon' => 'WA', 'title' => __('ui.landing.ops_inbox_title'), 'body' => __('ui.landing.ops_inbox_body'), 'count' => __('ui.landing.ops_inbox_count')], ['icon' => 'AI', 'title' => __('ui.landing.ops_ai_title'), 'body' => __('ui.landing.ops_ai_body'), 'count' => __('ui.landing.ops_ai_count')], ['icon' => 'TK', 'title' => __('ui.landing.ops_ticket_title'), 'body' => __('ui.landing.ops_ticket_body'), 'count' => __('ui.landing.ops_ticket_count')]] as $item)
                                    <div class="ops-item">
                                        <div class="ops-icon">{{ $item['icon'] }}</div>
                                        <div class="ops-copy">
                                            <strong>{{ $item['title'] }}</strong>
                                            <span>{{ $item['body'] }}</span>
                                        </div>
                                        <div class="ops-count">{{ $item['count'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </aside>
                    </div>
                </div>
            </section>

            <section class="section" id="features">
                <div class="section-head">
                    <h2>{{ __('ui.landing.features_title') }}</h2>
                    <p>{{ __('ui.landing.features_desc') }}</p>
                </div>

                <div class="feature-grid">
                    @foreach ([['title' => __('ui.landing.feature_inbox_title'), 'body' => __('ui.landing.feature_inbox_body'), 'icon' => 'WA'], ['title' => __('ui.landing.feature_ai_title'), 'body' => __('ui.landing.feature_ai_body'), 'icon' => 'AI'], ['title' => __('ui.landing.feature_ticket_title'), 'body' => __('ui.landing.feature_ticket_body'), 'icon' => 'TK'], ['title' => __('ui.landing.feature_monitor_title'), 'body' => __('ui.landing.feature_monitor_body'), 'icon' => 'LG']] as $feature)
                        <article class="feature">
                            <div class="feature-mark">{{ $feature['icon'] }}</div>
                            <h3>{{ $feature['title'] }}</h3>
                            <p>{{ $feature['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="section" id="workflow">
                <div class="workflow">
                    <div class="workflow-main">
                        <h2>{{ __('ui.landing.workflow_title') }}</h2>
                        <div class="workflow-list">
                            @foreach ([['title' => __('ui.landing.workflow_1_title'), 'body' => __('ui.landing.workflow_1_body')], ['title' => __('ui.landing.workflow_2_title'), 'body' => __('ui.landing.workflow_2_body')], ['title' => __('ui.landing.workflow_3_title'), 'body' => __('ui.landing.workflow_3_body')]] as $index => $item)
                                <div class="workflow-item">
                                    <div class="workflow-number">{{ $index + 1 }}</div>
                                    <div>
                                        <strong>{{ $item['title'] }}</strong>
                                        <span>{{ $item['body'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <aside class="admin-panel">
                        <h2>{{ __('ui.landing.admin_title') }}</h2>
                        <p>{{ __('ui.landing.admin_desc') }}</p>
                        <div class="hero-actions">
                            <a class="button button-primary"
                                href="{{ url('/admin') }}">{{ __('ui.landing.open_admin') }}</a>
                        </div>
                    </aside>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="footer-inner">
                <span>{{ __('ui.landing.footer_product') }}</span>
                <span>{{ __('ui.landing.footer_copyright', ['year' => now()->year]) }}</span>
            </div>
        </footer>
    </div>

    <script>
        (() => {
            const key = 'vpoint-care-landing-theme';
            const media = window.matchMedia('(prefers-color-scheme: dark)');
            const buttons = [...document.querySelectorAll('[data-theme-choice]')];
            const menu = document.querySelector('[data-mobile-menu]');
            const menuToggle = document.querySelector('[data-mobile-menu-toggle]');
            const menuClose = document.querySelector('[data-mobile-menu-close]');

            document.documentElement.setAttribute('translate', 'no');
            document.documentElement.classList.add('notranslate');
            document.body.setAttribute('translate', 'no');
            document.body.classList.add('notranslate');

            const resolveTheme = (choice) => {
                return choice === 'system' ? (media.matches ? 'dark' : 'light') : choice;
            };

            const applyTheme = (choice) => {
                const normalized = ['light', 'dark', 'system'].includes(choice) ? choice : 'system';

                document.documentElement.dataset.themeChoice = normalized;
                document.documentElement.dataset.theme = resolveTheme(normalized);
                localStorage.setItem(key, normalized);

                buttons.forEach((button) => {
                    const active = button.dataset.themeChoice === normalized;
                    button.classList.toggle('is-active', active);
                    button.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            };

            buttons.forEach((button) => {
                button.addEventListener('click', () => applyTheme(button.dataset.themeChoice));
            });

            const setMenuOpen = (open) => {
                if (!menu || !menuToggle) {
                    return;
                }

                menu.classList.toggle('is-open', open);
                menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            menuToggle?.addEventListener('click', (event) => {
                event.stopPropagation();
                setMenuOpen(!menu?.classList.contains('is-open'));
            });

            menuClose?.addEventListener('click', () => setMenuOpen(false));

            document.querySelectorAll('[data-mobile-menu-link]').forEach((link) => {
                link.addEventListener('click', () => setMenuOpen(false));
            });

            document.addEventListener('click', (event) => {
                if (!menu?.classList.contains('is-open')) {
                    return;
                }

                if (menu.contains(event.target) || menuToggle?.contains(event.target)) {
                    return;
                }

                setMenuOpen(false);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    setMenuOpen(false);
                }
            });

            media.addEventListener('change', () => {
                if ((localStorage.getItem(key) || 'system') === 'system') {
                    applyTheme('system');
                }
            });

            applyTheme(localStorage.getItem(key) || document.documentElement.dataset.themeChoice || 'system');
        })();
    </script>
</body>

</html>
