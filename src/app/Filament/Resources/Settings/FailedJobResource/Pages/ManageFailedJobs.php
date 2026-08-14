<?php

namespace App\Filament\Resources\Settings\FailedJobResource\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Settings\FailedJobResource;
use App\Support\AccessPermissions;
use Filament\Resources\Pages\ManageRecords;

class ManageFailedJobs extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = FailedJobResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::QUEUE_MONITOR_VIEW;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
