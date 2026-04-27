<?php

namespace App\Filament\Resources\Master\GrupWhatsapps\Pages;

use App\Filament\Resources\Master\GrupWhatsapps\GrupWhatsappResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGrupWhatsapps extends ManageRecords
{
    protected static string $resource = GrupWhatsappResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
