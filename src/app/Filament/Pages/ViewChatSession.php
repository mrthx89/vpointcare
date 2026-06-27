<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\FilamentBreadcrumbs;
use App\Support\FilamentAccess;
use App\Support\LocaleFormatter;
use App\Services\Ai\AiKnowledgeLearningService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ViewChatSession extends Page
{
    protected string $view = 'filament.pages.view-chat-session';
    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('ui.pages.view_chat.title');
    }

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::CHAT_HISTORY_VIEW);
    }

    public function getBreadcrumbs(): array
    {
        return FilamentBreadcrumbs::forMenu(AccessPermissions::CHAT_HISTORY_VIEW, __('ui.pages.view_chat.title'));
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
            $this->errorMessage = __('ui.pages.view_chat.session_id_missing');
            return;
        }

        $this->loadSession();
    }

    public function buatDraftKnowledge(AiKnowledgeLearningService $service): void
    {
        abort_unless(FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE), 403);

        $result = $service->createDraftFromChat($this->sessionId, auth()->id());

        if ($result['ok'] ?? false) {
            Notification::make()
                ->title(__('ui.ai_learning.draft_created_title'))
                ->body((string) ($result['title'] ?? $result['message'] ?? __('ui.ai_learning.draft_created_message')))
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('ui.ai_learning.draft_not_created_title'))
            ->body((string) ($result['reason'] ?? __('ui.ai_learning.not_reusable')))
            ->warning()
            ->send();
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
            $this->errorMessage = __('ui.pages.view_chat.session_not_found');
            return;
        }

        $this->session = [
            'Id' => $row->Id,
            'JenisChat' => $row->JenisChat,
            'NomorWhatsapp' => $row->NomorWhatsapp,
            'NamaKontak' => $row->NamaKontak,
            'NamaCustomer' => $row->NamaCustomer,
            'NamaInstansi' => $row->NamaInstansi ?: __('ui.common.not_mapped'),
            'Status' => $row->NamaStatusChat ?: __('ui.common.completed'),
            'TglTerakhir' => LocaleFormatter::dateTime($row->TglChatTerakhir),
            'NamaCS' => $row->NamaCS ?: __('ui.common.not_handled'),
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
                $namaPembuat = __('ui.common.system');
                if ($r->DibuatOleh && $hasPengguna) {
                    $namaPembuat = DB::table('Pengguna')
                        ->where('Id', $r->DibuatOleh)
                        ->value('NamaPengguna') ?? __('ui.common.system');
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

