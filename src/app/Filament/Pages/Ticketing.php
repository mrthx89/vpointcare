<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use Filament\Pages\Page;

class Ticketing extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('ui.navigation.operasional');
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'Ticketing';
    }

    public static function getNavigationLabel(): string
    {
        return 'Ticketing';
    }

    protected string $view = 'filament.pages.ticketing';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::TICKET_VIEW);
    }
}
