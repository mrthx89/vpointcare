<?php

namespace App\Filament\Resources\Operational\Tickets\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Operational\Tickets\TicketResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageTickets extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = TicketResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::TICKET_VIEW;

    protected static string|Width|null $modalWidth = Width::SevenExtraLarge;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}