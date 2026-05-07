<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\FilamentBreadcrumbs;
use App\Support\NavigationHelper;
use Filament\Pages\Page;

class Ticketing extends Page
{
    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return NavigationHelper::iconFor(AccessPermissions::TICKET_VIEW, 'heroicon-o-ticket');
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::TICKET_VIEW, __('ui.navigation.operasional'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::TICKET_VIEW, 20);
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'Ticketing';
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::TICKET_VIEW, 'Ticketing');
    }

    public function getBreadcrumbs(): array
    {
        return FilamentBreadcrumbs::forMenu(AccessPermissions::TICKET_VIEW, 'Ticketing');
    }

    protected string $view = 'filament.pages.ticketing';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::TICKET_VIEW)
            && NavigationHelper::isActive(AccessPermissions::TICKET_VIEW);
    }
}
