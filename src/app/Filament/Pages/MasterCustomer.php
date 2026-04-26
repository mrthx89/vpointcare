<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MasterCustomer extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string | \UnitEnum | null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Customer';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'Master Customer';

    protected string $view = 'filament.pages.master-customer';
}
