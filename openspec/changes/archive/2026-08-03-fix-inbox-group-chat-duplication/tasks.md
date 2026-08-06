## 1. Baseline Existing Group Fix

- [x] 1.1 `WahaWebhookProcessor::parseMessage()` memprioritaskan kandidat group JID `@g.us` dan menyimpan `_data.notifyName`.
- [x] 1.2 `findOrCreateChat()` mencari group unmapped melalui `IdWahaTerdeteksi`/`NomorWhatsapp` dalam scope `IdSesiWhatsapp` tanpa payload scan legacy.
- [x] 1.3 `InboxWhatsapp` menggabungkan row group sibling untuk mapped dan unmapped room serta mempertahankan private room.
- [x] 1.4 Regresi parser dan sibling aggregation tersedia di `src/tests/Unit/Services/Waha/WahaWebhookProcessorTest.php` dan `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`.
- [x] 1.5 Regresi dua sesi dengan group JID sama memastikan pesan memilih `TChat` pada sesi webhook.

## 2. Regression Tests First

- [x] 2.1 Tambahkan regresi group >200: latest window 100 row dan `hasOlderMessages`.
- [x] 2.2 Tambahkan regresi load older tanpa duplicate ID melalui `loadOlderMessages()`.
- [x] 2.3 Tambahkan regresi render group tanpa HTTP WAHA.
- [x] 2.4 Tambahkan regresi private realtime; existing mapped/unmapped group tests tetap dipertahankan.
- [x] 2.5 Jalankan test sebelum implementasi; oldest-window dan HTTP render gagal sesuai defect, private realtime lulus.

## 3. Canonical Group Identity and Legacy Backfill

- [x] 3.1 Tambahkan helper canonical `WahaChatHelper::groupJidFromPayload()` dengan validasi suffix `@g.us`.
- [x] 3.2 Refactor parser agar memakai helper tanpa mengubah output parser/idempotency.
- [x] 3.3 Buat command backfill dry-run/idempotent chunk 500 untuk `TChat.IdWahaTerdeteksi`, hanya memproses identity kosong dan memakai update guarded.
- [x] 3.4 Tambahkan `src/tests/Feature/Console/BackfillGroupChatIdentityTest.php` untuk dry-run, no-overwrite, write idempotent, payload tanpa group JID, dan larangan delete/merge row.
- [x] 3.5 Hapus payload `LIKE` dari jalur normal webhook dan sibling resolver.

## 4. Set-based Inbox Loading

- [x] 4.1 Gunakan derived `ROW_NUMBER()` SQL Server untuk detail terbaru per room.
- [x] 4.2 Ubah `loadInbox()` menjadi set-based dan pass payload terbaru ke formatter.
- [x] 4.3 Pertahankan dedupe group, unread, filter/status, dan private state.
- [x] 4.4 Sibling resolver memakai identity direct, session, dan mapping tanpa payload scan.
- [x] 4.5 Tambahkan assertion query-shape/performance: 50 room tidak boleh menghasilkan query detail/payload berulang per room.

## 5. Async Group Metadata

- [x] 5.1 Tambahkan `WahaSender::getGroupMetadata()` dengan logging/circuit behavior existing.
- [x] 5.2 Buat metadata job queue `waha-metadata`, retry/backoff, deduplication, dan failure log.
- [x] 5.3 Dispatch metadata setelah persistence webhook; metadata terpisah dari broadcast/message persistence.
- [x] 5.4 Formatter tidak lagi memanggil HTTP/WAHA.
- [x] 5.5 Tambahkan `src/tests/Unit/Jobs/RefreshWahaGroupMetadataJobTest.php` untuk success, non-success, retry/failure, sibling update, cache lock, dan broadcast follow-up.

## 6. Latest Window and Cursor Pagination

- [x] 6.1 Tambahkan page size 100 dan state cursor `(TglPesan, Id)`.
- [x] 6.2 `selectChat()` mengambil latest page descending dan render chronological.
- [x] 6.3 Tambahkan `loadOlderMessages()` dengan cursor predicate dan unique merge.
- [x] 6.6 Pertahankan preview/download URL dan kategori media untuk pesan payload-only pada setiap halaman pagination.
- [x] 6.4 Ubah `src/resources/views/filament/pages/inbox-whatsapp.blade.php` untuk action localized “load older messages” hanya ketika `hasOlderMessages` true.
- [x] 6.5 Tambahkan key label/status yang setara di `src/resources/lang/id/ui.php` dan `src/resources/lang/en/ui.php`.

## 7. Single Realtime Refresh

- [x] 7.1 Sederhanakan realtime refresh menjadi satu `loadInbox()` pass.
- [x] 7.2 Pastikan `src/resources/js/echo.js` tetap memakai channel `waha-inbox`, event `.inbox.updated`, payload `chat_id`, dan debounce 300 ms tanpa mengubah kontrak browser; source existing diverifikasi dan build lulus.
- [x] 7.3 Tambahkan integration coverage untuk event sibling mapped/unmapped/legacy, burst event, no duplicate message IDs, dan private chat regression.
- [x] 7.4 Tambahkan observability context untuk webhook persisted, broadcast queued, metadata queued/failed, dan backfill result tanpa secret atau payload body.

## 8. Validation and Deployment

- [x] 8.1 Jalankan `cd src; php -l` pada setiap PHP changed file.
- [x] 8.2 Jalankan `cd src; php artisan test --filter='(InboxWhatsappTest|WahaWebhookProcessorTest|BackfillGroupChatIdentityTest|RefreshWahaGroupMetadataJobTest)'`; semua test yang tersedia PASS.
- [x] 8.3 Jalankan `cd src; php artisan test` dan `vendor/bin/pint --test`; full test PASS, Pint global masih melaporkan formatter drift legacy yang tidak terkait.
- [x] 8.4 Jalankan `cd src; npm run build` karena view/JS/localization dapat berubah; Vite build sukses.
- [ ] 8.5 **Manual Deployment Task:** Pada SQL Server staging, backup lalu jalankan `php artisan waha:backfill-group-chat-identity --dry-run`, review output, jalankan command write, dan verifikasi repeat menghasilkan `updated=0`.
- [ ] 8.6 **Manual Deployment Task:** Verifikasi worker `webhooks`, `broadcasts`, `waha-metadata`, Reverb, latest/older pagination, duplicate webhook, dan private regression sebelum production.
- [ ] 8.7 **Manual Deployment Task:** Dokumentasikan rollback release dan backup restore; tidak ada migration schema atau destructive merge.
