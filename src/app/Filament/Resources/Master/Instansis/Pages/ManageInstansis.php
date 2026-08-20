<?php

namespace App\Filament\Resources\Master\Instansis\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Master\Instansis\InstansiResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInstansis extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = InstansiResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::MENU_MASTER_INSTANSI;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => InstansiResource::canCreate()),
        ];
    }
}
