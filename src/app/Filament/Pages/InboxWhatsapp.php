<?php

namespace App\Filament\Pages;

use App\Services\Waha\WahaSender;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
                'c.AutoReplyAiAktif',
                'c.AiSudahMenyapa',
                'c.TglAutoReplyAiTerakhir',
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
                'AutoReplyAiAktif' => (bool) $row->AutoReplyAiAktif,
                'AiSudahMenyapa' => (bool) $row->AiSudahMenyapa,
                'TglAutoReplyAiTerakhir' => $row->TglAutoReplyAiTerakhir,
                'MappingIdentifiers' => $this->mappingIdentifiers((object) [
                    'Id' => $row->Id,
                    'NomorWhatsapp' => $row->NomorWhatsapp,
                    'NamaGrupWhatsapp' => $row->NamaGrupWhatsapp,
                ]),
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
                'PesanError' => $row->PesanError,
                'DihasilkanOlehAi' => (bool) ($row->DihasilkanOlehAi ?? false),
            ])
            ->all();
    }

    public function toggleAutoReplyAi(): void
    {
        if (! $this->selectedChatId || ! $this->selectedChat) {
            return;
        }

        $active = ! (bool) ($this->selectedChat['AutoReplyAiAktif'] ?? false);

        DB::table('TChatM')->where('Id', $this->selectedChatId)->update([
            'AutoReplyAiAktif' => $active,
            'ModeAutoReplyAi' => $active ? 'ChatAktif' : 'Default',
            'TglEdit' => now(),
        ]);

        $this->loadInbox();

        Notification::make()
            ->title($active ? 'Auto reply AI sesi ini aktif.' : 'Auto reply AI sesi ini dimatikan.')
            ->success()
            ->send();
    }

    public function resetSapaanAi(): void
    {
        if (! $this->selectedChatId) {
            return;
        }

        DB::table('TChatM')->where('Id', $this->selectedChatId)->update([
            'AiSudahMenyapa' => false,
            'TglEdit' => now(),
        ]);

        $this->loadInbox();

        Notification::make()
            ->title('Status sapaan AI direset.')
            ->success()
            ->send();
    }

    public function refreshMappingChat(): void
    {
        if (! $this->selectedChatId) {
            return;
        }

        $chat = DB::table('TChatM')->where('Id', $this->selectedChatId)->first();

        if (! $chat) {
            return;
        }

        $mapping = $this->resolveMappingForChat($chat);

        if (! ($mapping['IdInstansi'] ?? null)) {
            $ids = $this->mappingIdentifiers($chat);

            Notification::make()
                ->title('Mapping belum ditemukan.')
                ->body('ID terdeteksi: ' . (implode(', ', array_slice($ids, 0, 8)) ?: '-') . '. Pastikan salah satu ID ini sama dengan master.')
                ->warning()
                ->send();

            return;
        }

        DB::table('TChatM')->where('Id', $this->selectedChatId)->update([
            'IdCustomer' => $mapping['IdCustomer'],
            'IdInstansi' => $mapping['IdInstansi'],
            'IdNomorWhatsapp' => $mapping['IdNomorWhatsapp'],
            'IdGrupWhatsapp' => $mapping['IdGrupWhatsapp'],
            'NamaKontak' => $mapping['NamaKontak'],
            'NamaGrupWhatsapp' => $mapping['NamaGrupWhatsapp'],
            'TglEdit' => now(),
        ]);

        $this->loadInbox();

        Notification::make()
            ->title('Mapping chat berhasil diperbarui.')
            ->success()
            ->send();
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
            'DibalasOleh' => $this->currentPenggunaId(),
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

    public function kirimBalasanWaha(WahaSender $wahaSender): void
    {
        $this->validate([
            'replyText' => ['required', 'string', 'max:4000'],
        ]);

        if (! $this->selectedChatId) {
            return;
        }

        $chat = DB::table('TChatM as c')
            ->leftJoin('MSesiWhatsapp as s', 's.Id', '=', 'c.IdSesiWhatsapp')
            ->leftJoin('MGrupWhatsapp as g', 'g.Id', '=', 'c.IdGrupWhatsapp')
            ->where('c.Id', $this->selectedChatId)
            ->select('c.*', 's.KodeSesi', 'g.IdGrupWaha')
            ->first();

        if (! $chat) {
            Notification::make()
                ->title('Chat tidak ditemukan.')
                ->danger()
                ->send();

            return;
        }

        $reply = $this->replyText;
        $sent = $wahaSender->sendText(
            $chat->KodeSesi ?: 'default',
            $this->wahaChatId($chat),
            $reply,
            'WAHA_MANUAL_SEND_TEXT'
        );

        $success = (bool) ($sent['ok'] ?? false);

        DB::table('TChatD')->insert([
            'Id' => (string) Str::orderedUuid(),
            'IdChatM' => $this->selectedChatId,
            'ArahPesan' => 'Keluar',
            'JenisPesan' => 'Teks',
            'IsiPesan' => $reply,
            'DikirimOlehCustomer' => false,
            'TglPesan' => now(),
            'TglDikirim' => $success ? now() : null,
            'StatusKirim' => $success ? 'Terkirim WAHA' : 'Gagal WAHA',
            'PesanError' => $success ? null : ($sent['error'] ?? 'WAHA gagal mengirim pesan.'),
            'DibalasOleh' => $this->currentPenggunaId(),
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
            ->title($success ? 'Balasan terkirim ke WAHA.' : 'Balasan gagal dikirim ke WAHA.')
            ->body($success ? null : ($sent['error'] ?? null))
            ->{$success ? 'success' : 'danger'}()
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
            'AutoReplyAiAktif' => (bool) ($row->AutoReplyAiAktif ?? false),
            'AiSudahMenyapa' => (bool) ($row->AiSudahMenyapa ?? false),
            'TglAutoReplyAiTerakhir' => $row->TglAutoReplyAiTerakhir ?? null,
            'MappingIdentifiers' => $this->mappingIdentifiers($row),
        ];
    }

    private function wahaChatId(object $chat): string
    {
        if ($chat->JenisChat === 'Grup' && $chat->IdGrupWaha) {
            return $chat->IdGrupWaha;
        }

        $latestIncomingChatId = $this->latestIncomingWahaChatId((string) $chat->Id);

        if ($latestIncomingChatId) {
            return $latestIncomingChatId;
        }

        return $this->normalizeWahaChatId((string) $chat->NomorWhatsapp);
    }

    private function currentPenggunaId(): ?string
    {
        $email = auth()->user()?->email;

        if (! $email) {
            return null;
        }

        return DB::table('MPengguna')->where('Email', $email)->value('Id');
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveMappingForChat(object $chat): array
    {
        $ids = $this->mappingIdentifiers($chat);
        $grup = null;
        $nomor = null;

        if ($chat->JenisChat === 'Grup') {
            $grup = $this->findGrupMapping($ids, $chat);
        } else {
            $nomor = $this->findNomorMapping($ids);
        }

        return [
            'IdCustomer' => $nomor->IdCustomer ?? null,
            'IdInstansi' => $grup->IdInstansi ?? $nomor->IdInstansi ?? null,
            'IdNomorWhatsapp' => $nomor->Id ?? null,
            'IdGrupWhatsapp' => $grup->Id ?? null,
            'NamaKontak' => $nomor->NamaKontak ?? $chat->NamaKontak ?? null,
            'NamaGrupWhatsapp' => $grup->NamaGrup ?? $chat->NamaGrupWhatsapp ?? null,
        ];
    }

    private function findNomorMapping(array $ids): ?object
    {
        $numbers = collect($ids)
            ->map(fn (string $id): ?string => preg_replace('/@.+$/', '', $id) ?: $id)
            ->map(fn (string $id): ?string => preg_replace('/[^0-9]/', '', $id) ?: null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($numbers !== []) {
            $nomor = DB::table('MNomorWhatsapp')
                ->whereIn('NomorWhatsapp', $numbers)
                ->where('NonAktif', false)
                ->first();

            if ($nomor) {
                return $nomor;
            }
        }

        if (! Schema::hasColumn('MNomorWhatsapp', 'IdWaha')) {
            return null;
        }

        return DB::table('MNomorWhatsapp')
            ->whereIn('IdWaha', $ids)
            ->where('NonAktif', false)
            ->first();
    }

    private function findGrupMapping(array $ids, object $chat): ?object
    {
        $grup = DB::table('MGrupWhatsapp')
            ->whereIn('IdGrupWaha', $ids)
            ->where('NonAktif', false)
            ->first();

        if ($grup) {
            return $grup;
        }

        $numbers = collect($ids)
            ->map(fn (string $id): ?string => preg_replace('/@.+$/', '', $id) ?: $id)
            ->map(fn (string $id): ?string => preg_replace('/[^0-9]/', '', $id) ?: null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($numbers !== []) {
            $grup = DB::table('MGrupWhatsapp')
                ->whereIn('IdGrupWaha', $numbers)
                ->where('NonAktif', false)
                ->first();

            if ($grup) {
                return $grup;
            }
        }

        $namaGrup = trim((string) ($chat->NamaGrupWhatsapp ?? ''));

        if ($namaGrup === '') {
            return null;
        }

        return DB::table('MGrupWhatsapp')
            ->where('NamaGrup', $namaGrup)
            ->where('NonAktif', false)
            ->first();
    }

    /**
     * @return array<int, string>
     */
    private function mappingIdentifiers(object $chat): array
    {
        $ids = [
            (string) ($chat->NomorWhatsapp ?? ''),
            (string) ($chat->NamaGrupWhatsapp ?? ''),
        ];

        $payload = $this->latestIncomingPayload((string) $chat->Id);

        if ($payload) {
            foreach ([
                'chatId',
                'from',
                'from.id',
                'id.remote',
                'id._serialized',
                '_data.id._serialized',
                '_data.id.remote',
                '_data.Info.Chat',
                '_data.chatId',
                'key.remoteJid',
                'chat.id',
                'chat.id._serialized',
                'to',
                'to.id',
                'groupId',
                'group.id',
                'participant',
                'author',
                'sender.id',
                '_data.author',
            ] as $key) {
                $value = Arr::get($payload, $key);

                if (is_string($value) && $value !== '') {
                    $ids[] = $value;
                }
            }

            foreach ($this->payloadIdentifierStrings($payload) as $value) {
                $ids[] = $value;
            }
        }

        $expanded = [];

        foreach ($ids as $id) {
            $id = trim($id);

            if ($id === '' || $id === '-') {
                continue;
            }

            $expanded[] = $id;
            $number = preg_replace('/@.+$/', '', $id) ?: $id;
            $number = preg_replace('/:.+$/', '', $number) ?: $number;
            $number = preg_replace('/[^0-9]/', '', $number) ?: null;

            if ($number) {
                $expanded[] = $number;
                $expanded[] = $number . '@c.us';
                $expanded[] = $number . '@s.whatsapp.net';
                $expanded[] = $number . '@lid';
            }
        }

        return array_values(array_unique($expanded));
    }

    /**
     * @return array<int, string>
     */
    private function payloadIdentifierStrings(array $payload): array
    {
        $values = [];
        array_walk_recursive($payload, function ($value) use (&$values): void {
            if (! is_string($value)) {
                return;
            }

            if (preg_match_all('/[0-9A-Za-z_.:-]+@(g\.us|c\.us|s\.whatsapp\.net|lid)/', $value, $matches)) {
                foreach ($matches[0] as $match) {
                    $values[] = $match;
                }
            }
        });

        return array_values(array_unique($values));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function latestIncomingPayload(string $chatId): ?array
    {
        $payloadJson = DB::table('TChatD')
            ->where('IdChatM', $chatId)
            ->where('ArahPesan', 'Masuk')
            ->whereNotNull('PayloadJson')
            ->orderByDesc('TglPesan')
            ->value('PayloadJson');

        if (! $payloadJson) {
            return null;
        }

        $payload = json_decode((string) $payloadJson, true);

        return is_array($payload) ? $payload : null;
    }

    private function latestIncomingWahaChatId(string $chatId): ?string
    {
        $payload = $this->latestIncomingPayload($chatId);

        if (! $payload) {
            return null;
        }

        foreach ([
            'chatId',
            'from',
            'from.id',
            '_data.id.remote',
            '_data.Info.Chat',
            'key.remoteJid',
        ] as $key) {
            $value = Arr::get($payload, $key);

            if (is_string($value) && $value !== '') {
                return $this->normalizeWahaChatId($value);
            }
        }

        return null;
    }

    private function normalizeWahaChatId(string $chatIdOrNumber): string
    {
        if (str_contains($chatIdOrNumber, '@')) {
            return str_ends_with($chatIdOrNumber, '@s.whatsapp.net')
                ? str_replace('@s.whatsapp.net', '@c.us', $chatIdOrNumber)
                : $chatIdOrNumber;
        }

        $number = preg_replace('/[^0-9]/', '', $chatIdOrNumber) ?: $chatIdOrNumber;

        return $number . '@c.us';
    }
}
