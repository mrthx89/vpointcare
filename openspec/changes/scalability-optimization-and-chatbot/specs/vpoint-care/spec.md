## ADDED Requirements

### Requirement: Asynchronous Webhook Processing

Sistem SHALL memproses webhook WAHA secara asynchronous untuk mencegah timeout dan blocking request.

#### Scenario: Webhook diterima dengan payload valid

- **GIVEN** WAHA mengirim webhook dengan payload valid
- **WHEN** sistem menerima request di endpoint `/webhooks/waha/{token?}`
- **THEN** sistem SHALL memvalidasi token dan HMAC dalam < 100ms
- **AND** sistem SHALL menyimpan log webhook ke `TLogWebhookWaha`
- **AND** sistem SHALL me-return HTTP 200 segera
- **AND** sistem SHALL dispatch `ProcessWebhookJob` ke queue `webhooks`
- **AND** `ProcessWebhookJob` SHALL memproses parsing, resolve customer, insert chat, dan dispatch AI reply

#### Scenario: Queue worker sibuk

- **GIVEN** queue `webhooks` memiliki antrian
- **WHEN** webhook baru masuk
- **THEN** webhook tetap direspon 200 tanpa menunggu antrian
- **AND** job akan diproses bergiliran oleh worker

### Requirement: Redis sebagai Cache & Queue Driver

Sistem SHALL menggunakan Redis sebagai driver cache, queue, dan session untuk mengurangi beban database.

#### Scenario: Cache settings AI

- **GIVEN** `CACHE_STORE=redis`
- **WHEN** sistem membutuhkan `MPengaturanAi`
- **THEN** sistem SHALL membaca dari Redis cache terlebih dahulu
- **AND** jika cache kosong, query ke database dan simpan ke Redis selama 300 detik
- **AND** saat admin mengubah pengaturan AI, cache SHALL di-invalidate

#### Scenario: Cache Schema::hasColumn

- **GIVEN** sistem berjalan di production
- **WHEN** service memanggil `Schema::hasColumn()`
- **THEN** sistem SHALL membaca dari Redis cache
- **AND** setelah migration, cache SHALL di-clear

#### Scenario: Queue via Redis

- **GIVEN** `QUEUE_CONNECTION=redis`
- **WHEN** job di-dispatch
- **THEN** job SHALL masuk ke Redis queue, bukan tabel database `jobs`

### Requirement: Dedicated Queue Workers

Sistem SHALL memiliki multiple queue workers terpisah untuk task yang berbeda.

#### Scenario: Webhook worker sibuk

- **GIVEN** queue `webhooks` penuh
- **WHEN** ada AI reply job
- **THEN** AI reply worker pada queue `ai-replies` SHALL tetap memproses tanpa terpengaruh antrian webhook

#### Scenario: Queue assignment

- **GIVEN** job baru di-dispatch
- **WHEN** job adalah ProcessWebhookJob
- **THEN** job SHALL masuk ke queue `webhooks`
- **WHEN** job adalah ProcessAiAutoReplyJob
- **THEN** job SHALL masuk ke queue `ai-replies`
- **WHEN** job adalah SendBroadcastDebouncedJob
- **THEN** job SHALL masuk ke queue `broadcasts`

### Requirement: Debounced Broadcast

Sistem SHALL mereduksi broadcast WebSocket untuk mencegah overload browser dan database.

#### Scenario: Burst pesan masuk

- **GIVEN** 10 pesan masuk dalam 1 detik untuk chat yang berbeda
- **WHEN** setiap pesan diproses
- **THEN** sistem SHALL hanya mengirim 1-2 broadcast total
- **AND** setiap browser SHALL menerima update dalam < 1 detik dari pesan pertama

#### Scenario: Broadcast per chat ID

- **GIVEN** `SendBroadcastDebouncedJob` di-dispatch
- **WHEN** job dijalankan
- **THEN** sistem SHALL cek flag `broadcast:pending:{chatId}`
- **AND** jika flag tidak ada, skip broadcast
- **AND** jika flag ada, hapus flag dan kirim broadcast

#### Scenario: Browser debounce

- **GIVEN** browser menerima event `inbox.updated`
- **WHEN** event diterima
- **THEN** browser SHALL men-debounce 300ms sebelum memanggil `loadInbox()`
- **AND** multiple event dalam 300ms hanya trigger satu kali load

### Requirement: Circuit Breaker WahaSender

Sistem SHALL memiliki circuit breaker untuk WAHA API call.

#### Scenario: WAHA server down

- **GIVEN** WAHA server tidak merespons
- **WHEN** `WahaSender` gagal 5 kali berturut-turut
- **THEN** circuit breaker SHALL terbuka selama 2 menit
- **AND** semua request WAHA SHALL langsung return error tanpa HTTP call
- **AND** sistem SHALL log "WAHA circuit breaker opened"
- **AND** setelah 2 menit, request berikutnya SHALL dicoba kembali

#### Scenario: WAHA pulih

- **GIVEN** circuit breaker terbuka
- **WHEN** 2 menit berlalu dan request baru masuk
- **THEN** sistem SHALL mencoba request WAHA
- **AND** jika sukses, circuit breaker SHALL ditutup dan counter direset

### Requirement: Rate Limiting Webhook

Endpoint webhook SHALL memiliki rate limiting.

#### Scenario: Rate limit terlampaui

- **GIVEN** lebih dari 100 request webhook dalam 1 menit
- **WHEN** request ke-101 masuk
- **THEN** sistem SHALL return HTTP 429 Too Many Requests
- **AND** WAHA SHALL menerima rate limit notification

### Requirement: Optimasi Dashboard Query

Dashboard SHALL menggunakan database aggregation untuk menghindari memory overload.

#### Scenario: Dashboard load dengan data 30 hari

- **GIVEN** ada 50.000+ pesan dalam 30 hari
- **WHEN** user membuka dashboard
- **THEN** sistem SHALL menggunakan SQL aggregation (`SUM`, `COUNT`, `GROUP BY`)
- **AND** PHP memory usage SHALL < 50MB
- **AND** load time SHALL < 2 detik

#### Scenario: Daily trend

- **GIVEN** user memilih range tanggal
- **WHEN** dashboard menampilkan daily trend
- **THEN** data SHALL di-grouping by tanggal di SQL, bukan di PHP

### Requirement: Shared WahaChatHelper

Sistem SHALL memiliki utility class terpusat untuk normalize WhatsApp ID.

#### Scenario: Normalize chat ID dari 3 sumber berbeda

- **GIVEN** `AiAutoReplyService`, `WahaWebhookProcessor`, dan `InboxWhatsapp` membutuhkan normalize
- **WHEN** mereka memanggil normalize
- **THEN** semua SHALL menggunakan `WahaChatHelper::normalizeChatId()`
- **AND** jika format WhatsApp ID berubah, cukup update 1 file

#### Scenario: Resolve LID phone number

- **GIVEN** webhook menerima JID dengan `@lid`
- **WHEN** processor memanggil helper
- **THEN** `WahaChatHelper::resolveLidPhoneNumber()` SHALL dipanggil
- **AND** hasil SHALL konsisten di semua service

### Requirement: Composite Database Indexes

Sistem SHALL memiliki composite indexes yang memadai untuk query hot path.

#### Scenario: Duplicate message check

- **GIVEN** webhook processor mengecek duplicate pesan
- **WHEN** query `WHERE IdPesanWaha = ?` dijalankan
- **THEN** index `IX_TChatD_IdPesanWaha_Partial` SHALL digunakan

#### Scenario: Unanswered chats query

- **GIVEN** `ChatBelumTerbalasNotifier` mencari chat belum terbalas
- **WHEN** query `WHERE ArahPesan = 'Masuk' AND DikirimOlehCustomer = 1` dijalankan
- **THEN** index `IX_TChatD_Arah_Dikirim_Tgl` SHALL digunakan

### Requirement: VPoint Assistant — Internal Chatbot

Sistem SHALL menyediakan chatbot internal untuk pengguna admin panel (VPoint Assistant).

#### Scenario: User membuka VPoint Assistant

- **GIVEN** user memiliki akses
- **WHEN** user membuka halaman VPoint Assistant
- **THEN** sistem SHALL menampilkan welcome screen dengan icon dan keterangan
- **AND** sistem SHALL menampilkan riwayat chat user sebelumnya

#### Scenario: User mengirim pertanyaan

- **GIVEN** user mengetik pertanyaan
- **WHEN** user mengirim pertanyaan
- **THEN** sistem SHALL menyimpan pertanyaan ke `TChatbotInternal`
- **AND** sistem SHALL mencari knowledge relevan dari `MPengetahuan`
- **AND** sistem SHALL memanggil AI provider dengan konteks knowledge
- **AND** sistem SHALL menyimpan jawaban AI ke `TChatbotInternal`
- **AND** UI SHALL menampilkan jawaban dengan knowledge tags

#### Scenario: Knowledge base digunakan (RAG)

- **GIVEN** user bertanya tentang fitur aplikasi
- **WHEN** sistem mencari knowledge
- **THEN** sistem SHALL mencari keyword di `JudulPengetahuan`, `IsiPengetahuan`, `Tag`, `SearchKeywords`
- **AND** sistem SHALL memprioritaskan knowledge dengan `PrioritasAi` tinggi
- **AND** sistem SHALL memasukkan maksimal 5 knowledge entries ke konteks
- **AND** jawaban AI SHALL menampilkan sumber knowledge yang digunakan

#### Scenario: Riwayat percakapan

- **GIVEN** user sudah bertanya sebelumnya
- **WHEN** user bertanya lagi
- **THEN** sistem SHALL mengirim 20 pesan terakhir sebagai konteks percakapan
- **AND** AI SHALL mempertahankan konteks percakapan

#### Scenario: Clear history

- **GIVEN** user memiliki riwayat chat
- **WHEN** user mengklik tombol clear history
- **THEN** sistem SHALL menampilkan konfirmasi
- **AND** jika dikonfirmasi, semua riwayat user SHALL dihapus
- **AND** UI SHALL kembali ke empty state

#### Scenario: Role-aware response

- **GIVEN** user CS bertanya
- **WHEN** sistem membangun system prompt
- **THEN** prompt SHALL menyertakan nama user dan nama role
- **AND** AI SHALL memberikan jawaban yang sesuai dengan role user

#### Scenario: AI provider gagal

- **GIVEN** AI provider tidak merespons
- **WHEN** VPoint Assistant memanggil AI
- **THEN** sistem SHALL menampilkan pesan error yang informatif
- **AND** sistem SHALL tidak crash

### Requirement: Security — Chatbot Internal

Sistem SHALL menjaga keamanan data internal.

#### Scenario: Data terisolasi per user

- **GIVEN** user A dan B menggunakan chatbot
- **WHEN** user A melihat riwayat
- **THEN** sistem SHALL hanya menampilkan riwayat user A
- **AND** user B tidak bisa melihat riwayat user A

#### Scenario: API key aman

- **GIVEN** AI provider error
- **WHEN** error ditampilkan ke user
- **THEN** error message SHALL tidak mengandung API key atau secret

### Requirement: Queue — AI Deduplication

Sistem SHALL mencegah AI menjawab chat yang sudah dijawab CS manual.

#### Scenario: CS menjawab sebelum AI job berjalan

- **GIVEN`ProcessAiAutoReplyJob` menunggu di queue
- **WHEN** job mulai dijalankan
- **THEN** sistem SHALL cek apakah ada reply CS setelah trigger
- **AND** jika CS sudah reply, job SHALL di-cancel (delete)
- **AND** AI tidak mengirim reply tambahan

### Requirement: Multilanguage Preservation

Sistem SHALL mempertahankan multilanguage untuk seluruh fitur optimasi dan VPoint Assistant.

#### Scenario: Label UI chatbot ditampilkan

- **GIVEN** user memakai locale `id` atau `en`
- **WHEN** user membuka VPoint Assistant
- **THEN** semua title, navigation label, placeholder, button, empty state, loading text, dan confirmation text SHALL tampil sesuai locale aktif
- **AND** tidak boleh ada hardcoded UI string kecuali nama brand

#### Scenario: Error AI provider terjadi

- **GIVEN** AI provider gagal merespons
- **WHEN** error ditampilkan ke user
- **THEN** pesan user-facing SHALL memakai localization key
- **AND** detail teknis SHALL hanya masuk log dan tidak membocorkan secret
