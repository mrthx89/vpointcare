# fix-minor-bugs-and-inbox-ui Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Perbaiki 5 isu utama pada VPoint Care — menambahkan breadcrumb pada semua halaman, merombak form Ticket/Task agar responsif, memperbaiki fatal TypeError dan logika AutoReply, mengoptimasi performa Inbox WhatsApp dengan menghilangkan N+1 query, serta mendesain ulang antarmuka Inbox agar setara WhatsApp Web/Professional Desktop.

**Architecture:**
1. Breadcrumbs menggunakan trait `HasMenuBreadcrumbs` kustom dengan menu code konsisten.
2. Ticket & Task Resource Form menggunakan `Section` visual dan `Grid` multi-kolom (`['sm'=>1, 'md'=>2, 'lg'=>3]`).
3. AutoReply memperbaiki urutan inisialisasi variabel `$isFirstReply` dan menambahkan logika OR pada gate global vs session-level.
4. Inbox memuat payload terakhir secara batch dalam satu query SQL.
5. UI/UX Inbox menggunakan layout dual-pane full-height, responsive drawer mobile, dan high-contrast bubbles menggunakan Tailwind CSS.

**Tech Stack:** PHP 8.3, Laravel 13, Filament 5, Tailwind CSS, Livewire, Microsoft SQL Server.

## Global Constraints

- Jangan mengubah file di `src/vendor/` atau generated asset.
- Tetap gunakan model `MPengguna` untuk autentikasi.
- Kompatibel dengan Microsoft SQL Server (`sqlsrv`).
- Pertahankan normalisasi WAHA `@c.us`, `@g.us`, `@s.whatsapp.net`.
- Jangan mengekspos secret, API key, atau password.

---

### Task 1: Tambahkan Breadcrumbs ke halaman Ticketing dan Task

**Files:**
- Modify: `src/app/Filament/Resources/Operational/Tickets/Pages/ManageTickets.php`
- Modify: `src/app/Filament/Resources/Operational/Tasks/Pages/ManageTasks.php`
- Modify: `src/app/Filament/Resources/Ticketing/StatusTickets/Pages/ManageStatusTickets.php`
- Modify: `src/app/Filament/Resources/Ticketing/StatusTasks/Pages/ManageStatusTasks.php`
- Modify: `src/app/Filament/Resources/Ticketing/Kategoris/Pages/ManageKategoriTickets.php`
- Modify: `src/app/Filament/Resources/Ticketing/Prioritas/Pages/ManagePrioritasTickets.php`

**Interfaces:**
- Uses: `App\Filament\Concerns\HasMenuBreadcrumbs` dan `App\Support\AccessPermissions`

- [ ] **Step 1: Tambahkan trait `HasMenuBreadcrumbs` dan menu code pada ManageTickets.php**

```php
namespace App\Filament\Resources\Operational\Tickets\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Operational\Tickets\TicketResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTickets extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = TicketResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::TICKET_VIEW;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
```

- [ ] **Step 2: Tambahkan trait `HasMenuBreadcrumbs` dan menu code pada ManageTasks.php**

```php
namespace App\Filament\Resources\Operational\Tasks\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Operational\Tasks\TaskResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTasks extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = TaskResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::TASK_VIEW;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
```

- [ ] **Step 3: Lakukan penyesuaian breadcrumb di ManageStatusTickets.php, ManageStatusTasks.php, ManageKategoriTickets.php, ManagePrioritasTickets.php (gunakan `AccessPermissions::TICKET_MANAGE` atau `TASK_MANAGE`)**

```php
use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Support\AccessPermissions;
// ... lakukan hal yang sama di 4 file resource master ticketing ...
```

- [ ] **Step 4: Jalankan `php artisan test` dan `php -l` pada file yang diubah**
Run: `php artisan test`
Expected: PASS

---

### Task 2: Refactor Form Ticket & Task Layout (UI/UX Responsif)

**Files:**
- Modify: `src/app/Filament/Resources/Operational/Tickets/TicketResource.php`
- Modify: `src/app/Filament/Resources/Operational/Tasks/TaskResource.php`
- Modify: `src/app/Filament/Resources/Operational/Tickets/Pages/ManageTickets.php`
- Modify: `src/app/Filament/Resources/Operational/Tasks/Pages/ManageTasks.php`

**Interfaces:**
- Uses: `Filament\Forms\Components\Section`, `Filament\Forms\Components\Grid`, `Filament\Support\Enums\Width`

- [ ] **Step 1: Refactor `form()` pada TicketResource.php dengan Grid dan Section**

```php
public static function form(Schema $schema): Schema
{
    return $schema->components([
        Section::make('Informasi Utama')
            ->schema([
                TextInput::make('NomorTicket')->disabled()->dehydrated(false),
                TextInput::make('JudulTicket')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('DeskripsiMasalah')->rows(5)->columnSpanFull(),
            ])->columns(['sm' => 1, 'md' => 2, 'lg' => 3]),

        Section::make('Klasifikasi & Penugasan')
            ->schema([
                Select::make('IdStatusTicket')->options(fn () => self::options('MStatusTicket', 'NamaStatusTicket'))->required()->searchable(),
                Select::make('IdKategoriTicket')->options(fn () => self::options('MKategoriTicket', 'NamaKategori'))->searchable(),
                Select::make('IdPrioritasTicket')->options(fn () => self::options('MPrioritasTicket', 'NamaPrioritas'))->searchable(),
                Select::make('DitugaskanKepada')->options(fn () => self::options('MPengguna', 'NamaPengguna'))->searchable(),
                DateTimePicker::make('TglTargetSelesai')->native(false),
                Select::make('IdCustomer')->options(fn () => self::options('MCustomer', 'NamaCustomer'))->searchable(),
                Select::make('IdInstansi')->options(fn () => self::options('MInstansi', 'NamaInstansi'))->searchable(),
            ])->columns(['sm' => 1, 'md' => 2, 'lg' => 3]),

        Section::make('Aktivitas & Riwayat Penugasan')
            ->schema([
                Repeater::make('activities')->relationship()->label(__('ui.ticketing.progress_note'))->schema([
                    Select::make('JenisAktivitas')->options(['Catatan' => 'Catatan'])->default('Catatan')->required(),
                    Textarea::make('IsiAktivitas')->required()
                ])->columnSpanFull(),

                Repeater::make('assignments')->relationship()->label(__('ui.ticketing.assignment_history'))->schema([
                    Select::make('DitugaskanDari')->options(fn () => self::options('MPengguna', 'NamaPengguna'))->disabled(),
                    Select::make('DitugaskanKepada')->options(fn () => self::options('MPengguna', 'NamaPengguna'))->disabled(),
                    TextInput::make('AlasanPenugasan')->disabled(),
                    DateTimePicker::make('TglPenugasan')->disabled()
                ])->addable(false)->deletable(false)->reorderable(false)->columnSpanFull(),
            ])->collapsible(),

        Section::make('Lampiran')
            ->schema([
                Repeater::make('attachments')->relationship()->label(__('ui.ticketing.attachments'))->schema([
                    FileUpload::make('PathFile')->disk('attachments')->directory('tickets')->maxSize(3072)->acceptedFileTypes(['image/*', 'application/pdf'])->storeFileNamesIn('NamaFile')->required(),
                    Placeholder::make('download')->content(fn ($record) => $record ? new HtmlString('<a href="'.route('admin.attachments.tickets.download', $record->Id).'">'.e(__('ui.ticketing.download')).'</a>') : '')
                ])->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => self::attachmentMetadata($data))->columnSpanFull(),
            ])->collapsible(),
    ]);
}
```

- [ ] **Step 2: Atur `modalWidth` pada ManageTickets.php dan ManageTasks.php ke `Width::SevenExtraLarge`**

```php
use Filament\Support\Enums\Width;

protected static string|Width|null $modalWidth = Width::SevenExtraLarge;
```

- [ ] **Step 3: Jalankan `php -l` pada TicketResource.php dan TaskResource.php**

---

### Task 3: Perbaiki Bug AutoReply AI

**Files:**
- Modify: `src/app/Services/Ai/AiAutoReplyService.php`

**Interfaces:**
- Consumes: `$chatId` string, `$settings` object
- Produces: valid auto-reply output stored to `TChatD`

- [ ] **Step 1: Pindahkan inisialisasi `$isFirstReply` sebelum dipanggil di `TAiPermintaan`**

```php
$requestId = (string) Str::orderedUuid();
$prompt = $this->buildPrompt($settings, $chat, $decision['template']);
$reply = $decision['template'];
$responsePayload = null;
$status = 'Selesai';
$error = null;
$usedAi = false;

// FIX: Inisialisasi sebelum dipakai
$isFirstReply = $this->isFirstInboxAiReply($chatId);

DB::table('TAiPermintaan')->insert([
    'Id' => $requestId,
    'JenisPermintaan' => 'Auto Reply WhatsApp',
    'ProviderAi' => $settings->ProviderAi ?: 'OpenAI',
    'ModelAi' => $this->inboxReplyModel($settings, $isFirstReply),
    // ...
]);
```

- [ ] **Step 2: Perbaiki Gate Check `handleIncomingChat` agar AutoReply aktif jika salah satu global ATAU sesi bernilai true**

```php
// AWAL:
if (! $settings || ! (bool) $settings->AutoReplyAktif) {
    return null;
}
// PERBAIKAN:
if (! $settings || (! (bool) $settings->AutoReplyAktif && ! (bool) $chat->AutoReplyAiAktif)) {
    return null;
}
```

- [ ] **Step 3: Teruskan `$isFirstReply` ke `generateChatCompletionReply()`**
```php
if ($provider === 'deepseek') {
    return $this->generateChatCompletionReply($settings, $prompt, $apiKey, 'deepseek', $isFirstReply);
}
// ...lakukan hal yang sama untuk openrouter dan ninerouter
```

- [ ] **Step 4: Jalankan syntax check `php -l src/app/Services/Ai/AiAutoReplyService.php`**

---

### Task 4: Batch-load Payload Pesan Terakhir dan Hilangkan N+1 Query di Inbox

**Files:**
- Modify: `src/app/Filament/Pages/InboxWhatsapp.php`

**Interfaces:**
- Produces: `$latestMessages` variable array mapped by `IdChat`

- [ ] **Step 1: Ubah query SQL di `loadInbox()` menggunakan single batch query tanpa looping `latestIncomingPayload()`**
```php
$latestMessages = DB::table('TChatD')
    ->whereIn('IdChat', $chatIds)
    ->select('IdChat', 'IsiPesan', 'PayloadJson')
    ->whereIn('ArahPesan', ['Masuk'])
    ->whereIn('TglPesan', function ($q) use ($chatIds) {
        $q->selectRaw('MAX(TglPesan)')
          ->from('TChatD')
          ->whereIn('IdChat', $chatIds);
    })
    ->get()
    ->keyBy('IdChat');
```

---

### Task 5: Defer panggilan HTTP synchronous WAHA ke background job

**Files:**
- Modify: `src/app/Filament/Pages/InboxWhatsapp.php`

**Interfaces:**
- `refreshWahaProfile()` dipindahkan dari Livewire event ke Queue Job

- [ ] **Step 1: Ubah `refreshWahaProfileIfNeeded` agar tidak blocking HTTP request**
```php
private function refreshWahaProfileIfNeeded(string $chatId): void
{
    // Hanya dispatch queue asinkron agar UI langsung muncul
    RefreshWahaProfileJob::dispatch($chatId);
}
```

---

### Task 6: Redesain UI/UX Inbox WhatsApp

**Files:**
- Modify: `src/resources/views/filament/pages/inbox-whatsapp.blade.php`

**Interfaces:**
- Consumes: Livewire properties `$chatRows`, `$messages`, `$selectedChat`

- [ ] **Step 1: Ubah wrapper utama menjadi flex container full height viewport**
```html
<div class="flex flex-col h-[calc(100vh-6.5rem)] bg-white dark:bg-gray-950 rounded-xl shadow-xl overflow-hidden">
    <!-- Header Top Area -->
    
    <div class="flex flex-1 overflow-hidden">
        <!-- Left Pane (Sidebar Chat) -->
        <aside class="w-full md:w-80 lg:w-96 border-r border-gray-200 dark:border-gray-800 flex flex-col">
            <!-- Search Input -->
            <!-- Chat List -->
        </aside>

        <!-- Right Pane (Chat Area) -->
        <main class="flex-1 flex flex-col bg-[#efeae2] dark:bg-gray-900 relative">
            <!-- Chat Header -->
            <!-- Message List -->
            <!-- Input Bar -->
        </main>
    </div>
</div>
```

- [ ] **Step 2: Tambahkan logika Alpine JS/Tailwind untuk toggle sidebar mobile drawer**
```html
<div x-data="{ showSidebar: true }" class="...">
    <aside x-show="showSidebar" class="...">
```

- [ ] **Step 3: Implementasikan WhatsApp-style bubble Chat dengan warna kontras**
```html
@php
    $bubbleClass = match($msg['ArahPesan']) {
        'Masuk' => 'bg-white dark:bg-gray-800 rounded-tl-none text-gray-900 dark:text-white',
        'Keluar' => 'bg-emerald-100 dark:bg-emerald-900 rounded-tr-none text-emerald-900 dark:text-emerald-50',
        default => 'bg-gray-100 dark:bg-gray-800'
    };
@endphp
<div class="max-w-[75%] px-4 py-2 shadow-sm rounded-xl {{ $bubbleClass }}">
    {{ $msg['IsiPesan'] }}
</div>
```

- [ ] **Step 4: Jalankan `npm run build`**
Run: `cd src && npm run build`
Expected: BUILD SUCCESS

- [ ] **Step 5: Commit**
```bash
git add src/resources/views/filament/pages/inbox-whatsapp.blade.php
git commit -m "feat: redesign inbox whatsapp UI to WhatsApp Web/Desktop professional style"
```
