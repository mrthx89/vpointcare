## Context

Perubahan ini melanjutkan `fix-inbox-group-chat-duplication`. Jalur webhook dan broadcast sudah dipisahkan melalui `ProcessWebhookJob` dan `SendBroadcastDebouncedJob`, tetapi `InboxWhatsapp` masih menggabungkan refresh daftar, resolusi identitas, request WAHA, dan pemuatan message history dalam satu siklus Livewire. Lihat `proposal.md` untuk motivasi dan `specs/vpoint-care/spec.md` untuk kontrak perilaku.

Constraint utama adalah PHP 8.3+, Laravel 13, Filament 5, SQL Server, queue yang sudah dipakai deployment, dan kompatibilitas dengan `TChat`/`TChatD` legacy. Tidak ada perubahan route, payload webhook, nama channel `waha-inbox`, atau tabel.

## Goals / Non-Goals

**Goals:**

- Menjadikan canonical group JID sebagai kunci sibling yang tersedia melalui kolom terindeks.
- Menghilangkan N+1 query dan external HTTP dari render daftar/room.
- Menampilkan latest message window secara deterministik dan memuat pesan lama dengan cursor.
- Menjaga idempotency webhook dan realtime private chat.
- Menyediakan backfill yang aman, dapat diaudit, dan dapat diulang.

**Non-Goals:**

- Tidak menggabungkan atau menghapus row legacy.
- Tidak melakukan migration schema, perubahan route, perubahan permission, atau penggantian provider realtime.
- Tidak menjadikan nama grup WAHA sebagai dependency untuk menampilkan pesan.

## Decisions

### 1. Canonical identity disimpan, bukan dicari ulang saat request

Tambahkan ekstraksi group JID bersama di `WahaChatHelper`, gunakan pada parser webhook dan command backfill, lalu isi `TChat.IdWahaTerdeteksi` untuk legacy row. `groupSiblingIds()` hanya menggunakan `IdSesiWhatsapp`, `JenisChat`, `IdGrupWhatsapp`, dan kolom identity; pencarian substring `PayloadJson` dikeluarkan dari jalur request.

Alternatif yang ditolak adalah mempertahankan `LIKE '%@g.us%'` dengan cache request. Cache tidak menghilangkan scan SQL Server dan dapat tetap membuat room besar lambat.

### 2. Query inbox dibuat set-based

`loadInbox()` mengambil detail terbaru untuk seluruh 50 chat melalui satu derived table SQL Server dengan `ROW_NUMBER() OVER (PARTITION BY IdChat ORDER BY TglPesan DESC, Id DESC)`. Payload terbaru yang diperlukan `formatChatRow()` ikut diambil dari derived table sehingga `latestIncomingPayload()` tidak dipanggil per row. Index existing `IX_TChatD_IdChat_TglPesan` tetap dipakai.

Alternatif `with()` Eloquent ditolak karena source memakai query builder legacy dan relasi tidak memecahkan payload/preview N+1 secara otomatis. Menambah index pada `PayloadJson` juga tidak efektif untuk pencarian substring.

### 3. Metadata grup diproses asynchronously

`formatChatRow()` hanya membaca `NamaGrupWhatsapp`, master mapping, payload yang sudah tersedia, atau JID. `RefreshWahaGroupMetadataJob` memanggil method `WahaSender` yang mencatat integrasi, memakai timeout 20 detik/job, maksimal 3 attempts, backoff `[30, 120]`, dan cache lock berdasarkan session+JID. Job memperbarui seluruh sibling yang memiliki session dan canonical JID, lalu memicu broadcast debounced.

Alternatif request WAHA synchronous dengan timeout lebih kecil ditolak karena tetap membuat latency bergantung pada jaringan dan dapat menghambat seluruh daftar inbox.

### 4. Latest window dan cursor memakai ordering deterministik

`selectChat()` memakai page size 100, `ORDER BY TglPesan DESC, Id DESC`, mengambil satu row tambahan untuk menentukan `hasOlderMessages`, lalu membalik hasil sebelum render. `loadOlderMessages()` memakai pasangan cursor `(TglPesan, Id)` dan menggabungkan hasil dengan unique key `TChatD.Id` sebelum prepend.

Alternatif mempertahankan `ORDER BY ASC LIMIT 200` ditolak karena itu adalah akar masalah latest message hilang pada group besar dan bukan pagination yang dapat dipakai pengguna.

### 5. Realtime memakai satu refresh aktif

`handleInboxUpdate()` menyegarkan daftar sekali, mencocokkan event private dengan `chatId`, mencocokkan group dengan `(IdSesiWhatsapp, groupJid)`, lalu memuat latest page active room sekali. Event tidak lagi menyebabkan `loadInbox()` memanggil `selectChat()` dan kemudian handler memanggilnya lagi.

`WahaInboxUpdated`, event name `.inbox.updated`, channel `waha-inbox`, dan payload `chat_id` tidak diubah agar browser lama tetap kompatibel.

### 6. Backfill tidak menghapus data

`waha:backfill-group-chat-identity` hanya mengisi `IdWahaTerdeteksi` yang kosong atau tidak canonical pada row `JenisChat='Grup'`. `--dry-run` wajib tersedia, command memproses chunk, melaporkan jumlah kandidat/gagal, dan tidak menulis body payload ke output. Operator wajib mengambil backup SQL Server sebelum eksekusi.

## Risks / Trade-offs

- Backfill menambah langkah deployment, tetapi menghilangkan scan payload dari request dan dapat diulang.
- Satu grup dapat memiliki beberapa legacy `TChat`; UI tetap menggabungkan secara virtual sehingga histori audit aman, tetapi physical deduplication tetap menjadi pekerjaan terpisah.
- Metadata nama grup dapat muncul beberapa detik setelah pesan karena async queue; pesan dan JID tetap tampil segera.
- Cursor page membatasi memory Livewire dan mempercepat load, tetapi menambah action UI “load older messages” dan key localization `id`/`en`.
- Query `ROW_NUMBER()` dan perbandingan cursor harus diuji pada SQL Server; test SQLite hanya memvalidasi behavior, bukan execution plan production.

## Migration Plan

1. Backup database SQL Server dan deploy code yang kompatibel dengan row lama.
2. Jalankan `php artisan waha:backfill-group-chat-identity --dry-run` dari `src`, review counts, lalu jalankan command tanpa flag.
3. Jalankan `php artisan optimize:clear`, `php artisan migrate --force` hanya sebagai langkah standar deployment tanpa migration baru, dan `npm run build` bila view/asset berubah.
4. Jalankan worker dengan queue `webhooks`, `broadcasts`, dan `waha-metadata`, serta restart Reverb.
5. Smoke-test private/group webhook, realtime, latest page, older page, duplicate webhook, dan failure metadata.

Rollback aplikasi dilakukan dengan release sebelumnya. Rollback data memakai backup atau daftar row dari output backfill; tidak ada operasi delete/merge otomatis.

## Open Questions

Tidak ada. Page size 100, timeout job 20 detik, attempts 3, dan queue name `waha-metadata` adalah keputusan desain yang sudah ditetapkan agar task dapat langsung dieksekusi.
