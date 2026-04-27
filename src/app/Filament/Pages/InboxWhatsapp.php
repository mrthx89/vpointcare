<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InboxWhatsapp extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string | \UnitEnum | null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Inbox WhatsApp';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Inbox WhatsApp';

    protected string $view = 'filament.pages.inbox-whatsapp';

    /** @var array<string, int> */
    public array $stats = [];

    /** @var array<int, array<string, mixed>> */
    public array $chatRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $messages = [];

    public ?string $selectedChatId = null;

    public ?array $selectedChat = null;

    public string $replyText = '';

    public function mount(): void
    {
        $this->loadInbox();
    }

    public function loadInbox(): void
    {
        $this->stats = [
            'baru' => (int) DB::table('TChatM')->count(),
            'belum_dibaca' => (int) DB::table('TChatM')->sum('JumlahPesanBelumDibaca'),
            'grup' => (int) DB::table('TChatM')->where('JenisChat', 'Grup')->count(),
            'unknown' => (int) DB::table('TChatM')->whereNull('IdInstansi')->count(),
        ];

        $rows = DB::table('TChatM as c')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->leftJoin('MCustomer as m', 'm.Id', '=', 'c.IdCustomer')
            ->leftJoin('MStatusChat as s', 's.Id', '=', 'c.IdStatusChat')
            ->select(
                'c.Id',
                'c.JenisChat',
                'c.NomorWhatsapp',
                'c.NamaKontak',
                'c.NamaGrupWhatsapp',
                'c.JumlahPesanBelumDibaca',
                'c.TglChatTerakhir',
                'i.NamaInstansi',
                'm.NamaCustomer',
                's.NamaStatusChat'
            )
            ->orderByDesc('c.TglChatTerakhir')
            ->limit(50)
            ->get();

        $this->chatRows = $rows->map(function (object $row): array {
            $lastMessage = DB::table('TChatD')
                ->where('IdChatM', $row->Id)
                ->orderByDesc('TglPesan')
                ->value('IsiPesan');

            return [
                'Id' => $row->Id,
                'JenisChat' => $row->JenisChat,
                'NamaInstansi' => $row->NamaInstansi ?: 'Belum dipetakan',
                'NamaCustomer' => $row->NamaCustomer,
                'NamaKontak' => $row->NamaKontak ?: '-',
                'NamaGrupWhatsapp' => $row->NamaGrupWhatsapp,
                'NomorWhatsapp' => $row->NomorWhatsapp,
                'Status' => $row->NamaStatusChat ?: 'Menunggu CS',
                'BelumDibaca' => (int) $row->JumlahPesanBelumDibaca,
                'TglChatTerakhir' => $row->TglChatTerakhir,
                'PesanTerakhir' => $lastMessage ?: '-',
            ];
        })->all();

        if (! $this->selectedChatId && $this->chatRows) {
            $this->selectChat($this->chatRows[0]['Id']);
            return;
        }

        if ($this->selectedChatId) {
            $this->selectChat($this->selectedChatId);
        }
    }

    public function selectChat(string $chatId): void
    {
        $this->selectedChatId = $chatId;
        $this->selectedChat = collect($this->chatRows)->firstWhere('Id', $chatId)
            ?? $this->loadChatHeader($chatId);

        $this->messages = DB::table('TChatD')
            ->where('IdChatM', $chatId)
            ->orderBy('TglPesan')
            ->limit(200)
            ->get()
            ->map(fn (object $row): array => [
                'Id' => $row->Id,
                'ArahPesan' => $row->ArahPesan,
                'IsiPesan' => $row->IsiPesan,
                'PengirimNomorWhatsapp' => $row->PengirimNomorWhatsapp,
                'PengirimNamaKontak' => $row->PengirimNamaKontak,
                'TglPesan' => $row->TglPesan,
                'StatusKirim' => $row->StatusKirim,
            ])
            ->all();
    }

    public function simpanBalasanLokal(): void
    {
        $this->validate([
            'replyText' => ['required', 'string', 'max:4000'],
        ]);

        if (! $this->selectedChatId) {
            return;
        }

        DB::table('TChatD')->insert([
            'Id' => (string) Str::orderedUuid(),
            'IdChatM' => $this->selectedChatId,
            'ArahPesan' => 'Keluar',
            'JenisPesan' => 'Teks',
            'IsiPesan' => $this->replyText,
            'DikirimOlehCustomer' => false,
            'TglPesan' => now(),
            'TglDikirim' => now(),
            'StatusKirim' => 'Draft Lokal',
            'TglBuat' => now(),
        ]);

        DB::table('TChatM')->where('Id', $this->selectedChatId)->update([
            'TglDibalasTerakhir' => now(),
            'TglChatTerakhir' => now(),
            'JumlahPesanBelumDibaca' => 0,
            'TglEdit' => now(),
        ]);

        $this->replyText = '';
        $this->loadInbox();

        Notification::make()
            ->title('Balasan tersimpan sebagai draft lokal.')
            ->body('Pengiriman ke WAHA akan disambungkan pada tahap berikutnya.')
            ->success()
            ->send();
    }

    private function loadChatHeader(string $chatId): ?array
    {
        $row = DB::table('TChatM as c')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->leftJoin('MCustomer as m', 'm.Id', '=', 'c.IdCustomer')
            ->leftJoin('MStatusChat as s', 's.Id', '=', 'c.IdStatusChat')
            ->where('c.Id', $chatId)
            ->select('c.*', 'i.NamaInstansi', 'm.NamaCustomer', 's.NamaStatusChat')
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'Id' => $row->Id,
            'JenisChat' => $row->JenisChat,
            'NamaInstansi' => $row->NamaInstansi ?: 'Belum dipetakan',
            'NamaCustomer' => $row->NamaCustomer,
            'NamaKontak' => $row->NamaKontak ?: '-',
            'NamaGrupWhatsapp' => $row->NamaGrupWhatsapp,
            'NomorWhatsapp' => $row->NomorWhatsapp,
            'Status' => $row->NamaStatusChat ?: 'Menunggu CS',
            'BelumDibaca' => (int) $row->JumlahPesanBelumDibaca,
            'TglChatTerakhir' => $row->TglChatTerakhir,
            'PesanTerakhir' => '-',
        ];
    }
}
