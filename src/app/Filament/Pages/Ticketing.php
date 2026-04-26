<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Ticketing extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-ticket';

    protected static string | \UnitEnum | null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Ticketing';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Ticketing';

    protected string $view = 'filament.pages.ticketing';
}
