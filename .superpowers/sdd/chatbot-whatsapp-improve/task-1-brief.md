### Task 1: Schema Snapshot dan Fixture Test

**Tujuan:** Menambahkan kontrak data minimum untuk identitas WAHA, avatar participant, dan idle session AI tanpa mengubah data master.

**Files:**
- Create: `src/database/migrations/2026_07_30_000001_add_waha_identity_snapshot_to_chat.php`
- Modify: `src/script/DATABASE_SCHEMA_WACS.sql`
- Modify: `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`
- Create: `src/tests/Feature/Waha/WahaChatIdentitySyncTest.php`
- Create: `src/tests/Feature/Waha/WahaWebhookGroupIngestionTest.php`
- Create: `src/tests/Feature/Ai/AiAutoReplySessionPolicyTest.php`

**Interfaces:**
- `TChat`: `NamaKontakWaha nvarchar(150) NULL`, `NamaGrupWaha nvarchar(200) NULL`, `TglIdentitasWahaDiambil datetime2 NULL`, `StatusIdentitasWaha varchar(30) NULL`, `PesanErrorIdentitasWaha nvarchar(500) NULL`.
- `TChatD`: `PengirimIdWaha varchar(200) NULL`, `UrlFotoProfilPengirim nvarchar(1000) NULL`, `TglFotoProfilPengirimDiambil datetime2 NULL`.
- `MPengaturanAi`: `BatasSesiAutoReplyMenit int NOT NULL DEFAULT 60`, valid `1..1440`.

- [ ] **Step 1: Buat migration SQL Server guarded**

Gunakan `DB::unprepared()` dan `COL_LENGTH()` sehingga database existing aman. `up()` menambah semua kolom bila belum ada; `down()` menjatuhkan constraint default/check dan kolom hanya bila ada. Tambahkan check constraint `CK_MPengaturanAi_BatasSesiAutoReplyMenit` dan default constraint `DF_MPengaturanAi_BatasSesiAutoReplyMenit`.

- [ ] **Step 2: Sinkronkan fresh schema**

Tambahkan kolom yang sama pada `CREATE TABLE TChat`, `CREATE TABLE TChatD`, dan `CREATE TABLE MPengaturanAi` di `src/script/DATABASE_SCHEMA_WACS.sql`, termasuk tipe `nvarchar`, `varchar`, `datetime2`, default 60, dan check `BETWEEN 1 AND 1440`.

- [ ] **Step 3: Lengkapi SQLite fixture**

Tambahkan kolom nullable/string/date-time pada table builder test existing dan fixture baru. Jangan menjalankan migration SQL Server pada SQLite; feature test membuat tabel minimum seperti pola `InboxWhatsappTest`.

- [ ] **Step 4: Jalankan gate schema**

Run:

```powershell
cd src
php -l database/migrations/2026_07_30_000001_add_waha_identity_snapshot_to_chat.php
php artisan test --filter=InboxWhatsappTest
```

Expected: syntax migration bersih dan test Inbox existing tetap lulus.

---

