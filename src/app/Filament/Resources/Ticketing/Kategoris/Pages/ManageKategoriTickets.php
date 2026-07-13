<?php

namespace App\Filament\Resources\Ticketing\Kategoris\Pages;

use App\Filament\Resources\Ticketing\Kategoris\KategoriTicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageKategoriTickets extends ManageRecords
{
    protected static string $resource = KategoriTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
