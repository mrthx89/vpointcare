# Change: Fix Group Chat Sync & Slow Loading

## Summary

Perbaiki sinkronisasi realtime dan performa Inbox WhatsApp untuk percakapan grup tanpa mengubah kontrak webhook, event broadcast, atau perilaku chat pribadi. Change ini melanjutkan perbaikan deduplikasi group yang sudah ada.

## Why

Pengguna melihat pesan grup terbaru terlambat atau tidak muncul karena room besar mengambil 200 pesan tertua (`ORDER BY TglPesan ASC` lalu `LIMIT 200`), sedangkan refresh realtime harus melewati operasi loading mahal. Inbox juga melakukan query detail/payload satu per satu, lookup nama grup melalui HTTP WAHA secara sinkron, dan scan `PayloadJson` untuk setiap room legacy.

## Problem Statement

- **Latest message tidak terlihat**: `InboxWhatsapp::selectChat()` membatasi 200 baris setelah mengurutkan menaik. Grup dengan lebih dari 200 pesan selalu merender jendela pesan tertua.
- **Realtime tertahan**: `handleInboxUpdate()` masuk ke `loadInbox()`, yang memproses hingga 50 room sebelum Livewire selesai.
- **N+1**: `loadInbox()` melakukan query `TChatD` per row dan `formatChatRow()` kembali membaca payload terbaru per row.
- **I/O eksternal pada render**: `wahaGroupName()` melakukan request dengan timeout lima detik untuk cache-miss group.
- **Legacy scan**: `groupSiblingIds()` dan `findOrCreateChat()` memakai `LIKE '%group_jid%'` pada `TChatD.PayloadJson`.
- **Worker**: webhook/broadcast memakai queue `webhooks`/`broadcasts`, tetapi dokumentasi worker umum belum menyatakan daftar queue tersebut.

## Current State

- `POST /webhooks/waha/{token?}` memvalidasi token/HMAC lalu dispatch `ProcessWebhookJob` ke queue `webhooks`.
- `WahaWebhookProcessor::process()` menyimpan `TChatD`, memperbarui `TChat`, lalu `ProcessWebhookJob` menjadwalkan `SendBroadcastDebouncedJob` ke queue `broadcasts`.
- `WahaInboxUpdated` memakai channel public `waha-inbox`; `src/resources/js/echo.js` meneruskan `.inbox.updated` ke Livewire `waha-inbox-updated`.
- Perbaikan sebelumnya sudah memprioritaskan JID `@g.us`, menggabungkan beberapa sibling group, dan memiliki regresi dasar di `InboxWhatsappTest` serta `WahaWebhookProcessorTest`.
- `TChat` memiliki index identitas WAHA dan `TChatD` memiliki `(IdChat, TglPesan)`, tetapi `PayloadJson` tidak dapat dioptimalkan dengan index biasa untuk substring search.
- Tidak ada migration schema pada change ini; legacy identity akan dinormalisasi dengan command idempotent.

## Goals

1. Pesan grup terbaru tampil otomatis setelah webhook tersimpan dan event realtime diterima.
2. Group mapped, unmapped, dan legacy memakai canonical JID per session tanpa room per anggota.
3. Room besar menampilkan latest window dan cursor pagination tanpa duplicate.
4. Loading inbox tidak melakukan HTTP WAHA atau query detail/payload per row.
5. Chat pribadi, identity mode, media, unread count, dan route tetap kompatibel.
6. Queue retry, idempotency, logging, dan rollback operasional terdokumentasi.

## Non-Goals

- Tidak mengubah format payload/route webhook, Reverb, Echo, Filament, SQL Server, atau driver queue.
- Tidak merge/delete fisik row `TChat` legacy.
- Tidak mengubah AI, ticketing, permission, atau master mapping selain identity group yang kosong/terdeteksi.

## Proposed Changes

1. Ekstrak group JID lewat helper bersama dan backfill `TChat.IdWahaTerdeteksi`; payload `LIKE` hanya dipakai command backfill.
2. Ubah preview inbox menjadi query set-based untuk detail terbaru per `IdChat`; hapus query per room dari `loadInbox()`/`formatChatRow()`.
3. Pindahkan lookup group metadata ke queue `waha-metadata`; persist nama dan broadcast setelah update.
4. Ambil 100 pesan terbaru secara descending, balik untuk render, dan gunakan cursor `(TglPesan, Id)` untuk pesan lama.
5. Pisahkan refresh list dan room aktif agar event tidak memanggil `selectChat()` dua kali.
6. Dokumentasikan worker, backfill, backup, smoke test, dan `npm run build` bila asset berubah.

## Capabilities

### New Capabilities

- Tidak ada capability baru; perubahan memperluas kontrak Inbox yang sudah ada.

### Modified Capabilities

- `care-desk`: requirement `WhatsApp Inbox` diperluas agar room grup menampilkan jendela terbaru, pagination cursor bebas duplikasi, dan realtime refresh tidak menunggu I/O WAHA.
- `care-desk`: requirement `WhatsApp Webhook Intake` diperjelas agar persistence/idempotency selesai sebelum broadcast, sedangkan metadata grup boleh diproses async.

## Impacted Areas

- **Application**: `src/app/Filament/Pages/InboxWhatsapp.php`, `src/app/Services/Waha/WahaWebhookProcessor.php`, `src/app/Support/WahaChatHelper.php`.
- **Queue/integration**: `src/app/Jobs/ProcessWebhookJob.php`, `src/app/Jobs/RefreshWahaGroupMetadataJob.php` (baru), `src/app/Services/Waha/WahaSender.php`.
- **Data maintenance**: `src/app/Console/Commands/BackfillGroupChatIdentity.php` (baru); tidak ada migration atau perubahan tabel.
- **UI/localization**: `src/resources/views/filament/pages/inbox-whatsapp.blade.php`, `src/resources/lang/id/ui.php`, `src/resources/lang/en/ui.php`.
- **Tests**: `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`, `src/tests/Unit/Services/Waha/WahaWebhookProcessorTest.php`, test job/command baru.
- **Deployment/docs**: `README.md`, worker queue dan asset build; tidak mengubah secret atau API endpoint.
- **Permission/security**: tidak ada permission baru; command backfill harus dijalankan oleh operator deployment dan tidak menampilkan payload/secret.

## Risks and Mitigations

- **Risk**: legacy row belum ter-backfill sehingga sibling tidak tergabung. **Mitigation**: command `--dry-run`, backup sebelum eksekusi, laporan jumlah row gagal, dan fallback diagnostik hanya untuk command.
- **Risk**: job metadata gagal atau WAHA lambat. **Mitigation**: metadata bukan dependency render, timeout/retry terbatas, circuit behavior `WahaSender`, dan nama/JID tersimpan tetap valid.
- **Risk**: cursor pagination menghilangkan pesan dari state. **Mitigation**: ordering deterministik `(TglPesan, Id)`, unique-by-Id saat prepend, dan test 250+ pesan.
- **Risk**: private chat ikut berubah. **Mitigation**: branch group-only untuk canonicalization/metadata, test private realtime dan identity mode, serta perbandingan query count.

## Validation

- `php -l` untuk seluruh PHP yang berubah.
- `php artisan test --filter='(InboxWhatsappTest|WahaWebhookProcessorTest|BackfillGroupChatIdentityTest|RefreshWahaGroupMetadataJobTest)'` dari `src`.
- `php artisan test`, `vendor/bin/pint --test`, dan `npm run build` bila view/JS/localization berubah.
- SQL Server smoke test dengan 100 room, 100.000 detail, dan satu grup 250+ pesan: tidak ada HTTP WAHA saat render, latest window berisi pesan terakhir, query payload scan tidak terjadi pada request, serta p95 open room <= 2 detik di environment benchmark.
- Realtime smoke test: webhook valid → row/message committed → event `inbox.updated` diterima → pesan grup terlihat tanpa full page reload dalam <= 3 detik ketika tiga worker queue aktif.

## Rollback

- Tidak ada migration yang perlu di-rollback.
- Sebelum backfill, lakukan backup SQL Server dan simpan output `--dry-run`; rollback data dilakukan dengan restore backup atau skrip operator yang hanya mengosongkan nilai identity yang ditulis command berdasarkan daftar output.
- Rollback aplikasi dengan release sebelumnya tetap kompatibel karena kolom existing tidak berubah dan sibling `TChat` tidak dihapus.
- Jika job metadata bermasalah, hentikan worker `waha-metadata`; persistence webhook, broadcast, dan tampilan fallback master/JID tetap berjalan.
