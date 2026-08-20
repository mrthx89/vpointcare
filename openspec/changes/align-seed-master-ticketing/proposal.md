# Change: Satukan Sumber Seed Master Ticketing

## Summary

Master status ticket, kategori ticket, dan prioritas ticket saat ini di-seed dari **dua sumber yang berbeda dan tidak selaras**: blok `INSERT` di `src/script/DATABASE_SCHEMA_WACS.sql` (dieksekusi migration) dan `Database\Seeders\DatabaseSeeder::seedTicketingMasters()`. Akibatnya database hasil `php artisan migrate --seed` berisi gabungan kedua daftar, dengan status ticket ganda yang maknanya tumpang tindih dan satu nilai SLA yang saling menimpa. Perubahan ini menetapkan `DatabaseSeeder` sebagai satu-satunya sumber kebenaran, menyelaraskan isinya, dan menonaktifkan kode master yang tidak lagi kanonik secara aman.

## Problem Statement

Supervisor yang membuat ticket melihat daftar status yang membingungkan karena berisi dua penamaan untuk tahapan kerja yang sama (`ANALISA` vs `DIANALISA_CS`, `DIKERJAKAN` vs `DALAM_PENGERJAAN`). Ticket yang seharusnya berada pada satu tahapan tersebar ke dua kode berbeda, sehingga statistik dashboard Ticketing (ticket aktif, overdue, selesai) dan pelaporan menjadi tidak dapat dipercaya. Selain itu SLA prioritas `RENDAH` berubah diam-diam tergantung urutan eksekusi migration dan seeder.

Selama dua sumber ini dipertahankan, setiap penambahan status baru harus dilakukan di dua tempat dan berpotensi menyimpang lagi.

## Current State

Verifikasi pada source code aktual.

**Sumber 1 — `src/script/DATABASE_SCHEMA_WACS.sql`** (dieksekusi oleh migration `2026_04_27_000001_create_vpoint_care_schema.php`, hanya pada koneksi `sqlsrv`):

- `MStatusTicket` (baris 793-805): `DRAFT`, `BARU`, `DIANALISA_CS`, `BUTUH_DATA_CUSTOMER`, `DITERUSKAN_DEVELOPER`, `DALAM_PENGERJAAN`, `MENUNGGU_DEPLOY`, `SELESAI`(final), `DITUTUP`(final), `DIBATALKAN`(final) — 10 status.
- `MKategoriTicket` (baris 815-822): `BUG`, `DATA`, `AKSES`, `REQUEST`, `KONSULTASI` — 5 kategori.
- `MPrioritasTicket` (baris 807-813): `RENDAH` (SLA 4320 menit), `NORMAL` (1440), `TINGGI` (480), `KRITIS` (120).
- Blok ini berupa `INSERT` polos tanpa penjaga idempotensi, sehingga hanya aman dijalankan sekali pada database baru.

**Sumber 2 — `src/database/seeders/DatabaseSeeder.php::seedTicketingMasters()`** (idempotent, memakai `updateOrInsert`, dijalankan setiap `db:seed`):

- `MStatusTicket`: `BARU`, `ANALISA`, `DIKERJAKAN`, `SELESAI`(final), `DITUTUP`(final) — 5 status.
- `MStatusTask`: `BARU`, `DIKERJAKAN`, `TERTUNDA`, `SELESAI`(final), `DITUTUP`(final) — hanya ada di sini, tidak ada konflik.
- `MKategoriTicket`: `UMUM`, `TEKNIS`, `LAYANAN` — 3 kategori.
- `MPrioritasTicket`: kode sama dengan Sumber 1, tetapi `RENDAH` memakai SLA **2880** menit.

**Kondisi database setelah `php artisan migrate --seed` pada instalasi baru:**

| Tabel | Hasil |
| --- | --- |
| `MStatusTicket` | 12 baris (10 dari SQL + `ANALISA` dan `DIKERJAKAN` dari seeder). `BARU`, `SELESAI`, `DITUTUP` ditimpa nilai `Urutan`/`Warna` dari seeder |
| `MKategoriTicket` | 8 baris (5 + 3), tanpa irisan kode |
| `MPrioritasTicket` | 4 baris, tetapi `RENDAH.BatasSlaMenit` menjadi 2880 karena seeder berjalan setelah migration |

**Konsumen data ini:**

- `src/app/Filament/Pages/Ticketing.php` memakai `KodeStatusTicket = 'BARU'` dan flag `StatusFinal` untuk seluruh statistik dashboard.
- `src/app/Models/Ticketing/Ticket.php` dan `Task.php` memakai `StatusFinal` untuk mengisi `TglDitutup`, `DitutupOleh`, dan `TglSelesai`.
- `src/app/Filament/Resources/Operational/Tickets/TicketResource.php` menampilkan seluruh status `NonAktif = 0` pada dropdown dan filter.
- `MPrioritasTicket.BatasSlaMenit` saat ini **belum** dipakai kode mana pun untuk menghitung `TglTargetSelesai`; nilainya murni data referensi.

## Goals

- Satu sumber kebenaran untuk master status/kategori/prioritas ticket, yaitu `DatabaseSeeder`.
- Daftar status ticket yang tidak ambigu: satu kode per tahapan kerja.
- Nilai SLA prioritas yang deterministik dan tidak bergantung urutan eksekusi.
- Aman untuk database production existing: tidak menghapus baris master yang masih direferensikan ticket.
- Instalasi baru dan database existing menghasilkan daftar master yang identik.

## Non-Goals

- Tidak mengubah `MStatusChat`, `MPeran`, dan `MPengaturanAi` yang juga di-seed dari file SQL; ketiganya di luar cakupan temuan ini.
- Tidak mengimplementasikan perhitungan `TglTargetSelesai` otomatis dari `BatasSlaMenit` maupun eskalasi SLA breach.
- Tidak mengubah `MStatusTask` yang sudah bersumber tunggal dari seeder.
- Tidak menambah UI baru; pengelolaan master tetap melalui resource Filament existing dengan permission `ticket.manage`.
- Tidak menghapus baris master mana pun dari database.

## Proposed Changes

### 1. Tetapkan daftar kanonik

Daftar kanonik mengadopsi **daftar dari `DATABASE_SCHEMA_WACS.sql`** karena daftar itu memodelkan alur eskalasi CS → developer yang nyata dan sudah ada di setiap database terpasang, sehingga tidak memperkenalkan kode baru ke data production.

`MStatusTicket` kanonik:

| Kode | Nama | Urutan | Final | Warna |
| --- | --- | --- | --- | --- |
| `DRAFT` | Draft | 10 | 0 | gray |
| `BARU` | Baru | 20 | 0 | info |
| `DIANALISA_CS` | Dianalisa CS | 30 | 0 | warning |
| `BUTUH_DATA_CUSTOMER` | Butuh Data Customer | 40 | 0 | warning |
| `DITERUSKAN_DEVELOPER` | Diteruskan ke Developer | 50 | 0 | primary |
| `DALAM_PENGERJAAN` | Dalam Pengerjaan | 60 | 0 | primary |
| `MENUNGGU_DEPLOY` | Menunggu Deploy | 70 | 0 | warning |
| `SELESAI` | Selesai | 80 | 1 | success |
| `DITUTUP` | Ditutup | 90 | 1 | gray |
| `DIBATALKAN` | Dibatalkan | 100 | 1 | danger |

`MKategoriTicket` kanonik: `BUG`, `DATA`, `AKSES`, `REQUEST`, `KONSULTASI`.

`MPrioritasTicket` kanonik: `RENDAH` (SLA **2880** menit), `NORMAL` (1440), `TINGGI` (480), `KRITIS` (120).

> **Keputusan (disetujui 2026-07-23):** nilai SLA `RENDAH` ditetapkan **2880 menit (2 hari)**, yaitu nilai yang efektif berlaku pada seluruh database yang sudah pernah menjalankan `db:seed`. Dengan begitu penyelarasan ini tidak mengubah nilai SLA mana pun yang sedang berjalan. Nilai 4320 dari file schema ditinggalkan.

### 2. Pindahkan seed ke satu tempat

- Hapus blok `INSERT INTO MStatusTicket`, `INSERT INTO MPrioritasTicket`, dan `INSERT INTO MKategoriTicket` dari `src/script/DATABASE_SCHEMA_WACS.sql`. Blok `INSERT` untuk `MPeran`, `MStatusChat`, dan `MPengaturanAi` dipertahankan (di luar cakupan).
- Perbarui `DatabaseSeeder::seedTicketingMasters()` agar memuat daftar kanonik lengkap di atas.
- Karena seeder memakai `updateOrInsert` berbasis kode, instalasi baru maupun database existing akan konvergen ke nilai yang sama.

### 3. Nonaktifkan kode non-kanonik secara aman

Untuk database existing yang sudah memiliki `ANALISA`, `DIKERJAKAN`, `UMUM`, `TEKNIS`, dan `LAYANAN`, dibuat migration idempotent yang untuk setiap kode non-kanonik:

- Bila **tidak ada** baris `TTicket` yang mereferensikannya (dan untuk kategori, juga tidak ada `TTask`): set `NonAktif = 1` sehingga hilang dari dropdown dan filter tanpa merusak foreign key.
- Bila **masih ada** referensi: biarkan baris tetap aktif dan catat peringatan pada output migration agar tim melakukan pemetaan ulang ticket secara sadar, bukan otomatis.

Migration ini **tidak menghapus baris** dan tidak memindahkan ticket antar status.

### 4. Dokumentasikan pemetaan

Menambahkan tabel pemetaan status lama → status kanonik pada `docs/` agar tim operasional dapat memindahkan ticket yang masih memakai kode lama:

| Kode lama | Disarankan menjadi |
| --- | --- |
| `ANALISA` | `DIANALISA_CS` |
| `DIKERJAKAN` | `DALAM_PENGERJAAN` |
| `UMUM` | `KONSULTASI` |
| `TEKNIS` | `BUG` |
| `LAYANAN` | `REQUEST` |

## Impacted Areas

| Area | Detail |
| --- | --- |
| Database — schema script | `src/script/DATABASE_SCHEMA_WACS.sql` (hapus 3 blok INSERT) |
| Database — seeder | `src/database/seeders/DatabaseSeeder.php` (`seedTicketingMasters()`) |
| Database — migration baru | `src/database/migrations/2026_07_23_000001_deactivate_non_canonical_ticketing_masters.php` |
| Data production | `MStatusTicket`, `MKategoriTicket`, `MPrioritasTicket` — hanya kolom `NonAktif`, `Urutan`, `Warna`, `BatasSlaMenit`, `TglEdit` |
| UI | Dropdown dan filter status/kategori/prioritas pada `TicketResource` dan `TaskResource` berubah isinya; tidak ada perubahan kode UI |
| Dashboard | `Ticketing` page tetap valid karena `BARU` dan `StatusFinal` dipertahankan |
| Permission | Tidak ada perubahan |
| Localization | Tidak ada key baru; nama master tersimpan sebagai data, bukan translation key |
| Queue/Scheduler/Broadcast | Tidak ada |
| Frontend asset | Tidak ada; `npm run build` tidak diperlukan |
| Dokumentasi | `docs/PRD_VPOINT_CARE_WACS.md` bagian TD-11 ditutup; tabel pemetaan status ditambahkan |

## Risks and Mitigations

| Risiko | Mitigasi |
| --- | --- |
| Ticket production masih memakai `ANALISA`/`DIKERJAKAN` dan menjadi tidak terlihat di filter | Migration hanya menonaktifkan kode yang **tidak** direferensikan; kode yang masih dipakai dibiarkan aktif dan dilaporkan |
| Menghapus INSERT dari schema SQL membuat instalasi baru tanpa master ticketing bila hanya menjalankan `migrate` tanpa `--seed` | `TTicket.IdStatusTicket` nullable sehingga tidak fatal; README dan tasks menegaskan `php artisan migrate --seed`. Ditambahkan catatan pada header file SQL bahwa master ticketing dimiliki seeder |
| `TTask.IdStatusTask` bersifat NOT NULL dan bergantung `MStatusTask` | `MStatusTask` sudah bersumber tunggal dari seeder dan tidak diubah oleh change ini |
| Perubahan `Urutan`/`Warna` pada `BARU`, `SELESAI`, `DITUTUP` mengubah tampilan badge | Nilai kanonik dipilih dari daftar SQL yang konsisten satu sama lain; perubahan bersifat kosmetik dan diverifikasi manual |
| Migration dijalankan berulang | Seluruh pernyataan dibungkus pemeriksaan keberadaan tabel/kolom dan `updateOrInsert`/`update` yang idempotent |
| Database bukan SQL Server | Migration memakai query builder Laravel tanpa sintaks khusus, sehingga aman; schema SQL tetap hanya untuk `sqlsrv` |

## Validation

```powershell
cd src
php -l database/seeders/DatabaseSeeder.php
php -l database/migrations/2026_07_23_000001_deactivate_non_canonical_ticketing_masters.php
php artisan test --filter=TicketingMasterSeed
php artisan test
vendor\bin\pint --test
```

Verifikasi data setelah `php artisan migrate --force` dan `php artisan db:seed --force` pada staging:

```sql
SELECT KodeStatusTicket, NamaStatusTicket, Urutan, StatusFinal, NonAktif FROM MStatusTicket ORDER BY Urutan;
-- Diharapkan: 10 baris aktif kanonik; ANALISA dan DIKERJAKAN NonAktif=1 bila tidak dipakai

SELECT KodeKategori, NamaKategori, NonAktif FROM MKategoriTicket ORDER BY KodeKategori;
-- Diharapkan: BUG, DATA, AKSES, REQUEST, KONSULTASI aktif; UMUM, TEKNIS, LAYANAN NonAktif=1 bila tidak dipakai

SELECT KodePrioritas, BatasSlaMenit FROM MPrioritasTicket ORDER BY Urutan;
-- Diharapkan: RENDAH=2880, NORMAL=1440, TINGGI=480, KRITIS=120

SELECT s.KodeStatusTicket, COUNT(*) FROM TTicket t JOIN MStatusTicket s ON s.Id = t.IdStatusTicket GROUP BY s.KodeStatusTicket;
-- Memastikan tidak ada ticket yang statusnya menjadi nonaktif
```

Verifikasi manual:

1. Buka resource Ticket → dropdown status berisi 10 status kanonik tanpa duplikasi makna.
2. Buka dashboard Ticketing → angka ticket baru/aktif/overdue/selesai konsisten dengan hasil query SQL di atas.
3. Ubah status ticket ke `SELESAI` → `TglSelesai`, `TglDitutup`, `DitutupOleh` terisi dan `TTicketD` mencatat perubahan status.
4. Jalankan `php artisan db:seed --force` dua kali → tidak ada baris duplikat dan hasil tetap sama (idempotensi).

## Rollback

Perubahan menyentuh data master production, sehingga **backup database wajib dilakukan sebelum deploy**.

1. Backup penuh `DBCareDesk` sebelum menjalankan migration.
2. Bila perlu rollback:

```powershell
cd src
php artisan migrate:rollback --step=1   # mengembalikan NonAktif kode non-kanonik ke 0
git revert <commit>                      # mengembalikan seeder dan schema SQL
php artisan db:seed --force              # mengembalikan daftar master versi sebelumnya
php artisan optimize:clear
```

3. Method `down()` migration HARUS mengembalikan `NonAktif = 0` hanya untuk kode yang benar-benar dinonaktifkan olehnya. Karena itu migration mencatat kode yang diubahnya, dan `down()` tidak boleh mengaktifkan kode yang memang sudah nonaktif sebelum migration berjalan.
4. Bila rollback dilakukan setelah pengguna memindahkan ticket ke status kanonik, pemindahan tersebut tidak dibatalkan — status kanonik tetap ada, sehingga data ticket tetap konsisten.
