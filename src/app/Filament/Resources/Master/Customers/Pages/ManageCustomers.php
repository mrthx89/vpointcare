<?php

namespace App\Filament\Resources\Master\Customers\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Master\Customers\CustomerResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomers extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = CustomerResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::MENU_MASTER_CUSTOMER;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => CustomerResource::canCreate()),
        ];
    }
}
