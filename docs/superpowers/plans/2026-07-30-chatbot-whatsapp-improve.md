# Chatbot WhatsAppAsli Improve Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyelesaikan OpenSpec `chatbot-whatsappasli-improve` agar Inbox WhatsAppAsli berbasis snapshot database, chat grup lengkap per `@g.us`, avatar benar, AI Agent menjawab sesuai All Session/idle session, base64 media tidak tampil bila media berhasil dirender, dan breadcrumb ticketing berjalan.

**Architecture:** Gunakan perubahan minimal pada shared path: schema snapshot di `TChat`/`TChatD`, adapter WAHA di `WahaSender`, job sinkronisasi metadata di queue `webhooks`, koreksi root cause grup di `WahaWebhookProcessor::findOrCreateChat()`, dan UI tetap di `InboxWhatsapp` + Blade. Hindari dependency baru; gunakan `SchemaCache`, `WahaChatHelper`, `FilamentBreadcrumbs`, queue job, localization, dan test pattern yang sudah ada.

**Tech Stack:** PHP 8.3+, Laravel 13, Filament 5, Livewire, PHPUnit 12, Microsoft SQL Server (`sqlsrv`) untuk produksi, SQLite in-memory untuk test terisolasi, Laravel database queue, WAHA, Blade, Vite, Tailwind CSS 4.

## Global Constraints

- Sumber kebenaran scope adalah `openspec/changes/chatbot-whatsappasli-improve`.
- Jangan membuat OpenSpec baru dan jangan menimpa change aktif; update checkbox `tasks.md` hanya setelah task benar-benar diverifikasi.
- Pertahankan `MPengguna` sebagai sumber autentikasi; jangan kembali ke tabel `users` default.
- Pertahankan route `/admin`, `/webhooks/waha/{token?}`, `/admin/waha-media/{message}`, dan `/profile-storage/{path}`.
- Pertahankan normalisasi WAHA untuk `@c.us`, `@s.whatsapp.net`, `@g.us`, dan `@lid`.
- Webhook harus cepat, tervalidasi, idempotent, dan memindahkan pekerjaan berat ke queue.
- Perubahan queue harus memakai queue name eksplisit, timeout, retry, deduplication, dan failure behavior.
- Perubahan AI harus mempertimbangkan provider, model, API key, jam kerja, hari libur, nomor pengecualian, knowledge, session, dan `KirimKeWaha`.
- Jangan menampilkan atau mencatat API key, webhook token, password, access token, raw provider payload, full prompt secret, stack trace, atau raw WAHA payload di UI.
- Perubahan UI user-facing wajib mendukung Bahasa Indonesia dan Inggris.
- Pertahankan kompatibilitas Microsoft SQL Server; jangan mengasumsikan sintaks MySQL/PostgreSQL.
- Jangan mengubah file di `src/vendor/`, generated asset, lock file, atau dependency.
- Gunakan TDD untuk logic non-trivial: tulis test merah, implementasi minimal, jalankan test hijau.
- Jangan menjalankan `migrate:fresh`, `db:wipe`, reset database, atau command destruktif lain.
- Jangan melakukan commit Git tanpa instruksi eksplisit pengguna.

---

## Source Map

### File Baru

- `src/database/migrations/2026_07_30_000001_add_waha_identity_snapshot_to_chat.php` — kolom snapshot WAHA, avatar participant, dan idle session AI dengan guard SQL Server.
- `src/app/Jobs/SyncWahaChatIdentityJob.php` — sinkronisasi nama/foto chat dan participant di queue `webhooks`.
- `src/tests/Feature/Waha/WahaChatIdentitySyncTest.php` — test metadata kontak/grup, LID, failure sanitasi, dan dispatch broadcast.
- `src/tests/Feature/Waha/WahaWebhookGroupIngestionTest.php` — test satu chat grup per sesi + raw group JID.
- `src/tests/Feature/Ai/AiAutoReplySessionPolicyTest.php` — test All Session dan idle session.
- `src/tests/Feature/Filament/TicketingBreadcrumbTest.php` — test breadcrumb route ticketing.

### File Diubah

- `src/app/Services/Waha/WahaSender.php` — metadata kontak/grup dan parser defensif.
- `src/app/Services/Waha/WahaWebhookProcessor.php` — identitas grup raw `@g.us` dan participant detail.
- `src/app/Jobs/ProcessWebhookJob.php` — dispatch sinkronisasi setelah webhook sukses.
- `src/app/Filament/Pages/InboxWhatsapp.php` — snapshot identity, avatar group/participant, dan state media.
- `src/resources/views/filament/pages/inbox-whatsapp.blade.php` — chat list/header/bubble dan fallback media.
- `src/app/Support/WahaMediaPayload.php` dan `src/app/Http/Controllers/WahaMediaController.php` — boundary decoding media.
- `src/app/Services/Ai/AiAutoReplyService.php`, `src/app/Jobs/ProcessAiAutoReplyJob.php` — policy session dan delivery status.
- `src/app/Filament/Pages/AiAgent.php`, `src/resources/views/filament/pages/ai-agent.blade.php` — setting idle session.
- Empat page ticketing `Manage*.php`, `HasMenuBreadcrumbs.php`, dan `FilamentBreadcrumbs.php` — breadcrumb.
- `src/resources/lang/id/ui.php` dan `src/resources/lang/en/ui.php` — localization.
- `src/script/DATABASE_SCHEMA_WACS.sql` — fresh-install schema.
- `openspec/changes/chatbot-whatsappasli-improve/tasks.md` — checkbox hasil verifikasi.

### File Referensi

- `README.md`, `openspec/project.md`, `openspec/specs/vpoint-care/spec.md`.
- `openspec/changes/chatbot-whatsappasli-improve/proposal.md`.
- `openspec/changes/chatbot-whatsappasli-improve/design.md`.
- `openspec/changes/chatbot-whatsappasli-improve/specs/vpoint-care/spec.md`.
- `src/app/Support/WahaChatHelper.php`.
- `src/app/Jobs/SendBroadcastDebouncedJob.php`.
- `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`.
- `src/tests/Unit/Support/WahaMediaPayloadTest.php` dan `src/tests/Feature/Http/Controllers/WahaMediaControllerTest.php`.

---

### Task 1: Schema Snapshot dan Fixture Test

**Tujuan:** Menambahkan kontrak data minimum untuk identitas WAHA, avatar participant, dan idle session AI tanpa mengubah data master.

**Files:**
- Create: `src/database/migrations/2026_07_30_000001_add_waha_identity_snapshot_to_chat.php`
- Modify: `src/script/DATABASE_SCHEMA_WACS.sql`
- Modify: `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`
- Create: `src/tests/Feature/Waha/WahaChatIdentitySyncTest.php`
- Create: `src/tests/Feature/Waha/WahaWebhookGroupIngestionTest.php`
- Create: `src/tests/Feature/Ai/AiAutoReplySessionPolicyTest.php`

**Interfaces:**
- `TChat`: `NamaKontakWaha nvarchar(150) NULL`, `NamaGrupWaha nvarchar(200) NULL`, `TglIdentitasWahaDiambil datetime2 NULL`, `StatusIdentitasWaha varchar(30) NULL`, `PesanErrorIdentitasWaha nvarchar(500) NULL`.
- `TChatD`: `PengirimIdWaha varchar(200) NULL`, `UrlFotoProfilPengirim nvarchar(1000) NULL`, `TglFotoProfilPengirimDiambil datetime2 NULL`.
- `MPengaturanAi`: `BatasSesiAutoReplyMenit int NOT NULL DEFAULT 60`, valid `1..1440`.

- [x] **Step 1: Buat migration SQL Server guarded**

Gunakan `DB::unprepared()` dan `COL_LENGTH()` sehingga database existing aman. `up()` menambah semua kolom bila belum ada; `down()` menjatuhkan constraint default/check dan kolom hanya bila ada. Tambahkan check constraint `CK_MPengaturanAi_BatasSesiAutoReplyMenit` dan default constraint `DF_MPengaturanAi_BatasSesiAutoReplyMenit`.

- [x] **Step 2: Sinkronkan fresh schema**

Tambahkan kolom yang sama pada `CREATE TABLE TChat`, `CREATE TABLE TChatD`, dan `CREATE TABLE MPengaturanAi` di `src/script/DATABASE_SCHEMA_WACS.sql`, termasuk tipe `nvarchar`, `varchar`, `datetime2`, default 60, dan check `BETWEEN 1 AND 1440`.

- [x] **Step 3: Lengkapi SQLite fixture**

Tambahkan kolom nullable/string/date-time pada table builder test existing dan fixture baru. Jangan menjalankan migration SQL Server pada SQLite; feature test membuat tabel minimum seperti pola `InboxWhatsappTest`.

- [x] **Step 4: Jalankan gate schema**

Run:

```powershell
cd src
php -l database/migrations/2026_07_30_000001_add_waha_identity_snapshot_to_chat.php
php artisan test --filter=InboxWhatsappTest
```

Expected: syntax migration bersih dan test Inbox existing tetap lulus.

---

### Task 2: Adapter Metadata WAHA

**Tujuan:** Menyediakan satu adapter metadata kontak/grup yang bisa dipakai job tanpa fetch saat render Inbox.

**Files:**
- Modify: `src/app/Services/Waha/WahaSender.php`
- Modify: `src/tests/Feature/Waha/WahaChatIdentitySyncTest.php`

**Interfaces:**
- `getContactInfo(string $session, string $contactId): array` mengembalikan `ok`, `name`, `pushname`, `id`, `phone`, `status`, `error`.
- `getGroupInfo(string $session, string $groupId): array` mengembalikan `ok`, `name`, `id`, `status`, `error`.
- Reuse `getContactProfilePictureUrl()` dan `getPhoneNumberByLid()`.

- [x] **Step 1: Tulis test merah parser response**

Gunakan `Http::fake()` untuk response kontak berisi `name`, `pushname`, `id` dan response grup berisi `subject`. Set `services.waha.base_url` ke `https://waha.test`; assert nama dan nomor ter-normalisasi.


```powershell
cd src
php artisan test --filter=WahaChatIdentitySyncTest
```

Expected: fail karena `getContactInfo()` dan `getGroupInfo()` belum ada.

- [x] **Step 3: Implementasikan adapter minimum**

Tambahkan method public di `WahaSender` yang memanggil helper GET existing, menormalisasi contact ID dengan `WahaChatHelper`, mengambil nama dari key `name`, `subject`, `pushname`, `shortName`, dan mengembalikan hanya field terproyeksi. Jangan mengembalikan body provider mentah. Untuk grup, endpoint memakai normalized raw `@g.us`; untuk personal `@lid`, pertahankan LID dan gunakan `phone` hasil response bila ada.

- [ ] **Step 4: Jalankan test adapter** (Blocked/parsial: runtime test/PHPUnit/Pint/WAHA/production tidak tersedia di environment ini.)

```powershell
cd src
php -l app/Services/Waha/WahaSender.php
php artisan test --filter=WahaChatIdentitySyncTest
```

Expected: adapter lulus dan `TLogIntegrasi` tidak mencatat secret.

---

### Task 3: Queue Sinkronisasi Identitas Chat

**Tujuan:** Mengisi snapshot nama/foto WAHA setelah webhook tersimpan, dengan dedupe, retry, timeout, dan fallback yang tidak menghapus snapshot terakhir.

**Files:**
- Create: `src/app/Jobs/SyncWahaChatIdentityJob.php`
- Modify: `src/app/Jobs/ProcessWebhookJob.php`
- Modify: `src/tests/Feature/Waha/WahaChatIdentitySyncTest.php`

**Interfaces:**
- `SyncWahaChatIdentityJob::dispatchDebounced(string $chatId): void`.
- Queue `webhooks`, `$tries = 3`, `$timeout = 30`, `backoff(): array` mengembalikan `[30, 120]`.
- Cache dedupe `waha:identity-sync:{chatId}` selama 60 detik.

- [x] **Step 1: Tulis test merah job group dan LID**

Fixture grup memakai `NomorWhatsapp='120363111@g.us'` dan mapping stale/null; fake group info dan profile picture. Assert `NamaGrupWaha`, `UrlFotoProfil`, dan status sukses. Fixture personal memakai `IdWahaTerdeteksi='111@lid'`; fake LID resolver/contact info; assert LID tetap tersimpan dan nomor hasil resolusi masuk ke `NomorWhatsappTerdeteksi`.

- [x] **Step 2: Implementasikan job**

Job mengambil `TChat` + `MSesiWhatsapp` + mapping group. Untuk grup, urutan raw ID hanya `TChat.NomorWhatsapp`, `TChat.IdWahaTerdeteksi`, payload raw group, lalu mapping `IdGrupWaha` bila valid dan berakhiran `@g.us`; participant tidak pernah menjadi avatar group ID. Untuk personal, gunakan `IdWahaTerdeteksi`, nomor terdeteksi, lalu nomor chat. Update `StatusIdentitasWaha=success|failed`, waktu percobaan, dan error maksimal 500 karakter. Saat WAHA gagal, pertahankan `Nama*Waha`, `UrlFotoProfil`, dan nomor terakhir.

- [x] **Step 3: Dispatch setelah webhook sukses**

Di `ProcessWebhookJob::handle()`, setelah `SendBroadcastDebouncedJob::dispatchDebounced($chatId)` dan sebelum `ProcessAiAutoReplyJob`, panggil `SyncWahaChatIdentityJob::dispatchDebounced($chatId)`. Jangan dispatch untuk duplicate/ignored webhook.

- [ ] **Step 4: Jalankan test job** (Blocked/parsial: runtime test/PHPUnit/Pint/WAHA/production tidak tersedia di environment ini.)

```powershell
cd src
php -l app/Jobs/SyncWahaChatIdentityJob.php
php -l app/Jobs/ProcessWebhookJob.php
php artisan test --filter=WahaChatIdentitySyncTest
```

Expected: snapshot sukses, failure tersanitasi, queue/dedupe sesuai kontrak.

---

### Task 4: Root Cause Ingestion GroupWhatsApp

**Tujuan:** Semua participant pada grup masuk ke satu `TChat` berdasarkan sesi + raw `group_jid @g.us`, bukan berdasarkan sender pertama atau mapping stale.

**Files:**
- Modify: `src/app/Services/Waha/WahaWebhookProcessor.php`
- Create: `src/tests/Feature/Waha/WahaWebhookGroupIngestionTest.php`

**Interfaces:**
- `parseMessage()` mempertahankan `group_jid` normalized `@g.us`.
- `findOrCreateChat(string $sessionId, array $parsed, array $mapping): string` mencari group berdasarkan `IdSesiWhatsapp`, `JenisChat='Grup'`, dan raw group JID.
- `TChatD.PengirimIdWaha` menyimpan participant JID; `PengirimNomorWhatsapp` tetap menyimpan nomor ternormalisasi.

- [x] **Step 1: Tulis test merah dua participant**

Proses dua payload dengan `120363111@g.us`, participant `628111@c.us` dan `628222@c.us`. Assert `ok`, `chat_id` sama, satu `TChat` grup, dua `TChatD`, `TChat.NomorWhatsapp='120363111@g.us'`, dan dua `PengirimIdWaha` berbeda.

- [x] **Step 2: Perbaiki parser raw group**

Normalisasi remote ID dengan `WahaChatHelper::normalizeChatId()`. Tandai grup bila hasil berakhiran `@g.us` atau flag `isGroup` aktif; set `group_jid=$remoteId` dan `senderJid=$participant` untuk grup. Jangan memakai participant sebagai remote chat ID.

- [x] **Step 3: Perbaiki query `findOrCreateChat()`**

Scope query dengan `IdSesiWhatsapp`. Untuk grup, cari `NomorWhatsapp=$parsed['group_jid']` atau `IdWahaTerdeteksi=$parsed['group_jid']`, baru fallback `IdGrupWhatsapp`. Saat insert/update, `NomorWhatsapp` dan `IdWahaTerdeteksi` grup selalu memakai `group_jid`; personal tetap memakai sender.

- [x] **Step 4: Simpan participant detail**

Saat `$chatMessage` dibuat, jika `SchemaCache::hasColumn('TChatD', 'PengirimIdWaha')`, isi dengan `parsed['pengirim_jid']`. Pertahankan `PengirimNamaKontak`, nomor, dan payload existing.

- [ ] **Step 5: Jalankan test ingestion** (Blocked/parsial: runtime test/PHPUnit/Pint/WAHA/production tidak tersedia di environment ini.)

```powershell
cd src
php -l app/Services/Waha/WahaWebhookProcessor.php
php artisan test --filter=WahaWebhookGroupIngestionTest
```

Expected: dua participant menghasilkan satu chat grup dan dua detail pesan.

---

### Task 5: Inbox WhatsAppAsli Snapshot dan Avatar Benar

**Tujuan:** UI WhatsAppAsli memakai snapshot database dan avatar grup dari raw group JID, sambil tetap jelas bagi CS: Grup/Pribadi, nama, JID, LID/nomor, preview, waktu, dan unread.

**Files:**
- Modify: `src/app/Filament/Pages/InboxWhatsapp.php`
- Modify: `src/resources/views/filament/pages/inbox-whatsapp.blade.php`
- Modify: `src/resources/lang/id/ui.php`
- Modify: `src/resources/lang/en/ui.php`
- Modify: `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`

**Interfaces:**
- `formatChatRow()` supplies `PrimaryName`, `Badge`, `ChatId`, `GroupId`, `ContactNumber`, `ResolvedNumber`, `SnapshotStatus`, and `SnapshotError` per display mode.
- `profileContactId()` returns raw `@g.us` for group only; participant/sender JID is never a group avatar candidate.
- `messageSenderAvatarUrl()` returns participant photo for incoming group message, AI logo for outgoing AI, and existing CS profile for outgoing CS.

- [x] **Step 1: Tulis test merah prioritas nama snapshot**

Tambahkan fixture chat grup dengan `NomorWhatsapp='120363111@g.us'`, `NamaGrupWhatsapp='Legacy Payload Name'`, `NamaGrupWaha='Nama Grup WAHA'`, serta `IdGrupWaha='999@g.us'`. Assert identity mode WhatsApp memakai `Nama Grup WAHA` dan group ID `120363111@g.us`.

- [x] **Step 2: Tulis test merah avatar grup**

Fake endpoint foto WAHA dan panggil action refresh yang digunakan Inbox. Assert request memakai `contactId=120363111@g.us`, bukan `628111@c.us`, bukan sender terakhir, dan bukan mapping stale `999@g.us`.

- [x] **Step 3: Tambahkan kolom snapshot ke query**

Di `loadChats()` select `c.NamaKontakWaha`, `c.NamaGrupWaha`, `c.TglIdentitasWahaDiambil`, `c.StatusIdentitasWaha`, dan `c.PesanErrorIdentitasWaha`. Di query messages select `d.PengirimIdWaha`, `d.UrlFotoProfilPengirim`, dan `d.TglFotoProfilPengirimDiambil`. Gunakan `SchemaCache::hasColumn()` bila diperlukan oleh database yang belum dimigrasi atau fixture lama.

- [x] **Step 4: Terapkan urutan identity mode**

Gunakan urutan berikut dalam `formatChatRow()`:

```php
$whatsappPrimaryName = $isGroup
    ? ($row->NamaGrupWaha ?: $rawGroupName ?: $rawGroupId)
    : ($row->NamaKontakWaha ?: $rawContactName ?: $rawContactNumber ?: $detectedWahaId);

$internalPrimaryName = $isGroup
    ? ($mappedGroupName ?: $row->NamaGrupWaha ?: $rawGroupName ?: $rawGroupId)
    : ($mappedContactName ?: $row->NamaKontakWaha ?: $rawContactName ?: $mappedContactNumber ?: $rawContactNumber);
```

Mode internal tetap mengutamakan mapping master dan snapshot hanya fallback.

- [x] **Step 5: Kunci resolver avatar grup**

Ganti branch grup di `profileContactId()` agar hanya mengembalikan candidate yang dinormalisasi dan berakhiran `@g.us`, dalam urutan raw chat JID, `IdWahaTerdeteksi`, raw payload group JID, lalu `IdGrupWaha` valid. Jika tidak ada, return `null` dan tampilkan fallback inisial; jangan call WAHA dengan participant.

- [x] **Step 6: Render avatar participant**

Untuk incoming group message, gunakan `UrlFotoProfilPengirim` (path public storage atau URL WAHA) sebelum fallback inisial. Avatar memiliki `alt` nama sender, focus state pada bubble tetap terlihat, dan kelas dark mode konsisten (`h-7 w-7 rounded-full object-cover`).

- [x] **Step 7: Terapkan pola UI list/header minimal**

Pertahankan Blade/Filament/Tailwind existing. Baris chat dan header wajib menampilkan avatar, nama, badge Grup/Pribadi, identifier monospace, preview, waktu, unread. Jangan mengimpor komponen WAHA Hub atau dependency frontend. Gunakan spacing konsisten 8px, label teks bukan ikon saja, focus ring, kontras dark mode, dan `alt` untuk gambar bermakna.

- [ ] **Step 8: Jalankan targeted UI test** (Blocked/parsial: runtime test/PHPUnit/Pint/WAHA/production tidak tersedia di environment ini.)

```powershell
cd src
php -l app/Filament/Pages/InboxWhatsapp.php
php artisan test --filter=InboxWhatsappTest
```

Expected: nama snapshot, `@g.us`, `@lid`/nomor hasil resolusi, badge, avatar yang benar, dan test Inbox lama lulus.

---

### Task 6: Participant Avatar Sync

**Tujuan:** Menampilkan foto profile user-user di GroupWhatsApp tanpa WAHA call pada saat Blade/Livewire dirender.

**Files:**
- Modify: `src/app/Jobs/SyncWahaChatIdentityJob.php`
- Modify: `src/tests/Feature/Waha/WahaChatIdentitySyncTest.php`
- Modify: `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`

**Interfaces:**
- Job memperbarui `TChatD.UrlFotoProfilPengirim` dan `TglFotoProfilPengirimDiambil` untuk participant grup yang belum punya foto atau stale satu hari.
- Cache dedupe participant: `waha:participant-profile:{session}:{sha1(participantJid)}`, TTL 1 jam.

- [x] **Step 1: Tulis test merah avatar participant**

Buat satu `TChat` grup, satu `TChatD` incoming dengan `PengirimIdWaha='628111@c.us'`, dan fake foto `https://cdn.test/budi.jpg`. Assert participant photo diperbarui, sementara `TChat.UrlFotoProfil` grup tidak berubah.

- [x] **Step 2: Tambahkan sync participant ke job**

Tambahkan private method yang mengambil maksimal 20 pesan masuk grup terbaru dengan JID participant dan foto null/stale. Untuk setiap participant yang belum terkena cache, panggil `getContactProfilePictureUrl()`, lalu update seluruh `TChatD` pada chat + participant JID yang sama. Failure tidak menghapus foto terakhir dan tidak mengubah foto grup.

- [x] **Step 3: Panggil hanya untuk chat grup**

Di `SyncWahaChatIdentityJob::handle()`, jalankan sync participant hanya bila `JenisChat='Grup'`, setelah metadata group sinkron. Tetap dispatch satu `SendBroadcastDebouncedJob` untuk refresh UI.


```powershell
cd src
php -l app/Jobs/SyncWahaChatIdentityJob.php
php artisan test --filter=WahaChatIdentitySyncTest
php artisan test --filter=InboxWhatsappTest
```

Expected: participant avatar tersimpan pada detail pesan; avatar percakapan grup tetap memakai raw group JID.

---

### Task 7: AI Agent All Session dan Idle Session

**Tujuan:** AI Agent menjawab semua incoming eligible saat All Session aktif, dan saat nonaktif menjawab chat pertama atau setelah idle minimal `BatasSesiAutoReplyMenit`.

**Files:**
- Modify: `src/app/Services/Ai/AiAutoReplyService.php`
- Modify: `src/app/Jobs/ProcessAiAutoReplyJob.php`
- Modify: `src/app/Filament/Pages/AiAgent.php`
- Modify: `src/resources/views/filament/pages/ai-agent.blade.php`
- Modify: `src/resources/lang/id/ui.php`
- Modify: `src/resources/lang/en/ui.php`
- Create: `src/tests/Feature/Ai/AiAutoReplySessionPolicyTest.php`

**Interfaces:**
- `AutoReplyJamKerjaBerlanjut` dilabeli All Session.
- `BatasSesiAutoReplyMenit`: integer `1..1440`, default `60`.
- Session policy emits reason code: `all_session`, `first_message`, `idle_session`, `active_session_skip` selain guard reason existing.

- [x] **Step 1: Tulis test merah `$isFirstReply`**

Aktifkan AI dengan provider fake dan call `handleIncomingChat()`. Assert tidak terjadi undefined variable serta `TAiPermintaan.ModelAi` mengikuti model pilihan first/non-first yang sudah ada.

- [x] **Step 2: Tulis test merah All Session aktif**

Insert dua incoming eligible selang satu menit dan AI reply setelah pesan pertama. Dengan `AutoReplyJamKerjaBerlanjut=true`, proses pesan kedua dan assert satu outgoing AI baru tersimpan.

- [x] **Step 3: Tulis test merah idle dan active session**

Dengan All Session off dan idle 60: pesan setelah 61 menit harus dibalas; pesan setelah 30 menit harus menghasilkan `skipped=true`, reason code `active_session_skip`, dan tidak menambah outgoing AI message.

- [x] **Step 4: Pindahkan perhitungan `$isFirstReply`**

Di `handleIncomingChat()`, set `$isFirstReply = $this->isFirstInboxAiReply($chatId);` sebelum insert `TAiPermintaan`; hapus assignment duplikat di dalam `try`.

- [x] **Step 5: Implementasikan helper policy idle**

Tambahkan helper yang mencari incoming customer sebelumnya sebelum message terbaru. Bila All Session aktif return allowed `all_session`; bila tidak ada message sebelumnya return `first_message`; bila selisih menit >= setting return `idle_session`; selain itu deny `active_session_skip`. Clamp setting ke `1..1440` agar data legacy invalid tidak mematikan flow.

- [x] **Step 6: Pertahankan urutan guard**

Jangan hapus check `AutoReplyAktif`, duplicate/already answered, manual reply, jam kerja, hari libur, nomor excluded, provider, knowledge, dan `KirimKeWaha`. Session policy berjalan setelah incoming terbaru tersedia dan sebelum call provider.

- [x] **Step 7: UI setting dan localization**

Tambahkan rules Livewire `required|integer|min:1|max:1440`, hydrate default 60, input type number `min=1 max=1440`, label All Session, serta helper text bahwa batas idle hanya berlaku ketika All Session off. Tambahkan string `id` dan `en`; jangan hardcode copy baru di Blade.

- [x] **Step 8: Delivery failure tidak dianggap sukses**

Jika `storeReply()` gagal mengirim WAHA, set `TChatD.StatusKirim='Gagal'`, simpan error tersanitasi maksimal 500 karakter, dan jangan update chat seolah dibalas/delivered. Draft lokal tetap sukses lokal bila `KirimKeWaha=false`. Pastikan `ProcessAiAutoReplyJob::failed()` mencatat context minimal tanpa prompt/provider body/secret.

- [ ] **Step 9: Jalankan AI test** (Blocked/parsial: runtime test/PHPUnit/Pint/WAHA/production tidak tersedia di environment ini.)

```powershell
cd src
php -l app/Services/Ai/AiAutoReplyService.php
php -l app/Jobs/ProcessAiAutoReplyJob.php
php -l app/Filament/Pages/AiAgent.php
php artisan test --filter=AiAutoReplySessionPolicyTest
```

Expected: All Session on membalas semua eligible incoming; All Session off membalas first/idle dan skip active session; tidak ada undefined `$isFirstReply`; WAHA delivery failure bukan status sukses.

---

### Task 8: Base64 Media Presentation

**Tujuan:** Jika media base64 berhasil dirender lewat media route, bubble tidak menampilkan teks base64; jika gagal konversi, tampilkan fallback diagnostik terbatas dan localized.

**Files:**
- Modify: `src/app/Support/WahaMediaPayload.php`
- Modify: `src/app/Filament/Pages/InboxWhatsapp.php`
- Modify: `src/app/Http/Controllers/WahaMediaController.php`
- Modify: `src/resources/views/filament/pages/inbox-whatsapp.blade.php`
- Modify: `src/resources/lang/id/ui.php`
- Modify: `src/resources/lang/en/ui.php`
- Modify: `src/tests/Unit/Support/WahaMediaPayloadTest.php`
- Modify: `src/tests/Feature/Http/Controllers/WahaMediaControllerTest.php`
- Modify: `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`

**Interfaces:**
- `WahaMediaPayload::inspectPayload()` mengembalikan metadata saja, tanpa decoded binary/base64.
- `WahaMediaPayload::fromPayloadJson()` mengembalikan contents hanya di controller.
- State message memiliki `HasRenderableMedia`, `MediaUrl`, `MediaCategory`, `ShowTextBody`, dan `Base64Fallback`.

- [x] **Step 1: Tulis test merah media valid**

Insert message dengan `IsiPesan` base64 image dan payload media valid. Assert Blade memakai `/admin/waha-media/{message}` dan tidak menyertakan string base64 pada response.

- [x] **Step 2: Tulis test merah media rusak**

Insert base64 rusak tanpa URL media valid. Assert fallback localized tampil dan tidak memuat `PayloadJson`, full base64, raw HTML, API key, token, atau stack trace.

- [x] **Step 3: Tambahkan presentation state**

Di formatter Inbox, hitung `embeddedMedia`, `hasUrlMedia`, `hasRenderableMedia`, dan `isBase64Text` dengan `base64_decode($candidate, true)` serta ambang panjang minimal 80. Set `ShowTextBody=!($hasRenderableMedia && $isBase64Text)` dan `Base64Fallback=!$hasRenderableMedia && $isBase64Text`.

- [x] **Step 4: Update render condition**

Render `IsiPesan` hanya saat `ShowTextBody`; render panel warning localized saat `Base64Fallback`; render image/sticker/audio/video/PDF/file melalui route controller saat media valid. State Livewire tidak boleh menyimpan decoded binary atau full payload JSON.

- [x] **Step 5: Pertahankan controller sebagai binary boundary**

Controller harus memakai decode strict, header aman termasuk `X-Content-Type-Options: nosniff`, dan log hanya `message_id`, source, reason code, serta optional status HTTP.

- [ ] **Step 6: Jalankan media test** (Blocked/parsial: runtime test/PHPUnit/Pint/WAHA/production tidak tersedia di environment ini.)

```powershell
cd src
php -l app/Support/WahaMediaPayload.php
php -l app/Http/Controllers/WahaMediaController.php
php -l app/Filament/Pages/InboxWhatsapp.php
php artisan test --filter=WahaMediaPayloadTest
php artisan test --filter=WahaMediaControllerTest
php artisan test --filter=InboxWhatsappTest
```

Expected: base64 valid menjadi media tanpa text mentah; fallback rusak tetap aman dan localized.

---

### Task 9: Breadcrumb Ticketing

**Tujuan:** Memulihkan breadcrumb pada empat halaman ticketing tanpa mengubah route, sidebar, atau permission.

**Files:**
- Modify: `src/app/Filament/Resources/Operational/Tickets/Pages/ManageTickets.php`
- Modify: `src/app/Filament/Resources/Ticketing/StatusTickets/Pages/ManageStatusTickets.php`
- Modify: `src/app/Filament/Resources/Ticketing/Prioritas/Pages/ManagePrioritasTickets.php`
- Modify: `src/app/Filament/Resources/Ticketing/Kategoris/Pages/ManageKategoriTickets.php`
- Modify: `src/app/Filament/Concerns/HasMenuBreadcrumbs.php`
- Modify: `src/app/Support/FilamentBreadcrumbs.php`
- Create: `src/tests/Feature/Filament/TicketingBreadcrumbTest.php`

**Interfaces:**
- Keempat page memakai `HasMenuBreadcrumbs`.
- Operational ticket memakai menu code `AccessPermissions::TICKET_VIEW`.
- Master status/prioritas/kategori memakai parent menu `ticket.view` dan resource navigation label sebagai current crumb.
- Label berasal dari `NavigationHelper` dan locale aktif.

- [x] **Step 1: Tulis test merah empat route**

Dengan user berizin, GET `/admin/operational/tickets`, `/admin/ticketing/status-tickets`, `/admin/ticketing/prioritas/prioritas-tickets`, dan `/admin/ticketing/kategoris/kategori-tickets`. Assert status OK, group/menu/current label tampil, dan label Ticket tidak terduplikasi. Tambahkan case locale `id`/`en` serta user tanpa permission mengikuti behavior deny existing.

- [x] **Step 2: Pasang trait pada page**

Tambahkan `use HasMenuBreadcrumbs;` dan property:

```php
protected static string $breadcrumbMenuCode = AccessPermissions::TICKET_VIEW;
```

ke empat page. Jangan ubah `$resource`, route, action Create, resource visibility, `ticket.view`, atau `ticket.manage`.

- [x] **Step 3: Cegah duplicate current label**

Jika helper menghasilkan `Ticket > Ticket`, perluas minimal `HasMenuBreadcrumbs`/`FilamentBreadcrumbs` agar parent berasal dari menu code dan current label berasal dari `static::getResource()::getNavigationLabel()`. Gunakan `array_values(array_unique(...))` hanya setelah membuang label kosong; jangan mengubah breadcrumb page lain.

- [ ] **Step 4: Jalankan breadcrumb test** (Blocked/parsial: runtime test/PHPUnit/Pint/WAHA/production tidak tersedia di environment ini.)

```powershell
cd src
php -l app/Support/FilamentBreadcrumbs.php
php -l app/Filament/Concerns/HasMenuBreadcrumbs.php
php -l app/Filament/Resources/Operational/Tickets/Pages/ManageTickets.php
php -l app/Filament/Resources/Ticketing/StatusTickets/Pages/ManageStatusTickets.php
php -l app/Filament/Resources/Ticketing/Prioritas/Pages/ManagePrioritasTickets.php
php -l app/Filament/Resources/Ticketing/Kategoris/Pages/ManageKategoriTickets.php
php artisan test --filter=TicketingBreadcrumbTest
```

Expected: semua route menampilkan breadcrumb terlokalisasi dan access behavior tidak berubah.

---

### Task 10: Localization dan OpenSpec Task Sync

**Tujuan:** Semua string user-facing tersedia di Bahasa Indonesia/Inggris, dan checkbox OpenSpec hanya mencerminkan pekerjaan yang benar-benar selesai.

**Files:**
- Modify: `src/resources/lang/id/ui.php`
- Modify: `src/resources/lang/en/ui.php`
- Modify: `openspec/changes/chatbot-whatsappasli-improve/tasks.md`

**Interfaces:**
- Key baru berada di subtree existing `ui.pages.inbox` dan `ui.pages.ai_agent`.
- Tidak ada string baru yang hanya tersedia satu bahasa.

- [x] **Step 1: Tambahkan key Inbox**

Tambahkan pasangan `id`/`en` untuk status sync identity, group/personal badge, identifier, participant avatar alt/fallback, refresh metadata, dan media base64 unavailable. Gunakan copy singkat dan actionable; jangan menampilkan error provider mentah.

- [x] **Step 2: Tambahkan key AI Agent**

Tambahkan `all_session`, `idle_session_limit_minutes`, dan helper text yang menjelaskan: All Session aktif menjawab setiap incoming eligible; jika off, AI menjawab first/idle sesuai batas menit.

- [x] **Step 3: Centang task OpenSpec yang sudah verified**

Edit `openspec/changes/chatbot-whatsappasli-improve/tasks.md` setelah masing-masing command lulus. Jangan centang migration deployment/manual verification bila belum dilakukan pada environment target.

- [x] **Step 4: Jalankan syntax localization**

```powershell
cd src
php -l resources/lang/id/ui.php
php -l resources/lang/en/ui.php
```

Expected: no syntax errors dan semua key yang dipakai Blade/PHP tersedia di dua locale.

---

### Task 11: Validasi Terpadu dan Deployment

**Tujuan:** Membuktikan implementasi sesuai OpenSpec dan menyediakan langkah deployment aman.

**Files:**
- Modify: `openspec/changes/chatbot-whatsappasli-improve/tasks.md`
- Reference: `deploy-update-server.bat`

**Interfaces:**
- Final handoff mencatat command yang dijalankan, hasil aktual, action migration/restart, risiko, dan task tersisa.

- [x] **Step 1: Jalankan PHP lint seluruh file berubah**

```powershell
cd src
php -l app/Services/Waha/WahaSender.php
php -l app/Services/Waha/WahaWebhookProcessor.php
php -l app/Jobs/SyncWahaChatIdentityJob.php
php -l app/Jobs/ProcessWebhookJob.php
php -l app/Services/Ai/AiAutoReplyService.php
php -l app/Jobs/ProcessAiAutoReplyJob.php
php -l app/Filament/Pages/InboxWhatsapp.php
php -l app/Filament/Pages/AiAgent.php
php -l app/Support/WahaMediaPayload.php
php -l app/Http/Controllers/WahaMediaController.php
php -l app/Support/FilamentBreadcrumbs.php
php -l app/Filament/Concerns/HasMenuBreadcrumbs.php
```

Expected: semua file melaporkan `No syntax errors detected`.

- [ ] **Step 2: Jalankan targeted test** (Blocked/parsial: runtime test/PHPUnit/Pint/WAHA/production tidak tersedia di environment ini.)

```powershell
cd src
php artisan test --filter=WahaChatIdentitySyncTest
php artisan test --filter=WahaWebhookGroupIngestionTest
php artisan test --filter=InboxWhatsappTest
php artisan test --filter=AiAutoReplySessionPolicyTest
php artisan test --filter=WahaMediaPayloadTest
php artisan test --filter=WahaMediaControllerTest
php artisan test --filter=TicketingBreadcrumbTest
```

Expected: seluruh targeted test lulus.

- [ ] **Step 3: Jalankan broader validation** (Blocked/parsial: runtime test/PHPUnit/Pint/WAHA/production tidak tersedia di environment ini.)

```powershell
cd src
php artisan test
vendor/bin/pint --test
npm run build
```

Expected: full test, Pint check, dan Vite build lulus. Jangan memperbaiki kegagalan unrelated; catat terpisah.

- [x] **Step 4: Validasi OpenSpec dan diff**

```powershell
cd ..
openspec validate chatbot-whatsappasli-improve --strict
git diff --check
```

Expected: OpenSpec valid, `git diff --check` exit code 0.

- [ ] **Step 5: Verifikasi migration SQL Server** (Blocked/parsial: runtime test/PHPUnit/Pint/WAHA/production tidak tersedia di environment ini.)

Pada environment staging/target yang memakai `sqlsrv`, backup database lalu jalankan:

```powershell
cd src
php artisan migrate --force
```

Expected: migration menambah kolom sekali, aman bila dijalankan ulang melalui status migration, existing rows tetap utuh, default AI idle menjadi 60.

- [ ] **Step 6: Restart runtime** (Blocked/parsial: runtime test/PHPUnit/Pint/WAHA/production tidak tersedia di environment ini.)

Setelah deploy:

```powershell
cd src
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Pastikan process manager menjalankan queue `webhooks`, `broadcasts`, dan queue AI existing. Restart Reverb hanya sesuai deployment process existing; tidak ada queue baru.

- [ ] **Step 7: Manual browser verification** (Blocked/parsial: runtime test/PHPUnit/Pint/WAHA/production tidak tersedia di environment ini.)

```text
1. WhatsAppAsli menampilkan badge Grup/Pribadi, nama snapshot, @g.us, dan @lid plus nomor hasil resolusi bila ada.
2. Dua participant grup masuk ke satu conversation; nama dan avatar participant tampil pada bubble.
3. Avatar conversation grup tidak berubah menjadi foto sender terakhir.
4. All Session on membalas setiap incoming eligible; off membalas first/idle dan skip active session.
5. Media base64 valid tampil sebagai preview/download tanpa teks base64; fallback rusak tetap aman.
6. Empat route ticketing menampilkan breadcrumb yang benar pada locale id dan en.
```

- [x] **Step 8: Final task sync**

Centang task OpenSpec sesuai bukti validasi. Sisakan task yang memerlukan production/staging bila environment lokal tidak tersedia, lalu jelaskan di final report.

---

## Self-Review

**Spec coverage:**
- WhatsAppAsli database snapshot: Tasks 1, 2, 3, 5, 10.
- Group/personal identity dan `@g.us`/`@lid`: Tasks 3, 4, 5.
- Missing group chats dari banyak participant: Task 4.
- Group profile photo salah: Tasks 3 dan 5.
- Participant profile photo: Task 6.
- AI no-reply dan All Session/idle: Task 7.
- Base64 media presentation: Task 8.
- Ticketing breadcrumbs: Task 9.
- Localization, security, observability, deployment: Tasks 7, 8, 10, 11.

**Placeholder scan:** Tidak ada marker placeholder atau langkah implementasi terbuka. Setiap task memiliki file, interface, test gate, command, dan expected result.

**Type consistency:** Nama kolom, job, method, queue, route, permission, dan setting mengikuti OpenSpec serta source yang diperiksa pada 30 Juli 2026.

## Execution Options

Plan complete and saved to `docs/superpowers/plans/2026-07-30-chatbot-whatsapp-improve.md`.

1. **Subagent-Driven (recommended)** — fresh worker per task dan review di antara task.
2. **Inline Execution** — eksekusi di session ini dengan checkpoint.

Jangan implementasikan sebelum pengguna memilih dan menyetujui opsi eksekusi.
