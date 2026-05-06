<?php

namespace App\Filament\Resources\Settings\HakAksess\Pages;

use App\Filament\Resources\Settings\HakAksess\HakAksesResource;
use Filament\Resources\Pages\ManageRecords;

class ManageHakAksess extends ManageRecords
{
    protected static string $resource = HakAksesResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
