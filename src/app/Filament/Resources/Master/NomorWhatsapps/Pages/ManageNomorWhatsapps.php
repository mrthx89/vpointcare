<?php

namespace App\Filament\Resources\Master\NomorWhatsapps\Pages;

use App\Filament\Resources\Master\NomorWhatsapps\NomorWhatsappResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageNomorWhatsapps extends ManageRecords
{
    protected static string $resource = NomorWhatsappResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => NomorWhatsappResource::canCreate()),
        ];
    }
}
