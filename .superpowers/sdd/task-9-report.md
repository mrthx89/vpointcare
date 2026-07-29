Change OpenSpec: `inbox-whatsapp-improvement`
Task: Task 9 regression/security + fix review Task 8

## File Berubah

- `src/app/Filament/Pages/InboxWhatsapp.php`
  - Menjaga `UrlMedia` data URI/raw base64 agar tidak diserialisasi ke public Livewire state.
  - Menambahkan `mediaPresentationCategory()` agar preview URL mengikuti allowlist `WahaMediaPayload::canPreviewInline()`.
  - Menambahkan kategori PDF eksplisit untuk preview aman.
- `src/resources/views/filament/pages/inbox-whatsapp.blade.php`
  - Menambahkan `role="group"` pada toggle mode identitas.
- `src/tests/Feature/Http/Controllers/WahaMediaControllerTest.php`
  - Coverage data URI, public storage, HTTP JSON base64, fallback reason aman, dan filename/header injection.
- `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`
  - Coverage internal group ID non-JID, URL legacy, non-media null URL, data URI tidak masuk state, preview allowlist, dan SVG/HTML/unknown sebagai file card.

## Command Validasi Terakhir

- `php -l app/Filament/Pages/InboxWhatsapp.php` PASS.
- `php -l resources/views/filament/pages/inbox-whatsapp.blade.php` PASS.
- `php -l tests/Feature/Http/Controllers/WahaMediaControllerTest.php` PASS.
- `php -l tests/Feature/Filament/Pages/InboxWhatsappTest.php` PASS.
- `php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --filter WahaMediaControllerTest` PASS, 16 tests, 101 assertions.
- `php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --filter InboxWhatsappTest` PASS, 9 tests, 97 assertions.
- `vendor/bin/pint --test tests/Feature/Filament/Pages/InboxWhatsappTest.php app/Filament/Pages/InboxWhatsapp.php resources/views/filament/pages/inbox-whatsapp.blade.php` PASS.

## Expected Result

- Media URL/data URI/storage/JSON/base64 tetap dilayani melalui route auth internal.
- Base64 dari PayloadJson atau UrlMedia data URI tidak masuk state/render Livewire.
- Preview inline hanya untuk raster image aman, audio, video, dan PDF; SVG/HTML/unknown menjadi file card/download.
- Grup internal tidak memakai nomor non-JID sebagai group/chat ID bila raw WhatsApp JID tersedia.
- Semua media memiliki route download `download=1`.

## Spec Compliance Self-Review

- Memenuhi skenario WAHA Media: URL, embedded base64, fallback URL gagal, MIME, preview aman, download, malformed response aman, guest auth.
- Memenuhi skenario WhatsApp Inbox: default mode whatsapp, mode internal, invalid mode fallback, identitas grup/personal, pemisahan sender grup.
- Tidak menambah migration, route, dependency, queue, scheduler, atau perubahan schema produksi.

## Code Quality Self-Review

- Patch minimal, terpusat pada helper/controller/Livewire/Blade yang discope.
- Security state dan preview allowlist kini ditest pada jalur raw URL serta embedded payload.
- Tidak ada secret atau payload media yang dicatat/dirender dalam test evidence.


## Fix Final Review 2026-07-29

- `WahaMediaPayload::inspectPayload()` sekarang memakai jalur decode strict `fromPayloadJson()` lalu menghapus `contents`, sehingga malformed Base64 tidak lagi dianggap media valid oleh Inbox.
- `WahaMediaController` sekarang memberi ASCII fallback eksplisit ke `ResponseHeaderBag::makeDisposition()` untuk filename Unicode.
- Regression tambahan:
  - `WahaMediaPayloadTest` memastikan `inspectPayload()` menolak malformed Base64.
  - `WahaMediaControllerTest` memastikan filename Unicode (`laporan-?.pdf`) tidak menghasilkan 500 dan memiliki `filename` ASCII + `filename*` UTF-8.
  - `InboxWhatsappTest` memastikan malformed embedded Base64 tidak membuat `MediaUrl`/`MediaDownloadUrl` dan tidak masuk Livewire state.

## Command Validasi Setelah Fix Final Review

- `php -l app/Support/WahaMediaPayload.php` PASS.
- `php -l app/Http/Controllers/WahaMediaController.php` PASS.
- `php -l tests/Unit/Support/WahaMediaPayloadTest.php` PASS.
- `php -l tests/Feature/Http/Controllers/WahaMediaControllerTest.php` PASS.
- `php -l tests/Feature/Filament/Pages/InboxWhatsappTest.php` PASS.
- `php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --filter WahaMediaPayloadTest` PASS, 40 tests, 186 assertions.
- `php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --filter WahaMediaControllerTest` PASS, 17 tests, 105 assertions.
- `php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --filter InboxWhatsappTest` PASS, 10 tests, 102 assertions.
- `vendor/bin/pint --test app/Support/WahaMediaPayload.php app/Http/Controllers/WahaMediaController.php tests/Unit/Support/WahaMediaPayloadTest.php tests/Feature/Http/Controllers/WahaMediaControllerTest.php tests/Feature/Filament/Pages/InboxWhatsappTest.php` PASS.
- `npm run build` PASS.
- `openspec validate inbox-whatsapp-improvement --strict` PASS.
- `php -d extension=pdo_sqlite -d extension=sqlite3 artisan test` BLOCKED sebelum test oleh SQL Server ODBC lokal: `Encryption not supported on the client`.
