<?php

namespace App\Filament\Resources\Master\NomorWhatsapps\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Master\NomorWhatsapps\NomorWhatsappResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageNomorWhatsapps extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = NomorWhatsappResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::MENU_MASTER_NOMOR_WHATSAPP;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => NomorWhatsappResource::canCreate()),
        ];
    }
}
