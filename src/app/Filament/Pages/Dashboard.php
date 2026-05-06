<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\LocaleFormatter;
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
        return __('ui.pages.dashboard.navigation_label');
    }

    protected string $view = 'filament.pages.dashboard';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::DASHBOARD_VIEW);
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

        $messageRows = DB::table('TChatD')
            ->whereBetween('TglPesan', [$start, $end])
            ->select('IdChat', 'ArahPesan', 'DihasilkanOlehAi', 'StatusKirim', 'TglPesan')
            ->orderBy('TglPesan')
            ->get();

        $incomingRows = $messageRows->where('ArahPesan', 'Masuk');
        $outgoingRows = $messageRows->where('ArahPesan', 'Keluar');
        $aiRows = $outgoingRows->where('DihasilkanOlehAi', true);
        $csRows = $outgoingRows->where('DihasilkanOlehAi', false);

        $incomingChats = $incomingRows->pluck('IdChat')->unique()->count();
        $unansweredChats = $this->unansweredChats($start, $end);
        $failedWaha = $outgoingRows->where('StatusKirim', 'Gagal WAHA')->count();
        $sentWaha = $outgoingRows->where('StatusKirim', 'Terkirim WAHA')->count();
        $deliveryTotal = $sentWaha + $failedWaha;
        $avgResponseMinutes = $this->averageResponseMinutes($messageRows);
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
            'incoming_messages' => $incomingRows->count(),
            'incoming_chats' => $incomingChats,
            'outgoing_cs' => $csRows->count(),
            'outgoing_ai' => $aiRows->count(),
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

        $this->teamRows = $this->teamPerformance($start, $end, $messageRows);
        $this->dailyRows = $this->dailyTrend($start, $end, $messageRows);
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

    private function averageResponseMinutes(Collection $messageRows): ?float
    {
        $durations = [];

        foreach ($messageRows->groupBy('IdChat') as $rows) {
            $orderedRows = $rows->sortBy('TglPesan')->values();

            foreach ($orderedRows as $index => $row) {
                if ($row->ArahPesan !== 'Masuk') {
                    continue;
                }

                $incomingAt = Carbon::parse($row->TglPesan);
                $nextOutgoing = $orderedRows
                    ->slice($index + 1)
                    ->first(fn (object $candidate): bool => $candidate->ArahPesan === 'Keluar');

                if (! $nextOutgoing) {
                    continue;
                }

                $durations[] = max(0, $incomingAt->diffInSeconds(Carbon::parse($nextOutgoing->TglPesan)) / 60);
            }
        }

        if (! $durations) {
            return null;
        }

        return round(array_sum($durations) / count($durations), 1);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function teamPerformance(Carbon $start, Carbon $end, Collection $messageRows): array
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

        $aiReplies = $messageRows
            ->where('ArahPesan', 'Keluar')
            ->where('DihasilkanOlehAi', true);

        if ($aiReplies->isNotEmpty()) {
            array_unshift($rows, [
                'name' => 'AI Agent',
                'email' => 'auto-reply',
                'replies' => $aiReplies->count(),
                'chats' => $aiReplies->pluck('IdChat')->unique()->count(),
                'sent' => $aiReplies->where('StatusKirim', 'Terkirim WAHA')->count(),
                'failed' => $aiReplies->where('StatusKirim', 'Gagal WAHA')->count(),
                'type' => 'AI',
            ]);
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dailyTrend(Carbon $start, Carbon $end, Collection $messageRows): array
    {
        $rows = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $dayRows = $messageRows->filter(fn (object $row): bool => Carbon::parse($row->TglPesan)->toDateString() === $key);

            $rows[] = [
                'date' => LocaleFormatter::shortDate($cursor),
                'incoming' => $dayRows->where('ArahPesan', 'Masuk')->count(),
                'cs' => $dayRows->where('ArahPesan', 'Keluar')->where('DihasilkanOlehAi', false)->count(),
                'ai' => $dayRows->where('ArahPesan', 'Keluar')->where('DihasilkanOlehAi', true)->count(),
            ];

            $cursor->addDay();

            if (count($rows) >= 31) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topClients(Carbon $start, Carbon $end): array
    {
        $unmappedLabel = __('ui.common.not_mapped');

        return DB::table('TChatD as d')
            ->join('TChat as c', 'c.Id', '=', 'd.IdChat')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->where('d.ArahPesan', 'Masuk')
            ->whereBetween('d.TglPesan', [$start, $end])
            ->select(
                DB::raw("COALESCE(i.NamaInstansi, ?) as NamaInstansi"),
                DB::raw('COUNT(*) as JumlahPesan'),
                DB::raw('COUNT(DISTINCT d.IdChat) as JumlahChat')
            )
            ->addBinding($unmappedLabel, 'select')
            ->groupBy(DB::raw('COALESCE(i.NamaInstansi, ?)'))
            ->addBinding($unmappedLabel, 'groupBy')
            ->orderByDesc('JumlahPesan')
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'name' => (string) $row->NamaInstansi,
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
