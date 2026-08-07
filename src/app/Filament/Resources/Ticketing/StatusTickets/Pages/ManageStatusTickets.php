<?php

namespace App\Filament\Resources\Ticketing\StatusTickets\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Ticketing\StatusTickets\StatusTicketResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStatusTickets extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = StatusTicketResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::TICKET_MANAGE;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}