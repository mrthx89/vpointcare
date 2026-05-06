<?php

namespace App\Filament\Resources\Settings\JobSchedules\Pages;

use App\Filament\Resources\Settings\JobSchedules\JobScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJobSchedule extends EditRecord
{
    protected static string $resource = JobScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
