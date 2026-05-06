<?php

namespace App\Filament\Resources\Master\AnggotaGrupWhatsapps\Pages;

use App\Filament\Resources\Master\AnggotaGrupWhatsapps\AnggotaGrupWhatsappResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAnggotaGrupWhatsapps extends ManageRecords
{
    protected static string $resource = AnggotaGrupWhatsappResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => AnggotaGrupWhatsappResource::canCreate()),
        ];
    }
}
