# Change: Scalability Optimization & Internal Chatbot

## Summary

Melakukan optimasi scalabilitas menyeluruh pada WACS (WhatsApp Customer Service) yang mencakup pergeseran dari synchronous ke asynchronous processing, pengaktifan Redis sebagai cache dan queue driver, optimasi query dashboard, caching konfigurasi, serta penambahan fitur baru **VPoint Assistant** — chatbot internal berbasis AI untuk pengguna admin panel yang membantu lookup knowledge, operasional queries, draft reply, dan guidance aplikasi.

Perubahan ini terbagi menjadi dua fase utama:

**Fase A — Scalability Optimization (Infrastruktur)**
Mengatasi bottleneck kritis pada webhook processing synchronous, database contention, dan zero-caching strategy agar sistem mampu menangani volume 10-100x lipat dari kapasitas saat ini.

**Fase B — Internal Chatbot (Fitur Baru)**
Membangun chatbot internal yang memanfaatkan AI provider dan knowledge base yang sudah ada, memberikan asisten digital kepada seluruh tim CS, Supervisor, dan Admin.

## Problem Statement

### Problem 1: Webhook Synchronous Processing (KRITIS)

`WahaWebhookController` memproses seluruh lifecycle dalam satu HTTP request secara sinkron:

1. Insert `TLogWebhookWaha` (write DB)
2. Parse dan resolve customer mapping (multiple queries)
3. Insert/update `TChat` (write DB)
4. Insert `TChatD` (write DB)
5. Resolve LID → phone number (HTTP call ke WAHA)
6. Call AI Auto Reply (HTTP call ke OpenAI/DeepSeek, timeout 45 detik!)
7. Insert `TAiPermintaan` + `TAiRespon` (write DB)
8. Send reply via WAHA (HTTP call)
9. Broadcast event ke Reverb WebSocket

Jika AI provider lambat (misal 10 detik), seluruh request terblok. WAHA biasanya retry setelah timeout, menggandakan pemrosesan. Dengan volume 100 chat/menit, worker akan kehabisan dan aplikasi menjadi tidak responsif.

### Problem 2: Database sebagai Cache & Queue Default

Saat ini `CACHE_STORE=database` dan `QUEUE_CONNECTION=database`. Artinya:
- Setiap auto-reply yang butuh cache settings berkompetisi di DB
- Setiap broadcast event menulis ke DB jobs table
- Semua queue job berkompetisi di tabel `jobs` yang sama dengan transaksi chat
- SQL Server memiliki lock mechanism yang lebih berat dari MySQL

### Problem 3: Dashboard Full Table Scan

`Dashboard::loadDashboard()` mengambil SELURUH data `TChatD` dalam range tanggal ke memory PHP, lalu melakukan filtering via Collection:

```php
$messageRows = DB::table('TChatD')
    ->whereBetween('TglPesan', [$start, $end])
    ->get(); // SELURUH baris dimuat ke RAM

$incomingRows = $messageRows->where('ArahPesan', 'Masuk');
$outgoingRows = $messageRows->where('ArahPesan', 'Keluar');
$aiRows = $outgoingRows->where('DihasilkanOlehAi', true);
```

Untuk 30 hari dengan 50.000 pesan, ini memuat 50K baris ke PHP memory dan iterate 5x untuk setiap kategori. Dengan 30 user buka dashboard bersamaan = 1.5 juta row di RAM.

### Problem 4: Zero Caching pada Data Statis

`MPengaturanAi` diquery di SETIAP webhook masuk. Tabel ini jarang diubah (sekali dikonfigurasi, berbulan-bulan tidak berubah). Begitu juga `Schema::hasColumn()` yang dipanggil multiple kali per request tanpa caching, melakukan schema introspection ke database setiap kali.

### Problem 5: Broadcast Storm

Setiap pesan baru memicu `broadcast(new WahaInboxUpdated($chatId))`. Jika ada 20 pesan masuk dalam 2 detik (burst), 20 WebSocket events dikirim. Browser harus memanggil `loadInbox()` 20 kali, masing-masing melakukan full DB query.

### Problem 6: Duplikasi Code di Hot Path

`normalizeWahaChatId()` dan `latestIncomingWahaChatId()` ada di 3 tempat:
- `AiAutoReplyService`
- `WahaWebhookProcessor` (inline)
- `InboxWhatsapp`

Ketika format WhatsApp ID berubah, harus diubah di 3 tempat. Bug di salah satu lokasi menyebabkan pesan tidak sampai.

### Problem 7: Tidak Ada Rate Limiting Webhook

Endpoint `/webhooks/waha/{token?}` tidak memiliki rate limiting. Jika WAHA mengalami loop, ribuan webhook per detik bisa menghancurkan application server. Tidak ada circuit breaker di `WahaSender` untuk mencegah cascade failure.

### Problem 8: Kebutuhan Internal AI Assistant

Tim CS, Supervisor, dan Admin membutuhkan cara cepat untuk:
- Mencari informasi dari knowledge base
- Mendapatkan statistik operasional ringkas
- Menyusun draft balasan untuk customer
- Mendapatkan guidance tentang fitur aplikasi

Saat ini semua harus dilakukan secara manual melalui UI yang berbeda-beda.

## Current State

### Webhook Flow
- Controller: `src/app/Http/Controllers/Webhook/WahaWebhookController.php`
- Processor: `src/app/Services/Waha/WahaWebhookProcessor.php`
- AI Service: `src/app/Services/Ai/AiAutoReplyService.php`
- WAHA Sender: `src/app/Services/Waha/WahaSender.php`
- Event: `src/app/Events/WahaInboxUpdated.php`
- Semua dipanggil synchronous dalam satu HTTP request

### Queue & Cache
- Default driver: `database` untuk queue dan cache
- Redis sudah dikonfigurasi di `config/database.php` tapi belum diaktifkan
- Satu Queue Worker yang menangani semua jenis job

### Dashboard
- `src/app/Filament/Pages/Dashboard.php`
- Memuat semua data ke memory, filtering di PHP
- Tidak ada caching atau aggregation

### Reverb WebSocket
- Sudah dikonfigurasi di `config/reverb.php`
- Scaling mode: `false` (single server only)
- Channel: public `waha-inbox`

### AI Providers
- OpenAI, DeepSeek, OpenRouter, NineRouter sudah didukung
- Knowledge Base: `MPengetahuan` dengan `SearchKeywords`, `PrioritasAi`
- Draft Learning: `TAiDraftPengetahuan` sudah ada

### Database
- Target: SQL Server
- Dev: SQLite
- Pattern: M* (master), T* (transaksi), UUID primary keys

## Goals

### Fase A — Scalability Optimization

1. **Mengubah webhook processing menjadi asynchronous** — Webhook hanya log & ack, pemrosesan berat dijalankan via queue worker.
2. **Mengaktifkan Redis sebagai cache & queue driver** — Memisahkan beban cache/queue dari database transaksional.
3. **Mengoptimasi dashboard query** — Menggunakan database aggregation alih-alih PHP memory filtering.
4. **Mengcache pengaturan AI** — Menghindari repeated query ke `MPengaturanAi` di setiap webhook.
5. **Mengcache `Schema::hasColumn()`** — Menghindari repeated schema introspection.
6. **Mereduksi broadcast storm** — Debounce broadcast per chat ID.
7. **Menambahkan rate limiting webhook** — Mencegah WAHA loop menghancurkan server.
8. **Menambahkan circuit breaker di WahaSender** — Mencegah cascade failure saat WAHA down.
9. **Deduplicate WahaChatId helper** — Memusatkan logic ke satu shared utility.
10. **Optimasi database indexes** — Menambahkan composite indexes untuk query hot path.

### Fase B — Internal Chatbot (VPoint Assistant)

1. **Membangun chatbot internal** — Chatbot AI untuk pengguna admin panel (CS, Supervisor, Admin).
2. **Knowledge Base RAG** — Chatbot otomatis mencari knowledge relevan dari `MPengetahuan`.
3. **Conversation history** — Menyimpan riwayat percakapan per user.
4. **Filament page** — UI chat modern dengan real-time typing indicator.
5. **Role-aware responses** — Chatbot memberikan jawaban sesuai role user.
6. **Clear history** — User bisa menghapus riwayat percakapan.

## Non-Goals

- Tidak mengganti AI provider atau model existing.
- Tidak mengubah kontrak webhook WAHA atau route existing.
- Tidak mengubah permission structure yang sudah ada.
- Tidak membuat chatbot untuk customer WhatsApp (hanya internal admin panel).
- Tidak mengimplementasikan streaming response (masih synchronous per message).
- Tidak mengimplementasikan voice input di fase ini.
- Tidak membuat perubahan backward-incompatible pada database schema existing.

## Impacted Areas

### Fase A
- `src/app/Http/Controllers/Webhook/WahaWebhookController.php` — Ringankan, tambah dispatch queue
- `src/app/Services/Waha/WahaWebhookProcessor.php` — Pindahkan ke Job
- `src/app/Services/Ai/AiAutoReplyService.php` — Tambah cache settings, pindahkan ke Job
- `src/app/Services/Waha/WahaSender.php` — Tambah circuit breaker
- `src/app/Events/WahaInboxUpdated.php` — Tidak berubah, tapi dispatch timing berubah
- `src/app/Filament/Pages/Dashboard.php` — Rewrite aggregation query
- `src/app/Filament/Pages/InboxWhatsapp.php` — Tambah debounce JS, pagination
- `src/app/Filament/Pages/InboxWhatsapp.php` — Gunakan WahaChatHelper
- `src/app/Support/WahaChatHelper.php` — **Baru** — Shared utility
- `src/app/Jobs/ProcessWebhookJob.php` — **Baru** — Async webhook processing
- `src/app/Jobs/ProcessAiAutoReplyJob.php` — **Baru** — Async AI reply
- `src/app/Jobs/SendBroadcastDebouncedJob.php` — **Baru** — Debounced broadcast
- `src/routes/web.php` — Tambah throttle middleware
- `src/config/queue.php` — Aktifkan Redis driver
- `src/config/cache.php` — Aktifkan Redis driver
- `src/.env.example` — Update default values
- `src/app/Providers/AppServiceProvider.php` — Cache schema checks

### Fase B
- `src/app/Services/Ai/InternalChatbotService.php` — **Baru** — Chatbot service
- `src/app/Filament/Pages/VPointAssistant.php` — **Baru** — Chat page
- `src/resources/views/filament/pages/vpoint-assistant.blade.php` — **Baru** — Chat UI
- `src/app/Support/AccessPermissions.php` — Tambah permission chatbot (opsional)
- `src/script/DATABASE_SCHEMA_WACS.sql` — Tambah `TChatbotInternal` table
- `src/lang/*/ui.php` — Tambah label chatbot
- `src/app/Models/ChatbotMessage.php` — **Baru** — Model untuk chat history

## Risks

### Fase A
- **Queue worker crash**: Jika queue worker mati, webhook terjawab tapi pemrosesan tertunda. Mitigasi: monitoring queue depth, auto-restart worker.
- **Redis down**: Cache miss rate tinggi, query kembali ke DB. Mitigasi: cache driver harus bisa fallback ke database.
- **Broadcast debounce delay**: Update inbox tertunda beberapa ratus milidetik. Mitigasi: delay hanya 300-500ms, masih real-time.
- **Dashboard aggregation stale**: Jika pakai cached summary, data bisa sedikit tertunda. Mitigasi: summary diupdate setiap pesan baru.
- **AI Job duplicate**: Jika webhook retry, bisa ada 2 AI job untuk chat yang sama. Mitigasi: deduplication check di job handler.

### Fase B
- **AI cost**: Setiap chat menghabiskan token. Mitigasi: limit history, limit knowledge context, gunakan model hemat.
- **Hallucination**: AI bisa menjawab tidak akurat. Mitigasi: ground with knowledge base, disclaimer, role-aware guardrails.
- **Data sensitivity**: Chat internal bisa mengandung info sensitif. Mitigasi: tidak store ke cloud, history hanya local DB.
- **Performance**: Banyak user chat bersamaan bisa membebani AI provider. Mitigasi: queue AI requests, rate limit per user.

## Rollout Strategy

### Fase A — Scalability (Minggu 1-2)
1. Aktifkan Redis (cache + queue) — 0.5 hari
2. Buat shared `WahaChatHelper` — 0.5 hari
3. Tambah `Schema::hasColumn` cache — 0.5 hari
4. Cache `MPengaturanAi` settings — 0.5 hari
5. Buat ProcessWebhookJob dan ProcessAiAutoReplyJob — 1 hari
6. Buat SendBroadcastDebouncedJob — 0.5 hari
7. Tambah rate limiting webhook — 0.5 hari
8. Tambah circuit breaker WahaSender — 0.5 hari
9. Optimasi dashboard query — 1 hari
10. Tambah composite database indexes — 0.5 hari
11. Test end-to-end — 1 hari

### Fase B — Internal Chatbot (Minggu 3)
1. Buat migration TChatbotInternal — 0.5 hari
2. Buat ChatbotMessage model — 0.5 hari
3. Buat InternalChatbotService — 1 hari
4. Buat VPointAssistant Filament page — 1 hari
5. Buat Blade view chat UI — 0.5 hari
6. Tambah permission dan localization — 0.5 hari
7. Test end-to-end — 0.5 hari

## Acceptance Criteria

### Fase A
- Webhook endpoint merespons dalam < 500ms untuk semua request
- AI auto-reply diproses via queue, tidak memblok webhook
- Redis berfungsi sebagai cache dan queue driver
- Dashboard load time < 2 detik untuk range 30 hari
- `MPengaturanAi` tidak diquery ulang dalam 5 menit setelah cache
- `Schema::hasColumn` tidak menghasilkan query DB setelah cold start
- Broadcast storm berkurang 90% dengan debounce
- Webhook rate limit aktif (100 req/min)
- Circuit breaker WAHA aktif setelah 5 consecutive failures
- Semua query hot path memiliki composite index yang memadai
- Semua test existing tetap passing

### Fase B
- Pengguna admin panel bisa membuka halaman VPoint Assistant
- Pengguna bisa mengirim pesan dan menerima jawaban AI
- Chatbot menggunakan knowledge base dari `MPengetahuan` (RAG)
- Riwayat percakapan tersimpan per user
- Chatbot tahu role user dan memberikan jawaban sesuai
- User bisa menghapus riwayat chat
- Chatbot tidak mengirim pesan ke WhatsApp atau customer
- Error handling untuk AI provider failure
- Responsive UI untuk desktop dan tablet
