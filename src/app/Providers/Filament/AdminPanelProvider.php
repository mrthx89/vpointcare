<?php

namespace App\Providers\Filament;

use App\Filament\Actions\EditOwnProfileAction;
use App\Filament\Auth\Login;
use App\Filament\Auth\Register;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\SetLocale;
use App\Support\NavigationHelper;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->registration(Register::class)
            ->brandName('VPoint Care')
            ->brandLogo(fn() => new HtmlString(
                '<div class="vpoint-brand"><img src="' . asset('images/logo_primary.svg') . '" alt="VPoint Care"><span>VPoint Care</span></div>'
            ))
            ->darkModeBrandLogo(fn() => new HtmlString(
                '<div class="vpoint-brand vpoint-brand-dark"><img src="' . asset('images/logo_secondary.svg') . '" alt="VPoint Care"><span>VPoint Care</span></div>'
            ))
            ->brandLogoHeight('2.25rem')
            ->maxContentWidth(Width::Full)
            ->simplePageMaxContentWidth(Width::Large)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->collapsedSidebarWidth('4.75rem')
            ->favicon(asset('images/logo_primary.svg'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn(): string => '<meta name="google" content="notranslate"><meta name="robots" content="notranslate">' . view('components.seo-meta')->render()
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn(): string => <<<'HTML'
<style>
    .wacs-locale-switcher { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; padding-inline: .5rem; }
    .wacs-locale-switcher-center { justify-content: center; padding-inline: 0; }
    .wacs-locale-label { font-size: .75rem; font-weight: 600; color: rgb(100 116 139); }
    .wacs-locale-options { display: inline-flex; align-items: center; gap: .125rem; border: 1px solid rgb(203 213 225); border-radius: .5rem; padding: .125rem; background: rgb(248 250 252); }
    .wacs-locale-option { min-width: 2.125rem; border-radius: .375rem; padding: .3125rem .5rem; text-align: center; font-size: .75rem; line-height: 1rem; font-weight: 700; color: rgb(71 85 105); text-decoration: none; transition: background-color .15s ease, color .15s ease, box-shadow .15s ease; }
    .wacs-locale-option:hover { background: white; color: rgb(15 23 42); }
    .wacs-locale-option.is-active { background: rgb(37 99 235); color: white; box-shadow: 0 1px 2px rgb(15 23 42 / .14); }
    .wacs-locale-switcher-compact .wacs-locale-options { background: transparent; }
    .wacs-external-auth { margin-top: 1rem; display: grid; gap: .875rem; }
    .wacs-auth-divider { display: flex; align-items: center; gap: .75rem; color: rgb(100 116 139); font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
    .wacs-auth-divider::before, .wacs-auth-divider::after { content: ''; height: 1px; flex: 1; background: rgb(226 232 240); }
    .wacs-auth-buttons { display: grid; gap: .625rem; }
    .wacs-auth-button { display: flex; align-items: center; justify-content: center; gap: .625rem; border-radius: .875rem; padding: .75rem 1rem; font-size: .925rem; font-weight: 800; text-decoration: none; transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease, background .15s ease; }
    .wacs-auth-button:hover { transform: translateY(-1px); box-shadow: 0 12px 24px rgb(15 23 42 / .12); }
    .wacs-auth-button-google { border: 1px solid rgb(203 213 225); background: white; color: rgb(15 23 42); }
    .wacs-auth-button-sso { border: 1px solid rgb(29 78 216); background: linear-gradient(135deg, rgb(37 99 235), rgb(14 165 233)); color: white; }
    .wacs-auth-icon { display: inline-grid; place-items: center; width: 1.625rem; height: 1.625rem; border-radius: 999px; background: rgb(241 245 249); color: rgb(37 99 235); font-weight: 900; }
    .wacs-auth-button-sso .wacs-auth-icon { background: rgb(255 255 255 / .18); color: white; }
    .wacs-auth-security-badge { border: 1px solid rgb(191 219 254); border-radius: .75rem; padding: .625rem .75rem; background: rgb(239 246 255); color: rgb(30 64 175); font-size: .8125rem; font-weight: 700; text-align: center; }
    .wacs-auth-alert { margin-bottom: 1rem; border-radius: .875rem; padding: .75rem .875rem; font-size: .875rem; font-weight: 700; }
    .wacs-auth-alert-success { border: 1px solid rgb(167 243 208); background: rgb(236 253 245); color: rgb(6 95 70); }
    .wacs-auth-alert-danger { border: 1px solid rgb(254 202 202); background: rgb(254 242 242); color: rgb(153 27 27); }
    .dark .wacs-locale-label { color: rgb(148 163 184); }
    .dark .wacs-locale-options { border-color: rgb(51 65 85); background: rgb(15 23 42); }
    .dark .wacs-locale-option { color: rgb(203 213 225); }
    .dark .wacs-locale-option:hover { background: rgb(30 41 59); color: white; }
    .dark .wacs-locale-option.is-active { background: rgb(59 130 246); color: white; }
    .dark .wacs-auth-divider { color: rgb(148 163 184); }
    .dark .wacs-auth-divider::before, .dark .wacs-auth-divider::after { background: rgb(51 65 85); }
    .dark .wacs-auth-button-google { border-color: rgb(51 65 85); background: rgb(15 23 42); color: rgb(226 232 240); }
    .dark .wacs-auth-icon { background: rgb(30 41 59); color: rgb(147 197 253); }
    .dark .wacs-auth-security-badge { border-color: rgb(30 64 175); background: rgb(15 23 42); color: rgb(147 197 253); }
    .dark .wacs-auth-alert-success { border-color: rgb(5 150 105); background: rgb(6 78 59); color: rgb(209 250 229); }
    .dark .wacs-auth-alert-danger { border-color: rgb(185 28 28); background: rgb(69 10 10); color: rgb(254 226 226); }
</style>
HTML
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn(): string => view('auth.external-login-actions')->render()
            )
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_AFTER,
                fn(): string => view('auth.external-register-actions')->render()
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn(): string => <<<HTML
<script>
    document.documentElement.lang = "{$this->currentLocale()}"
    document.documentElement.setAttribute('translate', 'no')
    document.documentElement.classList.add('notranslate')
    document.body.setAttribute('translate', 'no')
    document.body.classList.add('notranslate')
</script>
HTML
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn(): string => '<div style="padding: 1rem 1.5rem; text-align: center; font-size: 0.8125rem; color: rgb(100 116 139);">&copy; ' . date('Y') . ' VPoint Care. All rights reserved.</div>'
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn(): string => view('components.locale-switcher', ['compact' => true])->render()
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn(): HtmlString => new HtmlString(
                    '<script type="module" src="' . Vite::asset('resources/js/app.js') . '"></script>'
                )
            )
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Red,
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->navigationGroups(NavigationHelper::buildGroups())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->userMenuItems([
                EditOwnProfileAction::make(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    private function currentLocale(): string
    {
        return app()->getLocale();
    }
}

