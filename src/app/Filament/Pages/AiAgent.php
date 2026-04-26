<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AiAgent extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';

    protected static string | \UnitEnum | null $navigationGroup = 'Asisten';

    protected static ?string $navigationLabel = 'AI Agent';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'AI Agent';

    protected string $view = 'filament.pages.ai-agent';
}
