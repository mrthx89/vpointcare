<?php

namespace App\Filament\Resources\Ticketing\Kategoris\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Ticketing\Kategoris\KategoriTicketResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageKategoriTickets extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = KategoriTicketResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::TICKET_VIEW;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
