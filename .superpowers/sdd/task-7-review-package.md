# Review Package: Task 7 Final Re-review

## Base
HEAD f31f8c45628ac25d14149b2b8c02b03521b0fe99

## Status
Uncommitted changes only. No commit made per user instruction.

## Stat
 src/app/Filament/Pages/InboxWhatsapp.php | 246 ++++++++++++++++++++++++++-----
 1 file changed, 208 insertions(+), 38 deletions(-)

## Diff
diff --git a/src/app/Filament/Pages/InboxWhatsapp.php b/src/app/Filament/Pages/InboxWhatsapp.php
index 736f170..3981ddd 100644
--- a/src/app/Filament/Pages/InboxWhatsapp.php
+++ b/src/app/Filament/Pages/InboxWhatsapp.php
@@ -4,60 +4,63 @@
 
 use App\Models\Master\Pengguna;
 use App\Services\Ai\AiAutoReplyService;
 use App\Services\Ai\AiKnowledgeLearningService;
 use App\Services\Chat\ChatInitiationService;
 use App\Services\Waha\WahaSender;
 use App\Support\AccessPermissions;
 use App\Support\FilamentAccess;
 use App\Support\FilamentBreadcrumbs;
 use App\Support\NavigationHelper;
+use App\Support\WahaChatHelper;
+use App\Support\WahaMediaPayload;
 use Filament\Forms\Components\Radio;
 use Filament\Forms\Components\TextInput;
 use Filament\Forms\Concerns\InteractsWithForms;
 use Filament\Forms\Contracts\HasForms;
 use Filament\Notifications\Notification;
 use Filament\Pages\Page;
 use Filament\Schemas\Schema as FilamentSchema;
+use Illuminate\Contracts\Support\Htmlable;
 use Illuminate\Support\Arr;
 use Illuminate\Support\Carbon;
 use Illuminate\Support\Facades\Cache;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Facades\Schema;
 use Illuminate\Support\Facades\Storage;
 use Illuminate\Support\Str;
 use Illuminate\Validation\ValidationException;
 use Livewire\Attributes\On;
 use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
 use Livewire\WithFileUploads;
 
 class InboxWhatsapp extends Page implements HasForms
 {
     use InteractsWithForms;
     use WithFileUploads;
 
-    public static function getNavigationIcon(): string | \BackedEnum | null
+    public static function getNavigationIcon(): string|\BackedEnum|null
     {
         return NavigationHelper::iconFor(AccessPermissions::INBOX_VIEW, 'heroicon-o-chat-bubble-left-right');
     }
 
     public static function getNavigationGroup(): ?string
     {
         return NavigationHelper::groupFor(AccessPermissions::INBOX_VIEW, __('ui.navigation.operasional'));
     }
 
     public static function getNavigationSort(): ?int
     {
         return NavigationHelper::sortFor(AccessPermissions::INBOX_VIEW, 10);
     }
 
-    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
+    public function getTitle(): string|Htmlable
     {
         return 'Inbox WhatsApp';
     }
 
     public static function getNavigationLabel(): string
     {
         return NavigationHelper::labelFor(AccessPermissions::INBOX_VIEW, 'Inbox WhatsApp');
     }
 
     public function getBreadcrumbs(): array
@@ -107,20 +110,22 @@ public function canManageInbox(): bool
     public ?array $selectedChat = null;
 
     public string $replyText = '';
 
     public ?TemporaryUploadedFile $attachment = null;
 
     public string $filterText = '';
 
     public string $filterType = 'keduanya';
 
+    public string $identityDisplayMode = 'whatsapp';
+
     public string $startChatContactSearch = '';
 
     public ?string $startChatNomorWhatsappId = null;
 
     public string $startChatManualNumber = '';
 
     public string $startChatManualName = '';
 
     public ?string $startChatSessionId = null;
 
@@ -501,42 +506,73 @@ public function loadInbox(): void
             $this->selectChat($this->chatRows[0]['Id']);
 
             return;
         }
 
         if ($this->selectedChatId) {
             $this->selectChat($this->selectedChatId);
         }
     }
 
+    public function updatedIdentityDisplayMode(): void
+    {
+        if (! in_array($this->identityDisplayMode, ['whatsapp', 'internal'], true)) {
+            $this->identityDisplayMode = 'whatsapp';
+        }
+    }
+
     private function formatChatRow(object $row, string $lastMessage = '-'): array
     {
         $isGroup = $row->JenisChat === 'Grup';
+        $payload = $this->latestIncomingPayload((string) $row->Id);
         $groupName = $row->NamaGrupMaster ?: $row->NamaGrupWhatsapp;
         $groupWahaId = $row->IdGrupWaha ?? null;
         $groupNumber = $row->NomorGrupWhatsapp ?: ($groupWahaId ?: $row->NomorWhatsapp);
         $contactName = $row->NamaKontakMaster ?: $row->NamaKontak;
         $mappingIdentifiers = $this->mappingIdentifiers((object) [
             'Id' => $row->Id,
+            'Payload' => $payload,
+            'JenisChat' => $row->JenisChat,
             'NomorWhatsapp' => $isGroup ? $groupWahaId : ($row->NomorWhatsappMaster ?: $row->NomorWhatsapp),
             'NamaGrupWhatsapp' => $groupName,
+            'IdWahaTerdeteksi' => $row->IdWahaTerdeteksi ?? null,
+            'NomorWhatsappTerdeteksi' => $row->NomorWhatsappTerdeteksi ?? null,
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
+        $rawGroupId = $isGroup ? $this->payloadGroupId($payload) : null;
+        $rawGroupId ??= $isGroup && str_ends_with((string) $row->NomorWhatsapp, '@g.us') ? $row->NomorWhatsapp : null;
+        $rawGroupId ??= $isGroup && str_ends_with((string) ($row->IdWahaTerdeteksi ?? ''), '@g.us') ? $row->IdWahaTerdeteksi : null;
+        $rawGroupId ??= $isGroup && str_ends_with((string) ($row->NomorWhatsappTerdeteksi ?? ''), '@g.us') ? $row->NomorWhatsappTerdeteksi : null;
+        $rawGroupName = $isGroup ? $this->payloadGroupName($payload) ?: $row->NamaGrupWhatsapp : null;
+        $rawChatId = $isGroup
+            ? $rawGroupId
+            : ($this->payloadPersonalChatId($payload) ?: WahaChatHelper::normalizeChatId((string) $row->NomorWhatsapp));
+        $rawContactName = $isGroup ? null : ($row->NamaKontak ?: null);
+        $rawContactNumber = $isGroup ? null : ($row->NomorWhatsapp ?: null);
+        $mappedInstansi = $displayInstansi ?: null;
+        $mappedContactName = $isGroup ? null : ($row->NamaKontakMaster ?: $row->NamaCustomer ?: null);
+        $mappedContactNumber = $isGroup ? null : ($row->NomorWhatsappMaster ?: null);
+        $mappedGroupName = $isGroup ? ($row->NamaGrupMaster ?: null) : null;
+        $mappedGroupId = $isGroup ? $this->groupJid($row->IdGrupWaha ?: $row->NomorGrupWhatsapp ?: null) : null;
+        $whatsappPrimaryName = $isGroup ? ($rawGroupName ?: $rawGroupId) : ($rawContactName ?: $rawContactNumber);
+        $internalPrimaryName = $isGroup
+            ? ($mappedGroupName ?: $rawGroupName ?: $rawGroupId)
+            : ($mappedContactName ?: $rawContactName ?: $mappedContactNumber ?: $rawContactNumber);
 
         return [
             'Id' => $row->Id,
             'JenisChat' => $row->JenisChat,
             'NamaInstansi' => $displayInstansi ?: __('ui.common.not_mapped'),
             'NamaCustomer' => $row->NamaCustomer,
             'NamaKontak' => $contactName ?: '-',
             'NamaGrupWhatsapp' => $groupName,
             'NomorWhatsapp' => $isGroup ? $groupNumber : $contactNumber,
             'NomorWhatsappRaw' => $row->NomorWhatsapp,
@@ -554,20 +590,40 @@ private function formatChatRow(object $row, string $lastMessage = '-'): array
             // Handler info: siapa CS yang sedang menangani chat ini
             'DiambilOleh' => $row->DiambilOleh ?? null,
             'DiambilNamaCS' => $row->NamaDiambilOleh
                 ? (mb_strlen($row->NamaDiambilOleh) > 18
                     ? mb_substr($row->NamaDiambilOleh, 0, 15).'...'
                     : $row->NamaDiambilOleh)
                 : null,
             'DiambilOlehSaya' => isset($row->DiambilOleh)
                 && $row->DiambilOleh === $this->currentPenggunaId(),
             'MappingIdentifiers' => $mappingIdentifiers,
+            'Identity' => [
+                'whatsapp' => [
+                    'PrimaryName' => $whatsappPrimaryName,
+                    'Instansi' => null,
+                    'ContactName' => $rawContactName,
+                    'ContactNumber' => $rawContactNumber,
+                    'GroupName' => $rawGroupName,
+                    'GroupId' => $rawGroupId,
+                    'ChatId' => $rawChatId,
+                ],
+                'internal' => [
+                    'PrimaryName' => $internalPrimaryName,
+                    'Instansi' => $mappedInstansi,
+                    'ContactName' => $mappedContactName ?: $rawContactName,
+                    'ContactNumber' => $mappedContactNumber ?: $rawContactNumber,
+                    'GroupName' => $mappedGroupName ?: $rawGroupName,
+                    'GroupId' => $mappedGroupId ?: $rawGroupId,
+                    'ChatId' => $isGroup ? ($mappedGroupId ?: $rawGroupId) : ($mappedContactNumber ?: $rawChatId),
+                ],
+            ],
         ];
     }
 
     public function loadHistoryChats(): void
     {
         if (! $this->selectedChatId) {
             $this->historyChats = [];
 
             return;
         }
@@ -638,55 +694,71 @@ public function selectChat(string $chatId): void
             ->leftJoin('MPengguna as p', 'p.Id', '=', 'd.DibalasOleh')
             ->where('d.IdChat', $chatId)
             ->orderBy('d.TglPesan')
             ->limit(200)
             ->select(
                 'd.Id',
                 'd.ArahPesan',
                 'd.JenisPesan',
                 'd.IsiPesan',
                 'd.UrlMedia',
+                'd.PayloadJson',
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
-            ->map(fn (object $row): array => [
-                'Id' => $row->Id,
-                'ArahPesan' => $row->ArahPesan,
-                'JenisPesan' => $row->JenisPesan,
-                'IsiPesan' => $row->IsiPesan,
-                'UrlMedia' => $row->UrlMedia,
-                'NamaFileMedia' => $row->NamaFileMedia,
-                'TipeMime' => $row->TipeMime,
-                'MediaCategory' => $this->mediaCategory($row->JenisPesan, $row->TipeMime),
-                'MediaLabel' => $this->mediaLabel($row->JenisPesan, $row->TipeMime, $row->NamaFileMedia),
-                'MediaUrl' => $row->UrlMedia ? route('admin.waha-media.show', ['message' => $row->Id]) : null,
-                'PengirimNomorWhatsapp' => $row->PengirimNomorWhatsapp,
-                'PengirimNamaKontak' => $row->PengirimNamaKontak,
-                'TglPesan' => $row->TglPesan,
-                'StatusKirim' => $row->StatusKirim,
-                'PesanError' => $row->PesanError,
-                'DihasilkanOlehAi' => (bool) ($row->DihasilkanOlehAi ?? false),
-                'NamaPembalas' => $row->NamaPembalas,
-                'FotoProfilPembalasUrl' => $this->profileUrlFromPath($row->FotoProfilPembalasPath),
-                'SenderName' => $this->messageSenderName($row),
-                'SenderAvatarUrl' => $this->messageSenderAvatarUrl($row),
-            ])
+            ->map(function (object $row): array {
+                $media = blank($row->UrlMedia) || blank($row->TipeMime) || blank($row->NamaFileMedia)
+                    ? WahaMediaPayload::inspectPayload(
+                        $row->PayloadJson,
+                        $row->TipeMime,
+                        $row->NamaFileMedia,
+                        $row->JenisPesan,
+                    )
+                    : null;
+                $hasMedia = filled($row->UrlMedia) || $media !== null;
+                $mediaRoute = $hasMedia ? route('admin.waha-media.show', ['message' => $row->Id]) : null;
+
+                return [
+                    'Id' => $row->Id,
+                    'ArahPesan' => $row->ArahPesan,
+                    'JenisPesan' => $row->JenisPesan,
+                    'IsiPesan' => $row->IsiPesan,
+                    'UrlMedia' => $row->UrlMedia,
+                    'NamaFileMedia' => $row->NamaFileMedia,
+                    'TipeMime' => $row->TipeMime,
+                    'MediaCategory' => $media['category'] ?? $this->mediaCategory($row->JenisPesan, $row->TipeMime),
+                    'MediaLabel' => $media['file_name'] ?? $this->mediaLabel($row->JenisPesan, $row->TipeMime, $row->NamaFileMedia),
+                    'MediaUrl' => $mediaRoute,
+                    'MediaDownloadUrl' => $hasMedia ? route('admin.waha-media.show', ['message' => $row->Id, 'download' => 1]) : null,
+                    'PengirimNomorWhatsapp' => $row->PengirimNomorWhatsapp,
+                    'PengirimNamaKontak' => $row->PengirimNamaKontak,
+                    'TglPesan' => $row->TglPesan,
+                    'StatusKirim' => $row->StatusKirim,
+                    'PesanError' => $row->PesanError,
+                    'DihasilkanOlehAi' => (bool) ($row->DihasilkanOlehAi ?? false),
+                    'NamaPembalas' => $row->NamaPembalas,
+                    'FotoProfilPembalasUrl' => $this->profileUrlFromPath($row->FotoProfilPembalasPath),
+                    'SenderName' => $this->messageSenderName($row),
+                    'SenderNumber' => $this->messageSenderNumber($row),
+                    'SenderAvatarUrl' => $this->messageSenderAvatarUrl($row),
+                ];
+            })
             ->all();
 
         $this->loadHistoryChats();
         $this->loadInternalNotes();
 
         // Auto-claim chat jika belum ada yang menangani
         if (Schema::hasColumn('TChat', 'DiambilOleh')) {
             $current = DB::table('TChat')->where('Id', $chatId)->value('DiambilOleh');
             if (! $current) {
                 $myId = $this->currentPenggunaId();
@@ -744,20 +816,21 @@ public function buatDraftKnowledge(AiKnowledgeLearningService $service): void
 
             return;
         }
 
         Notification::make()
             ->title(__('ui.ai_learning.draft_not_created_title'))
             ->body((string) ($result['reason'] ?? __('ui.ai_learning.not_reusable')))
             ->warning()
             ->send();
     }
+
     public function updateModeKnowledgeAi(string $mode): void
     {
         abort_unless(FilamentAccess::can(AccessPermissions::INBOX_MANAGE), 403);
 
         if (! $this->selectedChatId || ! Schema::hasColumn('TChat', 'ModeKnowledgeAi')) {
             return;
         }
 
         $mode = in_array($mode, ['Ringan', 'AllKnowledge', 'Nonaktif'], true) ? $mode : 'Ringan';
         $limit = $mode === 'AllKnowledge' ? 20 : ($mode === 'Ringan' ? 5 : 0);
@@ -773,20 +846,21 @@ public function updateModeKnowledgeAi(string $mode): void
         Notification::make()
             ->title(__('ui.ai_learning.mode_updated_title'))
             ->body(match ($mode) {
                 'AllKnowledge' => __('ui.ai_learning.mode_updated_all'),
                 'Nonaktif' => __('ui.ai_learning.mode_updated_off'),
                 default => __('ui.ai_learning.mode_updated_light'),
             })
             ->success()
             ->send();
     }
+
     public function tutupPercakapan(AiAutoReplyService $aiService): void
     {
         abort_unless(FilamentAccess::can(AccessPermissions::INBOX_MANAGE), 403);
 
         if (! $this->selectedChatId || ! $this->selectedChat) {
             return;
         }
 
         $statusDitutupId = DB::table('MStatusChat')->where('KodeStatusChat', 'DITUTUP')->value('Id');
 
@@ -1403,21 +1477,51 @@ private function messagePreview(?object $message): string
     }
 
     private function messageSenderName(object $message): string
     {
         if ($message->ArahPesan === 'Keluar') {
             return (bool) ($message->DihasilkanOlehAi ?? false)
                 ? 'Medina'
                 : ((string) ($message->NamaPembalas ?: 'CS'));
         }
 
-        return (string) ($message->PengirimNamaKontak ?: $message->PengirimNomorWhatsapp ?: 'Customer');
+        $payload = $this->decodePayload($message->PayloadJson ?? null);
+
+        return (string) ($message->PengirimNamaKontak
+            ?: Arr::get($payload, 'sender.pushname')
+            ?: Arr::get($payload, 'notifyName')
+            ?: Arr::get($payload, 'pushName')
+            ?: $message->PengirimNomorWhatsapp
+            ?: 'Customer');
+    }
+
+    private function messageSenderNumber(object $message): ?string
+    {
+        if ($message->ArahPesan === 'Keluar') {
+            return null;
+        }
+
+        if ($message->PengirimNomorWhatsapp) {
+            return WahaChatHelper::normalizePhoneNumber((string) $message->PengirimNomorWhatsapp);
+        }
+
+        $payload = $this->decodePayload($message->PayloadJson ?? null);
+
+        foreach (['participant', 'author', 'sender.id', '_data.author'] as $key) {
+            $number = WahaChatHelper::normalizePhoneNumber(Arr::get($payload, $key));
+
+            if ($number) {
+                return $number;
+            }
+        }
+
+        return null;
     }
 
     private function messageSenderAvatarUrl(object $message): ?string
     {
         if ($message->ArahPesan !== 'Keluar') {
             return null;
         }
 
         if ((bool) ($message->DihasilkanOlehAi ?? false)) {
             return asset('images/logo_ai.svg');
@@ -1643,28 +1747,32 @@ private function findGrupMapping(array $ids, object $chat): ?object
             ->where('NamaGrup', $namaGrup)
             ->where('NonAktif', false)
             ->first();
     }
 
     /**
      * @return array<int, string>
      */
     private function mappingIdentifiers(object $chat): array
     {
+        if (($chat->JenisChat ?? null) === 'Grup') {
+            return $this->groupMappingIdentifiers($chat);
+        }
+
         $ids = [
             (string) ($chat->NomorWhatsapp ?? ''),
             (string) ($chat->NamaGrupWhatsapp ?? ''),
             (string) ($chat->IdWahaTerdeteksi ?? ''),
             (string) ($chat->NomorWhatsappTerdeteksi ?? ''),
         ];
 
-        $payload = $this->latestIncomingPayload((string) $chat->Id);
+        $payload = is_array($chat->Payload ?? null) ? $chat->Payload : $this->latestIncomingPayload((string) $chat->Id);
 
         if ($payload) {
             foreach ([
                 'chatId',
                 'from',
                 'from.id',
                 'id.remote',
                 'id._serialized',
                 '_data.id._serialized',
                 '_data.id.remote',
@@ -1717,20 +1825,93 @@ private function mappingIdentifiers(object $chat): array
                 $expanded[] = $number;
                 $expanded[] = $number.'@c.us';
                 $expanded[] = $number.'@s.whatsapp.net';
                 $expanded[] = $number.'@lid';
             }
         }
 
         return array_values(array_unique($expanded));
     }
 
+    /** @return array<int, string> */
+    private function groupMappingIdentifiers(object $chat): array
+    {
+        $ids = [];
+        $payloadGroupId = $this->payloadGroupId(is_array($chat->Payload ?? null) ? $chat->Payload : $this->latestIncomingPayload((string) $chat->Id));
+
+        foreach ([$payloadGroupId, $chat->NomorWhatsapp ?? null, $chat->IdWahaTerdeteksi ?? null, $chat->NomorWhatsappTerdeteksi ?? null] as $id) {
+            if (is_string($id) && str_ends_with(trim($id), '@g.us')) {
+                $ids[] = trim($id);
+            }
+        }
+
+        return array_values(array_unique($ids));
+    }
+
+    private function groupJid(?string $value): ?string
+    {
+        $value = trim((string) $value);
+
+        return str_ends_with($value, '@g.us') ? $value : null;
+    }
+
+    private function payloadGroupId(?array $payload): ?string
+    {
+        foreach (['chatId', 'from', 'from.id', 'id.remote', 'id._serialized', '_data.id._serialized', '_data.id.remote', '_data.Info.Chat', '_data.chatId', 'key.remoteJid', 'chat.id', 'chat.id._serialized', 'groupId', 'group.id'] as $key) {
+            $value = Arr::get($payload ?? [], $key);
+
+            if (is_string($value) && str_ends_with(trim($value), '@g.us')) {
+                return trim($value);
+            }
+        }
+
+        return null;
+    }
+
+    private function payloadGroupName(?array $payload): ?string
+    {
+        foreach (['group.subject', 'group.name', 'chat.name', '_data.chat.name'] as $key) {
+            $value = Arr::get($payload ?? [], $key);
+
+            if (is_string($value) && trim($value) !== '') {
+                return trim($value);
+            }
+        }
+
+        return null;
+    }
+
+    private function payloadPersonalChatId(?array $payload): ?string
+    {
+        foreach (['chatId', 'from', 'from.id', 'id.remote', 'id._serialized', '_data.id._serialized', '_data.id.remote', '_data.Info.Chat', '_data.chatId', 'key.remoteJid', 'chat.id', 'chat.id._serialized'] as $key) {
+            $value = Arr::get($payload ?? [], $key);
+
+            if (is_string($value) && ! str_ends_with(trim($value), '@g.us')) {
+                return WahaChatHelper::normalizeChatId($value);
+            }
+        }
+
+        return null;
+    }
+
+    /** @return array<string, mixed> */
+    private function decodePayload(?string $payloadJson): array
+    {
+        if (! $payloadJson) {
+            return [];
+        }
+
+        $payload = json_decode($payloadJson, true);
+
+        return is_array($payload) ? $payload : [];
+    }
+
     /**
      * @param  array<int, string>  $identifiers
      */
     private function displayPhoneNumber(array $identifiers): ?string
     {
         foreach ($identifiers as $identifier) {
             $identifier = trim($identifier);
 
             if ($identifier === '' || str_contains($identifier, '@lid') || str_contains($identifier, '@g.us')) {
                 continue;
@@ -1833,24 +2014,13 @@ private function latestIncomingWahaChatId(string $chatId): ?string
             if (is_string($value) && $value !== '') {
                 return $this->normalizeWahaChatId($value);
             }
         }
 
         return null;
     }
 
     private function normalizeWahaChatId(string $chatIdOrNumber): string
     {
-        if (str_contains($chatIdOrNumber, '@')) {
-            return str_ends_with($chatIdOrNumber, '@s.whatsapp.net')
-                ? str_replace('@s.whatsapp.net', '@c.us', $chatIdOrNumber)
-                : $chatIdOrNumber;
-        }
-
-        $number = preg_replace('/[^0-9]/', '', $chatIdOrNumber) ?: $chatIdOrNumber;
-
-        return $number.'@c.us';
+        return WahaChatHelper::normalizeChatId($chatIdOrNumber);
     }
 }
-
-
-

## Report
# Task 7 Report: InboxWhatsapp Livewire State

Implemented Task 7 and fixed review findings.

Validation:
- php -l app/Filament/Pages/InboxWhatsapp.php: PASS
- php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --filter InboxWhatsappTest: PASS, 3 tests, 35 assertions
- vendor/bin/pint --test app/Filament/Pages/InboxWhatsapp.php tests/Feature/Filament/Pages/InboxWhatsappTest.php: PASS

Review fixes:
- internal mapped group identifier is restricted to `@g.us` via groupJid(); NomorGrupWhatsapp non-JID is not used as GroupId/ChatId.
- latestIncomingPayload() result is reused by mappingIdentifiers/groupMappingIdentifiers through Payload object property to avoid duplicate payload query per row.
- selectChat() calls inspectPayload() only when UrlMedia is blank or MIME/file metadata is missing.

