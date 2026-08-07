<?php

namespace App\Filament\Resources\Operational\Tasks\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Operational\Tasks\TaskResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageTasks extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = TaskResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::TASK_VIEW;

    protected static string|Width|null $modalWidth = Width::SevenExtraLarge;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}