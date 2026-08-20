# Tasks: Modul Task dan Ticketing VPoint Care

> Status: rencana awal. Item [ ] belum dikerjakan. Item [x] akan ditandai setelah implementasi.

## 0. Pre-Implementation Decisions

- [x] Putuskan arsitektur Task: modul mandiri dengan link opsional ke ticket/chat/customer + checklist + komentar + lampiran.
- [x] Putuskan reuse master: `MPrioritasTicket` dan `MKategoriTicket` dipakai bersama ticket & task; buat baru hanya `MStatusTask`.
- [x] Putuskan format nomor: `TCK-YYYYMMDD-NNN` untuk ticket, `TSK-YYYYMMDD-NNN` untuk task.
- [x] Putuskan permission: `task.view` dan `task.manage`.
- [x] Putuskan role assignment: ADMIN all, SUPERVISOR_CS/CS/DEVELOPER both, VIEWER read-only.
- [x] Putuskan localization: tambah key `id`/`en` untuk semua label baru.
- [x] Putuskan assignment: ticket & task keduanya punya `DitugaskanKepada` (FK ke `MPengguna`) + tabel riwayat penugasan (`TTicketDPenugasan` sudah ada; buat `TTaskDPenugasan` baru agar konsisten).
- [x] Putuskan filter "saya": tambah quick filter "Ticket Saya"/"Task Saya" (DitugaskanKepada = user login) di kedua resource.
- [x] Putuskan notifikasi: saat ticket/task di-assign/ubah penugasan ke user lain, kirim notifikasi Filament + (opsional) record di log aktivitas. Fase 1: notifikasi in-app Filament. Fase 2 opsional: WhatsApp/queue notification.
- [x] Putuskan progress notes: Ticket catat note di `TTicketD` (`JenisAktivitas = Catatan`); Task catat note/komentar di `TTaskDKomentar`. Tidak buat tabel baru untuk notes.
- [x] Putuskan lampiran: Ticket pakai `TTicketDLampiran` (sudah ada); Task buat `TTaskDLampiran` (mirror `TTicketDLampiran`). Upload multiple, limit per file 3 MB, download auth-gated dengan cek permission.
- [x] Putuskan storage lampiran: simpan di disk terpisah (bukan `public`) + route download auth-gated agar file sensitif customer tidak publik.

## 1. Audit Existing Code

- [ ] Audit skema `TTicket`, `TTicketD`, `TTicketDPenugasan`, `TTicketDLampiran`, `MStatusTicket`, `MKategoriTicket`, `MPrioritasTicket` di `DATABASE_SCHEMA_WACS.sql`.
- [ ] Audit halaman `Ticketing.php` dan `ticketing.blade.php`.
- [ ] Audit `AccessPermissions.php` (TICKET_VIEW, TICKET_MANAGE, sidebarMenus, defaultRolePermissions).
- [ ] Audit `NavigationHelper.php` dan `DatabaseSeeder.php` pola seed permission/menu.
- [ ] Audit pola resource `PengetahuanResource` dan `CustomerResource` (form, table, filter, relation).
- [ ] Audit pola model `UsesSqlServerUuid`, `Pengetahuan`, `DraftPengetahuan` (booted, casts, constants).
- [ ] Audit `FilamentAccess` dan `HasMenuBreadcrumbs`.
- [ ] Audit label localization `resources/lang/id/ui.php` dan `en/ui.php`.
- [ ] Audit model `Pengguna` untuk sumber data assignee (role aktif, NonAktif false) dan helper `permissionCodes()`.
- [ ] Audit `PublicStorageController` dan `WahaMediaController` untuk pola penyajian file + route auth-gated.
- [ ] Audit `routes/web.php` dan `config/filesystems.php` untuk disk storage yang tersedia.

## 2. Database Migration

- [ ] Buat migration `2026_07_13_000001_add_task_and_ticketing_module.php`.
- [ ] Guard `DB::getDriverName() === sqlsrv`.
- [ ] Buat `MStatusTask` (idempotent `IF OBJECT_ID... IS NULL`): Id, KodeStatusTask, NamaStatusTask, Urutan, StatusFinal, Warna, NonAktif, audit.
- [ ] Buat `TTask`: Id, NomorTask (unique), IdTicket (nullable FK), IdChat (nullable FK), IdCustomer (nullable FK), IdInstansi (nullable FK), IdTugasInduk (nullable self-ref), IdKategoriTicket (nullable FK), IdPrioritasTicket (nullable FK), IdStatusTask (FK), JudulTask, DeskripsiTask, DitugaskanKepada (nullable FK ke MPengguna), TglDitugaskan, TglTargetSelesai, TglSelesai, TglDitutup, DitutupOleh, EstimasiMenit (nullable), audit.
- [ ] Buat `TTaskDPenugasan`: Id, IdTask (FK), DitugaskanDari (nullable FK MPengguna), DitugaskanKepada (FK MPengguna), AlasanPenugasan varchar(500), TglPenugasan, audit. (Konsisten dengan `TTicketDPenugasan`.)
- [ ] Buat `TTaskDChecklist`: Id, IdTask (FK), JudulItem, Selesai (bit default 0), Urutan, TglSelesai, DiselesaikanOleh, audit.
- [ ] Buat `TTaskDKomentar`: Id, IdTask (FK), IsiKomentar nvarchar(max), TglKomentar, audit. (Catatan/komentar pengerjaan task.)
- [ ] Buat `TTaskDLampiran`: Id, IdTask (FK), NamaFile varchar(255), PathFile varchar(1000), TipeFile varchar(100), UkuranFile bigint, audit. (Mirror `TTicketDLampiran`.)
- [ ] Tambah constraint unique `UQ_TTask_NomorTask`.
- [ ] Tambah FK: `FK_TTask_TTicket`, `FK_TTask_TChat`, `FK_TTask_MCustomer`, `FK_TTask_MInstansi`, `FK_TTask_MKategoriTicket`, `FK_TTask_MPrioritasTicket`, `FK_TTask_MStatusTask`, `FK_TTask_MPengguna_Assignee`, `FK_TTask_TTask_Induk` (self), `FK_TTaskDPenugasan_TTask`, `FK_TTaskDPenugasan_MPengguna_Dari`, `FK_TTaskDPenugasan_MPengguna_Kepada`, `FK_TTaskDChecklist_TTask`, `FK_TTaskDKomentar_TTask`, `FK_TTaskDLampiran_TTask`.
- [ ] Tambah index: `IX_TTask_IdTicket`, `IX_TTask_IdStatusTask`, `IX_TTask_IdChat`, `IX_TTask_IdCustomer`, `IX_TTask_DitugaskanKepada`, `IX_TTask_TglTargetSelesai`, `IX_TTask_IdTugasInduk`, `IX_TTaskDPenugasan_IdTask`, `IX_TTaskDChecklist_IdTask`, `IX_TTaskDKomentar_IdTask`, `IX_TTaskDLampiran_IdTask`.
- [ ] Seed default `MStatusTicket`, `MKategoriTicket`, `MPrioritasTicket`, `MStatusTask` (idempotent via `IF NOT EXISTS`).
- [ ] Update `DATABASE_SCHEMA_WACS.sql` dengan tabel baru + seed agar fresh-install konsisten.
- [ ] Buat `down()` yang drop tabel dalam urutan aman (TTaskDLampiran, TTaskDPenugasan, TTaskDChecklist, TTaskDKomentar, TTask, MStatusTask).

## 3. Model

- [ ] Buat `App\Models\Master\StatusTicket` (table `MStatusTicket`, `UsesSqlServerUuid`).
- [ ] Buat `App\Models\Master\KategoriTicket` (table `MKategoriTicket`).
- [ ] Buat `App\Models\Master\PrioritasTicket` (table `MPrioritasTicket`, cast `BatasSlaMenit` integer, `Urutan` integer).
- [ ] Buat `App\Models\Master\StatusTask` (table `MStatusTask`).
- [ ] Buat `App\Models\Ticketing\Ticket` (table `TTicket`): casts datetime, `booted()` set TglBuat/TglEdit, relasi BelongsTo (chat, customer, instansi, kategori, prioritas, status, assignee->MPengguna) + HasMany (detail, penugasan, lampiran, tasks).
- [ ] Buat `App\Models\Ticketing\TicketDetail` (table `TTicketD`): relasi BelongsTo ticket; helper scope/konstanta `JENIS_CATATAN = Catatan` untuk progress note.
- [ ] Buat `App\Models\Ticketing\TicketPenugasan` (table `TTicketDPenugasan`): relasi BelongsTo ticket + assignee.
- [ ] Buat `App\Models\Ticketing\TicketLampiran` (table `TTicketDLampiran`): cast `UkuranFile` integer; accessor nama/ukuran terformat.
- [ ] Buat `App\Models\Task\Task` (table `TTask`): casts, `booted()`, konstanta status helper, relasi (ticket, chat, customer, instansi, kategori, prioritas, status, assignee->MPengguna, induk, anak, penugasans, checklist, komentar, lampirans).
- [ ] Buat `App\Models\Task\TaskPenugasan` (table `TTaskDPenugasan`): relasi BelongsTo task + DitugaskanDari/DitugaskanKepada -> MPengguna.
- [ ] Buat `App\Models\Task\TaskChecklist` (table `TTaskDChecklist`): cast `Selesai` boolean.
- [ ] Buat `App\Models\Task\TaskKomentar` (table `TTaskDKomentar`): catatan/komentar pengerjaan.
- [ ] Buat `App\Models\Task\TaskLampiran` (table `TTaskDLampiran`): cast `UkuranFile` integer; accessor nama/ukuran terformat.
- [ ] Tambah relasi `tasks()` HasMany di model `Ticket`.
- [ ] Pastikan semua model tidak menyimpan timestamp otomatis (`$timestamps = false` via trait).

## 4. Service & Lampiran

- [ ] Buat `App\Services\Ticketing\TicketNumberService::generate(): string` (format `TCK-YYYYMMDD-NNN`, cek unique, retry).
- [ ] Buat `App\Services\Task\TaskNumberService::generate(): string` (format `TSK-YYYYMMDD-NNN`).
- [ ] Buat `App\Services\Ticketing\TicketService`: logika buat/ubah status/penugasan -> catat `TTicketD` + `TTicketDPenugasan` saat `DitugaskanKepada` berubah, isi `TglDitugaskan`, kirim notifikasi ke assignee baru; tambah catatan (`addNote`) -> insert `TTicketD` `JenisAktivitas = Catatan`.
- [ ] Buat `App\Services\Task\TaskService`: logika buat/ubah status/penugasan -> catat `TTaskDPenugasan` saat `DitugaskanKepada` berubah, isi `TglDitugaskan`, toggle checklist, tambah komentar, kirim notifikasi ke assignee baru.
- [ ] Buat `App\Services\AttachmentService`: simpan file ke disk terpisah (mis. `local`/custom disk `attachments`), generate path unik `{module}/{recordId}/{uuid}.{ext}`, validasi ukuran maks 3 MB per file, simpan metadata (NamaFile, PathFile, TipeFile, UkuranFile) ke `TTicketDLampiran`/`TTaskDLampiran`, hapus file saat record lampiran dihapus.
- [ ] Helper `assigneeOptions()`: query `MPengguna` aktif (NonAktif=false, status approved) untuk dropdown Select DitugaskanKepada di kedua resource.
- [ ] Konstanta `MAX_UPLOAD_BYTES = 3145728` (3 MB) di `AttachmentService` untuk reuse validasi server-side.

## 5. Master Filament Resource

- [ ] Buat `Ticketing\StatusTickets\StatusTicketResource` + `Pages\ManageStatusTickets` (gate `ticket.view`/`ticket.manage`).
- [ ] Buat `Ticketing\KategoriTickets\KategoriTicketResource` + `Pages\ManageKategoriTickets`.
- [ ] Buat `Ticketing\PrioritasTickets\PrioritasTicketResource` + `Pages\ManagePrioritasTickets`.
- [ ] Buat `Task\StatusTasks\StatusTaskResource` + `Pages\ManageStatusTasks` (gate `task.view`/`task.manage`).
- [ ] Semua resource ikuti pola `PengetahuanResource` (NavigationHelper, FilamentAccess, HasMenuBreadcrumbs, label id/en).

## 6. Ticket Filament Resource

- [ ] Buat `Ticketing\Tickets\TicketResource` (model `Ticket`).
- [ ] Form: NomorTicket (readonly auto), JudulTicket, DeskripsiMasalah, Select IdCustomer (searchable), Select IdInstansi, Select IdKategoriTicket, Select IdPrioritasTicket, Select IdStatusTicket, Select IdChat (opsional), Select DitugaskanKepada (MPengguna aktif, searchable), DateTimePicker TglTargetSelesai, Textarea RingkasanAi (opsional).
- [ ] Table: NomorTicket, customer/instansi, JudulTicket, status (badge warna), prioritas (badge), assignee (nama pengguna), TglTargetSelesai (warna merah jika overdue), TglSelesai, TglBuat.
- [ ] Filters: SelectFilter IdStatusTicket, IdPrioritasTicket, IdKategoriTicket, DitugaskanKepada; TernaryFilter/quick filter "Ticket Saya" (DitugaskanKepada = Auth::id()); DateRangeFilter TglBuat.
- [ ] Actions: CreateAction, EditAction, DeleteAction (gate ticket.manage), Action Tutup Ticket (set status final + TglDitutup + DitututOleh), Action Tugaskan Ulang (modal pilih assignee + alasan -> lewat TicketService).
- [ ] RelationManagers: `TicketDetailRelationManager` (activity timeline: status/penugasan/aktivitas + form tambah Catatan progress note), `TicketPenugasanRelationManager` (riwayat penugasan: dari->ke, alasan, tgl), `TicketLampiranRelationManager` (upload multiple FileUpload maxSize 3072 KB + download action + hapus), `TaskRelationManager` (task terikat ticket).
- [ ] Buat `Pages\ManageTickets` + (opsional) `Pages\ViewTicket` untuk detail.
- [ ] Gate `canViewAny` = `ticket.view` + NavigationHelper::isActive; `canCreate/canEdit/canDelete` = `ticket.manage`.
## 7. Task Filament Resource

- [ ] Buat `Task\Tasks\TaskResource` (model `Task`).
- [ ] Form: NomorTask (readonly auto), JudulTask, DeskripsiTask, Select IdTicket (opsional, searchable), Select IdChat (opsional), Select IdCustomer (opsional), Select IdKategoriTicket, Select IdPrioritasTicket, Select IdStatusTask, Select DitugaskanKepada (MPengguna aktif, searchable), Select IdTugasInduk (opsional), DateTimePicker TglTargetSelesai, Number EstimasiMenit.
- [ ] Table: NomorTask, JudulTask, status (badge), prioritas (badge), assignee (nama pengguna), IdTicket (link), TglTargetSelesai (overdue warna), checklist progress (x/total).
- [ ] Filters: IdStatusTask, IdPrioritasTicket, DitugaskanKepada; TernaryFilter/quick filter "Task Saya" (DitugaskanKepada = Auth::id()); IdTicket.
- [ ] Actions: CreateAction, EditAction, DeleteAction, Action Tandai Selesai, Action Tugaskan Ulang (modal pilih assignee + alasan -> lewat TaskService).
- [ ] RelationManagers: `TaskPenugasanRelationManager` (riwayat penugasan), `TaskChecklistRelationManager` (tambah/toggle/hapus item), `TaskKomentarRelationManager` (tambah catatan/komentar pengerjaan), `TaskLampiranRelationManager` (upload multiple FileUpload maxSize 3072 KB + download action + hapus).
- [ ] Buat `Pages\ManageTasks`.
- [ ] Gate `canViewAny` = `task.view`; `canCreate/canEdit/canDelete` = `task.manage`.

## 8. Lampiran Upload & Download

- [ ] Buat controller `App\Http\Controllers\AttachmentController` dengan method download($type, $id): resolve record `TTicketDLampiran`/`TTaskDLampiran`, cek permission (`ticket.view`/`task.view`), cek file ada di disk, stream file dengan nama asli + header `Content-Disposition: attachment`.
- [ ] Tambah route `/admin/attachments/{type}/{id}/download` -> `AttachmentController`, middleware `auth`, name `admin.attachments.download`.
- [ ] Konfigurasi disk `attachments` di `config/filesystems.php` (local/private, root `storage/app/attachments`) bila belum ada; pastikan tidak publik.
- [ ] `AttachmentService::store($uploadedFile, string $module, string $recordId): array` -> validasi 3 MB, simpan file, return metadata.
- [ ] `AttachmentService::delete(self $lampiran): void` -> hapus file dari disk + record DB.
- [ ] FileUpload di RelationManager: `->multiple()`, `->maxSize(3072)` (KB = 3 MB), `->acceptedFileTypes([image/*, application/pdf, ...])`, `->storeFileNames(false)`, custom save via `AttachmentService` (setiap file = 1 baris lampiran).
- [ ] Validasi server-side ukuran: Filament maxSize + form rule `file|max:3072` + cek eksplisit `AttachmentService::MAX_UPLOAD_BYTES`.
- [ ] Download action di tabel lampiran: link ke `route('admin.attachments.download', ...)` hanya untuk user dengan permission view.
- [ ] Tampilkan pesan error jelas (id/en) bila file > 3 MB ditolak.

## 9. Upgrade Halaman Ticketing Dashboard

- [ ] Update `Ticketing.php` untuk query statistik real dari `TTicket`/`TTask`.
- [ ] Update `ticketing.blade.php`: stats (baru, dalam pengerjaan, overdue SLA, selesai) dari DB, tabel queue real (limit 10 terbaru), tombol Buat Ticket & Kelola Ticket ke resource.
- [ ] Ganti label hardcoded demo dengan key localization dinamis.
- [ ] Tambah panel "Ticket Saya" (jumlah ticket yang DitugaskanKepada = user login, belum selesai) di dashboard.

## 10. Permission dan Navigation

- [ ] Tambah `TASK_VIEW`/`TASK_MANAGE` di `AccessPermissions`.
- [ ] Tambah definition label `task_view`/`task_manage`/`task_module` (id + en).
- [ ] Tambah sidebar menu `ticket.view` (resource) dan `task.view` (resource) di `sidebarMenus()`.
- [ ] Update `defaultRolePermissions()` dengan task.view/task.manage.
- [ ] Update `DatabaseSeeder::seedPermissions()` agar task permission ter-seed ke `MHakAkses`.
- [ ] Flush `NavigationHelper::flush()` setelah seed.

## 11. Localization

- [ ] Tambah key `id` di `resources/lang/id/ui.php`: permission task, model ticket, model task, master, dashboard, assignment (assignee, reassign, assignment_history, assigned_to, assigned_by, assign_to_me, my_tickets, my_tasks, reassign_reason), notes (progress_note, add_note, note_placeholder, notes_timeline), attachment (attachments, upload_file, upload_multiple, download, file_too_large, max_file_size, file_size, file_type, uploaded_at).
- [ ] Tambah key `en` di `resources/lang/en/ui.php`: pasangan Inggris untuk semua key di atas.
- [ ] Ganti key demo ticketing (`sample_issue_*` dll) jadi label dinamis.

## 12. Notifikasi Assignment (Fase 1 In-App)

- [ ] Buat notifikasi Filament `TicketAssignedNotification` (kirim ke `DitugaskanKepada` saat assign/ubah penugasan ticket).
- [ ] Buat notifikasi Filament `TaskAssignedNotification` (kirim ke `DitugaskanKepada` saat assign/ubah penugasan task).
- [ ] Pastikan notifikasi tidak bocor ke user tanpa akses (`ticket.view`/`task.view`).
- [ ] (Fase 2 opsional) Notifikasi WhatsApp/queue ke assignee via WAHA bila diinginkan.

## 13. Integrasi dari Chat (Fase 2 Opsional)

- [ ] Tambah action Buat Ticket dari Chat di `InboxWhatsapp`/`ViewChatSession` (pre-fill IdChat, IdCustomer, opsi pre-fill DitugaskanKepada).
- [ ] Tambah action Buat Task dari Chat (pre-fill IdChat).
- [ ] Pastikan action hanya tampil jika user punya `ticket.manage`/`task.manage`.

## 14. Testing

- [ ] Test migration fresh-install pada SQL Server kosong.
- [ ] Test migration pada database existing (idempotent).
- [ ] Test CRUD ticket (create, edit, assign, reassign, close, delete).
- [ ] Test CRUD task + checklist toggle + komentar/notes + assign/reassign.
- [ ] Test link task ke ticket tampil sebagai action-item.
- [ ] Test nomor auto-generated unique.
- [ ] Test SLA overdue ditandai benar.
- [ ] Test penugasan: ubah `DitugaskanKepada` mencatat baris baru di `TTicketDPenugasan`/`TTaskDPenugasan` dengan `DitugaskanDari` & `DitugaskanKepada` & `AlasanPenugasan`.
- [ ] Test filter "Ticket Saya"/"Task Saya" hanya menampilkan record DitugaskanKepada = user login.
- [ ] Test notifikasi assignment terkirim ke assignee baru (in-app).
- [ ] Test progress note: tulis note di ticket -> muncul di `TTicketD` `JenisAktivitas = Catatan`; tulis komentar task -> muncul di `TTaskDKomentar`.
- [ ] Test upload lampiran: multiple file (gambar + dokumen) tersimpan ke disk + metadata di `TTicketDLampiran`/`TTaskDLampiran`.
- [ ] Test limit 3 MB: file > 3 MB ditolak dengan pesan error jelas; file = 3 MB diterima.
- [ ] Test download lampiran: user dgn permission bisa download; user tanpa permission ditolak; file hilang -> 404.
- [ ] Test hapus lampiran: record + file fisik dihapus dari disk.
- [ ] Test permission: user tanpa `ticket.view`/`task.view` tidak melihat menu & tidak bisa download lampiran.
- [ ] Test localization id/en termasuk label assignment, notes, attachment.
- [ ] Jalankan `php -l` pada semua file baru/berubah.
- [ ] Jalankan `php artisan test` bila tersedia.

## 15. Documentation

- [ ] Update `docs/PLAN_TASK_DAN_TICKETING.md` sesuai implementasi final.
- [ ] Update `README.md` modul Ticketing & Task (termasuk alur assignment, notes, lampiran, limit ukuran).
- [ ] Update `openspec/specs/care-desk/spec.md` setelah change diterima.
- [ ] Dokumentasikan SOP penanganan ticket & task termasuk penugasan, eskalasi, catatan, dan lampiran.

## 16. Deployment Notes

- [ ] Backup database sebelum migration production.
- [ ] Jalankan `php artisan migrate --force`.
- [ ] Jalankan `php artisan db:seed --force` (atau seed master via migration).
- [ ] Pastikan direktori storage `storage/app/attachments` writable dan tidak publik (konfigurasi disk + permission filesystem).
- [ ] Jalankan `php artisan optimize:clear` lalu `php artisan optimize`.
- [ ] Restart queue worker bila service/job/notifikasi baru.
- [ ] Jalankan `npm run build` bila view/frontend asset berubah.
- [ ] Beri akses menu task/ticket ke role sesuai rollout.
