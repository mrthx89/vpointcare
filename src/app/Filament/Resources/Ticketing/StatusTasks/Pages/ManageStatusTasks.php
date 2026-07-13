<?php

namespace App\Filament\Resources\Ticketing\StatusTasks\Pages;

use App\Filament\Resources\Ticketing\StatusTasks\StatusTaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStatusTasks extends ManageRecords
{
    protected static string $resource = StatusTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
