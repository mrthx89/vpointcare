<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class LogData extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring';

    protected static ?string $navigationLabel = 'Log Data';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Log Data';

    protected string $view = 'filament.pages.log-data';
}
