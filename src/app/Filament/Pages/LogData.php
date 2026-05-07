<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\FilamentBreadcrumbs;
use App\Support\LocaleFormatter;
use App\Support\NavigationHelper;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LogData extends Page
{
    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return NavigationHelper::iconFor(AccessPermissions::LOG_DATA_VIEW, 'heroicon-o-clipboard-document-list');
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::LOG_DATA_VIEW, __('ui.navigation.monitoring'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::LOG_DATA_VIEW, 10);
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'Log Data';
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::LOG_DATA_VIEW, 'Log Data');
    }

    public function getBreadcrumbs(): array
    {
        return FilamentBreadcrumbs::forMenu(AccessPermissions::LOG_DATA_VIEW, 'Log Data');
    }

    protected string $view = 'filament.pages.log-data';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::LOG_DATA_VIEW)
            && NavigationHelper::isActive(AccessPermissions::LOG_DATA_VIEW);
    }

    /** @var array<int, array<string, mixed>> */
    public array $integrationLogs = [];

    /** @var array<int, array<string, mixed>> */
    public array $webhookLogs = [];

    /** @var array<string, mixed> */
    public array $jobStatus = [];

    /** @var array<int, array<string, mixed>> */
    public array $queueRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $pendingJobs = [];

    /** @var array<int, array<string, mixed>> */
    public array $failedJobs = [];

    /** @var array<int, array<string, mixed>> */
    public array $jobBatches = [];

    public function mount(): void
    {
        $this->loadLogs();
    }

    public function loadLogs(): void
    {
        $this->loadJobStatus();

        $this->integrationLogs = DB::table('TLogIntegrasi')
            ->select(
                'Id',
                'KodeIntegrasi',
                'UrlEndpoint',
                'MetodeHttp',
                'RequestJson',
                'ResponseJson',
                'StatusHttp',
                'Berhasil',
                'PesanError',
                'TglRequest',
                'TglResponse'
            )
            ->orderByDesc('TglRequest')
            ->limit(30)
            ->get()
            ->map(fn (object $row): array => [
                'Id' => (string) $row->Id,
                'KodeIntegrasi' => (string) $row->KodeIntegrasi,
                'UrlEndpoint' => (string) $row->UrlEndpoint,
                'MetodeHttp' => (string) $row->MetodeHttp,
                'RequestJson' => $this->shortText($row->RequestJson),
                'ResponseJson' => $this->shortText($row->ResponseJson),
                'StatusHttp' => $row->StatusHttp,
                'Berhasil' => (bool) $row->Berhasil,
                'PesanError' => $this->shortText($row->PesanError),
                'TglRequest' => $row->TglRequest,
                'TglResponse' => $row->TglResponse,
            ])
            ->all();

        $this->webhookLogs = DB::table('TLogWebhookWaha')
            ->select('Id', 'JenisEvent', 'SudahDiproses', 'PesanError', 'TglDiterima', 'TglDiproses')
            ->orderByDesc('TglDiterima')
            ->limit(15)
            ->get()
            ->map(fn (object $row): array => [
                'Id' => (string) $row->Id,
                'JenisEvent' => (string) $row->JenisEvent,
                'SudahDiproses' => (bool) $row->SudahDiproses,
                'PesanError' => $this->shortText($row->PesanError),
                'TglDiterima' => $row->TglDiterima,
                'TglDiproses' => $row->TglDiproses,
            ])
            ->all();
    }

    private function loadJobStatus(): void
    {
        $connection = (string) config('queue.default');
        $retryAfter = (int) config("queue.connections.{$connection}.retry_after", 90);
        $now = now()->timestamp;
        $staleReservedBefore = $now - $retryAfter;

        $this->jobStatus = [
            'connection' => $connection,
            'driver' => (string) config("queue.connections.{$connection}.driver", $connection),
            'defaultQueue' => (string) config("queue.connections.{$connection}.queue", 'default'),
            'retryAfter' => $retryAfter,
            'total' => 0,
            'pending' => 0,
            'reserved' => 0,
            'delayed' => 0,
            'staleReserved' => 0,
            'failed' => 0,
            'activeBatches' => 0,
            'pendingBatchJobs' => 0,
            'failedBatchJobs' => 0,
            'status' => 'healthy',
            'label' => __('ui.pages.log_data.queue_empty'),
            'description' => __('ui.pages.log_data.queue_empty_desc'),
            'oldestWaitingAt' => null,
            'updatedAt' => LocaleFormatter::dateTime(now()),
        ];
        $this->queueRows = [];
        $this->pendingJobs = [];
        $this->failedJobs = [];
        $this->jobBatches = [];

        if (! Schema::hasTable('jobs')) {
            $this->jobStatus['status'] = 'missing';
            $this->jobStatus['label'] = __('ui.pages.log_data.jobs_table_missing');
            $this->jobStatus['description'] = __('ui.pages.log_data.jobs_table_missing_desc');

            return;
        }

        $summary = DB::table('jobs')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN reserved_at IS NULL AND available_at <= ? THEN 1 ELSE 0 END) as pending', [$now])
            ->selectRaw('SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) as reserved')
            ->selectRaw('SUM(CASE WHEN reserved_at IS NULL AND available_at > ? THEN 1 ELSE 0 END) as delayed', [$now])
            ->selectRaw('SUM(CASE WHEN reserved_at IS NOT NULL AND reserved_at < ? THEN 1 ELSE 0 END) as stale_reserved', [$staleReservedBefore])
            ->selectRaw('MIN(CASE WHEN reserved_at IS NULL THEN created_at ELSE NULL END) as oldest_waiting_at')
            ->first();

        $failedCount = Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;
        $batchSummary = $this->batchSummary();

        $this->jobStatus = array_merge($this->jobStatus, [
            'total' => (int) ($summary->total ?? 0),
            'pending' => (int) ($summary->pending ?? 0),
            'reserved' => (int) ($summary->reserved ?? 0),
            'delayed' => (int) ($summary->delayed ?? 0),
            'staleReserved' => (int) ($summary->stale_reserved ?? 0),
            'failed' => $failedCount,
            'activeBatches' => $batchSummary['activeBatches'],
            'pendingBatchJobs' => $batchSummary['pendingBatchJobs'],
            'failedBatchJobs' => $batchSummary['failedBatchJobs'],
            'oldestWaitingAt' => $this->formatUnixTimestamp($summary->oldest_waiting_at ?? null),
        ]);

        $this->applyJobHealthLabel();
        $this->loadQueueRows($now);
        $this->loadPendingJobs($now);
        $this->loadFailedJobs();
        $this->loadJobBatches();
    }

    /**
     * @return array{activeBatches: int, pendingBatchJobs: int, failedBatchJobs: int}
     */
    private function batchSummary(): array
    {
        if (! Schema::hasTable('job_batches')) {
            return [
                'activeBatches' => 0,
                'pendingBatchJobs' => 0,
                'failedBatchJobs' => 0,
            ];
        }

        $summary = DB::table('job_batches')
            ->selectRaw('SUM(CASE WHEN finished_at IS NULL AND cancelled_at IS NULL THEN 1 ELSE 0 END) as active_batches')
            ->selectRaw('SUM(pending_jobs) as pending_batch_jobs')
            ->selectRaw('SUM(failed_jobs) as failed_batch_jobs')
            ->first();

        return [
            'activeBatches' => (int) ($summary->active_batches ?? 0),
            'pendingBatchJobs' => (int) ($summary->pending_batch_jobs ?? 0),
            'failedBatchJobs' => (int) ($summary->failed_batch_jobs ?? 0),
        ];
    }

    private function applyJobHealthLabel(): void
    {
        if (($this->jobStatus['failed'] ?? 0) > 0 || ($this->jobStatus['failedBatchJobs'] ?? 0) > 0) {
            $this->jobStatus['status'] = 'danger';
            $this->jobStatus['label'] = __('ui.pages.log_data.job_failed');
            $this->jobStatus['description'] = __('ui.pages.log_data.job_failed_desc');

            return;
        }

        if (($this->jobStatus['staleReserved'] ?? 0) > 0) {
            $this->jobStatus['status'] = 'danger';
            $this->jobStatus['label'] = __('ui.pages.log_data.job_stale');
            $this->jobStatus['description'] = __('ui.pages.log_data.job_stale_desc');

            return;
        }

        if (($this->jobStatus['pending'] ?? 0) > 0) {
            $this->jobStatus['status'] = 'warning';
            $this->jobStatus['label'] = __('ui.pages.log_data.job_waiting_worker');
            $this->jobStatus['description'] = __('ui.pages.log_data.job_waiting_worker_desc');

            return;
        }

        if (($this->jobStatus['reserved'] ?? 0) > 0 || ($this->jobStatus['activeBatches'] ?? 0) > 0) {
            $this->jobStatus['status'] = 'info';
            $this->jobStatus['label'] = __('ui.pages.log_data.queue_processing');
            $this->jobStatus['description'] = __('ui.pages.log_data.queue_processing_desc');

            return;
        }

        if (($this->jobStatus['delayed'] ?? 0) > 0) {
            $this->jobStatus['status'] = 'info';
            $this->jobStatus['label'] = __('ui.pages.log_data.delayed_job');
            $this->jobStatus['description'] = __('ui.pages.log_data.delayed_job_desc');
        }
    }

    private function loadQueueRows(int $now): void
    {
        $this->queueRows = DB::table('jobs')
            ->select('queue')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN reserved_at IS NULL AND available_at <= ? THEN 1 ELSE 0 END) as pending', [$now])
            ->selectRaw('SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) as reserved')
            ->selectRaw('SUM(CASE WHEN reserved_at IS NULL AND available_at > ? THEN 1 ELSE 0 END) as delayed', [$now])
            ->selectRaw('MIN(created_at) as oldest_created_at')
            ->groupBy('queue')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'queue' => (string) $row->queue,
                'total' => (int) $row->total,
                'pending' => (int) $row->pending,
                'reserved' => (int) $row->reserved,
                'delayed' => (int) $row->delayed,
                'oldestCreatedAt' => $this->formatUnixTimestamp($row->oldest_created_at),
            ])
            ->all();
    }

    private function loadPendingJobs(int $now): void
    {
        $this->pendingJobs = DB::table('jobs')
            ->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at')
            ->orderBy('created_at')
            ->limit(10)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'queue' => (string) $row->queue,
                'name' => $this->jobNameFromPayload($row->payload),
                'attempts' => (int) $row->attempts,
                'state' => $row->reserved_at ? 'reserved' : (((int) $row->available_at > $now) ? 'delayed' : 'pending'),
                'createdAt' => $this->formatUnixTimestamp($row->created_at),
                'availableAt' => $this->formatUnixTimestamp($row->available_at),
                'reservedAt' => $this->formatUnixTimestamp($row->reserved_at),
            ])
            ->all();
    }

    private function loadFailedJobs(): void
    {
        if (! Schema::hasTable('failed_jobs')) {
            return;
        }

        $this->failedJobs = DB::table('failed_jobs')
            ->select('id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at')
            ->orderByDesc('failed_at')
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'uuid' => (string) $row->uuid,
                'connection' => (string) $row->connection,
                'queue' => (string) $row->queue,
                'name' => $this->jobNameFromPayload($row->payload),
                'exception' => $this->shortText($this->firstExceptionLine((string) $row->exception), 240),
                'failedAt' => $row->failed_at,
            ])
            ->all();
    }

    private function loadJobBatches(): void
    {
        if (! Schema::hasTable('job_batches')) {
            return;
        }

        $this->jobBatches = DB::table('job_batches')
            ->select('id', 'name', 'total_jobs', 'pending_jobs', 'failed_jobs', 'cancelled_at', 'created_at', 'finished_at')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (string) $row->id,
                'name' => (string) $row->name,
                'totalJobs' => (int) $row->total_jobs,
                'pendingJobs' => (int) $row->pending_jobs,
                'failedJobs' => (int) $row->failed_jobs,
                'status' => $row->cancelled_at ? 'cancelled' : ($row->finished_at ? 'finished' : 'running'),
                'createdAt' => $this->formatUnixTimestamp($row->created_at),
                'finishedAt' => $this->formatUnixTimestamp($row->finished_at),
            ])
            ->all();
    }

    private function shortText(?string $value, int $limit = 500): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Str::limit($value, $limit);
    }

    private function jobNameFromPayload(?string $payload): string
    {
        if (! $payload) {
            return '-';
        }

        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return '-';
        }

        $name = $decoded['displayName']
            ?? $decoded['data']['commandName']
            ?? $decoded['job']
            ?? null;

        if (! $name) {
            return '-';
        }

        return Str::afterLast((string) $name, '\\');
    }

    private function firstExceptionLine(string $exception): string
    {
        $line = trim(Str::before($exception, "\n"));

        return $line !== '' ? $line : $exception;
    }

    private function formatUnixTimestamp(mixed $timestamp): ?string
    {
        if (! $timestamp) {
            return null;
        }

        return LocaleFormatter::dateTime(Carbon::createFromTimestamp((int) $timestamp));
    }
}
