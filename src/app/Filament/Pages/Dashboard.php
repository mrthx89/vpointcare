<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\FilamentBreadcrumbs;
use App\Support\LocaleFormatter;
use App\Support\NavigationHelper;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static bool $isDiscovered = false;

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('ui.pages.dashboard.navigation_label');
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::DASHBOARD_VIEW, __('ui.pages.dashboard.navigation_label'));
    }

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return NavigationHelper::iconFor(AccessPermissions::DASHBOARD_VIEW, 'heroicon-o-home');
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::DASHBOARD_VIEW, 1);
    }

    public function getBreadcrumbs(): array
    {
        return FilamentBreadcrumbs::forMenu(AccessPermissions::DASHBOARD_VIEW, __('ui.pages.dashboard.navigation_label'));
    }

    protected string $view = 'filament.pages.dashboard';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::DASHBOARD_VIEW)
            && NavigationHelper::isActive(AccessPermissions::DASHBOARD_VIEW);
    }

    public ?string $startDate = null;

    public ?string $endDate = null;

    /** @var array<string, mixed> */
    public array $summary = [];

    /** @var array<int, array<string, mixed>> */
    public array $teamRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $dailyRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $topClients = [];

    /** @var array<string, mixed> */
    public array $satisfaction = [];

    public string $lastUpdated = '';

    public function mount(): void
    {
        if ($this->syncDatesFromFilters()) {
            $this->loadDashboard();

            return;
        }

        $this->startDate = now()->toDateString();
        $this->endDate = now()->toDateString();
        $this->filters['date_range'] = $this->formatDateRangeValue($this->startDate, $this->endDate);

        $this->loadDashboard();
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')
                    ->schema([
                        DateRangePicker::make('date_range')
                            ->label(__('ui.pages.dashboard.period_filter'))
                            ->format(LocaleFormatter::dateInputFormat())
                            ->rangeSeparator(' to ')
                            ->showDropdowns()
                            ->autoApply(),
                    ])
                    ->columns(1)
                    ->compact(),
            ]);
    }

    public function setQuickRange(string $range): void
    {
        match ($range) {
            'today' => [$this->startDate, $this->endDate] = [now()->toDateString(), now()->toDateString()],
            '7d' => [$this->startDate, $this->endDate] = [now()->subDays(6)->toDateString(), now()->toDateString()],
            '30d' => [$this->startDate, $this->endDate] = [now()->subDays(29)->toDateString(), now()->toDateString()],
            'month' => [$this->startDate, $this->endDate] = [now()->startOfMonth()->toDateString(), now()->toDateString()],
            default => null,
        };

        $this->filters['date_range'] = $this->formatDateRangeValue($this->startDate, $this->endDate);

        $this->loadDashboard();
    }

    public function loadDashboard(): void
    {
        $this->syncDatesFromFilters();

        [$start, $end] = $this->periodBounds();

        $messageStats = DB::table('TChatD')
            ->whereBetween('TglPesan', [$start, $end])
            ->selectRaw("
                SUM(CASE WHEN ArahPesan = 'Masuk' THEN 1 ELSE 0 END) as IncomingMessages,
                COUNT(DISTINCT CASE WHEN ArahPesan = 'Masuk' THEN IdChat END) as IncomingChats,
                SUM(CASE WHEN ArahPesan = 'Keluar' AND (DihasilkanOlehAi = 0 OR DihasilkanOlehAi IS NULL) THEN 1 ELSE 0 END) as OutgoingCs,
                SUM(CASE WHEN ArahPesan = 'Keluar' AND DihasilkanOlehAi = 1 THEN 1 ELSE 0 END) as OutgoingAi,
                SUM(CASE WHEN ArahPesan = 'Keluar' AND StatusKirim = 'Gagal WAHA' THEN 1 ELSE 0 END) as FailedWaha,
                SUM(CASE WHEN ArahPesan = 'Keluar' AND StatusKirim = 'Terkirim WAHA' THEN 1 ELSE 0 END) as SentWaha
            ")
            ->first();

        $incomingMessages = (int) ($messageStats->IncomingMessages ?? 0);
        $incomingChats = (int) ($messageStats->IncomingChats ?? 0);
        $outgoingCs = (int) ($messageStats->OutgoingCs ?? 0);
        $outgoingAi = (int) ($messageStats->OutgoingAi ?? 0);
        $failedWaha = (int) ($messageStats->FailedWaha ?? 0);
        $sentWaha = (int) ($messageStats->SentWaha ?? 0);
        $unansweredChats = $this->unansweredChats($start, $end);
        $deliveryTotal = $sentWaha + $failedWaha;
        $avgResponseMinutes = $this->averageResponseMinutes($start, $end);
        $mappedChats = $this->mappedChats($start, $end);
        $periodChats = DB::table('TChat')
            ->whereBetween('TglChatTerakhir', [$start, $end])
            ->count();

        $statusDitutupId = DB::table('MStatusChat')->where('KodeStatusChat', 'DITUTUP')->value('Id');
        $activeChats = DB::table('TChat')
            ->whereNotNull('DiambilOleh')
            ->where(function ($query) use ($statusDitutupId): void {
                if ($statusDitutupId) {
                    $query->where('IdStatusChat', '!=', $statusDitutupId)
                          ->orWhereNull('IdStatusChat');
                }
            })
            ->count();

        $closedChats = DB::table('TChat')
            ->whereNotNull('DiambilOleh')
            ->where('IdStatusChat', $statusDitutupId)
            ->whereBetween('TglChatTerakhir', [$start, $end])
            ->count();

        $this->summary = [
            'incoming_messages' => $incomingMessages,
            'incoming_chats' => $incomingChats,
            'outgoing_cs' => $outgoingCs,
            'outgoing_ai' => $outgoingAi,
            'unanswered_chats' => $unansweredChats,
            'unread_messages' => (int) DB::table('TChat')->sum('JumlahPesanBelumDibaca'),
            'failed_waha' => $failedWaha,
            'sent_waha' => $sentWaha,
            'tickets_created' => (int) DB::table('TTicket')->whereBetween('TglBuat', [$start, $end])->count(),
            'avg_response_minutes' => $avgResponseMinutes,
            'period_chats' => $periodChats,
            'mapped_chats' => $mappedChats,
            'active_chats' => $activeChats,
            'closed_chats' => $closedChats,
        ];

        $this->teamRows = $this->teamPerformance($start, $end);
        $this->dailyRows = $this->dailyTrend($start, $end);
        $this->topClients = $this->topClients($start, $end);
        $this->satisfaction = $this->satisfactionIndex($incomingChats, $unansweredChats, $deliveryTotal, $sentWaha, $avgResponseMinutes, $periodChats, $mappedChats);
        $this->lastUpdated = LocaleFormatter::dateTime(now());
    }

    private function syncDatesFromFilters(): bool
    {
        $range = data_get($this->filters, 'date_range');

        if (! is_string($range) || trim($range) === '') {
            return false;
        }

        [$startDate, $endDate] = $this->parseDateRangeValue($range);

        if (! $startDate || ! $endDate) {
            return false;
        }

        $this->startDate = $startDate;
        $this->endDate = $endDate;

        return true;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function parseDateRangeValue(string $range): array
    {
        $value = trim($range);

        foreach ([' to ', ' - ', ' s/d '] as $separator) {
            if (! str_contains($value, $separator)) {
                continue;
            }

            [$rawStart, $rawEnd] = array_map('trim', explode($separator, $value, 2));

            return [
                $this->parseSingleFilterDate($rawStart),
                $this->parseSingleFilterDate($rawEnd),
            ];
        }

        $singleDate = $this->parseSingleFilterDate($value);

        return [$singleDate, $singleDate];
    }

    private function parseSingleFilterDate(string $value): ?string
    {
        foreach ([LocaleFormatter::dateInputFormat(), 'd-m-Y', 'm-d-Y', 'Y-m-d', 'd/m/Y', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function formatDateRangeValue(?string $startDate, ?string $endDate): ?string
    {
        if (! $startDate || ! $endDate) {
            return null;
        }

        $format = LocaleFormatter::dateInputFormat();

        return Carbon::parse($startDate)->format($format) . ' to ' . Carbon::parse($endDate)->format($format);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodBounds(): array
    {
        $start = Carbon::parse($this->startDate ?: now()->subDays(6)->toDateString())->startOfDay();
        $end = Carbon::parse($this->endDate ?: now()->toDateString())->endOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function unansweredChats(Carbon $start, Carbon $end): int
    {
        $latestIncoming = DB::table('TChatD')
            ->select('IdChat', DB::raw('MAX(TglPesan) as TglPesanTerakhirMasuk'))
            ->where('ArahPesan', 'Masuk')
            ->whereBetween('TglPesan', [$start, $end])
            ->groupBy('IdChat');

        $latestCsReply = DB::table('TChatD')
            ->select('IdChat', DB::raw('MAX(TglPesan) as TglPesanTerakhirCs'))
            ->where('ArahPesan', 'Keluar')
            ->where(function ($query): void {
                $query->whereNull('DihasilkanOlehAi')
                    ->orWhere('DihasilkanOlehAi', false);
            })
            ->groupBy('IdChat');

        return (int) DB::table('TChat as c')
            ->joinSub($latestIncoming, 'masuk', 'masuk.IdChat', '=', 'c.Id')
            ->leftJoinSub($latestCsReply, 'cs', 'cs.IdChat', '=', 'c.Id')
            ->where(function ($query): void {
                $query->whereNull('cs.TglPesanTerakhirCs')
                    ->orWhereColumn('cs.TglPesanTerakhirCs', '<', 'masuk.TglPesanTerakhirMasuk');
            })
            ->count();
    }

    private function mappedChats(Carbon $start, Carbon $end): int
    {
        return (int) DB::table('TChat')
            ->whereBetween('TglChatTerakhir', [$start, $end])
            ->whereNotNull('IdInstansi')
            ->count();
    }

    private function averageResponseMinutes(Carbon $start, Carbon $end): ?float
    {
        $firstIncoming = DB::table('TChatD')
            ->select('IdChat', DB::raw('MIN(TglPesan) as TglMasuk'))
            ->where('ArahPesan', 'Masuk')
            ->whereBetween('TglPesan', [$start, $end])
            ->groupBy('IdChat');

        $rows = DB::query()
            ->fromSub($firstIncoming, 'masuk')
            ->join('TChatD as keluar', function ($join): void {
                $join->on('keluar.IdChat', '=', 'masuk.IdChat')
                    ->whereRaw("keluar.ArahPesan = 'Keluar'")
                    ->whereColumn('keluar.TglPesan', '>', 'masuk.TglMasuk');
            })
            ->select('masuk.IdChat', 'masuk.TglMasuk', DB::raw('MIN(keluar.TglPesan) as TglKeluar'))
            ->groupBy('masuk.IdChat', 'masuk.TglMasuk')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $seconds = $rows->map(fn (object $row): float => max(0, Carbon::parse($row->TglMasuk)->diffInSeconds(Carbon::parse($row->TglKeluar))))
            ->average();

        return $seconds === null ? null : round($seconds / 60, 1);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function teamPerformance(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('TChatD as d')
            ->leftJoin('MPengguna as p', 'p.Id', '=', 'd.DibalasOleh')
            ->where('d.ArahPesan', 'Keluar')
            ->where('d.DihasilkanOlehAi', false)
            ->whereBetween('d.TglPesan', [$start, $end])
            ->select(
                'd.DibalasOleh',
                'p.NamaPengguna',
                'p.Email',
                DB::raw('COUNT(*) as JumlahBalasan'),
                DB::raw('COUNT(DISTINCT d.IdChat) as JumlahChat'),
                DB::raw("SUM(CASE WHEN d.StatusKirim = 'Terkirim WAHA' THEN 1 ELSE 0 END) as Terkirim"),
                DB::raw("SUM(CASE WHEN d.StatusKirim = 'Gagal WAHA' THEN 1 ELSE 0 END) as Gagal")
            )
            ->groupBy('d.DibalasOleh', 'p.NamaPengguna', 'p.Email')
            ->orderByDesc('JumlahBalasan')
            ->limit(10)
            ->get()
            ->map(fn (object $row): array => [
                'name' => $row->NamaPengguna ?: __('ui.pages.dashboard.unknown_cs'),
                'email' => $row->Email ?: '-',
                'replies' => (int) $row->JumlahBalasan,
                'chats' => (int) $row->JumlahChat,
                'sent' => (int) $row->Terkirim,
                'failed' => (int) $row->Gagal,
                'type' => 'CS',
            ])
            ->all();

        $aiStats = DB::table('TChatD')
            ->where('ArahPesan', 'Keluar')
            ->where('DihasilkanOlehAi', true)
            ->whereBetween('TglPesan', [$start, $end])
            ->selectRaw("COUNT(*) as JumlahBalasan, COUNT(DISTINCT IdChat) as JumlahChat, SUM(CASE WHEN StatusKirim = 'Terkirim WAHA' THEN 1 ELSE 0 END) as Terkirim, SUM(CASE WHEN StatusKirim = 'Gagal WAHA' THEN 1 ELSE 0 END) as Gagal")
            ->first();

        if ((int) ($aiStats->JumlahBalasan ?? 0) > 0) {
            array_unshift($rows, [
                'name' => 'AI Agent',
                'email' => 'auto-reply',
                'replies' => (int) $aiStats->JumlahBalasan,
                'chats' => (int) $aiStats->JumlahChat,
                'sent' => (int) $aiStats->Terkirim,
                'failed' => (int) $aiStats->Gagal,
                'type' => 'AI',
            ]);
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dailyTrend(Carbon $start, Carbon $end): array
    {
        $grouped = DB::table('TChatD')
            ->whereBetween('TglPesan', [$start, $end])
            ->selectRaw("CAST(TglPesan AS date) as Tanggal, SUM(CASE WHEN ArahPesan = 'Masuk' THEN 1 ELSE 0 END) as Masuk, SUM(CASE WHEN ArahPesan = 'Keluar' AND (DihasilkanOlehAi = 0 OR DihasilkanOlehAi IS NULL) THEN 1 ELSE 0 END) as Cs, SUM(CASE WHEN ArahPesan = 'Keluar' AND DihasilkanOlehAi = 1 THEN 1 ELSE 0 END) as Ai")
            ->groupBy(DB::raw('CAST(TglPesan AS date)'))
            ->get()
            ->keyBy(fn (object $row): string => Carbon::parse($row->Tanggal)->toDateString());

        $rows = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last) && count($rows) < 31) {
            $key = $cursor->toDateString();
            $row = $grouped->get($key);

            $rows[] = [
                'date' => LocaleFormatter::shortDate($cursor),
                'incoming' => (int) ($row->Masuk ?? 0),
                'cs' => (int) ($row->Cs ?? 0),
                'ai' => (int) ($row->Ai ?? 0),
            ];

            $cursor->addDay();
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topClients(Carbon $start, Carbon $end): array
    {
        return DB::table('TChatD as d')
            ->join('TChat as c', 'c.Id', '=', 'd.IdChat')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->where('d.ArahPesan', 'Masuk')
            ->whereBetween('d.TglPesan', [$start, $end])
            ->select(
                'i.NamaInstansi',
                DB::raw('COUNT(*) as JumlahPesan'),
                DB::raw('COUNT(DISTINCT d.IdChat) as JumlahChat')
            )
            ->groupBy('i.NamaInstansi')
            ->orderByDesc('JumlahPesan')
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'name' => $row->NamaInstansi ?: __('ui.common.not_mapped'),
                'messages' => (int) $row->JumlahPesan,
                'chats' => (int) $row->JumlahChat,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function satisfactionIndex(int $incomingChats, int $unansweredChats, int $deliveryTotal, int $sentWaha, ?float $avgResponseMinutes, int $periodChats, int $mappedChats): array
    {
        if ($incomingChats === 0) {
            return [
                'score' => null,
                'label' => __('ui.pages.dashboard.no_data_label'),
                'response_rate' => 0,
                'delivery_rate' => 0,
                'speed_score' => 0,
                'mapping_rate' => 0,
            ];
        }

        $responseRate = round((($incomingChats - $unansweredChats) / max(1, $incomingChats)) * 100);
        $deliveryRate = $deliveryTotal > 0 ? round(($sentWaha / $deliveryTotal) * 100) : 100;
        $mappingRate = $periodChats > 0 ? round(($mappedChats / $periodChats) * 100) : 100;
        $speedScore = match (true) {
            $avgResponseMinutes === null => 50,
            $avgResponseMinutes <= 5 => 100,
            $avgResponseMinutes <= 15 => 85,
            $avgResponseMinutes <= 60 => 65,
            $avgResponseMinutes <= 240 => 45,
            default => 25,
        };

        $score = (int) round(($responseRate * 0.4) + ($deliveryRate * 0.2) + ($speedScore * 0.25) + ($mappingRate * 0.15));

        return [
            'score' => $score,
            'label' => match (true) {
                $score >= 85 => __('ui.pages.dashboard.excellent'),
                $score >= 70 => __('ui.pages.dashboard.good'),
                $score >= 55 => __('ui.pages.dashboard.needs_attention'),
                default => __('ui.pages.dashboard.critical'),
            },
            'response_rate' => $responseRate,
            'delivery_rate' => $deliveryRate,
            'speed_score' => $speedScore,
            'mapping_rate' => $mappingRate,
        ];
    }
}
