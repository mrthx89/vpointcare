<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Resources\Operational\Tickets\Pages\ManageTickets;
use App\Filament\Resources\Operational\Tickets\TicketResource;
use App\Filament\Resources\Ticketing\Kategoris\KategoriTicketResource;
use App\Filament\Resources\Ticketing\Kategoris\Pages\ManageKategoriTickets;
use App\Filament\Resources\Ticketing\Prioritas\Pages\ManagePrioritasTickets;
use App\Filament\Resources\Ticketing\Prioritas\PrioritasTicketResource;
use App\Filament\Resources\Ticketing\StatusTickets\Pages\ManageStatusTickets;
use App\Filament\Resources\Ticketing\StatusTickets\StatusTicketResource;
use App\Models\Master\Pengguna;
use App\Support\AccessPermissions;
use App\Support\NavigationHelper;
use Filament\Panel;
use Tests\TestCase;

class TicketingBreadcrumbTest extends TestCase
{
    public function test_ticketing_pages_build_localized_breadcrumbs_without_duplicate_ticket_label(): void
    {
        foreach ([
            'id' => [
                [ManageTickets::class, '/admin/operational/tickets', ['Operasional', 'Ticketing', 'Ticket']],
                [ManageStatusTickets::class, '/admin/ticketing/status-tickets', ['Operasional', 'Ticketing', 'Status Ticket']],
                [ManagePrioritasTickets::class, '/admin/ticketing/prioritas/prioritas-tickets', ['Operasional', 'Ticketing', 'Prioritas']],
                [ManageKategoriTickets::class, '/admin/ticketing/kategoris/kategori-tickets', ['Operasional', 'Ticketing', 'Kategori']],
            ],
            'en' => [
                [ManageTickets::class, '/admin/operational/tickets', ['Operational', 'Ticketing', 'Tickets']],
                [ManageStatusTickets::class, '/admin/ticketing/status-tickets', ['Operational', 'Ticketing', 'Ticket Status']],
                [ManagePrioritasTickets::class, '/admin/ticketing/prioritas/prioritas-tickets', ['Operational', 'Ticketing', 'Priority']],
                [ManageKategoriTickets::class, '/admin/ticketing/kategoris/kategori-tickets', ['Operational', 'Ticketing', 'Category']],
            ],
        ] as $locale => $expectations) {
            app()->setLocale($locale);
            NavigationHelper::flush();

            foreach ($expectations as [$pageClass, $url, $breadcrumbs]) {
                self::assertSame($url, $pageClass::getResource()::getUrl(isAbsolute: false, panel: 'admin'));
                self::assertSame($breadcrumbs, app($pageClass)->getBreadcrumbs());
                self::assertCount(count(array_unique($breadcrumbs)), $breadcrumbs);
            }
        }
    }

    public function test_ticket_resources_preserve_view_and_manage_access_boundaries(): void
    {
        $this->actingAs($this->agent([]));

        foreach ([TicketResource::class, StatusTicketResource::class, PrioritasTicketResource::class, KategoriTicketResource::class] as $resource) {
            self::assertFalse($resource::canViewAny());
        }

        $this->actingAs($this->agent([AccessPermissions::TICKET_VIEW]));

        self::assertTrue(TicketResource::canViewAny());
        self::assertFalse(StatusTicketResource::canViewAny());
        self::assertFalse(PrioritasTicketResource::canViewAny());
        self::assertFalse(KategoriTicketResource::canViewAny());

        $this->actingAs($this->agent([AccessPermissions::TICKET_MANAGE]));

        self::assertFalse(TicketResource::canViewAny());
        self::assertTrue(StatusTicketResource::canViewAny());
        self::assertTrue(PrioritasTicketResource::canViewAny());
        self::assertTrue(KategoriTicketResource::canViewAny());
    }

    /** @param array<int, string> $permissions */
    private function agent(array $permissions): Pengguna
    {
        $agent = new class extends Pengguna
        {
            /** @var array<int, string> */
            public array $testPermissions = [];

            public function canAccessPanel(Panel $panel): bool
            {
                return true;
            }

            public function hasPermissionCode(string $permission): bool
            {
                return in_array($permission, $this->testPermissions, true);
            }
        };

        $agent->testPermissions = $permissions;

        return $agent;
    }
}
