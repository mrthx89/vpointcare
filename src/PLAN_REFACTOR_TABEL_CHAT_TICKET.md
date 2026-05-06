# Plan Refactor Nama Tabel Chat dan Ticket WACS

## Tujuan

Merapikan naming tabel transaksi chat dan ticket agar lebih konsisten:

- Header/master transaksi tidak lagi memakai suffix `M`.
- Detail transaksi tetap memakai suffix `D`.
- Tabel turunan/detail operasional memakai pola `T...D...`.
- Semua kolom FK yang masih memakai suffix `M` ikut disesuaikan.

Dokumen ini adalah plan implementasi. Refactor code dan database belum dilakukan dalam dokumen ini.

## Mapping Nama Tabel

| Area | Nama Saat Ini | Nama Target | Catatan |
|---|---|---|---|
| Chat header | `TChatM` | `TChat` | Rename tabel utama chat. |
| Chat detail pesan | `TChatD` | `TChatD` | Tetap, tetapi kolom FK berubah. |
| Catatan internal chat | `TChatCatatanInternal` | `TChatDCatatanInternal` | Rename sebagai detail/turunan chat. |
| Penugasan chat | `TChatPenugasan` | `TChatDPenugasan` | Rename sebagai detail/turunan chat. |
| Ticket header | `TTicketM` | `TTicket` | Rename tabel utama ticket. |
| Ticket detail aktivitas | `TTicketD` | `TTicketD` | Tetap, tetapi kolom FK berubah. |
| Lampiran ticket | `TTicketLampiran` | `TTicketDLampiran` | Rename sebagai detail/turunan ticket. |
| Penugasan ticket | `TTicketPenugasan` | `TTicketDPenugasan` | Rename sebagai detail/turunan ticket. |

## Mapping Kolom FK

| Tabel Saat Ini | Tabel Target | Kolom Saat Ini | Kolom Target | Referensi Target |
|---|---|---|---|---|
| `TChatD` | `TChatD` | `IdChatM` | `IdChat` | `TChat(Id)` |
| `TChatCatatanInternal` | `TChatDCatatanInternal` | `IdChatM` | `IdChat` | `TChat(Id)` |
| `TChatPenugasan` | `TChatDPenugasan` | `IdChatM` | `IdChat` | `TChat(Id)` |
| `TTicketM` | `TTicket` | `IdChatM` | `IdChat` | `TChat(Id)` |
| `TTicketD` | `TTicketD` | `IdTicketM` | `IdTicket` | `TTicket(Id)` |
| `TTicketLampiran` | `TTicketDLampiran` | `IdTicketM` | `IdTicket` | `TTicket(Id)` |
| `TTicketPenugasan` | `TTicketDPenugasan` | `IdTicketM` | `IdTicket` | `TTicket(Id)` |
| `TAiPermintaan` | `TAiPermintaan` | `IdChatM` | `IdChat` | `TChat(Id)` |
| `TAiPermintaan` | `TAiPermintaan` | `IdTicketM` | `IdTicket` | `TTicket(Id)` |

## Mapping Constraint dan Index

Nama constraint/index tidak wajib secara teknis, tetapi sebaiknya ikut dirapikan agar schema konsisten dan mudah ditrace.

### Chat

| Saat Ini | Target |
|---|---|
| `PK_TChatM` | `PK_TChat` |
| `DF_TChatM_Id` | `DF_TChat_Id` |
| `DF_TChatM_JenisChat` | `DF_TChat_JenisChat` |
| `DF_TChatM_Prioritas` | `DF_TChat_Prioritas` |
| `DF_TChatM_JumlahPesanBelumDibaca` | `DF_TChat_JumlahPesanBelumDibaca` |
| `DF_TChatM_AutoReplyAiAktif` | `DF_TChat_AutoReplyAiAktif` |
| `DF_TChatM_AiSudahMenyapa` | `DF_TChat_AiSudahMenyapa` |
| `DF_TChatM_ModeAutoReplyAi` | `DF_TChat_ModeAutoReplyAi` |
| `DF_TChatM_JumlahNotifikasiBelumTerbalas` | `DF_TChat_JumlahNotifikasiBelumTerbalas` |
| `DF_TChatM_TglBuat` | `DF_TChat_TglBuat` |
| `FK_TChatM_MSesiWhatsapp` | `FK_TChat_MSesiWhatsapp` |
| `FK_TChatM_MStatusChat` | `FK_TChat_MStatusChat` |
| `FK_TChatM_MCustomer` | `FK_TChat_MCustomer` |
| `FK_TChatM_MInstansi` | `FK_TChat_MInstansi` |
| `FK_TChatM_MNomorWhatsapp` | `FK_TChat_MNomorWhatsapp` |
| `FK_TChatM_MGrupWhatsapp` | `FK_TChat_MGrupWhatsapp` |
| `FK_TChatD_TChatM` | `FK_TChatD_TChat` |
| `FK_TChatPenugasan_TChatM` | `FK_TChatDPenugasan_TChat` |
| `FK_TChatCatatanInternal_TChatM` | `FK_TChatDCatatanInternal_TChat` |
| `FK_TTicketM_TChatM` | `FK_TTicket_TChat` |
| `FK_TAiPermintaan_TChatM` | `FK_TAiPermintaan_TChat` |
| `IX_TChatM_*` | `IX_TChat_*` |
| `IX_TChatD_IdChatM_TglPesan` | `IX_TChatD_IdChat_TglPesan` |
| `IX_TAiPermintaan_IdChatM` | `IX_TAiPermintaan_IdChat` |

### Ticket

| Saat Ini | Target |
|---|---|
| `PK_TTicketM` | `PK_TTicket` |
| `DF_TTicketM_Id` | `DF_TTicket_Id` |
| `DF_TTicketM_TglBuat` | `DF_TTicket_TglBuat` |
| `UQ_TTicketM_NomorTicket` | `UQ_TTicket_NomorTicket` |
| `FK_TTicketM_MCustomer` | `FK_TTicket_MCustomer` |
| `FK_TTicketM_MInstansi` | `FK_TTicket_MInstansi` |
| `FK_TTicketM_MKategoriTicket` | `FK_TTicket_MKategoriTicket` |
| `FK_TTicketM_MPrioritasTicket` | `FK_TTicket_MPrioritasTicket` |
| `FK_TTicketM_MStatusTicket` | `FK_TTicket_MStatusTicket` |
| `FK_TTicketM_TChatD` | `FK_TTicket_TChatD` |
| `FK_TTicketD_TTicketM` | `FK_TTicketD_TTicket` |
| `FK_TTicketPenugasan_TTicketM` | `FK_TTicketDPenugasan_TTicket` |
| `FK_TTicketLampiran_TTicketM` | `FK_TTicketDLampiran_TTicket` |
| `FK_TAiPermintaan_TTicketM` | `FK_TAiPermintaan_TTicket` |
| `IX_TTicketM_*` | `IX_TTicket_*` |
| `IX_TTicketD_IdTicketM_TglAktivitas` | `IX_TTicketD_IdTicket_TglAktivitas` |
| `IX_TAiPermintaan_IdTicketM` | `IX_TAiPermintaan_IdTicket` |

## Area Code yang Terdampak

Audit awal menemukan referensi nama lama pada area berikut:

### WAHA dan Media

- `app/Services/Waha/WahaWebhookProcessor.php`
- `app/Http/Controllers/WahaMediaController.php`
- `app/Http/Controllers/Webhook/WahaWebhookController.php` jika ada payload/log yang menyebut chat id.

Perubahan penting:

- `DB::table('TChatM')` menjadi `DB::table('TChat')`.
- `TChatD.IdChatM` menjadi `TChatD.IdChat`.
- Return payload seperti `chat_id` tetap boleh dipertahankan karena itu contract API/event, bukan nama kolom DB.

### AI Agent dan Notifikasi

- `app/Services/Ai/AiAutoReplyService.php`
- `app/Services/Ai/ChatBelumTerbalasNotifier.php`
- `app/Filament/Pages/AiAgent.php`
- `app/Console/Commands/KirimNotifikasiChatBelumTerbalas.php` jika ada query turunan.

Perubahan penting:

- Query chat header memakai `TChat`.
- Query detail pesan tetap `TChatD`, tetapi semua `IdChatM` menjadi `IdChat`.
- `TAiPermintaan.IdChatM` menjadi `IdChat`.
- `TAiPermintaan.IdTicketM` menjadi `IdTicket`.

### Filament UI

- `app/Filament/Pages/InboxWhatsapp.php`
- `app/Filament/Pages/ViewChatSession.php`
- `app/Filament/Pages/Dashboard.php`
- `app/Filament/Pages/Ticketing.php`
- `resources/views/filament/pages/inbox-whatsapp.blade.php` jika ada field literal.
- `resources/views/filament/pages/view-chat-session.blade.php` jika ada field literal.
- `resources/views/filament/pages/ticketing.blade.php` jika ada field literal.

Perubahan penting:

- Inbox memakai `TChat` sebagai header.
- Catatan internal memakai `TChatDCatatanInternal`.
- History chat/detail pesan tetap `TChatD`, FK `IdChat`.
- Dashboard group-by/join/subquery harus mengganti `IdChatM` ke `IdChat`.
- Ticket count memakai `TTicket`.

### Migrations dan Schema

- `DATABASE_SCHEMA_WACS.sql`
- `database/migrations/2026_04_27_000001_create_vpoint_care_schema.php`
- `database/migrations/2026_04_27_000002_add_whatsapp_group_mapping.php`
- `database/migrations/2026_04_27_000003_add_ai_auto_reply_settings.php`
- `database/migrations/2026_04_27_000004_add_unanswered_chat_notification_settings.php`
- `database/migrations/2026_05_01_000002_add_waha_profile_to_chat.php`

Catatan penting:

- Untuk database existing, harus dibuat migration baru yang melakukan rename dan rebuild FK/index.
- Untuk fresh install, `DATABASE_SCHEMA_WACS.sql` juga harus diperbarui agar schema baru langsung terbentuk dengan nama target.
- Migration lama yang sudah published idealnya tidak diubah untuk production history, tetapi karena base schema repo ini berasal dari file SQL lokal, fresh-install path wajib divalidasi setelah `DATABASE_SCHEMA_WACS.sql` diubah.

## Strategi Implementasi yang Disarankan

### Prinsip

- Lakukan refactor dalam satu batch yang terkontrol, karena tabel dan kolom FK saling terkait.
- Jangan membuat compatibility view lama kecuali dibutuhkan untuk rollback cepat.
- Pada SQL Server, rename kolom/tabel memakai `sp_rename`, tetapi FK/index/constraint sebaiknya drop dan recreate agar nama constraint bersih.
- Pastikan backup database dilakukan sebelum migration dijalankan.

### Tahap 1 - Freeze dan Backup

1. Stop sementara worker yang menulis chat/ticket:
   - queue worker
   - WAHA webhook delivery jika memungkinkan
   - Reverb tidak wajib stop, tetapi UI realtime sebaiknya tidak dipakai selama migration
2. Backup database `DBVPointCare`.
3. Catat jumlah row sebelum migration:
   - `TChatM`
   - `TChatD`
   - `TChatCatatanInternal`
   - `TChatPenugasan`
   - `TTicketM`
   - `TTicketD`
   - `TTicketLampiran`
   - `TTicketPenugasan`
   - `TAiPermintaan`

### Tahap 2 - Buat Migration Rename

Buat migration baru, misalnya:

`database/migrations/2026_05_06_000002_refactor_chat_ticket_table_names.php`

Isi migration `up()` harus melakukan urutan berikut:

1. Drop FK yang tergantung pada tabel/kolom lama:
   - FK dari `TChatD` ke `TChatM`
   - FK dari `TChatPenugasan` ke `TChatM`
   - FK dari `TChatCatatanInternal` ke `TChatM`
   - FK dari `TTicketM` ke `TChatM`
   - FK dari `TTicketM` ke `TChatD`
   - FK dari `TTicketD` ke `TTicketM`
   - FK dari `TTicketPenugasan` ke `TTicketM`
   - FK dari `TTicketLampiran` ke `TTicketM`
   - FK dari `TAiPermintaan` ke `TChatM`
   - FK dari `TAiPermintaan` ke `TTicketM`
2. Drop index lama yang memakai nama tabel/kolom lama:
   - `IX_TChatM_*`
   - `IX_TChatD_IdChatM_TglPesan`
   - `IX_TTicketM_*`
   - `IX_TTicketD_IdTicketM_TglAktivitas`
   - `IX_TAiPermintaan_IdChatM`
   - `IX_TAiPermintaan_IdTicketM`
3. Rename tabel:
   - `EXEC sp_rename 'TChatM', 'TChat'`
   - `EXEC sp_rename 'TChatCatatanInternal', 'TChatDCatatanInternal'`
   - `EXEC sp_rename 'TChatPenugasan', 'TChatDPenugasan'`
   - `EXEC sp_rename 'TTicketM', 'TTicket'`
   - `EXEC sp_rename 'TTicketLampiran', 'TTicketDLampiran'`
   - `EXEC sp_rename 'TTicketPenugasan', 'TTicketDPenugasan'`
4. Rename kolom FK:
   - `TChatD.IdChatM -> IdChat`
   - `TChatDCatatanInternal.IdChatM -> IdChat`
   - `TChatDPenugasan.IdChatM -> IdChat`
   - `TTicket.IdChatM -> IdChat`
   - `TTicketD.IdTicketM -> IdTicket`
   - `TTicketDLampiran.IdTicketM -> IdTicket`
   - `TTicketDPenugasan.IdTicketM -> IdTicket`
   - `TAiPermintaan.IdChatM -> IdChat`
   - `TAiPermintaan.IdTicketM -> IdTicket`
5. Rename/default constraints jika memungkinkan:
   - drop/recreate default constraints dengan nama baru, atau `sp_rename` constraint.
   - lebih aman gunakan helper SQL untuk mencari default constraint by column lalu drop/recreate hanya jika nama lama ada.
6. Recreate PK/UQ/FK dengan nama baru.
7. Recreate index dengan nama dan kolom baru.

### Tahap 3 - Buat Migration Down

`down()` harus reversible:

1. Drop FK/index baru.
2. Rename kolom target kembali ke nama lama.
3. Rename tabel target kembali ke nama lama.
4. Recreate FK/index lama.

Catatan: rollback rename di production harus tetap dianggap emergency action, bukan prosedur normal.

### Tahap 4 - Update Fresh Schema

Update `DATABASE_SCHEMA_WACS.sql`:

1. Rename `CREATE TABLE TChatM` menjadi `CREATE TABLE TChat`.
2. Rename `CREATE TABLE TTicketM` menjadi `CREATE TABLE TTicket`.
3. Rename tabel detail turunan sesuai mapping.
4. Rename semua kolom FK sesuai mapping.
5. Rename semua constraint/index sesuai target.
6. Update urutan create/drop di `2026_04_27_000001_create_vpoint_care_schema.php`:
   - drop child tables lebih dulu:
     - `TTicketDLampiran`
     - `TTicketDPenugasan`
     - `TTicketD`
     - `TTicket`
     - `TChatDCatatanInternal`
     - `TChatDPenugasan`
     - `TChatD`
     - `TAiPermintaan` harus diperhatikan urutannya karena FK ke chat/ticket
     - `TChat`

### Tahap 5 - Update Code Query

Lakukan replace terkontrol, bukan blind replace seluruh repo.

#### Replace Tabel

- `TChatM` -> `TChat`
- `TChatCatatanInternal` -> `TChatDCatatanInternal`
- `TChatPenugasan` -> `TChatDPenugasan`
- `TTicketM` -> `TTicket`
- `TTicketLampiran` -> `TTicketDLampiran`
- `TTicketPenugasan` -> `TTicketDPenugasan`

#### Replace Kolom

- `IdChatM` -> `IdChat`
- `IdTicketM` -> `IdTicket`

Pengecualian:

- Nama variable PHP seperti `$chatId` tidak perlu diganti.
- Nama event payload seperti `chat_id` tetap dipertahankan jika sudah menjadi contract frontend.
- Text UI tidak perlu mengikuti nama kolom DB.

### Tahap 6 - Validasi Code

Jalankan:

```bash
rg -n "TChatM|TChatCatatanInternal|TChatPenugasan|TTicketM|TTicketLampiran|TTicketPenugasan|IdChatM|IdTicketM" app database resources config routes DATABASE_SCHEMA_WACS.sql
php -l app/Services/Waha/WahaWebhookProcessor.php
php -l app/Services/Ai/AiAutoReplyService.php
php -l app/Services/Ai/ChatBelumTerbalasNotifier.php
php -l app/Filament/Pages/InboxWhatsapp.php
php -l app/Filament/Pages/ViewChatSession.php
php -l app/Filament/Pages/Dashboard.php
php artisan migrate --pretend
php artisan test
```

Target hasil `rg`:

- Tidak ada referensi nama lama di `app`, `resources`, `routes`, `config`.
- Referensi nama lama di migration historis hanya boleh ada jika sengaja dipertahankan dan tidak dieksekusi pada fresh path.

### Tahap 7 - Validasi Database

Setelah migration:

1. Pastikan tabel target ada:
   - `TChat`
   - `TChatD`
   - `TChatDCatatanInternal`
   - `TChatDPenugasan`
   - `TTicket`
   - `TTicketD`
   - `TTicketDLampiran`
   - `TTicketDPenugasan`
2. Pastikan tabel lama tidak ada:
   - `TChatM`
   - `TChatCatatanInternal`
   - `TChatPenugasan`
   - `TTicketM`
   - `TTicketLampiran`
   - `TTicketPenugasan`
3. Pastikan kolom lama tidak ada:
   - `IdChatM`
   - `IdTicketM`
4. Pastikan row count sama antara sebelum dan sesudah migration.
5. Pastikan FK aktif dan trusted:
   - cek `sys.foreign_keys`
   - cek `is_disabled = 0`
   - cek `is_not_trusted = 0`

### Tahap 8 - Validasi Fungsional

Flow yang harus dites manual:

1. WAHA webhook masuk dan membuat/menambah chat.
2. Inbox WhatsApp menampilkan daftar chat.
3. Pilih chat dan history pesan tampil.
4. Kirim balasan teks.
5. Kirim balasan dengan lampiran.
6. Simpan draft lokal.
7. Catatan internal chat tersimpan ke `TChatDCatatanInternal`.
8. Refresh mapping chat.
9. Refresh profil WAHA.
10. Auto reply AI membuat detail pesan baru di `TChatD`.
11. Notifikasi chat belum terbalas membaca `TChat`/`TChatD`.
12. Dashboard menghitung chat, unread, CS/AI reply, top client.
13. View Chat Session dari modal history tetap terbuka.
14. Ticketing membaca/menulis `TTicket` dan detail turunannya jika modul sudah aktif.

## Risiko dan Mitigasi

### Risiko 1 - WAHA menulis saat migration berjalan

Mitigasi:

- Stop queue worker dan tahan webhook sementara.
- Jalankan migration saat traffic rendah.

### Risiko 2 - Query raw terlewat

Mitigasi:

- Wajib `rg` untuk semua nama lama sebelum dan sesudah implementasi.
- Tambahkan helper konstanta nama tabel jika setelah refactor masih banyak query raw.

### Risiko 3 - Fresh install rusak

Mitigasi:

- Update `DATABASE_SCHEMA_WACS.sql`.
- Jalankan fresh migrate di database kosong sebelum dianggap selesai.

### Risiko 4 - Constraint/index lama tidak ikut rename

Mitigasi:

- Jangan hanya mengandalkan `sp_rename` tabel/kolom.
- Drop/recreate FK dan index dengan nama target.
- Cek `sys.foreign_keys`, `sys.indexes`, dan `sys.default_constraints`.

### Risiko 5 - Rollback sulit

Mitigasi:

- Backup DB sebelum migration.
- Sediakan `down()` migration yang benar.
- Jangan deploy code baru sebelum migration DB sukses.

## Rencana Eksekusi Aman

1. Commit/simpan state saat ini.
2. Buat migration rename dengan `up()` dan `down()`.
3. Update `DATABASE_SCHEMA_WACS.sql`.
4. Update code query dan schema checks.
5. Jalankan `php -l` untuk semua file yang disentuh.
6. Jalankan `php artisan migrate --pretend`.
7. Jalankan migration di local/dev.
8. Jalankan `php artisan test`.
9. Jalankan manual test flow WAHA, Inbox, AI, Dashboard, Ticket.
10. Jika valid, baru deploy ke production dengan backup dan downtime singkat.

## Keputusan yang Perlu Dikonfirmasi Sebelum Implementasi

Status: sudah dikonfirmasi pada 2026-05-06.

1. `IdChatM` diganti menjadi `IdChat` di semua tabel, termasuk `TChatD` dan `TAiPermintaan`.
2. `IdTicketM` diganti menjadi `IdTicket` di semua tabel, termasuk `TTicketD` dan `TAiPermintaan`.
3. Constraint dan index harus ikut direname penuh.
4. Production sedang tidak aktif, sehingga migration rename aman dijalankan sekarang dengan backup/checkpoint tetap disarankan.
5. Compatibility view tidak diperlukan.
