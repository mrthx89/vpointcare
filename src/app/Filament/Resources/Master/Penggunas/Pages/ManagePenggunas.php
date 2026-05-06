<?php

namespace App\Filament\Resources\Master\Penggunas\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Master\Penggunas\PenggunaResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePenggunas extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = PenggunaResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::USER_VIEW;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => PenggunaResource::canCreate()),
        ];
    }
}
