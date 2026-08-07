<?php

namespace App\Filament\Resources\Ticketing\StatusTasks\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Ticketing\StatusTasks\StatusTaskResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStatusTasks extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = StatusTaskResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::TASK_MANAGE;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}