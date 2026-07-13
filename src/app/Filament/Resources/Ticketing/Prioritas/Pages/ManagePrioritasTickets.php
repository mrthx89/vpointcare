<?php

namespace App\Filament\Resources\Ticketing\Prioritas\Pages;

use App\Filament\Resources\Ticketing\Prioritas\PrioritasTicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePrioritasTickets extends ManageRecords
{
    protected static string $resource = PrioritasTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
