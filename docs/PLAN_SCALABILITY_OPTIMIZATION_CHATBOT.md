# Plan: Scalability Optimization & Internal Chatbot (VPoint Assistant)

> Dokumen ini adalah panduan implementasi untuk perubahan scalabilitas dan fitur chatbot internal.
> Dibuat: 2026-06-28 | Status: **Belum Dimulai**

---

## Daftar Isi

1. [Ringkasan Perubahan](#1-ringkasan-perubahan)
2. [Fase A — Scalability Optimization](#2-fase-a--scalability-optimization)
3. [Fase B — VPoint Assistant (Internal Chatbot)](#3-fase-b--vpoint-assistant-internal-chatbot)
4. [Fase C — Deployment](#4-fase-c--deployment)
5. [Rollback Plan](#5-rollback-plan)
6. [Monitoring & Alerting](#6-monitoring--alerting)
7. [Estimasi Timeline](#7-estimasi-timeline)

---

## 1. Ringkasan Perubahan

### Fase A — Scalability Optimization
Mengubah arsitektur dari synchronous ke asynchronous untuk menangani volume 10-100x lipat.

| # | Perubahan | Impact | Effort |
|---|-----------|--------|--------|
| A1 | Aktifkan Redis (cache + queue + session) | **Foundation** | 0.5 hr |
| A2 | Shared WahaChatHelper | Code dedup | 0.5 hr |
| A3 | Cache Schema::hasColumn | Micro-opt | 0.5 hr |
| A4 | Cache MPengaturanAi | Quick win | 0.5 hr |
| A5 | Async webhook via ProcessWebhookJob | **KRITIS** | 1 hr |
| A6 | Async AI reply via ProcessAiAutoReplyJob | **KRITIS** | 1 hr |
| A7 | Debounced broadcast + browser | Infrastructure | 1 hr |
| A8 | Rate limiting webhook | Safety | 0.5 hr |
| A9 | Circuit breaker WahaSender | Resilience | 0.5 hr |
| A10 | Optimasi dashboard query | Performance | 1 hr |
| A11 | Composite database indexes | Performance | 0.5 hr |

### Fase B — VPoint Assistant (Internal Chatbot)
Fitur baru: AI chatbot untuk user admin panel.

| # | Perubahan | Effort |
|---|-----------|--------|
| B1 | Database migration TChatbotInternal | 0.5 hr |
| B2 | Model ChatbotMessage | 0.5 hr |
| B3 | InternalChatbotService (RAG + AI) | 1.5 hr |
| B4 | Filament Page + permission | 1 hr |
| B5 | Blade view + JavaScript | 1 hr |

---

## 2. Fase A — Scalability Optimization

### A1. Aktifkan Redis

**Step-by-step:**

1. Update `.env`:
   ```env
   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   REDIS_DB=0
   REDIS_CACHE_DB=1
   ```

2. Verifikasi Redis connection di `config/database.php`:
   ```php
   redis => [
       client => env('REDIS_CLIENT', 'phpredis'),
       default => [
           host => env('REDIS_HOST', '127.0.0.1'),
           port => env('REDIS_PORT', 6379),
           database => env('REDIS_DB', '0'),
       ],
       cache => [
           host => env('REDIS_HOST', '127.0.0.1'),
           port => env('REDIS_PORT', 6379),
           database => env('REDIS_CACHE_DB', '1'),
       ],
   ],
   ```

3. Tambah fallback di `AppServiceProvider`:
   ```php
   if (!extension_loaded('redis')) {
       config(['cache.default' => 'database', 'queue.default' => 'database']);
   }
   ```

4. Test:
   ```bash
   php artisan tinker
   Cache::store('redis')->put('test', 'ok', 60);
   Cache::store('redis')->get('test'); // harus 'ok'
   ```

### A2. Shared WahaChatHelper

**File baru:** `src/app/Support/WahaChatHelper.php`

Method yang dipindahkan:
- `normalizeChatId(string $id): string` — dari AiAutoReplyService
- `normalizeContactId(string $id): string` — dari WahaSender
- `toDisplayNumber(string $id): ?string` — dari InboxWhatsapp
- `toCUs(string $id): string` — dari semua service
- `expandIdentifiers(array $identifiers): array` — dari InboxWhatsapp
- `firstWahaId(array $identifiers): ?string` — dari InboxWhatsapp
- `displayPhoneNumber(array $identifiers): ?string` — dari InboxWhatsapp
- `latestIncomingWahaChatId(string $chatId): ?string` — dari AiAutoReplyService & InboxWhatsapp
- `resolveLidPhoneNumber(WahaSender $sender, string $session, array $parsed): array` — dari WahaWebhookProcessor

**Update file yang menggunakan duplicate code:**
- `AiAutoReplyService` — hapus `normalizeWahaChatId()`, `latestIncomingWahaChatId()`, `normalizeWahaChatId()`, gunakan `WahaChatHelper`
- `WahaWebhookProcessor` — ganti inline normalize logic dengan `WahaChatHelper`
- `InboxWhatsapp` — hapus `normalizeWahaChatId()`, `latestIncomingWahaChatId()`, `expandIdentifiers()`, `firstWahaId()`, `displayPhoneNumber()`, gunakan `WahaChatHelper`
- `WahaSender` — hapus `normalizeChatId()`, `normalizeContactId()`, `phoneNumberFromContactId()`, `firstPhoneContactId()`, `encodeWahaPathId()`, gunakan `WahaChatHelper`

### A3. Cache Schema::hasColumn()

**File diupdate:** `src/app/Providers/AppServiceProvider.php`

Tambah method:
```php
class SchemaCheck
{
    public static function hasColumn(string $table, string $column): bool
    {
        return Cache::rememberForever("schema:{$table}:{$column}", function () use ($table, $column) {
            return Schema::hasColumn($table, $column);
        });
    }
}
```

**File yang diupdate:**
- `WahaWebhookProcessor` — semua `Schema::hasColumn()` → `SchemaCheck::hasColumn()`
- `ChatInitiationService` — semua `Schema::hasColumn()` → `SchemaCheck::hasColumn()`
- `ChatBelumTerbalasNotifier` — semua `Schema::hasColumn()` → `SchemaCheck::hasColumn()`
- `AiAutoReplyService` — semua `Schema::hasColumn()` → `SchemaCheck::hasColumn()`
- `InboxWhatsapp` — semua `Schema::hasColumn()` → `SchemaCheck::hasColumn()`

**Cache clear saat migration:**
```php
// Di method boot AppServiceProvider
Artisan::after('migrate', function () {
    Cache::forget('schema:TChat:IdWahaTerdeteksi');
    // forget all schema keys via pattern...
});
```

### A4. Cache MPengaturanAi Settings

**File baru:** `src/app/Support/AiSettings.php`

```php
class AiSettings
{
    public static function get(): ?object
    {
        return Cache::remember('mpengaturan_ai_default', 300, function () {
            return DB::table('MPengaturanAi')
                ->where('KodePengaturan', 'DEFAULT')
                ->where('NonAktif', false)
                ->first();
        });
    }

    public static function flush(): void
    {
        Cache::forget('mpengaturan_ai_default');
    }
}
```

**Update service:**
- `AiAutoReplyService::settings()` → `AiSettings::get()`
- `AiKnowledgeLearningService::settings()` → `AiSettings::get()`
- `ChatBelumTerbalasNotifier` — semua query MPengaturanAi → `AiSettings::get()`

**Cache invalidation di Filament:**
- Di halaman `AiAgent.php` — setelah save, panggil `AiSettings::flush()`

### A5. Async Webhook via Queue

**File baru:** `src/app/Jobs/ProcessWebhookJob.php`

```php
class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public string $queue = 'webhooks';

    public function __construct(
        public string $webhookId,
        public array $payload,
        public ?string $sessionCode = null,
    ) {}

    public function handle(WahaWebhookProcessor $processor): void
    {
        $result = $processor->process($this->payload);

        if (($result['ok'] ?? false) && empty($result['duplicate']) && !empty($result['chat_id'])) {
            $chatId = (string) $result['chat_id'];

            // Dispatch AI auto-reply
            ProcessAiAutoReplyJob::dispatch($chatId);

            // Dispatch debounced broadcast
            SendBroadcastDebouncedJob::dispatchDebounced($chatId);
        }

        DB::table('TLogWebhookWaha')
            ->where('Id', $this->webhookId)
            ->update(['SudahDiproses' => true, 'TglDiproses' => now(), 'TglEdit' => now()]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('Webhook job failed', [
            'webhook_id' => $this->webhookId,
            'error' => $e->getMessage(),
        ]);

        DB::table('TLogWebhookWaha')
            ->where('Id', $this->webhookId)
            ->update(['PesanError' => $e->getMessage(), 'TglEdit' => now()]);
    }
}
```

**Update `WahaWebhookController`:**
```php
public function __invoke(Request $request, WahaWebhookProcessor $processor): JsonResponse
{
    // Validasi token + HMAC (sama seperti sekarang)
    
    // Insert lightweight log
    $webhookId = (string) Str::orderedUuid();
    DB::table('TLogWebhookWaha')->insert([
        'Id' => $webhookId,
        'JenisEvent' => 'message',
        'PayloadJson' => json_encode($request->all()),
        'TglDiterima' => now(),
        'SudahDiproses' => false,
        'TglBuat' => now(),
    ]);

    // Dispatch ke queue
    ProcessWebhookJob::dispatch($webhookId, $request->all());

    // Return immediate 200
    return response()->json(['ok' => true, 'queued' => true, 'webhook_id' => $webhookId]);
}
```

### A6. Async AI Reply via Queue

**File baru:** `src/app/Jobs/ProcessAiAutoReplyJob.php`

```php
class ProcessAiAutoReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 90;
    public string $queue = 'ai-replies';

    public function __construct(
        public string $chatId,
        public string $triggeredAt,
    ) {
        $this->triggeredAt = now()->toDateTimeString();
    }

    public function handle(AiAutoReplyService $autoReply): void
    {
        // Deduplication: cek apakah CS sudah reply manual
        $csReplied = DB::table('TChatD')
            ->where('IdChat', $this->chatId)
            ->where('ArahPesan', 'Keluar')
            ->where('DihasilkanOlehAi', false)
            ->where('TglPesan', '>=', $this->triggeredAt)
            ->exists();

        if ($csReplied) {
            Log::info('AI reply skipped — CS already replied', ['chat_id' => $this->chatId]);
            $this->delete();
            return;
        }

        $result = $autoReply->handleIncomingChat($this->chatId);

        if ($result && isset($result['delivery']) && ($result['delivery']['status'] ?? '') === 'Terkirim WAHA') {
            // Broadcast update untuk inbox
            SendBroadcastDebouncedJob::dispatchDebounced($this->chatId);
        }
    }
}
```

### A7. Debounced Broadcast

**File baru:** `src/app/Jobs/SendBroadcastDebouncedJob.php`

```php
class SendBroadcastDebouncedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public string $queue = 'broadcasts';

    private function __construct(
        public string $chatId,
    ) {}

    public static function dispatchDebounced(string $chatId): void
    {
        $key = "broadcast:pending:{$chatId}";

        if (Cache::get($key)) {
            return; // Already pending
        }

        Cache::put($key, true, now()->addSeconds(2));
        static::dispatch($chatId)->delay(now()->addMilliseconds(500));
    }

    public function handle(): void
    {
        $key = "broadcast:pending:{$this->chatId}";

        if (!Cache::pull($key)) {
            return; // Already broadcasted
        }

        broadcast(new WahaInboxUpdated($this->chatId))->toOthers();
    }
}
```

**Update JavaScript di inbox view** (file blade):
```javascript
document.addEventListener('livewire:init', () => {
    let updateTimer;
    window.Echo.channel('waha-inbox')
        .listen('.inbox.updated', (e) => {
            clearTimeout(updateTimer);
            updateTimer = setTimeout(() => {
                window.Livewire.dispatch('waha-inbox-updated');
            }, 300);
        });
});
```

### A8. Rate Limiting Webhook

**Update `routes/web.php`:**
```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('webhooks', function (Request $request) {
    return Limit::perMinute(100)->by($request->ip());
});

Route::post('/webhooks/waha/{token?}', WahaWebhookController::class)
    ->middleware(['throttle:webhooks'])
    ->name('webhooks.waha');
```

### A9. Circuit Breaker WahaSender

**Update `WahaSender`:**
```php
class WahaSender
{
    private static int $consecutiveFailures = 0;
    private static ?string $circuitOpenUntil = null;
    private const CIRCUIT_THRESHOLD = 5;
    private const CIRCUIT_COOLDOWN_SECONDS = 120;

    private function isCircuitOpen(): bool
    {
        if (self::$circuitOpenUntil === null) {
            return false;
        }

        if (now()->gt(self::$circuitOpenUntil)) {
            self::$circuitOpenUntil = null;
            self::$consecutiveFailures = 0;
            Log::info('WAHA circuit breaker reset');
            return false;
        }

        return true;
    }

    private function recordFailure(): void
    {
        self::$consecutiveFailures++;
        if (self::$consecutiveFailures >= self::CIRCUIT_THRESHOLD) {
            self::$circuitOpenUntil = now()->addSeconds(self::CIRCUIT_COOLDOWN_SECONDS);
            Log::critical('WAHA circuit breaker OPENED — too many consecutive failures');
        }
    }

    private function recordSuccess(): void
    {
        if (self::$consecutiveFailures > 0) {
            self::$consecutiveFailures = 0;
            Log::info('WAHA circuit breaker closed — success after failure');
        }
    }
}
```

Update `postJson()` dan `getJson()` untuk cek circuit breaker sebelum HTTP call.

### A10. Optimasi Dashboard Query

**Update `Dashboard::loadDashboard()` — ganti loop Collection dengan aggregation:**

```php
public function loadDashboard(): void
{
    [$start, $end] = $this->periodBounds();

    // Single aggregation query — ganti semua Collection filtering
    $this->summary = DB::table('TChatD')
        ->whereBetween('TglPesan', [$start, $end])
        ->selectRaw("
            COUNT(*) as total_messages,
            COUNT(DISTINCT IdChat) as unique_chats,
            SUM(CASE WHEN ArahPesan = 'Masuk' THEN 1 ELSE 0 END) as incoming,
            SUM(CASE WHEN ArahPesan = 'Keluar' AND (DihasilkanOlehAi = 1 OR DihasilkanOlehAi IS NULL) THEN 0 ELSE CASE WHEN ArahPesan = 'Keluar' THEN 1 ELSE 0 END END) as cs_replies,
            SUM(CASE WHEN DihasilkanOlehAi = 1 THEN 1 ELSE 0 END) as ai_replies,
            SUM(CASE WHEN StatusKirim LIKE 'Terkirim%' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN StatusKirim LIKE 'Gagal%' THEN 1 ELSE 0 END) as failed,
            COUNT(DISTINCT CASE WHEN ArahPesan = 'Masuk' THEN IdChat END) as incoming_chats
        ")
        ->first();

    // ...aggregation untuk teamRows, dailyTrend, topClients juga di SQL
}
```

### A11. Composite Database Indexes

Jalankan di SQL Server:
```sql
-- Duplicate message check
CREATE NONCLUSTERED INDEX IX_TChatD_IdPesanWaha_Partial
ON TChatD (IdPesanWaha)
WHERE IdPesanWaha IS NOT NULL;

-- Unanswered chats (ChatBelumTerbalasNotifier)
CREATE NONCLUSTERED INDEX IX_TChatD_Arah_Dikirim_Tgl
ON TChatD (ArahPesan, DikirimOlehCustomer, TglPesan DESC)
INCLUDE (IdChat, IsiPesan);

-- AI reply decision check
CREATE NONCLUSTERED INDEX IX_TChatD_IdChat_Arah_Ai_Tgl
ON TChatD (IdChat, ArahPesan, DihasilkanOlehAi, TglPesan DESC);

-- Dashboard aggregation
CREATE NONCLUSTERED INDEX IX_TChatD_TglPesan_Arah_Status
ON TChatD (TglPesan)
INCLUDE (IdChat, ArahPesan, DihasilkanOlehAi, StatusKirim);

-- Chatbot history
CREATE NONCLUSTERED INDEX IX_TChatbotInternal_Pengguna_Tgl
ON TChatbotInternal (IdPengguna, TglBuat DESC);
```

### Queue Worker Setup

**Windows (deploy-update-server.bat):**
```batch
start /B php artisan queue:work --queue=webhooks --timeout=60 --sleep=1 --tries=3 --name=webhooks
start /B php artisan queue:work --queue=ai-replies --timeout=90 --sleep=1 --tries=2 --name=ai-replies
start /B php artisan queue:work --queue=broadcasts,default --timeout=30 --sleep=1 --name=broadcasts
```

**Linux (Supervisor):**
```ini
[program:wacs-webhooks]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/wacs/src/artisan queue:work --queue=webhooks --timeout=60 --sleep=1 --tries=3
autostart=true
autorestart=true
numprocs=3
user=www-data

[program:wacs-ai-replies]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/wacs/src/artisan queue:work --queue=ai-replies --timeout=90 --sleep=1 --tries=2
autostart=true
autorestart=true
numprocs=5
user=www-data

[program:wacs-broadcasts]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/wacs/src/artisan queue:work --queue=broadcasts,default --timeout=30 --sleep=1
autostart=true
autorestart=true
numprocs=2
user=www-data
```

---

## 3. Fase B — VPoint Assistant (Internal Chatbot)

### B1. Database Migration

Jalankan di SQL Server:
```sql
IF OBJECT_ID('TChatbotInternal', 'U') IS NULL
BEGIN
    CREATE TABLE TChatbotInternal (
        Id uniqueidentifier NOT NULL CONSTRAINT DF_TChatbotInternal_Id DEFAULT NEWSEQUENTIALID(),
        IdPengguna uniqueidentifier NOT NULL,
        PeranPengirim varchar(20) NOT NULL,
        IsiPesan nvarchar(max) NOT NULL,
        IdAiRespon uniqueidentifier NULL,
        KonteksJson nvarchar(max) NULL,
        TglBuat datetime2 NOT NULL CONSTRAINT DF_TChatbotInternal_TglBuat DEFAULT SYSDATETIME(),
        CONSTRAINT PK_TChatbotInternal PRIMARY KEY (Id),
        CONSTRAINT FK_TChatbotInternal_MPengguna FOREIGN KEY (IdPengguna) REFERENCES MPengguna(Id),
        CONSTRAINT CK_TChatbotInternal_Peran CHECK (PeranPengirim IN ('user', 'assistant'))
    );

    CREATE INDEX IX_TChatbotInternal_Pengguna_Tgl ON TChatbotInternal (IdPengguna, TglBuat DESC);
END;
```

### B2. Model

**File baru:** `src/app/Models/ChatbotMessage.php`

```php
namespace App\Models;

use App\Models\Concerns\UsesSqlServerUuid;
use App\Models\Master\Pengguna;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'TChatbotInternal';
    protected $guarded = ['Id'];

    public const PERAN_USER = 'user';
    public const PERAN_ASSISTANT = 'assistant';

    protected $casts = [
        'KonteksJson' => 'array',
        'TglBuat' => 'datetime',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'IdPengguna');
    }
}
```

### B3. InternalChatbotService

**File baru:** `src/app/Services/Ai/InternalChatbotService.php`

**Key methods:**

| Method | Responsibility |
|--------|---------------|
| `ask(userId, message)` | Orchestrator utama |
| `conversationHistory(userId)` | Load 20 pesan terakhir |
| `searchKnowledge(query)` | RAG — search MPengetahuan |
| `buildSystemPrompt(knowledge, userId)` | System prompt builder |
| `callProvider(settings, messages)` | AI provider call |
| `clearHistory(userId)` | Delete per user |

**RAG search flow:**
```
1. Split user query jadi individual keywords (len > 2 chars)
2. Query MPengetahuan WHERE NonAktif = false
3. MATCH terhadap JudulPengetahuan, IsiPengetahuan, Tag, SearchKeywords
4. ORDER BY PrioritasAi DESC
5. LIMIT 5
6. Masukkan title + content ke system prompt
```

**AI provider call:** Reuse logic dari `AiAutoReplyService`:
- OpenAI Responses API
- DeepSeek / OpenRouter / NineRouter chat completions
- API key dari `MPengaturanAi` terenkripsi
- Timeout: 30 detik

### B4. Filament Page

**File baru:** `src/app/Filament/Pages/VPointAssistant.php`

**Properties:**
- `$userMessage: string` — wire:model
- `$messages: array` — history + current
- `$isTyping: bool` — loading state

**Methods:**
- `mount()` — load history dari DB
- `sendMessage()` — validasi → panggil service → update UI
- `clearHistory()` — confirm → delete → reset UI
- `loadHistory()` — fetch dari TChatbotInternal

**Navigation:**
- Icon: `heroicon-o-chat-bubble-bottom-center-text`
- Group: Operasional
- Sort: 15 (antara Inbox 10 dan Ticketing 20)

### B5. Blade View

**File baru:** `src/resources/views/filament/pages/vpoint-assistant.blade.php`

**Komponen:**
1. **Chat container** — `h-[calc(100vh-200px)]`, flex column
2. **Empty state** — icon besar, welcome text, hint
3. **Message bubbles** — user right (primary), assistant left (white/dark)
4. **Markdown render** — `Str::markdown()` untuk response
5. **Knowledge tags** — small text di bawah assistant bubble
6. **Timestamp** — kecil, pojok kanan bawah
7. **Typing indicator** — 3 animated dots
8. **Input area** — text input + send button + clear button
9. **Auto-scroll** — x-data, x-ref, x-init

---

## 4. Fase C — Deployment

### Pre-Deployment Checklist

- [ ] Backup database SQL Server
- [ ] Install Redis di server
- [ ] Update `.env` dengan Redis credentials
- [ ] Update `.env.example` sebagai referensi

### Deployment Steps

```bash
# 1. Pull code terbaru
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Migration
php artisan migrate --force

# 4. Clear & optimize
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

# 5. Rebuild frontend
npm ci
npm run build

# 6. Restart queue workers
# (restart supervisor atau jalankan ulang batch file)

# 7. Verify
php artisan cache:store redis
php artisan config:show cache
```

### Post-Deployment Verification

- [ ] `Cache::store('redis')->get('test')` → ok
- [ ] Webhook POST → response 200 < 500ms
- [ ] Queue worker memproses job
- [ ] Dashboard load < 2 detik
- [ ] VPoint Assistant bisa diakses
- [ ] Broadcast event sampai ke browser

---

## 5. Rollback Plan

### Rollback Fase A (Scalability)

Jika terjadi masalah setelah deployment:

```bash
# 1. Kembalikan .env ke konfigurasi lama
# CACHE_STORE=database
# QUEUE_CONNECTION=database
# SESSION_DRIVER=file

# 2. Clear config
php artisan config:clear && php artisan optimize:clear

# 3. Restart workers dengan config lama
# 4. Test webhook flow
```

### Rollback Fase B (Chatbot)

Tidak ada perubahan schema pada tabel existing. Rollback cukup hapus page dan service:

- Hapus `VPointAssistant.php`
- Hapus `vpoint-assistant.blade.php`
- Hapus `InternalChatbotService.php`
- Hapus `ChatbotMessage.php`
- Drop tabel `TChatbotInternal` (opsional, data tidak dipakai fitur lain)

---

## 6. Monitoring & Alerting

### Queue Monitoring

```bash
# Cek queue depth
php artisan queue:monitor redis:webhooks,ai-replies,broadcasts,default --max=100

# Jika depth > threshold → alert
```

### Redis Monitoring

```bash
# Memory usage
redis-cli INFO memory | grep used_memory_human

# Key count
redis-cli DBSIZE

# Jika used_memory > 80% dari maxmemory → alert
```

### Quality Metrics

| Metric | Target | Alert |
|--------|--------|-------|
| Webhook response time | < 500ms | > 2s |
| AI job processing time | < 30s | > 60s |
| Queue depth webhooks | < 50 | > 100 |
| Queue depth ai-replies | < 20 | > 50 |
| Dashboard load time | < 2s | > 5s |
| Broadcast per menit | < 60 | > 120 |
| Redis memory usage | < 200MB | > 500MB |

---

## 7. Estimasi Timeline

### Fase A — 4 hari

| Day | Tasks |
|-----|-------|
| Day 1 | A1 Redis + A2 WahaChatHelper + A3 Schema cache + A4 AI settings cache |
| Day 2 | A5 Async webhook + A6 Async AI reply |
| Day 3 | A7 Debounced broadcast + A8 Rate limiting + A9 Circuit breaker |
| Day 4 | A10 Dashboard + A11 Indexes + Queue worker setup + Testing |

### Fase B — 3 hari

| Day | Tasks |
|-----|-------|
| Day 5 | B1 Migration + B2 Model + B3 Service (RAG + AI) |
| Day 6 | B4 Filament Page + B5 Blade view + JavaScript |
| Day 7 | Testing semua scenario + Fix bugs + Documentation |

### Total: 7 hari kerja

---

## Event Flow Diagram (Updated)

```text
WAHA Webhook
    ↓ HTTP POST
[Rate Limiter: 100/min]
    ↓
WahaWebhookController
    ↓ (validasi token + HMAC)
Insert TLogWebhookWaha (lightweight)
    ↓
Return HTTP 200 OK (< 500ms)
    ↓
[Queue: webhooks]
    ↓
ProcessWebhookJob
    ├── Parse & resolve customer
    ├── Find or create session
    ├── Insert TChat + TChatD
    ├── Broadcast debounced → [Queue: broadcasts]
    └── AI reply → [Queue: ai-replies]
                          ↓
              ProcessAiAutoReplyJob
                  ├── Dedup check (CS replied?)
                  ├── Call AI provider
                  ├── Store TAiPermintaan + TAiRespon
                  ├── Store TChatD reply
                  ├── Send via WAHA (if enabled)
                  └── Broadcast update

[Queue: broadcasts]
    ↓
SendBroadcastDebouncedJob
    ├── Check pending flag in Redis
    ├── If flag exists → remove flag → broadcast
    └── If no flag → skip (already sent)

Browser (Laravel Echo)
    └── Debounce 300ms → loadInbox()
```

## Architecture Diagram

```text
┌─────────────────────────────────────────────────────────┐
│                    PHP Application                        │
│                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  Webhook     │  │  Filament    │  │  Queue       │   │
│  │  Controller  │  │  Admin Panel  │  │  Workers     │   │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘   │
│         │                 │                 │           │
│  ┌──────┴─────────────────┴─────────────────┴──────┐    │
│  │            Service Layer                         │    │
│  │  ┌──────────┐ ┌──────────┐ ┌────────────────┐   │    │
│  │  │WAHA      │ │AI        │ │Chatbot         │   │    │
│  │  │Processor │ │AutoReply │ │Internal        │   │    │
│  │  └──────────┘ └──────────┘ └────────────────┘   │    │
│  └──────────────────────────────────────────────────┘    │
└──────────────────────────┬──────────────────────────────┘
                           │
              ┌────────────┼────────────┐
              ▼            ▼            ▼
        ┌──────────┐ ┌──────────┐ ┌──────────┐
        │  Redis   │ │  SQL     │ │  WAHA    │
        │ Cache/   │ │  Server  │ │  Server  │
        │ Queue    │ │  (DB)    │ │          │
        └──────────┘ └──────────┘ └──────────┘

## Addendum: Multilanguage Preservation

Semua perubahan implementasi SHALL mempertahankan mekanisme multilanguage existing (`src/lang/id/ui.php` dan `src/lang/en/ui.php`).

### Rules

- Semua label UI baru wajib memakai `__('ui....')`, bukan hardcoded string.
- Semua notification success/error baru wajib memakai key localization.
- Semua page title, navigation label, placeholder, button, modal title, helper text, empty state, loading text, dan confirmation text wajib tersedia minimal di `id` dan `en`.
- Nama brand seperti `VPoint Assistant`, `WAHA`, `Redis`, `OpenAI`, `DeepSeek`, `OpenRouter`, `NineRouter` boleh tetap literal.
- Error teknis dari exception boleh disimpan di log, tetapi pesan yang tampil ke user harus localized dan disanitasi.
- Jika menambah permission/menu baru, label Indonesia dan Inggris harus sinkron.

### Required Localization Keys

- `ui.pages.chatbot.title`
- `ui.pages.chatbot.navigation_label`
- `ui.pages.chatbot.empty_title`
- `ui.pages.chatbot.empty_description`
- `ui.pages.chatbot.placeholder`
- `ui.pages.chatbot.send`
- `ui.pages.chatbot.clear_history`
- `ui.pages.chatbot.clear_confirm`
- `ui.pages.chatbot.typing`
- `ui.pages.chatbot.error_provider_missing`
- `ui.pages.chatbot.error_provider_failed`
- `ui.pages.chatbot.error_empty_response`
- `ui.pages.chatbot.knowledge_used`
- `ui.pages.chatbot.history_cleared`
- `ui.pages.chatbot.message_required`
- `ui.pages.chatbot.message_max`
- `ui.scalability.webhook_queued`
- `ui.scalability.circuit_breaker_active`
- `ui.scalability.ai_reply_skipped_cs_replied`

## Addendum 2026-06-28 — VPoint Assistant UX & Scale-up

### Fitur Baru
- Attach file di internal chatbot: user bisa melampirkan file teks seperti `.txt`, `.md`, `.csv`, `.json`, `.log`, `.sql`, `.xml`, `.yml`, `.yaml`; konten file masuk ke konteks AI secara terbatas agar token tetap terkendali.
- Mode AI `Ringan` / `Cepat`: `Ringan` membatasi knowledge retrieval dan menginstruksikan jawaban lebih pendek; `Cepat` memberi jawaban lebih lengkap namun tetap praktis.
- Mode knowledge `All Knowledge` / `Tanpa Knowledge`: user bisa memaksa AI memakai knowledge base atau menjawab hanya dari chat + file.
- Draft knowledge dari jawaban AI: jawaban assistant dapat dikirim menjadi `TAiDraftPengetahuan` untuk review dan approval sebelum masuk `MPengetahuan`.
- Copy clipboard: setiap jawaban AI punya icon copy seperti ChatGPT.
- Markdown rendering: jawaban AI dirender sebagai HTML aman sehingga heading, bullet, tabel, inline code, dan code block tampil terstruktur tanpa menampilkan literal `##`.

### Alasan Teknis
- Attach file mempercepat troubleshooting karena user tidak perlu copy-paste log panjang, namun tetap dibatasi 5 MB per file dan 12.000 karakter teks per file.
- Mode `Ringan` mengurangi beban token/provider untuk pertanyaan operasional sederhana.
- Mode `Tanpa Knowledge` berguna saat user bertanya tentang file/log spesifik dan tidak ingin retrieval knowledge mengganggu konteks.
- Draft knowledge menjaga governance: AI hanya membuat draft, manusia tetap review sebelum knowledge dipublish.
- Markdown + copy meningkatkan produktivitas CS/admin saat menyalin command, SOP, atau draft jawaban.

### Catatan Implementasi
- File non-teks tetap dicatat sebagai attachment, tetapi kontennya tidak diekstrak otomatis.
- Draft knowledge deduplicate memakai `HashKonten` agar jawaban yang sama tidak membuat draft berulang.
- Multilanguage ID/EN ditambahkan untuk label mode, attach file, copy, dan draft.

## Addendum 2026-06-28-B — Composed Layout & Clipboard Paste

### Masalah Sebelumnya
- Composer (input chat) sering tertutup karena area chat menggunakan `h-[calc(100dvh-10.5rem)]` yang tidak stabil di dalam page Filament.
- Tidak ada cara menempelkan file/gambar dari clipboard.

### Perbaikan
- Ubah layout menjadi komposer sticky mirip ChatGPT: area chat `flex-1 overflow-y-auto` dan composer `shrink-0` selalu terlihat di bawah.
- Composer melebar otomatis hingga `max-h-[180px]` saat user mengetik panjang.
- Auto-scroll cerdas: tetap mengikuti pesan baru hanya jika user sudah di bawah; jika user scroll ke atas, tidak memaksa scroll.
- Paste clipboard: event `paste` di level document menangkap file/gambar lalu memasukkannya ke `wire:model="attachments"` lewat `DataTransfer`, sehingga user tinggal Ctrl+V.
- Tombol kirim `Enter`, `Shift+Enter` untuk baris baru.
- Layout dibatasi `max-w-3xl` dengan padding bawah agar mirip chat modern.

### Files
- `src/resources/views/filament/pages/vpoint-assistant.blade.php`
- `src/resources/lang/id/ui.php` / `src/resources/lang/en/ui.php` untuk label `paste_hint`.

## Addendum 2026-06-28-C — Structured Reasoning & Quick Replies

### Istilah Fitur
- Fitur ini disebut **structured reasoning + quick replies**.
- Structured reasoning meminta model menyusun Goals, Constraints, Context, Intent, Plan, Tools, lalu Response.
- Quick replies adalah opsi tindak lanjut yang dihasilkan model agar user bisa klik pilihan tanpa mengetik ulang.

### Implementasi
- `InternalChatbotService` menginstruksikan model untuk menambahkan blok reasoning internal dan section `Selanjutnya`.
- Service mem-parse output AI:
  - `reply` yang tampil ke user hanya jawaban utama.
  - `reasoning` disimpan di `KonteksJson` untuk audit/debug.
  - `suggested_replies` disimpan di `KonteksJson` dan dikirim ke Livewire.
- `VPointAssistant` menyimpan `suggestedReplies` sebagai state Livewire.
- UI menampilkan quick reply chips di atas composer floating.
- Klik chip mengisi textarea, user tetap bisa edit sebelum menekan send.

### Acceptance Criteria
- Jawaban AI tidak menampilkan Goals/Constraints/Plan ke user.
- Opsi tindak lanjut tampil sebagai chips setelah AI menjawab.
- Klik chip mengisi input chat.
- History memuat ulang suggested replies terakhir dari `KonteksJson`.
