# Change: Improve Group Metadata Caching & Remove Base64 from Chat UI

## Summary

Perubahan ini membuat Inbox WhatsApp menampilkan nama grup dari kolom cache database, menambahkan aksi sinkronisasi metadata grup berbasis queue, dan memastikan payload base64/media blob tidak muncul di UI chat. Rendering inbox tetap membaca database lokal dan tidak melakukan HTTP WAHA secara sinkron.

## Problem Statement

Group chat masih dapat menampilkan canonical WhatsApp Group JID seperti `120363028059901162@g.us` karena `InboxWhatsapp::formatChatRow()` memakai fallback raw group ID ketika nama grup kosong. Repository saat ini menyimpan nama grup di `TChat.NamaGrupWhatsapp`; pengguna melaporkan environment memiliki kolom `TChat.GroupName`, sehingga implementasi harus membaca/menulis `GroupName` bila tersedia dan tetap backward compatible dengan `NamaGrupWhatsapp`.

`InboxWhatsapp` juga masih memiliki jalur `wahaGroupName()` yang melakukan HTTP WAHA langsung dan dipanggil saat mapping tidak ditemukan. Walau bukan render utama, ini bertentangan dengan target bahwa metadata grup disinkronkan lewat queue. Media chat sudah memakai route preview/download, tetapi pesan media dengan base64 di `IsiPesan` atau payload-derived body dapat tetap muncul sebagai teks setelah preview.

## Current State

- `src/app/Filament/Pages/InboxWhatsapp.php` memilih `NamaGrupWhatsapp`, payload group name, lalu group JID untuk display identity.
- `src/app/Filament/Pages/InboxWhatsapp.php` memiliki `wahaGroupName()` dengan `Http::get()` ke WAHA.
- `src/app/Jobs/RefreshWahaGroupMetadataJob.php` memanggil `WahaSender::getGroupMetadata()` pada queue `waha-metadata` dan mengupdate `NamaGrupWhatsapp`.
- `src/app/Jobs/ProcessWebhookJob.php` sudah dispatch metadata job setelah webhook group persisted.
- `src/resources/views/filament/pages/inbox-whatsapp.blade.php` menampilkan media preview/download lalu tetap menampilkan `IsiPesan` bila tidak kosong.
- `src/app/Support/WahaMediaPayload.php` dan `src/app/Http/Controllers/WahaMediaController.php` tetap perlu payload base64 untuk endpoint media internal.

## Goals

1. Group chat title SHALL memakai `TChat.GroupName` bila kolom tersedia dan berisi nilai.
2. Jika nama grup database kosong, UI SHALL menampilkan fallback localized `Unknown Group`/`Grup tidak dikenal`, bukan Group JID.
3. Group JID tetap tersedia dalam state internal identity/debug, tetapi tidak menjadi visible title utama.
4. Aksi `Sync Group Name` dispatch `RefreshWahaGroupMetadataJob` hanya untuk group aktif.
5. Aksi `Sync All Group Names` untuk admin dispatch job hanya bagi grup tanpa nama database.
6. Rendering inbox SHALL tidak melakukan HTTP WAHA metadata dan tidak menunggu queue.
7. Media UI SHALL menampilkan preview, filename/label, caption aman, dan download; tidak menampilkan base64/media blob.

## Non-Goals

- Tidak menambah endpoint WAHA baru.
- Tidak mengubah kontrak realtime, pagination, route `/admin/waha-media/{message}`, atau private chat.
- Tidak menghapus payload base64 dari database.
- Tidak membuat migration schema baru; `GroupName` didukung bila sudah ada di database.
- Tidak mengubah modul HistoriChat, AI, atau notification di luar kebutuhan langsung inbox/job.

## Proposed Changes

1. Tambahkan helper group-name cache di `InboxWhatsapp` yang membaca `GroupName` jika ada, fallback ke `NamaGrupWhatsapp`, dan fallback terakhir ke localization `unknown_group`.
2. Update query `loadInbox()`, selected-chat query, profile refresh query, dan sibling query agar memilih `GroupName` via `Schema::hasColumn()`/`SchemaCache` compatible expression.
3. Hapus pemanggilan `wahaGroupName()` dari Inbox path dan ganti dengan dispatch metadata job async.
4. Tambahkan Livewire methods `syncSelectedGroupName()` dan `syncMissingGroupNames()` yang hanya dispatch `RefreshWahaGroupMetadataJob` ke queue existing.
5. Update `RefreshWahaGroupMetadataJob` agar menulis `GroupName` bila kolom tersedia dan tetap menulis `NamaGrupWhatsapp` bila kolom legacy tersedia.
6. Tambahkan tombol/action localized di Blade untuk sync single dan sync all sesuai permission.
7. Sanitasi formatter pesan media: base64/data URI/blob-like `IsiPesan` tidak dikirim ke Livewire state sebagai teks, tetapi caption normal tetap tampil.
8. Tambahkan regresi untuk GroupName display, Unknown Group fallback, queue dispatch, no render-time WAHA request, media preview/download, dan base64 tidak tampil.

## Impacted Areas

- **Inbox UI**: `src/app/Filament/Pages/InboxWhatsapp.php`, `src/resources/views/filament/pages/inbox-whatsapp.blade.php`.
- **Queue/job**: `src/app/Jobs/RefreshWahaGroupMetadataJob.php`.
- **Localization**: `src/resources/lang/id/ui.php`, `src/resources/lang/en/ui.php`.
- **Tests**: `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`, `src/tests/Unit/Jobs/RefreshWahaGroupMetadataJobTest.php`.
- **Database**: Tidak ada migration; code mendukung optional `TChat.GroupName` dan existing `TChat.NamaGrupWhatsapp`.
- **API/Route/Realtime**: Tidak ada perubahan kontrak.

## Risks and Mitigations

- **Risk**: environment tanpa kolom `GroupName` error. **Mitigation**: semua select/update memakai pengecekan schema dan fallback `NamaGrupWhatsapp`.
- **Risk**: grup tanpa nama menjadi sulit di-debug. **Mitigation**: Group JID tetap ada pada internal identity state, bukan title visible.
- **Risk**: queue belum jalan sehingga nama tetap Unknown. **Mitigation**: UI tetap non-blocking dan action memberi summary queued/skipped/failed.
- **Risk**: caption valid terhapus. **Mitigation**: hanya sembunyikan teks yang cocok base64/data URI/blob JSON besar pada pesan media.
- **Risk**: private chat regression. **Mitigation**: branch logic hanya `JenisChat='Grup'` dan existing private realtime test tetap dijalankan.

## Validation

- `cd src; php artisan test --filter='(InboxWhatsappTest|RefreshWahaGroupMetadataJobTest)'`.
- `cd src; php artisan test`.
- `cd src; npm run build`.
- `cd src; php artisan view:cache`.
- `openspec validate improve-group-metadata-cache-and-safe-media-ui --type change --strict --no-interactive`.
- `git diff --check`.

## Rollback

Tidak ada migration. Rollback aplikasi dengan release sebelumnya cukup mengembalikan file code/view/lang/test. Jika metadata queue bermasalah, hentikan worker `waha-metadata`; rendering tetap memakai cached database name atau Unknown Group. Data `GroupName`/`NamaGrupWhatsapp` yang sudah terisi tetap backward compatible.
