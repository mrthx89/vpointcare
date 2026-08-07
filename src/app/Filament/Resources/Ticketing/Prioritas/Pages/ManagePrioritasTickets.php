<?php

namespace App\Filament\Resources\Ticketing\Prioritas\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Ticketing\Prioritas\PrioritasTicketResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePrioritasTickets extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = PrioritasTicketResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::TICKET_MANAGE;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}