<?php

namespace App\Filament\Resources\Master\GrupWhatsapps\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Master\GrupWhatsapps\GrupWhatsappResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGrupWhatsapps extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = GrupWhatsappResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::MENU_MASTER_GRUP_WHATSAPP;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => GrupWhatsappResource::canCreate()),
        ];
    }
}
