# Change: Modul Task dan Ticketing VPoint Care

## Summary

Tambahkan modul **Ticketing** fungsional penuh dan modul **Task** mandiri ke aplikasi VPoint Care (WACS). Modul Ticketing sudah memiliki skema database lengkap (`TTicket`, `TTicketD`, `TTicketDPenugasan`, `TTicketDLampiran` beserta master `MStatusTicket`, `MKategoriTicket`, `MPrioritasTicket`) tetapi UI saat ini masih halaman demo statis dengan data hardcoded. Modul Task belum ada sama sekali.

Perubahan ini akan:

1. Mengaktifkan modul Ticketing dengan model Eloquent, Filament Resource penuh, master data terkelola, dan meng-upgrade halaman `Ticketing` menjadi dashboard data nyata.
2. Menambahkan modul Task mandiri yang dapat di-link opsional ke Ticket, Chat, dan Customer, sekaligus dapat berperilaku sebagai checklist/action-item di dalam sebuah ticket.
3. Menyediakan seed default untuk master status/kategori/prioritas ticket dan status task.
4. Memperluas permission (`task.view`, `task.manage`) dan sinkronisasi `AccessPermissions`, `NavigationHelper`, serta `DatabaseSeeder`.
5. Memperluas label multi-bahasa Indonesia (`id`) dan Inggris (`en`), serta mempertahankan PHP 8.3+ dan kompatibilitas SQL Server.

## Problem Statement

Aplikasi VPoint Care saat ini belum dapat menindaklanjuti masalah customer menjadi ticket yang terkelola. Skema ticket sudah ada di database, tetapi:

- Tidak ada model Eloquent untuk `TTicket` dan tabel turunannya.
- Tidak ada Filament Resource untuk membuat, mengedit, menugaskan, dan menutup ticket.
- Halaman `Ticketing` (`src/app/Filament/Pages/Ticketing.php`) hanya menampilkan statistik dan tabel statis yang tidak terhubung ke database.
- Master `MStatusTicket`, `MKategoriTicket`, dan `MPrioritasTicket` belum di-seed data default, sehingga ticket tidak bisa dibuat tanpa master tersedia.

Selain itu, tim operasional membutuhkan modul **Task** untuk mengelola tindakan lanjutan (action items) yang fleksibel:

- Tugas bisa muncul dari sebuah ticket (checklist penyelesaian).
- Tugas bisa muncul langsung dari percakapan chat tanpa harus membuat ticket.
- Tugas bisa berupa todo mandiri tim CS/Developer.
- Tugas perlu checklist sub-item, komentar kolaborasi, prioritas, tenggat, dan status.

Saat ini tidak ada tabel, model, UI, maupun permission untuk modul Task.

## Current State

### Ticketing Schema (Sudah Ada)

Skema ticket lengkap sudah didefinisikan di `src/script/DATABASE_SCHEMA_WACS.sql`:

- `TTicket`: header ticket dengan `NomorTicket` (unique), `IdChat` (nullable, link ke `TChat`), `IdCustomer`, `IdInstansi`, `IdKategoriTicket`, `IdPrioritasTicket`, `IdStatusTicket`, `JudulTicket`, `DeskripsiMasalah`, `DibuatDariPesanId`, `DitugaskanKepada`, `TglDitugaskan`, `TglTargetSelesai`, `TglSelesai`, `TglDitutup`, `DitutupOleh`, `RingkasanAi`, serta audit `TglBuat/DibuatOleh/TglEdit/DieditOleh`.
- `TTicketD`: detail aktivitas ticket (`JenisAktivitas`, `IsiAktivitas`, `StatusSebelum`, `StatusSesudah`, `DitujukanKepada`, `TglAktivitas`).
- `TTicketDPenugasan`: riwayat penugasan (`DitugaskanDari`, `DitugaskanKepada`, `AlasanPenugasan`, `TglPenugasan`).
- `TTicketDLampiran`: lampiran ticket.
- `MStatusTicket`: `KodeStatusTicket`, `NamaStatusTicket`, `Urutan`, `StatusFinal`, `Warna`.
- `MKategoriTicket`: `KodeKategori`, `NamaKategori`, `Keterangan`.
- `MPrioritasTicket`: `KodePrioritas`, `NamaPrioritas`, `Urutan`, `BatasSlaMenit`, `Warna`.

FK dan index sudah ada (mis. `FK_TTicket_MStatusTicket`, `IX_TTicketM_IdStatusTicket`, `IX_TTicketM_TglTargetSelesai`).

### Ticketing UI (Masih Demo)

- `src/app/Filament/Pages/Ticketing.php`: halaman Filament `Page` sederhana dengan gate `AccessPermissions::TICKET_VIEW`.
- `src/resources/views/filament/pages/ticketing.blade.php`: statistik hardcoded (`12`, `8`, `15`, `3`) dan tabel contoh (`TCK-20260426-001`, `PT Maju Sistem`, dll).
- Tidak membaca/menulis `TTicket`.

### Permission (Sebagian Sudah Ada)

`src/app/Support/AccessPermissions.php` sudah mendefinisikan:

- `TICKET_VIEW = ticket.view`
- `TICKET_MANAGE = ticket.manage`

Keduanya sudah di-assign ke role default (`ADMIN`, `SUPERVISOR_CS`, `CS`, `DEVELOPER`) di `defaultRolePermissions()`. Label lokal `ticket_view`/`ticket_manage` sudah ada di `resources/lang/id/ui.php`.

Namun:

- Tidak ada permission `task.view`/`task.manage`.
- Tidak ada menu sidebar untuk resource ticket/task (hanya halaman `Ticketing` yang terdaftar via `TICKET_VIEW`).

### Task (Belum Ada)

Tidak ada tabel, model, resource, halaman, permission, atau label untuk modul Task.
## Goals

- Membuat ticket dari chat customer, dari customer/instansi, atau langsung manual.
- Mengelola siklus ticket: buat -> analisa -> tugaskan -> kerjakan -> selesai/tutup, dengan audit trail di `TTicketD`.
- Menugaskan ticket ke pengguna (`DitugaskanKepada`) dengan riwayat penugasan di `TTicketDPenugasan`.
- Melampirkan file ke ticket via `TTicketDLampiran`.
- Menghitung SLA overdue dari `MPrioritasTicket.BatasSlaMenit` dan `TTicket.TglTargetSelesai`.
- Membuat task mandiri atau terikat ke ticket/chat/customer.
- Menambahkan checklist sub-item dan komentar pada task.
- Mengelola master status/kategori/prioritas ticket dan status task melalui Filament Resource.
- Mengubah halaman `Ticketing` menjadi dashboard real dengan statistik dari database.
- Mempertahankan kompatibilitas SQL Server, multi-bahasa, dan PHP 8.3+.

## Solution / Proposed Design

### Arsitektur Task

`TTask` adalah modul mandiri dengan relasi opsional:

- `IdTicket` (nullable) -> link ke `TTicket`. Jika terisi, task berperilaku sebagai action-item ticket.
- `IdChat` (nullable) -> link ke `TChat`. Task bisa dibuat dari chat tanpa ticket.
- `IdCustomer` (nullable) -> link ke `MCustomer`.
- `IdTugasInduk` (nullable) -> sub-task bersarang (self-reference).
- `IdPrioritasTicket` (nullable) -> reuse `MPrioritasTicket` agar tidak membuat master prioritas baru.
- `IdKategoriTicket` (nullable) -> reuse `MKategoriTicket`.
- `IdStatusTask` -> link ke `MStatusTask` (master baru, karena lifecycle task berbeda dari ticket).

Tabel turunan task:

- `TTaskDChecklist`: sub-item checklist (`JudulItem`, `Selesai` bit, `Urutan`, `TglSelesai`, `DiselesaikanOleh`).
- `TTaskDKomentar`: komentar/kolaborasi (`IsiKomentar`, `TglKomentar`).

Master baru:

- `MStatusTask`: `KodeStatusTask`, `NamaStatusTask`, `Urutan`, `StatusFinal`, `Warna`, `NonAktif`, + audit.

Reuse master yang sudah ada untuk menghindari table bloat:

- `MPrioritasTicket` dipakai bersama ticket & task (semantik prioritas identik).
- `MKategoriTicket` dipakai bersama ticket & task.

### Nomor Ticket dan Task

- `TTicket.NomorTicket`: format `TCK-YYYYMMDD-NNN`, auto-generated, unique (`UQ_TTicket_NomorTicket`).
- `TTask.NomorTask`: format `TSK-YYYYMMDD-NNN`, auto-generated, unique (`UQ_TTask_NomorTask`).

Generasi nomor dilakukan di service `TicketNumberService` / `TaskNumberService` dengan sequence harian per tanggal.

### Master Seed Default

- `MStatusTicket`: BARU, DALAM_ANALISA, DITERUSKAN_DEV, DALAM_PENGERJAAN, MENUNGGU_INFO, SELESAI, DITUTUP, DIBATALKAN.
- `MKategoriTicket`: BUG, PERMINTAAN, INFORMASI, KELUHAN, LAINNYA.
- `MPrioritasTicket`: RENDAH (Urutan 1), NORMAL (Urutan 2), TINGGI (Urutan 3, SLA 480 menit), MENDESAK (Urutan 4, SLA 240 menit).
- `MStatusTask`: BELUM_MULAI, DALAM_PENGERJAAN, DIBLOKIR, SELESAI, DIBATALKAN.

### Filament Resource

Mengikuti pola `PengetahuanResource` dan `CustomerResource`:

- Gate `canViewAny()`/`canCreate()`/`canEdit()`/`canDelete()` via `FilamentAccess::can()`.
- Navigasi via `NavigationHelper::iconFor/groupFor/sortFor/labelFor`.
- Breadcrumb via `HasMenuBreadcrumbs`.
- Form `Schema`, table `Table`, filter `SelectFilter`/`TernaryFilter`, action `EditAction`/`CreateAction`.
- RelationManagers untuk detail turunan.

### Permission Baru

- `TASK_VIEW = task.view`
- `TASK_MANAGE = task.manage`

Assignment role:

- `ADMIN`: semua permission.
- `SUPERVISOR_CS`: `ticket.view`, `ticket.manage`, `task.view`, `task.manage`.
- `CS`: `ticket.view`, `ticket.manage`, `task.view`, `task.manage`.
- `DEVELOPER`: `ticket.view`, `ticket.manage`, `task.view`, `task.manage`.
- `VIEWER`: `ticket.view`, `task.view` (read-only).
## Impacted Areas

- `src/database/migrations/` -- migration baru `2026_07_13_000001_add_task_and_ticketing_module.php`.
- `src/script/DATABASE_SCHEMA_WACS.sql` -- tambah tabel task + master status task + seed default.
- `src/app/Models/` -- model baru (StatusTicket, KategoriTicket, PrioritasTicket, StatusTask, Ticket, TicketDetail, TicketPenugasan, TicketLampiran, Task, TaskChecklist, TaskKomentar).
- `src/app/Filament/Resources/Ticketing/` -- resource ticket + master ticket.
- `src/app/Filament/Resources/Task/` -- resource task + master status task.
- `src/app/Filament/Pages/Ticketing.php` + view -- upgrade dashboard real.
- `src/app/Support/AccessPermissions.php` -- permission task + sidebar menu.
- `src/app/Support/NavigationHelper.php` -- sinkron (via `sidebarMenus()`).
- `src/database/seeders/DatabaseSeeder.php` -- seed master ticket/task + permission task.
- `src/resources/lang/id/ui.php` + `en/ui.php` -- label lengkap.
- `README.md` -- update modul Ticketing & Task.
- `openspec/specs/vpoint-care/spec.md` -- update base spec setelah change diterima.

## Acceptance Criteria

- User dengan `ticket.view` melihat daftar ticket nyata dari `TTicket`; user dengan `ticket.manage` dapat membuat, mengedit, menugaskan, dan menutup ticket.
- User dengan `task.view` melihat daftar task; user dengan `task.manage` dapat membuat, mengedit, menugaskan, menyelesaikan, dan menghapus task.
- Task dapat di-link opsional ke ticket, chat, dan customer; task yang terikat ticket tampil sebagai action-item di halaman detail ticket.
- Ticket dapat di-link opsional ke chat, customer, dan instansi.
- Master status/kategori/prioritas ticket dan status task dapat dikelola melalui Filament Resource dengan gate permission.
- SLA overdue dihitung dari `MPrioritasTicket.BatasSlaMenit` dan `TTicket.TglTargetSelesai`; ticket overdue ditandai pada dashboard dan tabel.
- `NomorTicket` dan `NomorTask` auto-generated dengan format konsisten dan unique.
- Kolom audit `DibuatOleh/TglBuat/DieditOleh/TglEdit` terisi otomatis.
- Aktivitas ticket tercatat di `TTicketD` saat status berubah, penugasan berubah, atau komentar ditambah.
- Semua label UI tampil dalam bahasa Indonesia dan Inggris sesuai locale aktif.
- Migration berjalan pada SQL Server (`sqlsrv`) dan fresh-install konsisten dengan `DATABASE_SCHEMA_WACS.sql`.
- Halaman `Ticketing` menampilkan statistik nyata (baru, dalam pengerjaan, overdue SLA, selesai) dari database, bukan hardcoded.
- Permission baru `task.view`/`task.manage` terdaftar di `AccessPermissions`, di-seed ke `MHakAkses`, dan ter-assign ke role default.

## Risks and Mitigations

- **SQL Server migration gagal di production**: Backup database sebelum migration; migration idempotent (`IF OBJECT_ID... IS NULL`); jalankan `--pretend` sebelum `--force`.
- **Master kosong menyebabkan ticket/task tidak bisa dibuat**: Seed default wajib dijalankan dalam migration; resource form menampilkan dropdown master hanya yang `NonAktif = false`.
- **Nomor ticket/task collision**: Generate nomor dalam transaksi dengan cek unique; retry singkat jika collision.
- **Foreign key nullable menyebabkan orphan**: FK nullable sengaja untuk fleksibilitas link opsional; cleanup tidak otomatis (soft policy).
- **Performa dashboard**: Gunakan query agregat dengan index yang sudah ada (`IX_TTicket_IdStatusTicket`, `IX_TTicket_TglTargetSelesai`); tambah index baru untuk task.
- **Akses tidak sengaja ke role VIEWER**: Explicit assign `task.view` read-only; tidak memberikan `task.manage` ke VIEWER.
## OpenSpec Base Spec Relationship

Base spec `openspec/specs/vpoint-care/spec.md` sudah mendefinisikan `### Requirement: Ticketing` (high-level) dan `### Requirement: Master Data Management`. Change ini:

- **Memperluas** `### Requirement: Ticketing` di delta spec dengan nama heading yang sama, menambahkan scenario detail (create from chat, assign, status change, final status, overdue SLA, attachment, master managed, dashboard real, authorization) sambil mempertahankan dua scenario awal ("Agent creates ticket", "Ticket appears in operational view").
- **Menambah** `### Requirement: Task Management` sebagai capability baru yang belum ada di base spec.
- Base spec akan di-update setelah change diterima/diimplementasi (merge delta ke `openspec/specs/vpoint-care/spec.md`), sesuai konvensi OpenSpec.
## Assignment, Reassignment & Notification

Baik Ticket maupun Task mendukung penugasan ke pengguna lain (`MPengguna`) sebagai handler/PIC. Ini adalah bagian inti modul agar jelas siapa yang menangani setiap item.

### Field Penugasan

- `TTicket.DitugaskanKepada` (uniqueidentifier, nullable) -> `MPengguna.Id`.
- `TTask.DitugaskanKepada` (uniqueidentifier, nullable) -> `MPengguna.Id`.
- Keduanya juga punya `TglDitugaskan` (timestamp saat ditugaskan).

### Riwayat Penugasan (Audit Trail)

- Ticket: `TTicketDPenugasan` (sudah ada di skema) -> `DitugaskanDari`, `DitugaskanKepada`, `AlasanPenugasan`, `TglPenugasan`.
- Task: `TTaskDPenugasan` (baru, konsisten dengan `TTicketDPenugasan`) -> `DitugaskanDari`, `DitugaskanKepada`, `AlasanPenugasan`, `TglPenugasan`.
- Setiap kali `DitugaskanKepada` berubah (assign baru atau reassign), sistem mencatat satu baris baru di tabel riwayat, sehingga jejak penugasan tetap lengkap dan dapat diaudit.
- RelationManager `TicketPenugasanRelationManager` dan `TaskPenugasanRelationManager` menampilkan riwayat penugasan di halaman detail ticket/task.

### Dropdown Assignee

- Form Ticket & Task memakai `Select DitugaskanKepada` yang hanya menampilkan `MPengguna` aktif (`NonAktif = false`, status registrasi approved).
- Searchable + preload agar mudah memilih handler.

### Filter "Ticket Saya" / "Task Saya"

- Kedua resource menyediakan quick filter / TernaryFilter yang membatasi daftar ke record `DitugaskanKepada = Auth::id()`, sehingga setiap user dapat melihat pekerjaan yang menjadi tanggung jawabnya.
- Dashboard Ticketing juga menampilkan panel "Ticket Saya" (jumlah ticket belum selesai yang ditugaskan ke user login).

### Aksi Tugaskan Ulang

- Action `Tugaskan Ulang` pada Ticket & Task: modal pilih assignee baru + field `AlasanPenugasan`, lalu lewat `TicketService`/`TaskService` update `DitugaskanKepada`, `TglDitugaskan`, catat `TTicketDPenugasan`/`TTaskDPenugasan`, dan kirim notifikasi.

### Notifikasi Assignment

- Fase 1 (in-app): `TicketAssignedNotification` / `TaskAssignedNotification` (Filament notification) dikirim ke `DitugaskanKepada` saat assign/reassign.
- Notifikasi tidak dikirim ke user tanpa akses modul (`ticket.view`/`task.view`).
- Fase 2 (opsional): notifikasi WhatsApp/queue ke assignee via WAHA bila diinginkan.

### Acceptance Criteria Tambahan (Assignment)

- `TTicket` dan `TTask` keduanya menyimpan `DitugaskanKepada` dan dapat di-assign ke pengguna lain.
- Setiap perubahan `DitugaskanKepada` mencatat baris baru di `TTicketDPenugasan` / `TTaskDPenugasan` dengan `DitugaskanDari`, `DitugaskanKepada`, dan `AlasanPenugasan`.
- Filter "Ticket Saya" / "Task Saya" bekerja dan hanya menampilkan record yang ditugaskan ke user login.
- Notifikasi in-app terkirim ke assignee baru saat assign/reassign.
- Riwayat penugasan terlihat di halaman detail ticket/task via RelationManager.
## Progress Notes & Lampiran (Attachments)

Selain assignment, modul Ticketing & Task mendukung pencatatan progress dan lampiran file selama pengerjaan.

### Progress Notes (Catatan Pengerjaan)

- **Ticket**: catatan pengerjaan disimpan sebagai baris aktivitas di `TTicketD` dengan `JenisAktivitas = Catatan` dan `IsiAktivitas` (nvarchar max). Ini memanfaatkan tabel activity log yang sudah ada, sehingga note menjadi bagian timeline audit ticket (status, penugasan, catatan) dalam satu urutan kronologis. Tidak perlu tabel baru.
- **Task**: catatan/komentar pengerjaan disimpan di `TTaskDKomentar` (`IsiKomentar`, `TglKomentar`) yang sudah direncanakan. Note/komentar tampil di timeline detail task.
- RelationManager detail menyediakan form tambah catatan (textarea) selama pengerjaan; setiap catatan mencatat `DibuatOleh` dan timestamp.

### Lampiran (Upload & Download, Multiple, Limit 3 MB)

- **Ticket**: pakai `TTicketDLampiran` (sudah ada di skema): `IdTicket`, `NamaFile`, `PathFile`, `TipeFile`, `UkuranFile`, audit.
- **Task**: buat `TTaskDLampiran` (mirror `TTicketDLampiran`): `IdTask`, `NamaFile`, `PathFile`, `TipeFile`, `UkuranFile`, audit.
- **Multiple**: satu ticket/task bisa punya banyak lampiran; setiap file = satu baris lampiran.
- **Limit ukuran**: maksimal 3 MB per file (`maxSize(3072)` KB / `max:3072` validation / `MAX_UPLOAD_BYTES = 3145728`). File > 3 MB ditolak dengan pesan error jelas (id/en). Tujuan: mencegah boncos penyimpanan server.
- **Upload**: lewat `FileUpload` Filament `->multiple()->maxSize(3072)` di `TicketLampiranRelationManager`/`TaskLampiranRelationManager`, disimpan via `AttachmentService` ke disk terpisah (`storage/app/attachments`, bukan disk `public`) agar file sensitif customer tidak publik.
- **Download**: route auth-gated `/admin/attachments/{type}/{id}/download` -> `AttachmentController`, cek permission `ticket.view`/`task.view`, stream file asli dengan header `Content-Disposition: attachment`.
- **Hapus lampiran**: record DB + file fisik dihapus dari disk oleh `AttachmentService::delete()`.
- **Tipe file**: gambar (image/*) dan dokumen umum (PDF, docx, xlsx, dll) diterima; konfigurasi `acceptedFileTypes` dapat disesuaikan.

### Acceptance Criteria Tambahan (Notes & Lampiran)

- User dengan `ticket.manage`/`task.manage` dapat menulis progress note selama pengerjaan; note tercatat dengan author & timestamp dan tampil di timeline.
- User dapat mengunggah beberapa file sekaligus ke ticket/task; setiap file tersimpan dengan metadata (nama, path, tipe, ukuran).
- File lebih besar dari 3 MB ditolak; file <= 3 MB diterima.
- User dengan permission view dapat mengunduh lampiran; user tanpa permission ditolak.
- Lampiran disimpan di disk non-publik; hanya accessible via route download auth-gated.
- Menghapus lampiran menghapus record DB dan file fisik dari disk.
