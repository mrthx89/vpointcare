<?php

namespace App\Filament\Resources\Settings\JobScheduleResource\Pages;

use App\Filament\Resources\Settings\JobScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageJobSchedules extends ManageRecords
{
    protected static string $resource = JobScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
