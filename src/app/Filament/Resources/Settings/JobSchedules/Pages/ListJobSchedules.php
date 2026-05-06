<?php

namespace App\Filament\Resources\Settings\JobSchedules\Pages;

use App\Filament\Resources\Settings\JobSchedules\JobScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJobSchedules extends ListRecords
{
    protected static string $resource = JobScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
