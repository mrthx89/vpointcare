<?php

namespace App\Filament\Resources\Master\Instansis\Pages;

use App\Filament\Resources\Master\Instansis\InstansiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInstansis extends ManageRecords
{
    protected static string $resource = InstansiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
