<?php

namespace App\Filament\Resources\Operational\Tickets\Pages;

use App\Filament\Resources\Operational\Tickets\TicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTickets extends ManageRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
