<?php

namespace App\Filament\Resources\Settings\FailedJobResource\Pages;

use App\Filament\Resources\Settings\FailedJobResource;
use Filament\Resources\Pages\ManageRecords;

class ManageFailedJobs extends ManageRecords
{
    protected static string $resource = FailedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
