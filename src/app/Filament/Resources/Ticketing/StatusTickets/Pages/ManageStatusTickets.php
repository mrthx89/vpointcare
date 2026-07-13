<?php

namespace App\Filament\Resources\Ticketing\StatusTickets\Pages;

use App\Filament\Resources\Ticketing\StatusTickets\StatusTicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStatusTickets extends ManageRecords
{
    protected static string $resource = StatusTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
