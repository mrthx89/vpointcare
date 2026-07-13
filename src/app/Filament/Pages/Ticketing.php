<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\FilamentBreadcrumbs;
use App\Support\NavigationHelper;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Ticketing extends Page
{
    /** @var array<string, int> */
    public array $stats = [];

    /** @var array<int, object> */
    public array $recentTickets = [];

    public int $myTickets = 0;

    public function mount(): void
    {
        $finalIds = DB::table('MStatusTicket')->where('StatusFinal', true)->pluck('Id');
        $newId = DB::table('MStatusTicket')->where('KodeStatusTicket', 'BARU')->value('Id');
        $this->stats = [
            'new' => $newId ? DB::table('TTicket')->where('IdStatusTicket', $newId)->count() : 0,
            'progress' => DB::table('TTicket')->whereNotIn('IdStatusTicket', $finalIds)->count(),
            'overdue' => DB::table('TTicket')->whereNotIn('IdStatusTicket', $finalIds)->where('TglTargetSelesai', '<', now())->count(),
            'done' => DB::table('TTicket')->whereIn('IdStatusTicket', $finalIds)->count(),
        ];
        $this->myTickets = DB::table('TTicket')->where('DitugaskanKepada', Auth::id())->whereNotIn('IdStatusTicket', $finalIds)->count();
        $this->recentTickets = DB::table('TTicket as t')->leftJoin('MStatusTicket as s', 's.Id', '=', 't.IdStatusTicket')->leftJoin('MPengguna as p', 'p.Id', '=', 't.DitugaskanKepada')->select('t.Id', 't.NomorTicket', 't.JudulTicket', 't.TglTargetSelesai', 's.NamaStatusTicket', 'p.NamaPengguna')->orderByDesc('t.TglBuat')->limit(10)->get()->all();
    }
    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return NavigationHelper::iconFor(AccessPermissions::TICKET_VIEW, 'heroicon-o-ticket');
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::TICKET_VIEW, __('ui.navigation.operasional'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::TICKET_VIEW, 20);
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'Ticketing';
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::TICKET_VIEW, 'Ticketing');
    }

    public function getBreadcrumbs(): array
    {
        return FilamentBreadcrumbs::forMenu(AccessPermissions::TICKET_VIEW, 'Ticketing');
    }

    protected string $view = 'filament.pages.ticketing';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::TICKET_VIEW)
            && NavigationHelper::isActive(AccessPermissions::TICKET_VIEW);
    }
}
