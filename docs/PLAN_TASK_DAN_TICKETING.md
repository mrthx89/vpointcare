# Plan Implementasi Modul Task dan Ticketing VPoint Care

**Tanggal:** 2026-07-13  
**Status:** Diimplementasikan 2026-07-13  
**Target:** Menambahkan modul Ticketing fungsional penuh dan modul Task mandiri ke aplikasi VPoint Care (WACS).

## Tujuan

1. Mengaktifkan modul Ticketing yang skema database-nya sudah ada (`TTicket` dan turunannya) tetapi UI masih demo statis.
2. Menambahkan modul Task mandiri dengan relasi opsional ke Ticket, Chat, dan Customer, dilengkapi penugasan, checklist, catatan, dan lampiran.
3. Menyediakan master data (status/kategori/prioritas ticket + status task) terkelola via Filament.
4. Mendukung penugasan (assignment) ticket & task ke pengguna lain, lengkap dengan riwayat penugasan, filter "saya", dan notifikasi.
5. Mendukung progress notes selama pengerjaan dan lampiran file (upload/download multiple, limit 3 MB/file).
6. Memperluas permission dan sinkronisasi navigation/seeder.
7. Mempertahankan kompatibilitas SQL Server, multi-bahasa (id/en), dan PHP 8.3+.

## Hasil Implementasi

- Migration SQL Server idempotent tersedia di `src/database/migrations/2026_07_13_000001_add_task_and_ticketing_module.php`.
- Resource operasional tersedia di `src/app/Filament/Resources/Operational/Tickets` dan `src/app/Filament/Resources/Operational/Tasks`.
- Resource master tersedia di `src/app/Filament/Resources/Ticketing`.
- Assignment history, notifikasi database, notes, checklist, attachment privat 3 MB, permission `task.view`/`task.manage`, filter pengguna aktif, dan dashboard data nyata telah diterapkan.
- Validasi lokal: PHPUnit unit test, PHP lint, route discovery, dan Vite production build.
- Migration integration tetap harus dijalankan pada SQL Server target setelah backup karena environment development saat implementasi menolak koneksi ODBC terenkripsi.

## SOP Operasional

1. Buat ticket dari Resource Ticket, pilih customer/instansi, kategori, prioritas, target SLA, dan assignee.
2. Tambahkan catatan progres pada bagian aktivitas; setiap perubahan status otomatis masuk timeline `TTicketD`.
3. Gunakan Task untuk action item. Task dapat ditautkan ke ticket, diberi assignee, checklist, komentar, target, dan estimasi.
4. Gunakan filter "Ticket Saya" atau "Task Saya" untuk melihat pekerjaan pengguna aktif.
5. Lampiran maksimal 3 MB per file. File disimpan privat dan diunduh melalui route yang memeriksa autentikasi serta permission.
6. Saat eskalasi atau reassignment, pilih assignee baru. Sistem mempertahankan riwayat penugasan dan mengirim notifikasi database.
7. Sebelum deployment: backup database, jalankan migration dan seeder, pastikan `storage/app/attachments` writable, build asset, clear/optimize cache, lalu restart worker.

## Konteks dan Konvensi

Aplikasi memakai:

- PHP 8.3+, Laravel 13, Filament 5.6.
- SQL Server (`sqlsrv`) dengan UUID primary key (`NEWSEQUENTIALID()`).
- Model pakai trait `UsesSqlServerUuid` (key `Id`, `$timestamps = false`, keyType string).
- Kolom audit: `TglBuat`, `DibuatOleh`, `TglEdit`, `DieditOleh`, `NonAktif`.
- Migration raw SQL Server idempotent (`IF OBJECT_ID(...) IS NULL`), guard `sqlsrv`.
- Filament Resource pakai `NavigationHelper` + `FilamentAccess` + `HasMenuBreadcrumbs`.
- Localization di `resources/lang/id/ui.php` dan `resources/lang/en/ui.php`.
- OpenSpec di `openspec/changes/<id>/` dengan `proposal.md`, `tasks.md`, `specs/care-desk/spec.md`.
- Sumber user/assignee: `MPengguna` (model auth) dengan relasi role `MPeran`.
- Pola penyajian file: `PublicStorageController`/`WahaMediaController` (auth-gated), `Storage` facade.

## Arsitektur

### Ticket (skema sudah ada)

- `TTicket` (header, ada `DitugaskanKepada` + `TglDitugaskan`), `TTicketD` (aktivitas + catatan pengerjaan), `TTicketDPenugasan` (riwayat penugasan), `TTicketDLampiran` (lampiran).
- Master: `MStatusTicket`, `MKategoriTicket`, `MPrioritasTicket`.

### Task (baru)

- `TTask` (header, ada `DitugaskanKepada` + `TglDitugaskan`) dengan link opsional: `IdTicket`, `IdChat`, `IdCustomer`, `IdInstansi`, `IdTugasInduk` (self-ref), `IdKategoriTicket`, `IdPrioritasTicket`, `IdStatusTask`.
- `TTaskDPenugasan` (riwayat penugasan, konsisten dengan `TTicketDPenugasan`).
- `TTaskDChecklist` (sub-item), `TTaskDKomentar` (catatan/komentar pengerjaan), `TTaskDLampiran` (lampiran, mirror `TTicketDLampiran`).
- Master baru: `MStatusTask`.
- Reuse `MPrioritasTicket` dan `MKategoriTicket` agar tidak membuat master duplikat.

### Keputusan Desain

- Task = modul mandiri yang juga bisa menjadi action-item ticket (`IdTicket` nullable).
- Reuse master prioritas & kategori ticket untuk task.
- Buat master baru hanya `MStatusTask` karena lifecycle task berbeda dari ticket.
- Nomor: `TCK-YYYYMMDD-NNN` (ticket), `TSK-YYYYMMDD-NNN` (task).
- Penugasan: ticket & task keduanya punya `DitugaskanKepada` (FK `MPengguna`) + tabel riwayat penugasan (`TTicketDPenugasan` sudah ada; `TTaskDPenugasan` baru) + filter "saya" + notifikasi in-app.
- Progress notes: Ticket catat di `TTicketD` (`JenisAktivitas = Catatan`); Task catat di `TTaskDKomentar`. Tidak buat tabel baru untuk notes.
- Lampiran: Ticket pakai `TTicketDLampiran` (sudah ada); Task buat `TTaskDLampiran`. Upload multiple, limit 3 MB/file, download auth-gated, simpan di disk non-publik `storage/app/attachments`.

## Mapping Tabel Baru

| Tabel | Kunci | Relasi |
|---|---|---|
| `MStatusTask` | `KodeStatusTask` unique | master status task |
| `TTask` | `NomorTask` unique | FK opsional ke TTicket, TChat, MCustomer, MInstansi, MKategoriTicket, MPrioritasTicket, MStatusTask, MPengguna (DitugaskanKepada), self (IdTugasInduk) |
| `TTaskDPenugasan` | - | FK ke TTask + MPengguna (DitugaskanDari, DitugaskanKepada); riwayat penugasan task |
| `TTaskDChecklist` | - | FK ke TTask |
| `TTaskDKomentar` | - | FK ke TTask; catatan/komentar pengerjaan |
| `TTaskDLampiran` | - | FK ke TTask; mirror `TTicketDLampiran` (NamaFile, PathFile, TipeFile, UkuranFile) |

## Seed Default

### MStatusTicket

| Kode | Nama | Urutan | StatusFinal |
|---|---|---|---|
| BARU | Baru | 10 | 0 |
| DALAM_ANALISA | Dalam Analisa | 20 | 0 |
| DITERUSKAN_DEV | Diteruskan ke Developer | 30 | 0 |
| DALAM_PENGERJAAN | Dalam Pengerjaan | 40 | 0 |
| MENUNGGU_INFO | Menunggu Informasi | 50 | 0 |
| SELESAI | Selesai | 60 | 1 |
| DITUTUP | Ditutup | 70 | 1 |
| DIBATALKAN | Dibatalkan | 80 | 1 |

### MKategoriTicket

| Kode | Nama |
|---|---|
| BUG | Bug / Error |
| PERMINTAAN | Permintaan Fitur |
| INFORMASI | Informasi |
| KELUHAN | Keluhan |
| LAINNYA | Lainnya |

### MPrioritasTicket

| Kode | Nama | Urutan | BatasSlaMenit |
|---|---|---|---|
| RENDAH | Rendah | 10 | NULL |
| NORMAL | Normal | 20 | NULL |
| TINGGI | Tinggi | 30 | 480 |
| MENDESAK | Mendesak | 40 | 240 |

### MStatusTask

| Kode | Nama | Urutan | StatusFinal |
|---|---|---|---|
| BELUM_MULAI | Belum Mulai | 10 | 0 |
| DALAM_PENGERJAAN | Dalam Pengerjaan | 20 | 0 |
| DIBLOKIR | Diblokir | 30 | 0 |
| SELESAI | Selesai | 40 | 1 |
| DIBATALKAN | Dibatalkan | 50 | 1 |
## Assignment & Penugasan

Modul mendukung penugasan ticket & task ke pengguna lain (`MPengguna`) sebagai handler:

- Field: `DitugaskanKepada` + `TglDitugaskan` di `TTicket` dan `TTask`.
- Riwayat: `TTicketDPenugasan` (sudah ada) dan `TTaskDPenugasan` (baru) mencatat `DitugaskanDari`, `DitugaskanKepada`, `AlasanPenugasan`, `TglPenugasan` setiap kali assignee berubah.
- UI: `Select DitugaskanKepada` (MPengguna aktif, searchable) di form; kolom assignee di tabel; `TicketPenugasanRelationManager`/`TaskPenugasanRelationManager` di detail.
- Aksi: `Tugaskan Ulang` (modal assignee + alasan) lewat `TicketService`/`TaskService`.
- Filter: quick filter "Ticket Saya"/"Task Saya" (`DitugaskanKepada = Auth::id()`); panel "Ticket Saya" di dashboard.
- Notifikasi: `TicketAssignedNotification`/`TaskAssignedNotification` (in-app Filament) ke assignee baru saat assign/reassign; fase 2 opsional WhatsApp.

## Progress Notes & Lampiran

### Progress Notes

- Ticket: catatan pengerjaan disimpan di `TTicketD` dengan `JenisAktivitas = Catatan` (manfaatkan activity log yang sudah ada) -> note jadi bagian timeline audit.
- Task: catatan/komentar pengerjaan disimpan di `TTaskDKomentar` (`IsiKomentar`, `TglKomentar`).
- RelationManager detail menyediakan form tambah catatan (textarea) selama pengerjaan; setiap catatan mencatat `DibuatOleh` + timestamp.

### Lampiran (Upload/Download, Multiple, Limit 3 MB)

- Ticket: `TTicketDLampiran` (sudah ada); Task: `TTaskDLampiran` (baru, mirror). Kolom: `NamaFile`, `PathFile`, `TipeFile`, `UkuranFile`.
- Multiple: satu ticket/task bisa banyak lampiran; setiap file = satu baris.
- Limit: maks 3 MB/file (`maxSize(3072)` KB / `max:3072` / `MAX_UPLOAD_BYTES = 3145728`). File > 3 MB ditolak dgn pesan id/en. Tujuan: cegah boncos penyimpanan server.
- Upload: `FileUpload` Filament `->multiple()->maxSize(3072)` di `TicketLampiranRelationManager`/`TaskLampiranRelationManager`, simpan via `AttachmentService` ke disk non-publik `storage/app/attachments`.
- Download: route auth-gated `/admin/attachments/{type}/{id}/download` -> `AttachmentController`, cek permission `ticket.view`/`task.view`, stream file asli (`Content-Disposition: attachment`).
- Hapus: `AttachmentService::delete()` hapus record DB + file fisik dari disk.
- Tipe file: gambar (image/*) + dokumen umum (PDF, docx, xlsx, dll); `acceptedFileTypes` dapat disesuaikan.

## File yang Akan Dibuat/Diubah

### Database
- `src/database/migrations/2026_07_13_000001_add_task_and_ticketing_module.php` (baru)
- `src/script/DATABASE_SCHEMA_WACS.sql` (update)
- `src/config/filesystems.php` (tambah disk `attachments`)

### Model (baru)
- `src/app/Models/Master/StatusTicket.php`
- `src/app/Models/Master/KategoriTicket.php`
- `src/app/Models/Master/PrioritasTicket.php`
- `src/app/Models/Master/StatusTask.php`
- `src/app/Models/Ticketing/Ticket.php`
- `src/app/Models/Ticketing/TicketDetail.php`
- `src/app/Models/Ticketing/TicketPenugasan.php`
- `src/app/Models/Ticketing/TicketLampiran.php`
- `src/app/Models/Task/Task.php`
- `src/app/Models/Task/TaskPenugasan.php`
- `src/app/Models/Task/TaskChecklist.php`
- `src/app/Models/Task/TaskKomentar.php`
- `src/app/Models/Task/TaskLampiran.php`

### Service & Controller (baru)
- `src/app/Services/Ticketing/TicketNumberService.php`
- `src/app/Services/Ticketing/TicketService.php` (buat/ubah status/penugasan + catat TTicketD/TTicketDPenugasan + addNote + notifikasi)
- `src/app/Services/Task/TaskNumberService.php`
- `src/app/Services/Task/TaskService.php` (buat/ubah status/penugasan + catat TTaskDPenugasan + toggle checklist + komentar + notifikasi)
- `src/app/Services/AttachmentService.php` (store/download/delete, validasi 3 MB, disk non-publik)
- `src/app/Http/Controllers/AttachmentController.php` (download auth-gated)
- Notifikasi: `TicketAssignedNotification.php`, `TaskAssignedNotification.php`

### Routes (ubah)
- `src/routes/web.php` (tambah `/admin/attachments/{type}/{id}/download`)

### Filament Resource (baru)
- `src/app/Filament/Resources/Ticketing/Tickets/TicketResource.php` + Pages
- `src/app/Filament/Resources/Ticketing/StatusTickets/StatusTicketResource.php` + Pages
- `src/app/Filament/Resources/Ticketing/KategoriTickets/KategoriTicketResource.php` + Pages
- `src/app/Filament/Resources/Ticketing/PrioritasTickets/PrioritasTicketResource.php` + Pages
- `src/app/Filament/Resources/Task/Tasks/TaskResource.php` + Pages
- `src/app/Filament/Resources/Task/StatusTasks/StatusTaskResource.php` + Pages
- RelationManagers: TicketDetail (timeline+notes), TicketPenugasan, TicketLampiran, Task (di ticket), TaskPenugasan, TaskChecklist, TaskKomentar, TaskLampiran

### Filament Pages (ubah)
- `src/app/Filament/Pages/Ticketing.php`
- `src/resources/views/filament/pages/ticketing.blade.php`

### Support (ubah)
- `src/app/Support/AccessPermissions.php`
- `src/database/seeders/DatabaseSeeder.php`

### Localization (ubah)
- `src/resources/lang/id/ui.php`
- `src/resources/lang/en/ui.php`

### Dokumentasi (ubah)
- `README.md`
- `openspec/specs/care-desk/spec.md`

## Tahap Eksekusi

### Tahap 1 — Database
1. Buat migration raw SQL Server idempotent.
2. Buat tabel `MStatusTask`, `TTask`, `TTaskDPenugasan`, `TTaskDChecklist`, `TTaskDKomentar`, `TTaskDLampiran`.
3. Tambah FK (termasuk FK ke MPengguna untuk DitugaskanKepada), unique, index.
4. Seed default master.
5. Update `DATABASE_SCHEMA_WACS.sql`.
6. Validasi `php artisan migrate --pretend`.

### Tahap 2 — Model
1. Buat semua model dengan `UsesSqlServerUuid`.
2. Tambah casts, `booted()`, konstanta, relasi (assignee -> MPengguna, penugasans, lampirans, komentar/detail).

### Tahap 3 — Master Resource
1. Buat resource status/kategori/prioritas ticket + status task.
2. Gate permission, navigasi, breadcrumb.

### Tahap 4 — Service, Notifikasi & Lampiran
1. `TicketNumberService`/`TaskNumberService`.
2. `TicketService`/`TaskService` (penugasan + addNote).
3. `AttachmentService` (store/download/delete, validasi 3 MB, disk non-publik).
4. `AttachmentController` + route download auth-gated.
5. Notifikasi `TicketAssignedNotification`/`TaskAssignedNotification`.

### Tahap 5 — Ticket Resource
1. Form (Select DitugaskanKepada), table (assignee), filter (status/prioritas/kategori/assignee + "Ticket Saya").
2. RelationManagers (detail+notes, penugasan, lampiran upload/download, task).
3. Action Tugaskan Ulang + Tutup Ticket.

### Tahap 6 — Task Resource
1. Form (Select DitugaskanKepada), table (assignee), filter (status/prioritas/assignee + "Task Saya").
2. RelationManagers (penugasan, checklist, komentar/notes, lampiran upload/download).
3. Action Tugaskan Ulang + Tandai Selesai.

### Tahap 7 — Dashboard
1. Upgrade `Ticketing.php` query statistik real + panel "Ticket Saya".
2. Update blade view.

### Tahap 8 — Permission & Navigation
1. Tambah `task.view`/`task.manage`.
2. Update `sidebarMenus`, `defaultRolePermissions`.
3. Update seeder, flush navigation.

### Tahap 9 — Localization
1. Tambah key id/en lengkap (assignment, notes, attachment).

### Tahap 10 — Integrasi Chat (opsional)
1. Action buat ticket/task dari Inbox/ViewChatSession (pre-fill assignee opsional).

### Tahap 11 — Testing
1. `php -l` semua file.
2. `php artisan test`.
3. Manual test CRUD, penugasan/reassign, filter saya, notifikasi, progress notes, upload/download lampiran, limit 3 MB, SLA, checklist, localization.

### Tahap 12 — Dokumentasi
1. Update README, OpenSpec base spec, plan.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
|---|---|
| Migration gagal di production | Backup DB; migration idempotent; `--pretend` sebelum `--force` |
| Master kosong | Seed default dalam migration; form filter `NonAktif = false` |
| Collision nomor | Generate dalam transaksi + cek unique + retry |
| FK nullable orphan | Soft policy; link opsional sengaja |
| Performa dashboard | Pakai index existing + index baru; query agregat |
| Akses VIEWER berlebih | Explicit read-only; tidak beri `*.manage` |
| Assignee nonaktif dipilih | Dropdown hanya MPengguna aktif; cek pada service |
| Notifikasi bocor | Gate notifikasi sesuai `ticket.view`/`task.view` |
| File lampiran boncos server | Limit 3 MB/file + disk non-publik + hapus file saat record dihapus; monitoring storage |
| File sensitif bocor publik | Simpan di disk non-publik; hanya via route download auth-gated + cek permission |
| Disk storage tidak writable | Pastikan `storage/app/attachments` writable saat deployment |
| Upload bypass client validation | Validasi server-side `max:3072` + cek eksplisit `MAX_UPLOAD_BYTES` di `AttachmentService` |

## Validasi Akhir

1. Tabel target ada: `MStatusTask`, `TTask`, `TTaskDPenugasan`, `TTaskDChecklist`, `TTaskDKomentar`, `TTaskDLampiran`.
2. Master ter-seed: `MStatusTicket`, `MKategoriTicket`, `MPrioritasTicket`, `MStatusTask`.
3. CRUD ticket & task berfungsi.
4. Penugasan: assign & reassign mencatat riwayat di `TTicketDPenugasan`/`TTaskDPenugasan`.
5. Filter "Ticket Saya"/"Task Saya" bekerja.
6. Notifikasi assignment terkirim ke assignee baru (in-app).
7. Progress notes: catatan ticket muncul di `TTicketD` (`JenisAktivitas = Catatan`); catatan task di `TTaskDKomentar`.
8. Lampiran: upload multiple tersimpan; download berfungsi; hapus bersih record+file.
9. Limit 3 MB: file > 3 MB ditolak; <= 3 MB diterima.
10. Lampiran hanya accessible via route auth-gated (permission view).
11. Checklist toggle & komentar berfungsi.
12. SLA overdue ditandai.
13. Permission gate bekerja.
14. Localization id/en tampil (assignment, notes, attachment).
15. Dashboard Ticketing tampilkan data real + panel "Ticket Saya".
16. `php -l` dan `php artisan test` lulus.
17. Fresh-install konsisten dengan `DATABASE_SCHEMA_WACS.sql`.
