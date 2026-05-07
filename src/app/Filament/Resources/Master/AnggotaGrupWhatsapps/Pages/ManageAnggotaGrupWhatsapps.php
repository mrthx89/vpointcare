<?php

namespace App\Filament\Resources\Master\AnggotaGrupWhatsapps\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Master\AnggotaGrupWhatsapps\AnggotaGrupWhatsappResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAnggotaGrupWhatsapps extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = AnggotaGrupWhatsappResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::MENU_MASTER_ANGGOTA_GRUP;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => AnggotaGrupWhatsappResource::canCreate()),
        ];
    }
}
