# Review Package: Task 6 Re-review

## Base
HEAD f31f8c45628ac25d14149b2b8c02b03521b0fe99

## Status
Uncommitted changes only. No commit made per user instruction.

## Stat

## Diff

## Report
# Task 6 Report: Inbox WhatsApp Livewire RED Tests

## OpenSpec

- Change: `inbox-whatsapp-improvement`
- Task: 6 - Tulis Livewire Test Merah untuk Media dan Identitas

## Files

- Created `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`.
- Production files, Blade, localization, OpenSpec tasks, migration, route, dan dependency tidak diubah.

## Test Coverage

- `identityDisplayMode` default `whatsapp` dan input invalid kembali ke `whatsapp`.
- Fixture grup memisahkan JID chat `120363999999999999@g.us`, participant `628222222222@c.us`, dan mapped group `120363000000000000@g.us`.
- Proyeksi `Identity` WhatsApp/internal, sender member, `MediaUrl`, `MediaDownloadUrl`, dan larangan Base64 pada state message.
- Fixture pribadi membedakan raw contact dari `MNomorWhatsapp`, lalu memverifikasi proyeksi raw/internal serta preservasi `filterType`, `selectedChatId`, dan urutan message saat toggle.
- Schema SQLite dibuat langsung di `setUp`; tidak memakai migration SQL Server.

## Validation

```powershell
cd src
php -l tests/Feature/Filament/Pages/InboxWhatsappTest.php
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --filter InboxWhatsappTest
```

- Syntax: PASS (`No syntax errors detected`).
- PHPUnit: RED yang diharapkan, 3 tests/3 failures tanpa setup atau bootstrap error.
- Failure saat ini: `identityDisplayMode` belum ada dan chat row belum memiliki key `Identity`; implementasi Task 7 juga akan memenuhi assertion lanjutan untuk `PayloadJson`, `MediaDownloadUrl`, dan `SenderNumber`.

## Setup Note

- Schema test juga memuat `TChat.DiambilOleh`, `TglDiambil`, dan `TglEdit` karena query Inbox saat ini selalu melakukan join/auto-claim pada kolom tersebut, meski branch profil WAHA tidak aktif.
- User test tetap memakai model internal `Pengguna` dan hanya memperoleh permission `inbox.view`, sehingga UI reply/manage tidak menuntut tabel di luar schema Task 6.

## Deployment and Rollback

- Tidak ada migration, deployment action, queue/restart, atau rollback data untuk Task 6.

## Review Follow-up

- `TglDiambil` dan `TglEdit` dihapus dari schema SQLite. `DiambilOleh` tetap minimum yang diperlukan karena `InboxWhatsapp::loadInbox()` selalu melakukan `leftJoin` ke `c.DiambilOleh`, bahkan ketika branch optional auto-claim tidak dipakai.
- Fixture mengisi `DiambilOleh` dengan `agent-existing`, sehingga auto-claim tidak berjalan dan test tidak memerlukan kolom write `TglDiambil` atau `TglEdit`.
- Test grup sekarang memeriksa `selectedChat.Identity` untuk JID/nama grup WhatsApp dan nama grup internal, serta membuktikan JID participant tidak menggantikan identitas grup.
- Assertion Base64 sekarang memeriksa gabungan public state `messages`, `selectedChat`, dan `chatRows`.

