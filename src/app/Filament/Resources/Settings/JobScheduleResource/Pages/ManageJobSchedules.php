<?php

namespace App\Filament\Resources\Settings\JobScheduleResource\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Settings\JobScheduleResource;
use App\Support\AccessPermissions;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageJobSchedules extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = JobScheduleResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::JOB_SCHEDULE_VIEW;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
