<?php

namespace App\Filament\Pages;

use App\Services\Waha\WahaSender;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema as FilamentSchema;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class InboxWhatsapp extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

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

    /** @var array<int, array<string, mixed>> */
    public array $historyChats = [];

    /** @var array<int, array<string, mixed>> */
    public array $internalNotes = [];

    public string $newInternalNote = '';

    public ?string $selectedChatId = null;

    public ?array $selectedChat = null;

    public string $replyText = '';

    public ?TemporaryUploadedFile $attachment = null;

    public string $filterText = '';

    public string $filterType = 'keduanya';

    /** Cache ID pengguna agar tidak query DB berulang kali per request. */
    private ?string $cachedPenggunaId = null;

    public function mount(): void
    {
        $this->loadInbox();
    }

    /**
     * Dipanggil oleh Laravel Echo (via JavaScript) ketika Reverb
     * WebSocket menerima event 'WahaInboxUpdated' dari webhook.
     * Ini yang membuat halaman ini real-time seperti SignalR.
     */
    #[On('waha-inbox-updated')]
    public function handleInboxUpdate(): void
    {
        $this->loadInbox();
    }

    public function updatedFilterText(): void
    {
        $this->refreshFilteredInbox();
    }

    public function updatedFilterType(): void
    {
        if (!in_array($this->filterType, ['pribadi', 'grup', 'keduanya'], true)) {
            $this->filterType = 'keduanya';
        }

        $this->refreshFilteredInbox();
    }

    public function form(FilamentSchema $schema): FilamentSchema
    {
        return $schema
            ->components([
                TextInput::make('filterText')
                    ->hiddenLabel()
                    ->placeholder('Filter nama, nomor WA, atau ID WAHA')
                    ->live(debounce: 300),
                Radio::make('filterType')
                    ->hiddenLabel()
                    ->options([
                        'pribadi' => 'Pribadi',
                        'grup' => 'Grup',
                        'keduanya' => 'Keduanya',
                    ])
                    ->inline()
                    ->live(),
            ]);
    }

    private function refreshFilteredInbox(): void
    {
        $this->resetSelectedChat();
        $this->loadInbox();
    }

    public function loadInternalNotes(): void
    {
        if (!$this->selectedChatId) {
            $this->internalNotes = [];
            return;
        }

        $this->internalNotes = DB::table('TChatCatatanInternal')
            ->where('IdChatM', $this->selectedChatId)
            ->orderBy('TglBuat', 'asc')
            ->get()
            ->map(function ($row) {
                // Fetch creator name if table Pengguna exists
                $creatorName = 'Sistem';
                if ($row->DibuatOleh && Schema::hasTable('Pengguna')) {
                    $creatorName = DB::table('Pengguna')->where('Id', $row->DibuatOleh)->value('NamaPengguna') ?? 'Sistem';
                }

                return [
                    'Id' => $row->Id,
                    'IsiCatatan' => $row->IsiCatatan,
                    'TglBuat' => $row->TglBuat,
                    'DibuatOlehNama' => $creatorName,
                ];
            })
            ->all();
    }

    public function saveInternalNote(): void
    {
        if (!$this->selectedChatId || trim($this->newInternalNote) === '') {
            return;
        }

        DB::table('TChatCatatanInternal')->insert([
            'Id' => (string) Str::orderedUuid(),
            'IdChatM' => $this->selectedChatId,
            'IsiCatatan' => trim($this->newInternalNote),
            'DibuatOleh' => $this->currentPenggunaId(),
            'TglBuat' => now(),
        ]);

        $this->newInternalNote = '';
        $this->loadInternalNotes();

        Notification::make()
            ->title('Catatan internal berhasil disimpan.')
            ->success()
            ->send();
    }

    public function loadInbox(): void
    {
        $this->stats = [
            'baru' => (int) DB::table('TChatM')->count(),
            'belum_dibaca' => (int) DB::table('TChatM')->sum('JumlahPesanBelumDibaca'),
            'grup' => (int) DB::table('TChatM')->where('JenisChat', 'Grup')->count(),
            'unknown' => (int) DB::table('TChatM as c')
                ->leftJoin('MGrupWhatsapp as g', 'g.Id', '=', 'c.IdGrupWhatsapp')
                ->whereNull('c.IdInstansi')
                ->whereNull('g.IdInstansi')
                ->count(),
        ];

        $nomorHasIdWaha = Schema::hasColumn('MNomorWhatsapp', 'IdWaha');
        $chatDetailHasFileName = Schema::hasColumn('TChatD', 'NamaFileMedia');
        $chatDetailHasMimeType = Schema::hasColumn('TChatD', 'TipeMime');

        $hasDiambilOleh = Schema::hasColumn('TChatM', 'DiambilOleh');
        $hasWahaProfileColumns = Schema::hasColumn('TChatM', 'UrlFotoProfil');

        $query = DB::table('TChatM as c')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->leftJoin('MCustomer as m', 'm.Id', '=', 'c.IdCustomer')
            ->leftJoin('MNomorWhatsapp as n', 'n.Id', '=', 'c.IdNomorWhatsapp')
            ->leftJoin('MGrupWhatsapp as g', 'g.Id', '=', 'c.IdGrupWhatsapp')
            ->leftJoin('MInstansi as gi', 'gi.Id', '=', 'g.IdInstansi')
            ->leftJoin('MStatusChat as s', 's.Id', '=', 'c.IdStatusChat')
            ->leftJoin('MPengguna as pd', 'pd.Id', '=', 'c.DiambilOleh')
            ->select(
                'c.Id',
                'c.JenisChat',
                'c.NomorWhatsapp',
                'c.NamaKontak',
                'c.NamaGrupWhatsapp',
                $hasWahaProfileColumns ? 'c.IdWahaTerdeteksi' : DB::raw('NULL as IdWahaTerdeteksi'),
                $hasWahaProfileColumns ? 'c.NomorWhatsappTerdeteksi' : DB::raw('NULL as NomorWhatsappTerdeteksi'),
                $hasWahaProfileColumns ? 'c.UrlFotoProfil' : DB::raw('NULL as UrlFotoProfil'),
                $hasWahaProfileColumns ? 'c.TglFotoProfilDiambil' : DB::raw('NULL as TglFotoProfilDiambil'),
                'c.JumlahPesanBelumDibaca',
                'c.TglChatTerakhir',
                'c.AutoReplyAiAktif',
                'c.AiSudahMenyapa',
                'c.TglAutoReplyAiTerakhir',
                $hasDiambilOleh ? 'c.DiambilOleh' : DB::raw('NULL as DiambilOleh'),
                'i.NamaInstansi',
                'gi.NamaInstansi as NamaInstansiGrup',
                'm.NamaCustomer',
                'n.NamaKontak as NamaKontakMaster',
                'n.NomorWhatsapp as NomorWhatsappMaster',
                $nomorHasIdWaha ? 'n.IdWaha as NomorIdWaha' : DB::raw('NULL as NomorIdWaha'),
                'g.NamaGrup as NamaGrupMaster',
                'g.IdGrupWaha',
                'g.NomorGrupWhatsapp',
                's.NamaStatusChat',
                'pd.NamaPengguna as NamaDiambilOleh'
            );

        if ($this->filterType === 'pribadi') {
            $query->where('c.JenisChat', 'Pribadi');
        } elseif ($this->filterType === 'grup') {
            $query->where('c.JenisChat', 'Grup');
        }

        $search = trim($this->filterText);

        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($query) use ($like, $nomorHasIdWaha, $hasWahaProfileColumns): void {
                $query
                    ->where('c.NamaKontak', 'like', $like)
                    ->orWhere('c.NomorWhatsapp', 'like', $like)
                    ->orWhere('c.NamaGrupWhatsapp', 'like', $like)
                    ->orWhere('i.NamaInstansi', 'like', $like)
                    ->orWhere('gi.NamaInstansi', 'like', $like)
                    ->orWhere('m.NamaCustomer', 'like', $like)
                    ->orWhere('n.NamaKontak', 'like', $like)
                    ->orWhere('n.NomorWhatsapp', 'like', $like)
                    ->orWhere('g.NamaGrup', 'like', $like)
                    ->orWhere('g.IdGrupWaha', 'like', $like)
                    ->orWhere('g.NomorGrupWhatsapp', 'like', $like);

                if ($nomorHasIdWaha) {
                    $query->orWhere('n.IdWaha', 'like', $like);
                }

                if ($hasWahaProfileColumns) {
                    $query
                        ->orWhere('c.NomorWhatsappTerdeteksi', 'like', $like)
                        ->orWhere('c.IdWahaTerdeteksi', 'like', $like);
                }
            });
        }

        $statusDitutupId = DB::table('MStatusChat')->where('KodeStatusChat', 'DITUTUP')->value('Id');
        if ($statusDitutupId) {
            $query->where(function ($q) use ($statusDitutupId) {
                $q->where('c.IdStatusChat', '!=', $statusDitutupId)
                    ->orWhereNull('c.IdStatusChat');
            });
        }

        $rows = $query
            ->orderByDesc('c.TglChatTerakhir')
            ->limit(50)
            ->get();

        $this->chatRows = $rows->map(function (object $row) use ($chatDetailHasFileName, $chatDetailHasMimeType): array {
            $lastMessage = DB::table('TChatD')
                ->where('IdChatM', $row->Id)
                ->orderByDesc('TglPesan')
                ->select(
                    'IsiPesan',
                    'JenisPesan',
                    'UrlMedia',
                    $chatDetailHasMimeType ? 'TipeMime' : DB::raw('NULL as TipeMime'),
                    $chatDetailHasFileName ? 'NamaFileMedia' : DB::raw('NULL as NamaFileMedia')
                )
                ->first();

            return $this->formatChatRow($row, $this->messagePreview($lastMessage));
        })->all();

        $selectedExists = $this->selectedChatId
            && collect($this->chatRows)->contains('Id', $this->selectedChatId);

        if (!$selectedExists) {
            $this->selectedChatId = null;
            $this->selectedChat = null;
            $this->messages = [];
        }

        if (!$this->selectedChatId && $this->chatRows) {
            $this->selectChat($this->chatRows[0]['Id']);

            return;
        }

        if ($this->selectedChatId) {
            $this->selectChat($this->selectedChatId);
        }
    }

    private function formatChatRow(object $row, string $lastMessage = '-'): array
    {
        $isGroup = $row->JenisChat === 'Grup';
        $groupName = $row->NamaGrupMaster ?: $row->NamaGrupWhatsapp;
        $groupWahaId = $row->IdGrupWaha ?? null;
        $groupNumber = $row->NomorGrupWhatsapp ?: ($groupWahaId ?: $row->NomorWhatsapp);
        $contactName = $row->NamaKontakMaster ?: $row->NamaKontak;
        $mappingIdentifiers = $this->mappingIdentifiers((object) [
            'Id' => $row->Id,
            'NomorWhatsapp' => $isGroup ? $groupWahaId : ($row->NomorWhatsappMaster ?: $row->NomorWhatsapp),
            'NamaGrupWhatsapp' => $groupName,
        ]);
        $contactNumber = $row->NomorWhatsappMaster
            ?: ($row->NomorWhatsappTerdeteksi ?? null)
            ?: $this->displayPhoneNumber($mappingIdentifiers)
            ?: $row->NomorWhatsapp;
        $displayInstansi = $isGroup
            ? ($row->NamaInstansiGrup ?: $row->NamaInstansi)
            : $row->NamaInstansi;
        $detectedWahaId = $isGroup
            ? $groupWahaId
            : (($row->IdWahaTerdeteksi ?? null) ?: $this->firstWahaId($mappingIdentifiers) ?: ($row->NomorIdWaha ?? null));

        return [
            'Id' => $row->Id,
            'JenisChat' => $row->JenisChat,
            'NamaInstansi' => $displayInstansi ?: 'Belum dipetakan',
            'NamaCustomer' => $row->NamaCustomer,
            'NamaKontak' => $contactName ?: '-',
            'NamaGrupWhatsapp' => $groupName,
            'NomorWhatsapp' => $isGroup ? $groupNumber : $contactNumber,
            'NomorWhatsappRaw' => $row->NomorWhatsapp,
            'IdWaha' => $detectedWahaId,
            'FotoProfilUrl' => $row->UrlFotoProfil ?? null,
            'Status' => $row->NamaStatusChat ?: 'Menunggu CS',
            'BelumDibaca' => (int) $row->JumlahPesanBelumDibaca,
            'TglChatTerakhir' => $row->TglChatTerakhir,
            'PesanTerakhir' => $lastMessage,
            'AutoReplyAiAktif' => (bool) $row->AutoReplyAiAktif,
            'AiSudahMenyapa' => (bool) $row->AiSudahMenyapa,
            'TglAutoReplyAiTerakhir' => $row->TglAutoReplyAiTerakhir,
            // Handler info: siapa CS yang sedang menangani chat ini
            'DiambilOleh' => $row->DiambilOleh ?? null,
            'DiambilNamaCS' => $row->NamaDiambilOleh
                ? (mb_strlen($row->NamaDiambilOleh) > 18
                    ? mb_substr($row->NamaDiambilOleh, 0, 15) . '...'
                    : $row->NamaDiambilOleh)
                : null,
            'DiambilOlehSaya' => isset($row->DiambilOleh)
                && $row->DiambilOleh === $this->currentPenggunaId(),
            'MappingIdentifiers' => $mappingIdentifiers,
        ];
    }

    public function loadHistoryChats(): void
    {
        if (!$this->selectedChatId) {
            $this->historyChats = [];
            return;
        }

        $rawChat = DB::table('TChatM')->where('Id', $this->selectedChatId)->first();
        if (!$rawChat) {
            $this->historyChats = [];
            return;
        }

        $conditions = [];
        if ($rawChat->IdCustomer)
            $conditions[] = ['c.IdCustomer', '=', $rawChat->IdCustomer];
        if ($rawChat->IdInstansi)
            $conditions[] = ['c.IdInstansi', '=', $rawChat->IdInstansi];
        if ($rawChat->IdNomorWhatsapp)
            $conditions[] = ['c.IdNomorWhatsapp', '=', $rawChat->IdNomorWhatsapp];
        if ($rawChat->NomorWhatsapp)
            $conditions[] = ['c.NomorWhatsapp', '=', $rawChat->NomorWhatsapp];

        $query = DB::table('TChatM as c')
            ->leftJoin('MStatusChat as s', 's.Id', '=', 'c.IdStatusChat')
            ->where('c.Id', '!=', $this->selectedChatId);

        if (empty($conditions)) {
            $this->historyChats = [];
            return;
        }

        $query->where(function ($q) use ($conditions) {
            foreach ($conditions as $cond) {
                $q->orWhere($cond[0], $cond[1], $cond[2]);
            }
        });

        $this->historyChats = $query->orderByDesc('c.TglChatTerakhir')
            ->select('c.Id', 'c.TglChatTerakhir', 's.NamaStatusChat', 'c.JumlahPesanBelumDibaca')
            ->limit(20)
            ->get()
            ->map(fn($r) => [
                'Id' => $r->Id,
                'TglChatTerakhir' => $r->TglChatTerakhir,
                'NamaStatusChat' => $r->NamaStatusChat ?: 'Selesai',
                'JumlahPesanBelumDibaca' => (int) $r->JumlahPesanBelumDibaca,
            ])
            ->all();
    }

    public function selectChat(string $chatId): void
    {
        $this->selectedChatId = $chatId;
        $this->selectedChat = collect($this->chatRows)->firstWhere('Id', $chatId)
            ?? $this->loadChatHeader($chatId);
        $this->refreshWahaProfileIfNeeded($chatId);
        $this->selectedChat = $this->loadChatHeader($chatId) ?? $this->selectedChat;
        $chatDetailHasFileName = Schema::hasColumn('TChatD', 'NamaFileMedia');
        $chatDetailHasMimeType = Schema::hasColumn('TChatD', 'TipeMime');

        $penggunaHasFotoProfil = Schema::hasColumn('MPengguna', 'FotoProfilPath');

        $this->messages = DB::table('TChatD as d')
            ->leftJoin('MPengguna as p', 'p.Id', '=', 'd.DibalasOleh')
            ->where('d.IdChatM', $chatId)
            ->orderBy('d.TglPesan')
            ->limit(200)
            ->select(
                'd.Id',
                'd.ArahPesan',
                'd.JenisPesan',
                'd.IsiPesan',
                'd.UrlMedia',
                $chatDetailHasFileName ? 'NamaFileMedia' : DB::raw('NULL as NamaFileMedia'),
                $chatDetailHasMimeType ? 'TipeMime' : DB::raw('NULL as TipeMime'),
                'd.PengirimNomorWhatsapp',
                'd.PengirimNamaKontak',
                'd.TglPesan',
                'd.StatusKirim',
                'd.PesanError',
                'd.DihasilkanOlehAi',
                'd.DibalasOleh',
                'p.NamaPengguna as NamaPembalas',
                $penggunaHasFotoProfil ? 'p.FotoProfilPath as FotoProfilPembalasPath' : DB::raw('NULL as FotoProfilPembalasPath')
            )
            ->get()
            ->map(fn(object $row): array => [
                'Id' => $row->Id,
                'ArahPesan' => $row->ArahPesan,
                'JenisPesan' => $row->JenisPesan,
                'IsiPesan' => $row->IsiPesan,
                'UrlMedia' => $row->UrlMedia,
                'NamaFileMedia' => $row->NamaFileMedia,
                'TipeMime' => $row->TipeMime,
                'MediaCategory' => $this->mediaCategory($row->JenisPesan, $row->TipeMime),
                'MediaLabel' => $this->mediaLabel($row->JenisPesan, $row->TipeMime, $row->NamaFileMedia),
                'MediaUrl' => $row->UrlMedia ? route('admin.waha-media.show', ['message' => $row->Id]) : null,
                'PengirimNomorWhatsapp' => $row->PengirimNomorWhatsapp,
                'PengirimNamaKontak' => $row->PengirimNamaKontak,
                'TglPesan' => $row->TglPesan,
                'StatusKirim' => $row->StatusKirim,
                'PesanError' => $row->PesanError,
                'DihasilkanOlehAi' => (bool) ($row->DihasilkanOlehAi ?? false),
                'NamaPembalas' => $row->NamaPembalas,
                'FotoProfilPembalasUrl' => $this->profileUrlFromPath($row->FotoProfilPembalasPath),
                'SenderName' => $this->messageSenderName($row),
                'SenderAvatarUrl' => $this->messageSenderAvatarUrl($row),
            ])
            ->all();

        $this->loadHistoryChats();
        $this->loadInternalNotes();

        // Auto-claim chat jika belum ada yang menangani
        if (Schema::hasColumn('TChatM', 'DiambilOleh')) {
            $current = DB::table('TChatM')->where('Id', $chatId)->value('DiambilOleh');
            if (!$current) {
                $myId = $this->currentPenggunaId();
                if ($myId) {
                    DB::table('TChatM')->where('Id', $chatId)->update([
                        'DiambilOleh' => $myId,
                        'TglDiambil' => now(),
                        'TglEdit' => now(),
                    ]);
                }
            }
        }
    }

    public function toggleAutoReplyAi(): void
    {
        if (!$this->selectedChatId || !$this->selectedChat) {
            return;
        }

        $active = !(bool) ($this->selectedChat['AutoReplyAiAktif'] ?? false);

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

    public function tutupPercakapan(\App\Services\Ai\AiAutoReplyService $aiService): void
    {
        if (!$this->selectedChatId || !$this->selectedChat) {
            return;
        }

        $statusDitutupId = DB::table('MStatusChat')->where('KodeStatusChat', 'DITUTUP')->value('Id');

        if (! $statusDitutupId) {
            Notification::make()
                ->title('Status DITUTUP belum tersedia.')
                ->body('Tambahkan status chat dengan kode DITUTUP terlebih dahulu.')
                ->danger()
                ->send();

            return;
        }

        $updateData = [
            'IdStatusChat' => $statusDitutupId,
            'AutoReplyAiAktif' => false,
            'TglEdit' => now(),
        ];

        if (Schema::hasColumn('TChatM', 'DitutupOleh')) {
            $updateData['DitutupOleh'] = $this->currentPenggunaId();
            $updateData['TglDitutup'] = now();
        }

        DB::table('TChatM')->where('Id', $this->selectedChatId)->update($updateData);

        $aiService->sendClosingMessage($this->selectedChatId);

        $this->loadInbox();

        Notification::make()
            ->title('Percakapan telah ditutup.')
            ->success()
            ->send();
    }

    public function resetSapaanAi(): void
    {
        if (!$this->selectedChatId) {
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
        if (!$this->selectedChatId) {
            return;
        }

        $chat = DB::table('TChatM')->where('Id', $this->selectedChatId)->first();

        if (!$chat) {
            return;
        }

        $mapping = $this->resolveMappingForChat($chat);

        if (!($mapping['IdInstansi'] ?? null)) {
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

    public function refreshProfilWaha(): void
    {
        if (!$this->selectedChatId) {
            return;
        }

        if (!Schema::hasColumn('TChatM', 'UrlFotoProfil')) {
            Notification::make()
                ->title('Kolom profil WAHA belum tersedia.')
                ->warning()
                ->send();

            return;
        }

        $updated = $this->refreshWahaProfile($this->selectedChatId, true);
        $this->loadInbox();

        Notification::make()
            ->title($updated ? 'Profil WAHA diperbarui.' : 'Profil WAHA belum tersedia.')
            ->body($updated ? null : 'WAHA bisa mengembalikan kosong jika foto disembunyikan oleh privasi kontak atau mapping LID belum tersedia.')
            ->{$updated ? 'success' : 'warning'}()
            ->send();
    }

    public function simpanBalasanLokal(): void
    {
        $this->validate([
            'replyText' => ['required', 'string', 'max:4000'],
        ]);

        if (!$this->selectedChatId) {
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

    public function removeAttachment(): void
    {
        $this->attachment = null;
    }

    public function kirimBalasanWaha(WahaSender $wahaSender): void
    {
        $this->validate([
            'replyText' => ['nullable', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file', 'max:51200'],
        ]);

        if (!$this->selectedChatId) {
            return;
        }

        $reply = trim($this->replyText);

        if ($reply === '' && !$this->attachment) {
            Notification::make()
                ->title('Isi pesan atau lampirkan file dulu.')
                ->warning()
                ->send();

            return;
        }

        $chat = DB::table('TChatM as c')
            ->leftJoin('MSesiWhatsapp as s', 's.Id', '=', 'c.IdSesiWhatsapp')
            ->leftJoin('MGrupWhatsapp as g', 'g.Id', '=', 'c.IdGrupWhatsapp')
            ->where('c.Id', $this->selectedChatId)
            ->select('c.*', 's.KodeSesi', 'g.IdGrupWaha')
            ->first();

        if (!$chat) {
            Notification::make()
                ->title('Chat tidak ditemukan.')
                ->danger()
                ->send();

            return;
        }

        if ($this->attachment) {
            $sent = $this->sendAttachmentReply($wahaSender, $chat, $reply);
        } else {
            $sent = [
                'response' => $wahaSender->sendText(
                    $chat->KodeSesi ?: 'default',
                    $this->wahaChatId($chat),
                    $reply,
                    'WAHA_MANUAL_SEND_TEXT'
                ),
                'message' => [
                    'JenisPesan' => 'Teks',
                    'IsiPesan' => $reply,
                ],
            ];
        }

        $success = (bool) ($sent['response']['ok'] ?? false);

        DB::table('TChatD')->insert(array_merge([
            'Id' => (string) Str::orderedUuid(),
            'IdChatM' => $this->selectedChatId,
            'ArahPesan' => 'Keluar',
            'DikirimOlehCustomer' => false,
            'TglPesan' => now(),
            'TglDikirim' => $success ? now() : null,
            'StatusKirim' => $success ? 'Terkirim WAHA' : 'Gagal WAHA',
            'PesanError' => $success ? null : ($sent['response']['error'] ?? 'WAHA gagal mengirim pesan.'),
            'DibalasOleh' => $this->currentPenggunaId(),
            'TglBuat' => now(),
        ], $sent['message']));

        DB::table('TChatM')->where('Id', $this->selectedChatId)->update([
            'TglDibalasTerakhir' => now(),
            'TglChatTerakhir' => now(),
            'JumlahPesanBelumDibaca' => 0,
            'TglEdit' => now(),
        ]);

        $this->replyText = '';
        $this->attachment = null;
        $this->loadInbox();

        Notification::make()
                    ->title($success ? 'Balasan terkirim ke WAHA.' : 'Balasan gagal dikirim ke WAHA.')
                    ->body($success ? null : ($sent['response']['error'] ?? null))
            ->{$success ? 'success' : 'danger'}()
                ->send();
    }

    /**
     * @return array{response: array<string, mixed>, message: array<string, mixed>}
     */
    private function sendAttachmentReply(WahaSender $wahaSender, object $chat, string $caption): array
    {
        $file = $this->attachment;
        $mimeType = $file?->getMimeType() ?: 'application/octet-stream';
        $fileName = $file?->getClientOriginalName() ?: ('lampiran-whatsapp.' . ($file?->extension() ?: 'bin'));
        $realPath = $file?->getRealPath();
        $contents = $realPath ? file_get_contents($realPath) : false;

        if ($contents === false) {
            return [
                'response' => [
                    'ok' => false,
                    'error' => 'File lampiran tidak bisa dibaca.',
                ],
                'message' => $this->outgoingMediaMessage($mimeType, $fileName, $caption, null),
            ];
        }

        [$sendContents, $sendMimeType, $sendFileName] = $this->wahaReadyFile($contents, $mimeType, $fileName);

        $response = $wahaSender->sendMedia(
            $chat->KodeSesi ?: 'default',
            $this->wahaChatId($chat),
            base64_encode($sendContents),
            $sendMimeType,
            $sendFileName,
            $caption !== '' ? $caption : null,
            'WAHA_MANUAL_SEND_MEDIA'
        );

        $storedUrl = null;
        $path = $file?->store('chat-outgoing', 'public');

        if ($path) {
            $storedUrl = Storage::disk('public')->url($path);
        }

        return [
            'response' => $response,
            'message' => $this->outgoingMediaMessage($mimeType, $fileName, $caption, $storedUrl),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function outgoingMediaMessage(string $mimeType, string $fileName, string $caption, ?string $url): array
    {
        $message = [
            'JenisPesan' => $this->outgoingMediaType($mimeType),
            'IsiPesan' => $caption !== '' ? $caption : null,
            'UrlMedia' => $url,
        ];

        if (Schema::hasColumn('TChatD', 'NamaFileMedia')) {
            $message['NamaFileMedia'] = $fileName;
        }

        if (Schema::hasColumn('TChatD', 'TipeMime')) {
            $message['TipeMime'] = $mimeType;
        }

        return $message;
    }

    private function outgoingMediaType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'Gambar';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'Video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'Audio';
        }

        return 'Dokumen';
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function wahaReadyFile(string $contents, string $mimeType, string $fileName): array
    {
        if (!str_starts_with($mimeType, 'image/') || in_array($mimeType, ['image/jpeg', 'image/jpg'], true)) {
            return [$contents, $mimeType, $fileName];
        }

        $jpeg = $this->convertImageToJpeg($contents);

        if ($jpeg === null) {
            return [$contents, $mimeType, $fileName];
        }

        $baseName = pathinfo($fileName, PATHINFO_FILENAME) ?: 'whatsapp-image';

        return [$jpeg, 'image/jpeg', $baseName . '.jpg'];
    }

    private function convertImageToJpeg(string $contents): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($contents);

        if (!$source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $canvas = imagecreatetruecolor($width, $height);

        if (!$canvas) {
            imagedestroy($source);

            return null;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 90);
        $jpeg = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        return is_string($jpeg) && $jpeg !== '' ? $jpeg : null;
    }

    private function loadChatHeader(string $chatId): ?array
    {
        $nomorHasIdWaha = Schema::hasColumn('MNomorWhatsapp', 'IdWaha');
        $hasWahaProfileColumns = Schema::hasColumn('TChatM', 'UrlFotoProfil');
        $hasDiambilOleh = Schema::hasColumn('TChatM', 'DiambilOleh');

        $row = DB::table('TChatM as c')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->leftJoin('MCustomer as m', 'm.Id', '=', 'c.IdCustomer')
            ->leftJoin('MNomorWhatsapp as n', 'n.Id', '=', 'c.IdNomorWhatsapp')
            ->leftJoin('MGrupWhatsapp as g', 'g.Id', '=', 'c.IdGrupWhatsapp')
            ->leftJoin('MInstansi as gi', 'gi.Id', '=', 'g.IdInstansi')
            ->leftJoin('MStatusChat as s', 's.Id', '=', 'c.IdStatusChat')
            ->where('c.Id', $chatId)
            ->select(
                'c.*',
                'i.NamaInstansi',
                'gi.NamaInstansi as NamaInstansiGrup',
                'm.NamaCustomer',
                'n.NamaKontak as NamaKontakMaster',
                'n.NomorWhatsapp as NomorWhatsappMaster',
                $nomorHasIdWaha ? 'n.IdWaha as NomorIdWaha' : DB::raw('NULL as NomorIdWaha'),
                $hasWahaProfileColumns ? 'c.IdWahaTerdeteksi' : DB::raw('NULL as IdWahaTerdeteksi'),
                $hasWahaProfileColumns ? 'c.NomorWhatsappTerdeteksi' : DB::raw('NULL as NomorWhatsappTerdeteksi'),
                $hasWahaProfileColumns ? 'c.UrlFotoProfil' : DB::raw('NULL as UrlFotoProfil'),
                $hasWahaProfileColumns ? 'c.TglFotoProfilDiambil' : DB::raw('NULL as TglFotoProfilDiambil'),
                $hasDiambilOleh ? 'c.DiambilOleh' : DB::raw('NULL as DiambilOleh'),
                DB::raw('NULL as NamaDiambilOleh'),
                'g.NamaGrup as NamaGrupMaster',
                'g.IdGrupWaha',
                'g.NomorGrupWhatsapp',
                's.NamaStatusChat'
            )
            ->first();

        if (!$row) {
            return null;
        }

        return $this->formatChatRow($row);
    }

    private function refreshWahaProfileIfNeeded(string $chatId): void
    {
        if (!Schema::hasColumn('TChatM', 'UrlFotoProfil')) {
            return;
        }

        $lastFetchedAt = DB::table('TChatM')->where('Id', $chatId)->value('TglFotoProfilDiambil');

        if ($lastFetchedAt && Carbon::parse($lastFetchedAt)->gt(now()->subDay())) {
            return;
        }

        $this->refreshWahaProfile($chatId, false);
    }

    private function refreshWahaProfile(string $chatId, bool $forceRefresh): bool
    {
        if (!Schema::hasColumn('TChatM', 'UrlFotoProfil')) {
            return false;
        }

        $chat = DB::table('TChatM as c')
            ->leftJoin('MSesiWhatsapp as s', 's.Id', '=', 'c.IdSesiWhatsapp')
            ->leftJoin('MGrupWhatsapp as g', 'g.Id', '=', 'c.IdGrupWhatsapp')
            ->where('c.Id', $chatId)
            ->select(
                'c.Id',
                'c.JenisChat',
                'c.NomorWhatsapp',
                'c.NamaKontak',
                'c.NamaGrupWhatsapp',
                'c.IdWahaTerdeteksi',
                'c.NomorWhatsappTerdeteksi',
                'c.UrlFotoProfil',
                's.KodeSesi',
                'g.IdGrupWaha'
            )
            ->first();

        if (!$chat) {
            return false;
        }

        $wahaSender = app(WahaSender::class);
        $session = $chat->KodeSesi ?: 'default';
        $identifiers = $this->mappingIdentifiers($chat);
        $contactId = $this->profileContactId($chat, $identifiers);
        $update = [
            'TglFotoProfilDiambil' => now(),
            'TglEdit' => now(),
        ];

        if (!$contactId) {
            DB::table('TChatM')->where('Id', $chatId)->update($update);

            return false;
        }

        $detectedWahaId = $this->firstWahaId(array_merge([$contactId], $identifiers));
        $detectedPhone = $this->displayPhoneNumber(array_merge([$contactId], $identifiers));

        if (str_contains($contactId, '@lid')) {
            $lidResult = $wahaSender->getPhoneNumberByLid($session, $contactId);

            if (($lidResult['phone'] ?? null) && is_string($lidResult['phone'])) {
                $detectedPhone = $lidResult['phone'];
                $contactId = ($lidResult['pn'] ?? null) ?: $detectedPhone . '@c.us';
            }
        }

        $picture = $wahaSender->getContactProfilePictureUrl($session, $contactId, $forceRefresh);

        $update['IdWahaTerdeteksi'] = $detectedWahaId ?: $contactId;
        $update['NomorWhatsappTerdeteksi'] = $detectedPhone;

        if (($picture['ok'] ?? false)) {
            $update['UrlFotoProfil'] = $picture['url'] ?? null;
        }

        DB::table('TChatM')->where('Id', $chatId)->update($update);

        return (bool) (($picture['url'] ?? null) || $detectedPhone);
    }

    /**
     * @param  array<int, string>  $identifiers
     */
    private function profileContactId(object $chat, array $identifiers): ?string
    {
        if (($chat->JenisChat ?? null) === 'Grup') {
            return $chat->IdGrupWaha ?: $this->firstWahaId($identifiers);
        }

        return $this->firstWahaId($identifiers)
            ?: (($chat->NomorWhatsappTerdeteksi ?? null) ? $chat->NomorWhatsappTerdeteksi . '@c.us' : null)
            ?: (($chat->NomorWhatsapp ?? null) && $chat->NomorWhatsapp !== '-' ? $chat->NomorWhatsapp . '@c.us' : null);
    }

    private function resetSelectedChat(): void
    {
        $this->selectedChatId = null;
        $this->selectedChat = null;
        $this->messages = [];
    }

    private function messagePreview(?object $message): string
    {
        if (!$message) {
            return '-';
        }

        $text = trim((string) ($message->IsiPesan ?? ''));

        if ($text !== '') {
            return $text;
        }

        return '[' . $this->mediaLabel($message->JenisPesan ?? null, $message->TipeMime ?? null, $message->NamaFileMedia ?? null) . ']';
    }

    private function messageSenderName(object $message): string
    {
        if ($message->ArahPesan === 'Keluar') {
            return (bool) ($message->DihasilkanOlehAi ?? false)
                ? 'Medina'
                : ((string) ($message->NamaPembalas ?: 'CS'));
        }

        return (string) ($message->PengirimNamaKontak ?: $message->PengirimNomorWhatsapp ?: 'Customer');
    }

    private function messageSenderAvatarUrl(object $message): ?string
    {
        if ($message->ArahPesan !== 'Keluar') {
            return null;
        }

        if ((bool) ($message->DihasilkanOlehAi ?? false)) {
            return asset('images/logo_ai.svg');
        }

        return $this->profileUrlFromPath($message->FotoProfilPembalasPath ?? null);
    }

    private function profileUrlFromPath(?string $path): ?string
    {
        return $path ? route('public-storage.show', ['path' => ltrim($path, '/')]) : null;
    }

    private function mediaCategory(?string $jenisPesan, ?string $mimeType): string
    {
        $type = strtolower((string) $jenisPesan);
        $mime = strtolower((string) $mimeType);

        if (str_starts_with($mime, 'image/') || str_starts_with($type, 'image/') || in_array($type, ['gambar', 'image', 'photo', 'picture', 'stiker', 'sticker'], true)) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/') || str_starts_with($type, 'video/') || $type === 'video') {
            return 'video';
        }

        if (str_starts_with($mime, 'audio/') || str_starts_with($type, 'audio/') || in_array($type, ['audio', 'voice', 'ptt'], true)) {
            return 'audio';
        }

        if ($type !== '' && $type !== 'teks' && $type !== 'text') {
            return 'file';
        }

        return 'text';
    }

    private function mediaLabel(?string $jenisPesan, ?string $mimeType, ?string $fileName): string
    {
        if ($fileName) {
            return $fileName;
        }

        $category = $this->mediaCategory($jenisPesan, $mimeType);

        return match ($category) {
            'image' => 'Gambar',
            'video' => 'Video',
            'audio' => 'Audio',
            'file' => (string) ($jenisPesan ?: 'Dokumen'),
            default => 'Pesan',
        };
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
        if ($this->cachedPenggunaId !== null) {
            return $this->cachedPenggunaId ?: null;
        }

        $email = auth()->user()?->email;

        if (!$email) {
            $this->cachedPenggunaId = '';

            return null;
        }

        $this->cachedPenggunaId = (string) (DB::table('MPengguna')->where('Email', $email)->value('Id') ?? '');

        return $this->cachedPenggunaId ?: null;
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
            ->map(fn(string $id): ?string => preg_replace('/@.+$/', '', $id) ?: $id)
            ->map(fn(string $id): ?string => preg_replace('/[^0-9]/', '', $id) ?: null)
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

        if (!Schema::hasColumn('MNomorWhatsapp', 'IdWaha')) {
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
            ->map(fn(string $id): ?string => preg_replace('/@.+$/', '', $id) ?: $id)
            ->map(fn(string $id): ?string => preg_replace('/[^0-9]/', '', $id) ?: null)
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
            (string) ($chat->IdWahaTerdeteksi ?? ''),
            (string) ($chat->NomorWhatsappTerdeteksi ?? ''),
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

            if (str_contains($id, '@lid') || str_contains($id, '@g.us')) {
                continue;
            }

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
     * @param  array<int, string>  $identifiers
     */
    private function displayPhoneNumber(array $identifiers): ?string
    {
        foreach ($identifiers as $identifier) {
            $identifier = trim($identifier);

            if ($identifier === '' || str_contains($identifier, '@lid') || str_contains($identifier, '@g.us')) {
                continue;
            }

            if (str_contains($identifier, '@') && !str_contains($identifier, '@c.us') && !str_contains($identifier, '@s.whatsapp.net')) {
                continue;
            }

            $number = preg_replace('/@.+$/', '', $identifier) ?: $identifier;
            $number = preg_replace('/:.+$/', '', $number) ?: $number;
            $number = preg_replace('/[^0-9]/', '', $number) ?: null;

            if ($number && strlen($number) >= 8) {
                return $number;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $identifiers
     */
    private function firstWahaId(array $identifiers): ?string
    {
        foreach ($identifiers as $identifier) {
            $identifier = trim($identifier);

            if ($identifier !== '' && preg_match('/@(c\.us|s\.whatsapp\.net|lid|g\.us)$/', $identifier)) {
                return str_ends_with($identifier, '@s.whatsapp.net')
                    ? str_replace('@s.whatsapp.net', '@c.us', $identifier)
                    : $identifier;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function payloadIdentifierStrings(array $payload): array
    {
        $values = [];
        array_walk_recursive($payload, function ($value) use (&$values): void {
            if (!is_string($value)) {
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

        if (!$payloadJson) {
            return null;
        }

        $payload = json_decode((string) $payloadJson, true);

        return is_array($payload) ? $payload : null;
    }

    private function latestIncomingWahaChatId(string $chatId): ?string
    {
        $payload = $this->latestIncomingPayload($chatId);

        if (!$payload) {
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
