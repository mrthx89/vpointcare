# Review Package: Task 2 Final Re-review

## Base
HEAD f31f8c45628ac25d14149b2b8c02b03521b0fe99

## Status
Uncommitted changes only. No commit made per user instruction.

## Stat

## Diff

## Report
# Task 2 Report: WahaMediaPayload

## File Berubah

- `src/app/Support/WahaMediaPayload.php` (baru): helper final stateless untuk inspeksi dan decode media embedded WAHA.
- `.superpowers/sdd/task-2-report.md` (baru): laporan implementasi dan validasi Task 2.

`src/tests/Unit/Support/WahaMediaPayloadTest.php` tidak diubah; test yang telah tersedia dipakai sebagai kontrak TDD.

## TDD

Sebelum implementasi, menjalankan `vendor/bin/phpunit --filter WahaMediaPayloadTest` dari `src` menghasilkan exit code `1`: 26 error `Class "App\\Support\\WahaMediaPayload" not found`. Ini mengonfirmasi fase merah karena helper belum ada.

## Validasi

Dilakukan dari `src` setelah implementasi:

```text
php -l app/Support/WahaMediaPayload.php
No syntax errors detected in app/Support/WahaMediaPayload.php

php -l tests/Unit/Support/WahaMediaPayloadTest.php
No syntax errors detected in tests/Unit/Support/WahaMediaPayloadTest.php

vendor/bin/phpunit --filter WahaMediaPayloadTest
OK (26 tests, 122 assertions)
```

Seluruh command validasi exit code `0`.

## Kepatuhan Spec

- `WahaMediaPayload` adalah class `final` dengan method publik `inspectPayload`, `fromPayloadJson`, `fromDataUri`, dan `fromBinary` sesuai signature Task 2.
- Kandidat diperiksa deterministik melalui key eksplisit yang disetujui; tidak ada pencarian rekursif seluruh payload.
- `data`, `file`, dan `body` root, termasuk `file.data` dan `file.base64`, memerlukan konteks media; plain text tidak dianggap media.
- `inspectPayload` hanya mengembalikan metadata presentasi dan tidak melakukan decode atau mengekspos `contents`, base64, payload, maupun raw media.
- Decode base64 menghapus whitespace lalu memakai `base64_decode(..., true)`; JSON invalid, encoded invalid, dan konten kosong menghasilkan `null`.
- Data URI non-base64 memakai `rawurldecode` untuk kompatibilitas perilaku controller sebelumnya.
- Metadata MIME dan filename mengikuti key yang ditentukan; kategori dan inline minimal tersedia untuk kebutuhan Task 1.

## Kualitas Kode

- Menggunakan `Arr::get` untuk path metadata/kandidat eksplisit dan helper privat kecil untuk menghindari duplikasi.
- Tidak menambah dependency, konfigurasi, route, database, queue, atau perubahan pada controller/Livewire/Blade/OpenSpec tasks.
- Return shape konsisten: inspect tanpa `contents`; decoder menyertakan `contents` hanya untuk consumer endpoint media berikutnya.

## Concern / Batasan

- Validasi MIME/signature lengkap, fallback extension, sanitasi filename, dan aturan keamanan Content-Disposition sengaja belum diimplementasikan karena scope Task 3.
- `fromBinary` pada Task 2 masih memakai MIME deklaratif/payload dan fallback `application/octet-stream`; controller belum memakai helper ini sampai Task 4.

## Review Fix

### Akar Masalah

- `candidate()` salah memperlakukan `file.data` dan `file.base64` sebagai root raw candidate sehingga keduanya membutuhkan media context.
- `hasMediaContext()` hanya mengenali istilah Inggris, sehingga `JenisPesan` webhook berbahasa Indonesia seperti `Gambar` tidak mengaktifkan kandidat root raw.
- Cabang data URI pada `fromPayloadJson()` hanya meneruskan filename deklaratif dan membuang metadata filename payload.

### Perbaikan

- `file.data` dan `file.base64` kini selalu valid sebagai nested candidate; hanya root `data`, `file`, dan `body` yang memerlukan media context.
- Context dan kategori mengenali `Gambar`, `Stiker`, `Dokumen`, serta variasi media WAHA Inggris/Indonesia (`Audio`, `PTT`, `Video`, dan `File`).
- Delegasi data URI meneruskan filename payload bila filename deklaratif kosong, sehingga return konsisten dengan `inspectPayload()`.
- Menambah test kandidat nested `file.*`, context `Gambar`, payload data URI dengan metadata, serta decode `fromDataUri()` untuk base64 dan raw URL-encoded.

### TDD dan Validasi

Test baru dijalankan sebelum perbaikan dan gagal dengan 5 error kandidat/context yang menghasilkan `null` serta 1 failure filename payload data URI bernilai `null`.

Setelah perbaikan, dijalankan dari `src`:

```text
php -l app/Support/WahaMediaPayload.php
No syntax errors detected in app/Support/WahaMediaPayload.php

php -l tests/Unit/Support/WahaMediaPayloadTest.php
No syntax errors detected in tests/Unit/Support/WahaMediaPayloadTest.php

vendor/bin/phpunit --filter WahaMediaPayloadTest
OK (32 tests, 146 assertions)
```

Semua command exit code `0`. Tidak ada perubahan pada controller, Livewire, Blade, OpenSpec tasks, dependency, atau commit Git.

## Review Fix Kedua

### Akar Masalah

`fromPayloadJson()` meneruskan filename payload pada cabang data URI, tetapi tidak memakai metadata MIME payload ketika URI berbentuk `data:;base64,...`. Akibatnya `fromDataUri()` memakai fallback `application/octet-stream`.

### Perbaikan dan Regresi

- Menambah regresi `media.dataUrl = data:;base64,...` dengan `media.mimetype = image/png` dan `media.filename = photo.png`.
- Setelah decode data URI, helper memakai MIME payload hanya apabila MIME deklaratif dan MIME data URI keduanya tidak tersedia; precedence MIME yang ada tetap dipertahankan.
- Filename payload tetap diteruskan sebagai `photo.png`; kategori menjadi `image` dan inline tetap aktif melalui MIME fallback.

### Validasi

Regresi awal gagal dengan expected `image/png` dan actual `application/octet-stream`. Setelah perbaikan, dijalankan dari `src`:

```text
php -l app/Support/WahaMediaPayload.php
No syntax errors detected in app/Support/WahaMediaPayload.php

php -l tests/Unit/Support/WahaMediaPayloadTest.php
No syntax errors detected in tests/Unit/Support/WahaMediaPayloadTest.php

vendor/bin/phpunit --filter WahaMediaPayloadTest
OK (33 tests, 151 assertions)
```

Semua command exit code `0`; tidak ada file di luar kepemilikan Task 2 yang diubah dan tidak ada commit dibuat.

