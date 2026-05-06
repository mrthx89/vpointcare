<?php

namespace App\Filament\Resources\Settings\JobSchedules\Pages;

use App\Filament\Resources\Settings\JobSchedules\JobScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobSchedule extends CreateRecord
{
    protected static string $resource = JobScheduleResource::class;
}
