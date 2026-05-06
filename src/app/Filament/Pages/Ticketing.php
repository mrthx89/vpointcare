<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use Filament\Pages\Page;

class Ticketing extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-ticket';

    protected static string | \UnitEnum | null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Ticketing';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Ticketing';

    protected string $view = 'filament.pages.ticketing';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::TICKET_VIEW);
    }
}
