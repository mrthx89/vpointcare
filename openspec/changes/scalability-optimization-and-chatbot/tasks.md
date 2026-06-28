# Tasks: Scalability Optimization & Internal Chatbot

> Status Update: Implementasi kode sudah selesai dan tervalidasi pada 2026-06-28. Item implementasi utama ditandai selesai di ringkasan ini. Item deployment/infra production seperti install Redis, menjalankan migration SQL Server production, menjalankan queue worker production, dan browser test real device tetap perlu dilakukan saat publish.

## Implementation Status Summary

- [x] Async webhook processing dibuat melalui `ProcessWebhookJob`.
- [x] AI auto-reply dipindahkan ke `ProcessAiAutoReplyJob`.
- [x] Broadcast Reverb dibuat debounce melalui `SendBroadcastDebouncedJob`.
- [x] Browser Echo listener dibuat debounce 300ms.
- [x] Webhook route diberi throttle `100,1`.
- [x] `WahaSender` diberi circuit breaker.
- [x] `WahaChatHelper` dibuat untuk normalisasi WAHA ID.
- [x] `SchemaCache` dibuat untuk cache `Schema::hasColumn()` dan `Schema::hasTable()`.
- [x] `AiSettings` dibuat untuk cache `MPengaturanAi`.
- [x] Dashboard query utama dioptimasi memakai SQL aggregation.
- [x] Migration composite indexes dibuat.
- [x] Migration `TChatbotInternal` dibuat.
- [x] Model `ChatbotMessage` dibuat.
- [x] `InternalChatbotService` dibuat dengan RAG ringan dari `MPengetahuan`.
- [x] Page `VPointAssistant` dibuat di Filament.
- [x] Blade view `vpoint-assistant` dibuat.
- [x] Multilanguage ID/EN ditambahkan untuk chatbot dan scalability messages.
- [x] `src/.env.example` disiapkan untuk Redis production.
- [x] `src/Dockerfile` ditambah ekstensi PHP Redis.
- [x] `src/docker-compose.yml` ditambah service Redis dan dedicated queue workers.
- [x] `deploy-update-server.bat` disesuaikan untuk rebuild image, Redis, dedicated workers, migrate, optimize, dan health check baru.
- [x] `DATABASE_SCHEMA_WACS.sql` ditambah referensi tabel/index baru.
- [x] Validasi `php -l` semua file berubah sukses.
- [x] Validasi `npm run build` sukses.
- [x] Validasi route chatbot dan webhook sukses.
- [x] Validasi translation key sukses.
- [x] Validasi `php artisan test` sukses.

## Deployment / Manual Verification Pending

- [ ] Install dan aktifkan Redis di server production/Docker.
- [ ] Update `.env` production sesuai Redis dan queue worker.
- [ ] Backup database SQL Server production.
- [ ] Jalankan `php artisan migrate --force` di production.
- [ ] Jalankan queue worker untuk `webhooks`, `ai-replies`, `broadcasts`, dan `default`.
- [ ] Jalankan browser test VPoint Assistant di locale `id` dan `en`.
- [ ] Jalankan webhook test dengan payload WAHA asli.
- [ ] Monitor queue depth, Redis memory, dan log WAHA setelah publish.

---

## FASE A ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â SCALABILITY OPTIMIZATION

### A0. Pre-Implementation Decisions

- [ ] Putuskan apakah Redis digunakan untuk cache, queue, session, dan broadcast, atau hanya subset.
- [ ] Putuskan fallback strategy jika Redis down: kembali ke database driver otomatis atau gagal.
- [ ] Putuskan debounce delay untuk broadcast: 300ms, 500ms, atau 1000ms.
- [ ] Putuskan rate limit threshold untuk webhook: 100 req/menit, 200 req/menit, atau value lain.
- [ ] Putuskan circuit breaker threshold WahaSender: 3, 5, atau 10 consecutive failures.
- [ ] Putuskan retention period log tables: 30, 60, atau 90 hari.
- [ ] Putuskan apakah dashboard pakai materialized summary table atau query aggregation biasa.
- [ ] Putuskan apakah inbox pagination pakai cursor-based atau offset-based.
- [ ] Putuskan nama queue: `webhooks`, `ai-replies`, `broadcasts`, `default`, `low`.

### A1. Audit Existing Code

- [ ] Audit `WahaWebhookController` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â rangkai seluruh synchronous flow dan ukur estimasi waktu.
- [ ] Audit `WahaWebhookProcessor` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â hitung jumlah DB queries per webhook request.
- [ ] Audit `AiAutoReplyService` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â hitung HTTP calls, DB writes, dan timeout scenarios.
- [ ] Audit `WahaSender` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â identifikasi semua HTTP call points dan failure modes.
- [ ] Audit `Dashboard::loadDashboard()` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â ukur data volume dan memory footprint.
- [ ] Audit `ChatBelumTerbalasNotifier::unansweredChats()` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â ukur query complexity.
- [ ] Audit `InboxWhatsapp::loadInbox()` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â ukur query count dan data volume per request.
- [ ] Audit semua `Schema::hasColumn()` calls ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â hitung total per request lifecycle.
- [ ] Audit semua `DB::table('MPengaturanAi')` calls ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â hitung frequency per request.
- [ ] Audit duplicate `normalizeWahaChatId()` di 3 file berbeda.
- [ ] Audit duplicate `latestIncomingWahaChatId()` di `AiAutoReplyService` dan `InboxWhatsapp`.
- [ ] Audit `config/queue.php` dan `config/cache.php` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â konfigurasi Redis existing.
- [ ] Audit `config/reverb.php` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â konfigurasi scaling existing.

### A2. Aktifkan Redis

- [ ] Update `config/cache.php` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â pastikan store `redis` terkonfigurasi dengan benar.
- [ ] Update `config/queue.php` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â pastikan connection `redis` terkonfigurasi.
- [ ] Update `config/database.php` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â pastikan Redis connection benar.
- [ ] Update `config/session.php` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â pertimbangkan session driver ke Redis.
- [ ] Update `.env.example`:
  - `CACHE_STORE=redis`
  - `QUEUE_CONNECTION=redis`
  - `SESSION_DRIVER=redis`
  - `REDIS_HOST=127.0.0.1`
  - `REDIS_PORT=6379`
  - `REDIS_DB=0`
  - `REDIS_CACHE_DB=1`
  - `REVERB_SCALING_ENABLED=true`
- [ ] Tambah fallback driver di `AppServiceProvider` jika Redis tidak tersedia.
- [ ] Test: verify `Cache::store('redis')` berfungsi.
- [ ] Test: verify queue dispatch ke Redis berfungsi.

### A3. Shared WahaChatHelper

- [ ] Buat `src/app/Support/WahaChatHelper.php`.
- [ ] Pindahkan `normalizeChatId(string $id): string` dari 3 sumber.
- [ ] Pindahkan `normalizeContactId(string $id): string`.
- [ ] Pindahkan `toDisplayNumber(string $id): ?string`.
- [ ] Pindahkan `toCUs(string $id): string`.
- [ ] Pindahkan `latestIncomingWahaChatId(string $chatId): ?string`.
- [ ] Pindahkan `resolveLidPhoneNumber(WahaSender $sender, string $session, array $parsed): array`.
- [ ] Pindahkan `expandIdentifiers(array $identifiers): array` dari InboxWhatsapp.
- [ ] Pindahkan `firstWahaId(array $identifiers): ?string` dari InboxWhatsapp.
- [ ] Pindahkan `displayPhoneNumber(array $identifiers): ?string` dari InboxWhatsapp.
- [ ] Update `AiAutoReplyService` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â gunakan `WahaChatHelper`.
- [ ] Update `WahaWebhookProcessor` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â gunakan `WahaChatHelper`.
- [ ] Update `InboxWhatsapp` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â gunakan `WahaChatHelper`.
- [ ] Test: verify semua normalize logic menghasilkan output yang sama.
- [ ] Test: verify `@s.whatsapp.net` ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ `@c.us` conversion.
- [ ] Test: verify `@lid` resolution.
- [ ] Test: verify nomor tanpa `@` di-convert ke format `62xxxxxxxxx@c.us`.

### A4. Cache Schema::hasColumn()

- [ ] Buat helper method di `AppServiceProvider` atau `WahaChatHelper`:
  - `SchemaCheck::hasColumn(string $table, string $column): bool`
  - Gunakan `Cache::rememberForever()` dengan key `schema:{$table}:{$column}`
- [ ] Update `WahaWebhookProcessor` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â ganti semua `Schema::hasColumn()` calls.
- [ ] Update `ChatInitiationService` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â ganti semua `Schema::hasColumn()` calls.
- [ ] Update `ChatBelumTerbalasNotifier` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â ganti `Schema::hasColumn()` calls.
- [ ] Update `AiAutoReplyService` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â ganti `Schema::hasColumn()` calls.
- [ ] Tambah cache clear logic saat migration dijalankan:
  - `php artisan migrate` ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ `Cache::tags(['schema'])->flush()` atau manual forget
- [ ] Test: verify `SchemaCheck::hasColumn()` mengembalikan nilai yang benar.
- [ ] Test: verify cache dihit setelah call pertama.

### A5. Cache MPengaturanAi Settings

- [ ] Buat helper method `App\Support\AiSettings::get(): ?object`:
  - `Cache::remember('mpengaturan_ai_default', 300, fn() => DB::table(...) )`
- [ ] Update `AiAutoReplyService::settings()` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â gunakan `AiSettings::get()`.
- [ ] Update `AiKnowledgeLearningService::settings()` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â gunakan `AiSettings::get()`.
- [ ] Update `ChatBelumTerbalasNotifier` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â gunakan `AiSettings::get()`.
- [ ] Tambah cache invalidation di Filament resource MPengaturanAi:
  - `Cache::forget('mpengaturan_ai_default')` setelah save
- [ ] Test: verify settings di-cache setelah query pertama.
- [ ] Test: verify cache invalidation setelah update.

### A6. Async Webhook Processing (KRITIS)

- [ ] Buat `src/app/Jobs/ProcessWebhookJob.php`:
  - Implements `ShouldQueue`
  - Constructor: `$payloadId: string`, `$payload: array`
  - Timeout: 60 detik, tries: 3
  - Queue: `webhooks`
  - Handle: pindahkan seluruh logic dari `WahaWebhookProcessor::process()`
  - Error handling: update `TLogWebhookWaha` dengan error
- [ ] Buat `src/app/Jobs/ProcessAiAutoReplyJob.php`:
  - Implements `ShouldQueue`
  - Constructor: `$chatId: string`
  - Timeout: 90 detik, tries: 2
  - Queue: `ai-replies`
  - Handle: deduplication check (cek apakah CS sudah reply manual), lalu panggil `AiAutoReplyService::handleIncomingChat()`
  - Error handling: log error, update `TAiPermintaan` status
- [ ] Update `WahaWebhookController`:
  - Hanya validasi token + HMAC
  - Insert lightweight log ke `TLogWebhookWaha`
  - Dispatch `ProcessWebhookJob::dispatch()`
  - Return response 200 segera
- [ ] Update `ProcessWebhookJob` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â setelah processing:
  - Dispatch `ProcessAiAutoReplyJob::dispatch($chatId)` jika ada chat_id
  - Dispatch `SendBroadcastDebouncedJob::dispatch($chatId)`
- [ ] Test: verify webhook response < 500ms.
- [ ] Test: verify ProcessWebhookJob diproses oleh worker.
- [ ] Test: verify AI reply diproses terpisah dari webhook.
- [ ] Test: verify deduplication ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â jika CS reply manual saat AI job waiting, AI skip.
- [ ] Test: verify retry ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â jika AI provider timeout, job di-retry.

### A7. Debounced Broadcast

- [ ] Buat `src/app/Jobs/SendBroadcastDebouncedJob.php`:
  - Implements `ShouldQueue`
  - Constructor: `$chatId: string`
  - Delay: 500ms dari dispatch
  - Queue: `broadcasts`
  - Handle:
    - Check flag `Cache::get("broadcast:pending:{$chatId}")`
    - Jika flag tidak ada, skip (sudah broadcast)
    - Jika flag ada, pull flag, broadcast event
  - Method static `dispatchDebounced(string $chatId)`: set flag + dispatch job
- [ ] Update `ProcessWebhookJob` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â ganti `broadcast()` dengan `SendBroadcastDebouncedJob::dispatchDebounced($chatId)`.
- [ ] Update `ChatInitiationService` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â ganti `broadcast()` dengan debounce pattern.
- [ ] Update JavaScript di inbox view:
  - Tambah debounce 300ms pada Echo listener `inbox.updated`
  - Gunakan `setTimeout` dan `clearTimeout` pattern
- [ ] Test: verify burst 10 pesan dalam 1 detik menghasilkan 1-2 broadcast, bukan 10.
- [ ] Test: verify inbox tetap update secara real-time.
- [ ] Test: verify tidak ada missed updates.

### A8. Rate Limiting Webhook

- [ ] Update `routes/web.php`:
  - Tambah middleware `throttle:100,1` pada webhook route
  - Atau buat custom rate limiter di `AppServiceProvider`:
    ```php
    RateLimiter::for('webhooks', function (Request $request) {
        return Limit::perMinute(100)->by($request->ip());
    });
    ```
- [ ] Test: verify 101st request dalam 1 menit mendapat 429 Too Many Requests.
- [ ] Test: verify normal request < 100/menit tetap berfungsi.

### A9. Circuit Breaker WahaSender

- [ ] Update `WahaSender`:
  - Tambah static counter `$consecutiveFailures`
  - Tambah static `$circuitOpenUntil`
  - Di `postJson()`:
    - Cek apakah circuit breaker aktif (check `$circuitOpenUntil`)
    - Jika aktif, return error tanpa HTTP call
    - Jika request gagal, increment counter
    - Jika counter >= 5, buka circuit selama 2 menit
    - Jika request sukses, reset counter
  - Tambah method `isCircuitOpen(): bool`
  - Tambah method `resetCircuit(): void`
- [ ] Tambah logging saat circuit breaker activate/deactivate.
- [ ] Test: verify circuit breaker buka setelah 5 failures.
- [ ] Test: verify request diblokir saat circuit open.
- [ ] Test: verify circuit auto-reset setelah cooldown.

### A10. Optimasi Dashboard Query

- [ ] Rewrite `Dashboard::loadDashboard()` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â gunakan DB aggregation:
  ```sql
  SELECT
    COUNT(*) as total_messages,
    SUM(CASE WHEN ArahPesan = 'Masuk' THEN 1 ELSE 0 END) as incoming,
    SUM(CASE WHEN ArahPesan = 'Keluar' AND DihasilkanOlehAi = 1 THEN 1 ELSE 0 END) as ai_replies,
    SUM(CASE WHEN ArahPesan = 'Keluar' AND (DihasilkanOlehAi = 0 OR DihasilkanOlehAi IS NULL) THEN 1 ELSE 0 END) as cs_replies,
    COUNT(DISTINCT IdChat) as unique_chats,
    SUM(CASE WHEN StatusKirim LIKE 'Terkirim%' THEN 1 ELSE 0 END) as sent,
    SUM(CASE WHEN StatusKirim LIKE 'Gagal%' THEN 1 ELSE 0 END) as failed
  FROM TChatD
  WHERE TglPesan BETWEEN @start AND @end
  ```
- [ ] Rewrite `teamRows()` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â gunakan DB aggregation di SQL.
- [ ] Rewrite `dailyTrend()` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â gunakan `GROUP BY DATE(TglPesan)`.
- [ ] Rewrite `topClients()` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â sudah OK (menggunakan GROUP BY).
- [ ] Pertimbangkan caching summary per date range:
  - `Cache::remember("dashboard:{$start}:{$end}", 60, fn() => ...)`
- [ ] Test: verify data accuracy match dengan query lama.
- [ ] Test: verify load time < 2 detik untuk 30 hari data.
- [ ] Test: verify memory usage < 50MB (vs sebelumnya 200MB+).

### A11. Composite Database Indexes

- [ ] Tambah index untuk duplicate message check:
  ```sql
  CREATE INDEX IX_TChatD_IdPesanWaha_Partial
  ON TChatD (IdPesanWaha)
  WHERE IdPesanWaha IS NOT NULL;
  ```
- [ ] Tambah index untuk unanswered chats query:
  ```sql
  CREATE INDEX IX_TChatD_Arah_Dikirim_Tgl
  ON TChatD (ArahPesan, DikirimOlehCustomer, TglPesan)
  INCLUDE (IdChat, IsiPesan);
  ```
- [ ] Tambah index untuk AI reply decision:
  ```sql
  CREATE INDEX IX_TChatD_AiReply_Check
  ON TChatD (IdChat, ArahPesan, DihasilkanOlehAi, TglPesan);
  ```
- [ ] Tambah index untuk dashboard aggregation:
  ```sql
  CREATE INDEX IX_TChatD_TglPesan_Arah
  ON TChatD (TglPesan)
  INCLUDE (IdChat, ArahPesan, DihasilkanOlehAi, StatusKirim);
  ```
- [ ] Tambah index untuk chatbot history:
  ```sql
  CREATE INDEX IX_TChatbotInternal_Pengguna_Tgl
  ON TChatbotInternal (IdPengguna, TglBuat DESC);
  ```
- [ ] Jalankan `EXPLAIN` atau query execution plan untuk verify index usage.

### A12. Queue Worker Strategy

- [ ] Buat artisan command `vpoint:workers` atau shell script untuk menjalankan multiple workers:
  ```
  php artisan queue:work --queue=webhooks --timeout=60 --sleep=1 --tries=3
  php artisan queue:work --queue=ai-replies --timeout=90 --sleep=1 --tries=2
  php artisan queue:work --queue=broadcasts,default --timeout=30 --sleep=1
  ```
- [ ] Update deployment script ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â jalankan workers sebagai supervised process.
- [ ] Tambah monitoring script ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â cek queue depth:
  ```
  php artisan queue:monitor redis:webhooks,ai-replies,broadcasts,default --max=100
  ```
- [ ] Update `deploy-update-server.bat` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â tambah restart workers.

### A13. Integration Testing ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â Fase A

- [ ] Test full webhook flow end-to-end:
  - POST webhook ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ response 200 < 500ms
  - Verify ProcessWebhookJob berjalan di queue
  - Verify TChat + TChatD terisi
  - Verify ProcessAiAutoReplyJob ter-dispatch
  - Verify AI reply terkirim
  - Verify broadcast ke browser
  - Verify inbox update di browser
- [ ] Test burst scenario: kirim 50 webhooks dalam 10 detik.
- [ ] Test AI provider timeout scenario.
- [ ] Test WAHA down scenario (circuit breaker).
- [ ] Test Redis down scenario (fallback ke database).
- [ ] Test dashboard load dengan data volume tinggi.
- [ ] Test inbox load dengan 500+ active chats.

---

## FASE B ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â INTERNAL CHATBOT (VPoint Assistant)

### B0. Pre-Implementation Decisions

- [ ] Putuskan permission: pakai `dashboard.view` existing atau buat `chatbot.access`.
- [ ] Putuskan riwayat disimpan berapa lama: unlimited, 30 hari, atau 100 pesan per user.
- [ ] Putuskan max context messages untuk AI: 10, 20, atau 30 pesan.
- [ ] Putuskan model AI yang dipakai: same as auto-reply atau model khusus chatbot.
- [ ] Putuskan apakah chatbot punya halaman sendiri atau integrated di sidebar.
- [ ] Putuskan apakah perlu "new conversation" button atau single continuous thread.
- [ ] Putuskan max tokens per response: 500, 1000, 2000.

### B1. Database Migration

- [ ] Buat migration SQL Server untuk tabel `TChatbotInternal`:
  ```sql
  CREATE TABLE TChatbotInternal (
      Id uniqueidentifier NOT NULL DEFAULT NEWSEQUENTIALID(),
      IdPengguna uniqueidentifier NOT NULL,
      PeranPengirim varchar(20) NOT NULL,
      IsiPesan nvarchar(max) NOT NULL,
      IdAiRespon uniqueidentifier NULL,
      KonteksJson nvarchar(max) NULL,
      TglBuat datetime2 NOT NULL DEFAULT SYSDATETIME(),
      CONSTRAINT PK_TChatbotInternal PRIMARY KEY (Id),
      CONSTRAINT FK_TChatbotInternal_MPengguna FOREIGN KEY (IdPengguna) REFERENCES MPengguna(Id),
      CONSTRAINT CK_TChatbotInternal_Peran CHECK (PeranPengirim IN ('user', 'assistant'))
  );
  CREATE INDEX IX_TChatbotInternal_Pengguna_Tgl ON TChatbotInternal (IdPengguna, TglBuat DESC);
  ```
- [ ] Buat migration idempotent dengan `IF OBJECT_ID(...)` pattern.
- [ ] Tambah ke `DATABASE_SCHEMA_WACS.sql`.
- [ ] Test migration forward dan rollback.

### B2. Model

- [ ] Buat `src/app/Models/ChatbotMessage.php`:
  - Table: `TChatbotInternal`
  - Use `UsesSqlServerUuid` trait
  - Guarded: `['Id']`
  - Casts: `TglBuat => datetime`, `KonteksJson => array`
  - Relation: `belongsTo(Pengguna::class, 'IdPengguna')`
  - Constants: `PERAN_USER = 'user'`, `PERAN_ASSISTANT = 'assistant'`
- [ ] Test: verify model bisa read/write ke tabel.

### B3. InternalChatbotService

- [ ] Buat `src/app/Services/Ai/InternalChatbotService.php`.
- [ ] Method `ask(string $userId, string $message): array`:
  - Load conversation history (last 20 messages)
  - Search knowledge base (`MPengetahuan`) berdasarkan keyword dari message
  - Build system prompt dengan:
    - User info (name, role)
    - Knowledge context (RAG)
    - Instructions (Bahasa Indonesia, jujur, format markdown)
  - Save user message ke `TChatbotInternal`
  - Call AI provider (reuse logic dari `AiAutoReplyService`)
  - Save assistant response ke `TChatbotInternal`
  - Return result
- [ ] Method `conversationHistory(string $userId): Collection`:
  - Query last 20 messages dari `TChatbotInternal`
  - Ordered by `TglBuat` ASC
  - Filter by `IdPengguna`
- [ ] Method `searchKnowledge(string $query): array`:
  - Split query jadi keywords
  - Search `MPengetahuan` WHERE `NonAktif = false`
  - Match against `JudulPengetahuan`, `IsiPengetahuan`, `Tag`, `SearchKeywords`
  - Order by `PrioritasAi` DESC
  - Limit 5 results
- [ ] Method `buildSystemPrompt(array $knowledge, string $userId): string`:
  - Include user name dan role
  - Include knowledge context
  - Include instructions untuk behavior
- [ ] Method `callProvider(object $settings, array $messages): string`:
  - Reuse provider dispatch logic dari `AiAutoReplyService`
  - Support OpenAI, DeepSeek, OpenRouter, NineRouter
  - Timeout: 30 detik
- [ ] Method `clearHistory(string $userId): int`:
  - Delete semua `TChatbotInternal` untuk user
  - Return jumlah deleted
- [ ] Reuse `AiSettings::get()` dari Fase A untuk settings.
- [ ] Reuse API key resolver dari Fase A.
- [ ] Sanitasi: tidak menyimpan API key di response/error.

### B4. Filament Page

- [ ] Buat `src/app/Filament/Pages/VPointAssistant.php`:
  - Properties:
    - `$userMessage: string`
    - `$messages: array`
    - `$isTyping: bool`
  - `getNavigationIcon()`: `heroicon-o-chat-bubble-bottom-center-text`
  - `getNavigationGroup()`: operational group
  - `getNavigationLabel()`: `'VPoint Assistant'`
  - `getTitle()`: `'VPoint Assistant'`
  - `canAccess()`: check permission
  - `mount()`: load history
  - `sendMessage()`: validate input, call service, update UI
  - `clearHistory()`: delete history, reset UI
  - `loadHistory()`: load from DB
- [ ] Tambah localization keys di `lang/id/ui.php` dan `lang/en/ui.php`.
- [ ] Tambah navigation sort value yang tepat.
- [ ] Tambah breadcrumbs.

### B5. Blade View

- [ ] Buat `src/resources/views/filament/pages/vpoint-assistant.blade.php`:
  - Chat container dengan overflow scroll
  - Empty state dengan icon dan welcome message
  - Message bubbles:
    - User: kanan, primary color, rounded
    - Assistant: kiri, white/dark, border, markdown render
  - Knowledge source tags di bawah assistant message
  - Timestamp per message
  - Typing indicator (3 bouncing dots)
  - Input area:
    - Text input dengan placeholder
    - Send button (disabled saat loading)
    - Clear history button dengan confirmation dialog
  - Wire:submit.prevent on form
  - x-data untuk auto-scroll ke bawah
  - Dark mode support
  - Responsive untuk desktop dan tablet
- [ ] Test: verify rendering di browser.
- [ ] Test: verify dark mode.
- [ ] Test: verify responsive.

### B6. Permission dan Localization

- [ ] Tambah `AccessPermissions::CHATBOT_ACCESS = 'chatbot.access'` (opsional, atau reuse `dashboard.view`).
- [ ] Update `lang/id/ui.php` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â tambah keys:
  - `ui.pages.chatbot.title`
  - `ui.pages.chatbot.navigation_label`
  - `ui.pages.chatbot.placeholder`
  - `ui.pages.chatbot.empty_state`
  - `ui.pages.chatbot.clear_confirm`
  - `ui.pages.chatbot.typing`
  - `ui.pages.chatbot.error`
- [ ] Update `lang/en/ui.php` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â tambah English equivalents.
- [ ] Test: verify tidak ada hardcoded string di UI.

### B7. Integration Testing ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â Fase B

- [ ] Test: buka halaman VPoint Assistant, verify empty state.
- [ ] Test: kirim pesan "Apa itu VPoint Care?", verify AI response.
- [ ] Test: verify knowledge base digunakan (ada tag di bawah response).
- [ ] Test: kirim pertanyaan lanjutan, verify context/history dipertahankan.
- [ ] Test: verify riwayat tersimpan di database.
- [ ] Test: refresh halaman, verify riwayat masih ada.
- [ ] Test: clear history, verify semua terhapus.
- [ ] Test: verify error handling jika AI provider down.
- [ ] Test: verify chatbot tidak mengirim ke WhatsApp.
- [ ] Test: verify permission ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â user tanpa akses tidak bisa buka halaman.
- [ ] Test: verify 2 user berbeda punya history terpisah.
- [ ] Test: verify role-aware response (CS vs Admin).
- [ ] Test: verify responsive layout di desktop dan tablet.
- [ ] Test: verify dark mode.

---

## FASE C ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â DOKUMENTASI & DEPLOYMENT

### C1. Documentation

- [ ] Update `openspec/project.md` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â tambah referensi ke change ini.
- [ ] Update `openspec/specs/vpoint-care/spec.md` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â tambah requirements baru.
- [ ] Update `README.md`:
  - Tambah section Redis setup
  - Tambah section queue worker strategy
  - Tambah section VPoint Assistant usage
  - Update architecture diagram
- [ ] Buat `docs/PLAN_SCALABILITY_OPTIMIZATION_CHATBOT.md` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â plan document lengkap.
- [ ] Update `.env.example` ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â tambah semua env vars baru dengan komentar.
- [ ] Update deployment guide (jika ada) dengan:
  - Redis installation steps
  - Queue worker supervisor config
  - Scheduler setup

### C2. Deployment Checklist

- [ ] Backup database sebelum migration.
- [ ] Install Redis di server production.
- [ ] Update `.env` production:
  - `CACHE_STORE=redis`
  - `QUEUE_CONNECTION=redis`
  - `SESSION_DRIVER=redis`
  - Redis credentials
- [ ] Jalankan `php artisan migrate --force`.
- [ ] Jalankan `php artisan config:clear && php artisan optimize`.
- [ ] Jalankan `npm run build`.
- [ ] Restart queue workers.
- [ ] Verify Redis connection.
- [ ] Verify webhook endpoint berfungsi.
- [ ] Verify inbox real-time updates.
- [ ] Verify dashboard load time.
- [ ] Verify VPoint Assistant accessible.
- [ ] Monitor queue depth selama 24 jam pertama.
- [ ] Monitor Redis memory usage.
- [ ] Monitor AI provider usage dan cost.

### B8. Multilanguage Preservation

- [ ] Audit `src/lang/id/ui.php` dan `src/lang/en/ui.php` untuk struktur key existing.
- [ ] Tambahkan semua key UI VPoint Assistant di `src/lang/id/ui.php`.
- [ ] Tambahkan semua key UI VPoint Assistant di `src/lang/en/ui.php`.
- [ ] Tambahkan key notification/error untuk async webhook, AI skipped, dan circuit breaker bila tampil ke user.
- [ ] Ganti semua hardcoded label di `VPointAssistant.php` dengan `__('ui.pages.chatbot....')`.
- [ ] Ganti semua hardcoded label di blade chatbot dengan localization keys.
- [ ] Pastikan nama brand tetap literal bila memang brand (`VPoint Assistant`, `WAHA`, `Redis`).
- [ ] Test locale `id`: semua label tampil Bahasa Indonesia.
- [ ] Test locale `en`: semua label tampil Bahasa Inggris.
- [ ] Pastikan exception technical detail tidak tampil langsung ke user tanpa sanitasi/localization.
