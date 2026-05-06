<?php

namespace App\Providers\Filament;

use App\Filament\Actions\EditOwnProfileAction;
use App\Filament\Auth\Login;
use App\Filament\Auth\Register;
use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->brandLogo(fn () => new HtmlString(
                '<div class="vpoint-brand"><img src="' . asset('images/logo_primary.svg') . '" alt="VPoint Care"><span>VPoint Care</span></div>'
            ))
            ->darkModeBrandLogo(fn () => new HtmlString(
                '<div class="vpoint-brand vpoint-brand-dark"><img src="' . asset('images/logo_secondary.svg') . '" alt="VPoint Care"><span>VPoint Care</span></div>'
            ))
            ->brandLogoHeight('2.25rem')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->collapsedSidebarWidth('4.75rem')
            ->favicon(asset('images/logo_primary.svg'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn (): string => '<meta name="google" content="notranslate"><meta name="robots" content="notranslate">'
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => <<<'HTML'
<script>
    document.documentElement.setAttribute('translate', 'no')
    document.documentElement.classList.add('notranslate')
    document.body.setAttribute('translate', 'no')
    document.body.classList.add('notranslate')
</script>
HTML
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): string => '<div style="padding: 1rem 1.5rem; text-align: center; font-size: 0.8125rem; color: rgb(100 116 139);">&copy; ' . date('Y') . ' VPoint Care. All rights reserved.</div>'
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): HtmlString => new HtmlString(
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
}
