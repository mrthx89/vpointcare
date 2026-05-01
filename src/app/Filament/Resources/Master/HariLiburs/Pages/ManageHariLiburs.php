<?php

namespace App\Filament\Resources\Master\HariLiburs\Pages;

use App\Filament\Resources\Master\HariLiburs\HariLiburResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHariLiburs extends ManageRecords
{
    protected static string $resource = HariLiburResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
