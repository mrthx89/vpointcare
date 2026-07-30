# Laporan Task 1: Schema Snapshot dan Fixture Test

## Status

DONE_WITH_CONCERNS

## OpenSpec

- Change: `chatbot-whatsappasli-improve`.
- Task OpenSpec selesai: 1.1 sampai 1.5.
- Task job/ingestion/AI khusus tidak dibuat pada task ini sesuai resolusi konflik plan; tidak ada test placeholder baru.

## Audit Awal

- `TChat` sudah memiliki identifier dan foto profil legacy (`IdWahaTerdeteksi`, `NomorWhatsappTerdeteksi`, `UrlFotoProfil`, dan `TglFotoProfilDiambil`), tetapi belum memiliki snapshot nama/status/error identitas WAHA.
- `TChatD` sudah memiliki nomor/nama pengirim grup, tetapi belum memiliki JID pengirim atau snapshot foto participant.
- `MPengaturanAi` belum memiliki batas idle auto-reply yang tervalidasi.

## Implementasi

- Menambahkan migration SQL Server guarded `2026_07_30_000001_add_waha_identity_snapshot_to_chat.php` dengan `DB::unprepared()` dan `COL_LENGTH()`.
- Migration menambahkan snapshot `TChat`, metadata participant `TChatD`, serta `MPengaturanAi.BatasSesiAutoReplyMenit` dengan default `60` dan check `1..1440`.
- Menambahkan index `IX_TChat_StatusIdentitasWaha_TglIdentitasWahaDiambil` pada `(StatusIdentitasWaha, TglIdentitasWahaDiambil)` tanpa mengubah index JID.
- Setiap kolom baru diberi extended-property marker `WACS_Migration_20260730_000001`; `down()` hanya menghapus index, constraint, marker, dan kolom ketika marker tersebut membuktikan migration ini yang membuat kolom dependency. Tidak menghapus `TChat`, `TChatD`, maupun mapping master.
- Menyelaraskan `DATABASE_SCHEMA_WACS.sql` dan fixture SQLite `InboxWhatsappTest` dengan seluruh kolom snapshot yang terdampak serta data fixture nyata.

## Validasi

- PASS: `php -l database/migrations/2026_07_30_000001_add_waha_identity_snapshot_to_chat.php` - tidak ada syntax error.
- PASS (setelah perbaikan ownership): `php -l database/migrations/2026_07_30_000001_add_waha_identity_snapshot_to_chat.php`, static marker check untuk sembilan kolom, dan `git diff --check`.
- PASS: `php -l tests/Feature/Filament/Pages/InboxWhatsappTest.php` - tidak ada syntax error.
- PASS: static contract check untuk seluruh kolom, constraint, dan index required.
- PASS: `git diff --check` - tidak ada whitespace error.
- PASS: `openspec validate chatbot-whatsappasli-improve --strict` - change valid.
- PASS: review ownership-safe memastikan index dan constraint hanya dibuat/dihapus saat marker kolom dependency milik migration ini ada.
- BLOCKED: `php artisan test --filter=InboxWhatsappTest` - Artisan melaporkan `Command "test" is not defined` sebelum test berjalan.
- Evidence blocker: `php -m` hanya menampilkan `PDO`, `pdo_mysql`, `pdo_pgsql`, dan `pdo_sqlsrv`; `pdo_sqlite` tidak tersedia. `vendor/bin/phpunit` juga tidak ada.

## Self-Review

- Tipe dan batas semua kolom sesuai task brief: `nvarchar`, `varchar`, `datetime2`, default `60`, dan check `BETWEEN 1 AND 1440`.
- Migration aman untuk database SQL Server existing melalui guard per tabel/kolom/index/constraint dan marker ownership per kolom.
- Snapshot tetap terpisah dari `MNomorWhatsapp`, `MGrupWhatsapp`, customer, dan instansi; tidak ada secret atau payload WAHA mentah yang ditambahkan.
- Tidak ada dependency, migration destruktif, test placeholder, atau commit Git.

## Deployment dan Rollback

- Sebelum production, backup database SQL Server dan hentikan worker yang memakai schema ini.
- Deploy migration lalu jalankan `php artisan migrate --force`; restart worker queue `webhooks` saat task job berikutnya sudah dideploy.
- Rollback batch migration ini bila diperlukan; `down()` hanya menghapus index/constraint/kolom snapshot baru dan tidak menghapus chat, pesan, atau mapping master.

## Concern

- Integrasi migration terhadap SQL Server dan `InboxWhatsappTest` belum dapat dieksekusi pada environment ini sampai `pdo_sqlite` serta dependency PHPUnit/Laravel test command tersedia.
