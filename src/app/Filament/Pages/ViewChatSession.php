<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\LocaleFormatter;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ViewChatSession extends Page
{
    protected string $view = 'filament.pages.view-chat-session';
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::CHAT_HISTORY_VIEW);
    }

    public string $sessionId = '';
    public ?array $session = null;
    public array $messages = [];
    public array $internalNotes = [];
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->sessionId = request()->query('id', '');

        if (!$this->sessionId) {
            $this->errorMessage = 'ID sesi tidak ditemukan.';
            return;
        }

        $this->loadSession();
    }

    private function loadSession(): void
    {
        $row = DB::table('TChat as c')
            ->leftJoin('MStatusChat as s', 's.Id', '=', 'c.IdStatusChat')
            ->leftJoin('MNomorWhatsapp as n', 'n.Id', '=', 'c.IdNomorWhatsapp')
            ->leftJoin('MGrupWhatsapp as g', 'g.Id', '=', 'c.IdGrupWhatsapp')
            ->leftJoin('MCustomer as cu', 'cu.Id', '=', 'c.IdCustomer')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->leftJoin('MPengguna as pd', 'pd.Id', '=', 'c.DiambilOleh')
            ->where('c.Id', $this->sessionId)
            ->select(
                'c.Id',
                'c.JenisChat',
                'c.NomorWhatsapp',
                'c.TglChatTerakhir',
                'c.DiambilOleh',
                's.NamaStatusChat',
                DB::raw('COALESCE(n.NamaKontak, g.NamaGrup, c.NomorWhatsapp) as NamaKontak'),
                DB::raw('COALESCE(cu.NamaCustomer, \'\') as NamaCustomer'),
                DB::raw('COALESCE(i.NamaInstansi, \'\') as NamaInstansi'),
                DB::raw('COALESCE(pd.NamaPengguna, \'\') as NamaCS'),
            )
            ->first();

        if (!$row) {
            $this->errorMessage = 'Sesi chat tidak ditemukan.';
            return;
        }

        $this->session = [
            'Id' => $row->Id,
            'JenisChat' => $row->JenisChat,
            'NomorWhatsapp' => $row->NomorWhatsapp,
            'NamaKontak' => $row->NamaKontak,
            'NamaCustomer' => $row->NamaCustomer,
            'NamaInstansi' => $row->NamaInstansi ?: 'Belum dipetakan',
            'Status' => $row->NamaStatusChat ?: 'Selesai',
            'TglTerakhir' => LocaleFormatter::dateTime($row->TglChatTerakhir),
            'NamaCS' => $row->NamaCS ?: 'Belum ditangani',
        ];

        // Load messages
        $hasMime = Schema::hasColumn('TChatD', 'TipeMime');
        $hasFileName = Schema::hasColumn('TChatD', 'NamaFileMedia');

        $this->messages = DB::table('TChatD')
            ->where('IdChat', $this->sessionId)
            ->orderBy('TglPesan')
            ->limit(500)
            ->select(
                'Id',
                'ArahPesan',
                'JenisPesan',
                'IsiPesan',
                'UrlMedia',
                'PengirimNomorWhatsapp',
                'PengirimNamaKontak',
                'TglPesan',
                'StatusKirim',
                $hasMime ? 'TipeMime' : DB::raw('NULL as TipeMime'),
                $hasFileName ? 'NamaFileMedia' : DB::raw('NULL as NamaFileMedia'),
            )
            ->get()
            ->map(fn(object $r) => [
                'Id' => $r->Id,
                'ArahPesan' => $r->ArahPesan,
                'JenisPesan' => $r->JenisPesan,
                'IsiPesan' => $r->IsiPesan,
                'UrlMedia' => $r->UrlMedia,
                'NamaFileMedia' => $r->NamaFileMedia,
                'PengirimNamaKontak' => $r->PengirimNamaKontak ?: $r->PengirimNomorWhatsapp,
                'TglFormatted' => LocaleFormatter::dateTime($r->TglPesan),
                'StatusKirim' => $r->StatusKirim,
                'IsOutgoing' => $r->ArahPesan === 'Keluar',
            ])
            ->all();

        // Load internal notes - ikuti logika di InboxWhatsapp.php
        $hasPengguna = Schema::hasTable('Pengguna');
        $this->internalNotes = DB::table('TChatDCatatanInternal')
            ->where('IdChat', $this->sessionId)
            ->orderBy('TglBuat')
            ->get()
            ->map(function (object $r) use ($hasPengguna) {
                $namaPembuat = 'Sistem';
                if ($r->DibuatOleh && $hasPengguna) {
                    $namaPembuat = DB::table('Pengguna')
                        ->where('Id', $r->DibuatOleh)
                        ->value('NamaPengguna') ?? 'Sistem';
                }
                return [
                    'IsiCatatan' => $r->IsiCatatan,
                    'NamaPembuat' => $namaPembuat,
                    'TglFormatted' => LocaleFormatter::dateTime($r->TglBuat),
                ];
            })
            ->all();
    }
}
