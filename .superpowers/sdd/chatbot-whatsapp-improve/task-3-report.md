# Laporan Task 3: Queue Sinkronisasi Identitas Chat

## Status

Implementasi Task 3 selesai secara statis pada change OpenSpec `chatbot-whatsappasli-improve`. Runtime test tidak dapat dijalankan karena instalasi Artisan tidak menyediakan perintah `test` dan `vendor/bin/pint` tidak tersedia.

## Perubahan

- Menambahkan `src/app/Jobs/SyncWahaChatIdentityJob.php` pada queue `webhooks` dengan `tries=3`, `timeout=30`, backoff `[30, 120]`, dan cache dedupe `waha:identity-sync:{chatId}` selama 60 detik.
- Job membaca `TChat` dan `MSesiWhatsapp`; untuk grup memilih raw JID `@g.us` dari `NomorWhatsapp`, `IdWahaTerdeteksi`, payload detail, lalu fallback `MGrupWhatsapp.IdGrupWaha` yang tervalidasi. Identifier participant tidak dipakai untuk metadata atau foto grup.
- Job mempertahankan `@lid`, menyimpan nomor hasil resolver ke `NomorWhatsappTerdeteksi`, memperbarui snapshot secara atomik, serta menyimpan error generik maksimal aman tanpa respons mentah atau secret.
- Kegagalan metadata tidak mengubah nama, foto, atau nomor snapshot terakhir dan melempar error generik agar Laravel menerapkan retry. Handler `failed()` tetap menandai status gagal tanpa menghapus snapshot.
- Broadcast debounce hanya diantrikan jika field identitas snapshot berubah.
- `ProcessWebhookJob` kini mengantrikan sinkronisasi setelah `process()` sukses, nonduplicate, dan nonignored, sehingga transaksi webhook telah selesai sebelum request metadata dimulai.
- Menambahkan test feature untuk grup dengan mapping stale, personal `@lid`, kegagalan dan preservasi snapshot, dedupe, broadcast berdasarkan perubahan snapshot, serta dispatch webhook sukses/duplicate/ignored.

## Validasi

| Command | Hasil |
| --- | --- |
| `cd src; php -l app/Jobs/SyncWahaChatIdentityJob.php` | Lulus, tidak ada syntax error. |
| `cd src; php -l app/Jobs/ProcessWebhookJob.php` | Lulus, tidak ada syntax error. |
| `cd src; php -l tests/Feature/Waha/WahaChatIdentitySyncTest.php` | Lulus, tidak ada syntax error. |
| `openspec validate chatbot-whatsappasli-improve --strict` | Lulus: change valid. |
| `git diff --check` | Lulus, tanpa whitespace error. |
| `cd src; php artisan test --filter=WahaChatIdentitySyncTest` | Tidak dapat dijalankan: `Command "test" is not defined`; exit code 1. |
| `cd src; vendor/bin/pint --test ...` | Tidak dapat dijalankan: `vendor/bin/pint` tidak tersedia. |

## Deployment

- Tidak ada migration baru pada task ini; dependency kolom snapshot yang sudah ada wajib dimigrasikan lebih dahulu.
- Setelah deploy, restart worker queue `webhooks` agar class job baru dimuat.
- Jalankan targeted test dan Pint pada environment dengan dependency development lengkap sebelum production.
