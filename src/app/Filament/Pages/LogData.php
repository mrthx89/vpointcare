<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LogData extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring';

    protected static ?string $navigationLabel = 'Log Data';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Log Data';

    protected string $view = 'filament.pages.log-data';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::LOG_DATA_VIEW);
    }

    /** @var array<int, array<string, mixed>> */
    public array $integrationLogs = [];

    /** @var array<int, array<string, mixed>> */
    public array $webhookLogs = [];

    public function mount(): void
    {
        $this->loadLogs();
    }

    public function loadLogs(): void
    {
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

    private function shortText(?string $value, int $limit = 500): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Str::limit($value, $limit);
    }
}
