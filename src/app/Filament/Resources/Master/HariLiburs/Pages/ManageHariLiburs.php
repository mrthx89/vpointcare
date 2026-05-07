<?php

namespace App\Filament\Resources\Master\HariLiburs\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Master\HariLiburs\HariLiburResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHariLiburs extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = HariLiburResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::HOLIDAY_VIEW;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => HariLiburResource::canCreate()),
        ];
    }
}
