# Change: Perbaiki Nama Pembuat Catatan Internal Chat

## Summary

Catatan internal chat (`TChatDCatatanInternal`) selalu menampilkan pembuatnya sebagai "Sistem" di Inbox WhatsApp maupun halaman Detail Sesi Chat, meskipun kolom `DibuatOleh` sudah terisi dengan benar. Penyebabnya adalah query yang menargetkan tabel `Pengguna` yang tidak pernah ada di schema WACS; tabel pengguna yang benar adalah `MPengguna`. Perubahan ini mengarahkan lookup ke tabel yang benar dan memusatkan logikanya agar tidak terduplikasi di dua halaman.

## Problem Statement

Agent dan supervisor memakai catatan internal untuk serah-terima penanganan chat. Karena nama pembuat catatan tidak pernah tampil, catatan kehilangan akuntabilitas: pembaca tidak tahu siapa yang menulis, kapan keputusan diambil, dan kepada siapa harus konfirmasi. Data untuk menampilkannya sudah tersimpan lengkap, hanya tidak pernah terbaca.

Bug ini tidak menimbulkan error yang terlihat karena query dijaga `Schema::hasTable('Pengguna')` yang selalu mengembalikan `false`, sehingga sistem diam-diam jatuh ke label fallback.

## Current State

Verifikasi pada source code aktual:

- Tabel pengguna aplikasi adalah `MPengguna` (`src/script/DATABASE_SCHEMA_WACS.sql`, model `App\Models\Master\Pengguna` dengan `protected $table = 'MPengguna'`).
- Tabel `users` bawaan Laravel sudah dihapus oleh migration `2026_05_06_000004_drop_users_after_pengguna_auth_refactor.php`. Tabel bernama `Pengguna` (tanpa prefix `M`) tidak pernah ada pada schema mana pun.
- `src/app/Filament/Pages/InboxWhatsapp.php:324-325` — `loadInternalNotes()` memeriksa `Schema::hasTable('Pengguna')` lalu query `DB::table('Pengguna')`. Hasil: `DibuatOlehNama` selalu `__('ui.common.system')`.
- `src/app/Filament/Pages/ViewChatSession.php:153,161` — pola yang sama pada `$hasPengguna` dan `DB::table('Pengguna')`. Hasil: `NamaPembuat` selalu `__('ui.common.system')`.
- Penulisan catatan sudah benar: `InboxWhatsapp::saveInternalNote()` mengisi `DibuatOleh` dari `currentPenggunaId()` (ID `MPengguna` pengguna login), sehingga data historis yang sudah ada tetap dapat dipulihkan tampilannya tanpa migrasi data.
- Komentar pada `ViewChatSession` menyatakan "ikuti logika di InboxWhatsapp.php", yang mengonfirmasi bug ini tersalin dari satu halaman ke halaman lain.

## Goals

- Nama pembuat catatan internal tampil benar di Inbox WhatsApp dan Detail Sesi Chat, termasuk untuk catatan yang sudah tersimpan sebelumnya.
- Catatan dengan `DibuatOleh` kosong atau merujuk pengguna yang sudah dihapus tetap tampil aman sebagai "Sistem", bukan error.
- Logika resolusi nama pembuat berada di satu tempat sehingga tidak terduplikasi lagi.
- Menghapus pemeriksaan `Schema::hasTable('Pengguna')` yang menyesatkan.

## Non-Goals

- Tidak mengubah struktur tabel `TChatDCatatanInternal` dan tidak melakukan migrasi data.
- Tidak mengubah alur pembuatan, permission, atau tampilan isi catatan internal.
- Tidak menambahkan avatar/foto profil pembuat catatan.
- Tidak mengubah cara nama pembalas pesan (`TChatD.DibalasOleh`) ditampilkan; jalur itu sudah benar memakai join ke `MPengguna`.

## Proposed Changes

1. **Satu sumber logika.** Membuat trait `App\Filament\Concerns\ResolvesCatatanInternal` yang menyediakan `catatanInternalRows(string $chatId): array`, lalu dipakai oleh `InboxWhatsapp` dan `ViewChatSession`. Trait dipilih karena kedua pemakainya adalah Filament Page dan repo sudah memakai pola ini pada `App\Filament\Concerns\HasMenuBreadcrumbs`.

2. **Query tunggal, bukan N+1.** Helper mengambil seluruh baris catatan untuk satu chat, mengumpulkan `DibuatOleh` yang unik dan tidak kosong, lalu melakukan satu query `MPengguna whereIn('Id', ...)` untuk memetakan `Id => NamaPengguna`. Implementasi saat ini melakukan satu query per baris catatan.

3. **Fallback aman.** Bila `DibuatOleh` kosong, tidak ditemukan di `MPengguna`, atau nama kosong, sistem memakai `__('ui.common.system')`.

4. **Hapus pemeriksaan tabel yang salah.** Menghapus seluruh referensi `Schema::hasTable('Pengguna')` dan `DB::table('Pengguna')` pada kedua halaman. `MPengguna` adalah tabel inti yang selalu ada, sehingga tidak perlu pemeriksaan keberadaan tabel.

5. **Bentuk data konsisten.** Kedua halaman memakai kunci array yang sama sehingga blade tidak perlu dua konvensi berbeda. `InboxWhatsapp` saat ini memakai `DibuatOlehNama`, `ViewChatSession` memakai `NamaPembuat`. Helper mengembalikan keduanya agar blade existing tidak perlu diubah dan tidak ada risiko regresi tampilan.

## Impacted Areas

| Area | Detail |
| --- | --- |
| File baru | `src/app/Filament/Concerns/ResolvesCatatanInternal.php` |
| File diubah | `src/app/Filament/Pages/InboxWhatsapp.php` (`loadInternalNotes()`), `src/app/Filament/Pages/ViewChatSession.php` (`loadSession()`) |
| Database | Tidak ada perubahan schema, tidak ada migration, tidak ada perubahan data |
| API/Route | Tidak ada |
| Permission | Tidak ada; akses catatan tetap mengikuti `inbox.view`/`inbox.manage` dan `chat_history.view` |
| Localization | Tidak ada key baru; tetap memakai `ui.common.system` yang sudah ada di `id` dan `en` |
| Queue/Scheduler/Broadcast | Tidak ada |
| Frontend asset | Tidak ada perubahan blade/CSS/JS, sehingga `npm run build` tidak diperlukan |
| Deployment | Cukup deploy kode + `php artisan optimize:clear` |

## Risks and Mitigations

| Risiko | Mitigasi |
| --- | --- |
| Blade memakai kunci array yang berbeda per halaman dan bisa pecah bila kunci diubah | Helper mengembalikan kunci lama pada kedua halaman (`DibuatOlehNama` dan `NamaPembuat`); blade tidak diubah |
| Cache `Schema::hasTable('Pengguna')` bernilai `false` masih tersimpan di cache aplikasi | Pemeriksaan tersebut dihapus seluruhnya, bukan diperbaiki, sehingga cache lama tidak lagi dibaca |
| Catatan lama merujuk pengguna yang sudah dihapus | Fallback ke `ui.common.system` dipertahankan dan diuji eksplisit |
| Nama pengguna nonaktif tetap perlu tampil pada catatan historis | Query `MPengguna` sengaja **tidak** memfilter `NonAktif` agar jejak historis tetap terbaca, konsisten dengan catatan schema "audit user tidak dibuat FK agar data historis tetap aman" |

## Validation

```powershell
cd src
php -l app/Filament/Concerns/ResolvesCatatanInternal.php
php -l app/Filament/Pages/InboxWhatsapp.php
php -l app/Filament/Pages/ViewChatSession.php
php artisan test --filter=CatatanInternal
php artisan test
vendor\bin\pint --test
```

Verifikasi manual:

1. Buka Inbox WhatsApp, pilih chat, tambahkan catatan internal baru → nama pengguna yang login tampil pada catatan tersebut.
2. Muat ulang halaman → nama tetap tampil (bukan hanya efek sesi berjalan).
3. Buka Histori Chat → Detail Sesi Chat pada chat yang sama → nama pembuat catatan tampil identik dengan yang di Inbox.
4. Periksa catatan lama yang dibuat sebelum perbaikan → nama pembuatnya kini tampil.
5. Periksa catatan dengan `DibuatOleh` NULL (bila ada) → tetap tampil "Sistem" tanpa error.

## Rollback

Tidak ada perubahan schema maupun data, sehingga rollback cukup dengan mengembalikan kode:

```powershell
git revert <commit>
cd src
php artisan optimize:clear
```

Tidak diperlukan backup database untuk perubahan ini.
