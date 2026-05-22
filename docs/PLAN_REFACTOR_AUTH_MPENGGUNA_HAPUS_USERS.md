# Plan Refactor Auth ke MPengguna dan Hapus Users

Tanggal: 2026-05-06

## Status Eksekusi

Status: sudah dieksekusi pada 2026-05-06.

Hasil eksekusi:

1. Auth Laravel/Filament sudah diarahkan ke `MPengguna`.
2. Tabel `users` sudah dihapus dari database aktif.
3. `MPengguna` tetap ada dan admin `mrthx.89@gmail.com` aktif.
4. `sessions` dikosongkan dan `sessions.user_id` sudah diubah menjadi `nvarchar(100)` untuk UUID.
5. Semua tabel transaksi `T*` sudah diverifikasi kosong.
6. Runtime lama `App\Models\User`, `UserResource`, dan `UserPenggunaSyncService` sudah dihapus.
7. `php artisan test`, `php -l`, `route:list`, dan auth-provider check sudah berhasil.

## Tujuan

Refactor autentikasi aplikasi agar `MPengguna` menjadi satu-satunya sumber data user internal. Tabel `users` akan dihapus dari database dan dari jalur kode aplikasi. `MPengguna` tidak dihapus.

Selain itu, data transaksi pada tabel berawalan `T` akan dibersihkan terlebih dahulu agar database kembali kosong dari data operasional sebelum refactor auth dijalankan.

## Keputusan yang Sudah Ditetapkan

1. Auth utama pindah dari `users` ke `MPengguna`.
2. Tabel `users` dihapus.
3. Tabel `MPengguna` tetap dipertahankan.
4. Session login lama tidak dipertahankan. User wajib login ulang.
5. Data transaksi pada semua tabel dengan prefix `T` dibersihkan dahulu.
6. Tabel Laravel pendukung seperti `sessions`, `cache`, `jobs`, dan `password_reset_tokens` tidak ikut dianggap tabel transaksi `T`.

## Kondisi Saat Ini

### Auth

Saat ini aplikasi masih memakai model `App\Models\User` untuk auth Laravel dan Filament.

File terdampak:

- `config/auth.php`
- `app/Models/User.php`
- `app/Models/Master/Pengguna.php`
- `app/Filament/Auth/Login.php`
- `app/Filament/Auth/Register.php`
- `app/Filament/Actions/EditOwnProfileAction.php`
- `app/Support/FilamentAccess.php`
- `app/Filament/Resources/System/Users/UserResource.php`
- `app/Filament/Resources/System/Users/Pages/ManageUsers.php`
- `app/Filament/Resources/Master/Penggunas/PenggunaResource.php`
- `app/Filament/Resources/Master/Penggunas/Pages/ManagePenggunas.php`
- `app/Services/Auth/UserPenggunaSyncService.php`
- `database/seeders/DatabaseSeeder.php`
- `database/factories/UserFactory.php`

### Database

`MPengguna` sudah punya field auth:

- `Id`
- `NamaPengguna`
- `IdPeran`
- `Email`
- `Password`
- `NomorWhatsappInternal`
- `FotoProfilPath`
- `Alamat`
- `RememberToken`
- `EmailTerverifikasiPada`
- `LoginTerakhirPada`
- `NonAktif`
- `TglBuat`
- `TglEdit`
- `UserId`

Field yang akan dihapus dari `MPengguna`:

- `UserId`

Constraint/index yang akan dihapus:

- `FK_MPengguna_users`
- `UX_MPengguna_UserId`

Tabel yang akan dihapus:

- `users`

Tabel yang tetap dipertahankan:

- `MPengguna`
- `sessions`
- `password_reset_tokens`
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`
- `migrations`

Catatan session: kolom `sessions.user_id` harus memakai tipe string karena auth identifier `MPengguna.Id` adalah UUID.

## Daftar Tabel Transaksi yang Akan Dibersihkan

Berdasarkan schema saat ini, tabel berawalan `T` adalah:

1. `TLogAktivitas`
2. `TLogError`
3. `TLogIntegrasi`
4. `TLogWebhookWaha`
5. `TChat`
6. `TChatD`
7. `TChatDPenugasan`
8. `TChatDCatatanInternal`
9. `TTicket`
10. `TTicketD`
11. `TTicketDPenugasan`
12. `TTicketDLampiran`
13. `TAiPermintaan`
14. `TAiRespon`

## Urutan Bersih Data Transaksi

Karena ada FK antar tabel transaksi, delete harus dari child ke parent.

Sebelum delete, nullable FK yang membentuk siklus harus diputus:

1. `TChatD.IdAiRespon = NULL`
2. `TTicket.DibuatDariPesanId = NULL`

Urutan delete yang disarankan:

1. `TTicketDLampiran`
2. `TTicketDPenugasan`
3. `TTicketD`
4. `TChatDCatatanInternal`
5. `TChatDPenugasan`
6. `TChatD`
7. `TAiRespon`
8. `TAiPermintaan`
9. `TTicket`
10. `TChat`
11. `TLogWebhookWaha`
12. `TLogIntegrasi`
13. `TLogError`
14. `TLogAktivitas`

Jika SQL Server menolak `TRUNCATE` karena FK, gunakan `DELETE FROM` dalam transaction. Setelah delete, reseed identity tidak diperlukan karena tabel utama memakai UUID/default `NEWSEQUENTIALID()`.

## Strategi Refactor

### Prinsip

- `MPengguna` menjadi auth model tunggal.
- Nama tabel bisnis tetap `MPengguna`, tidak diganti menjadi `users`.
- Field database tetap memakai nama existing seperti `Email`, `Password`, `RememberToken`, dan `NonAktif`.
- UI boleh tetap memakai label "User" jika lebih natural untuk admin, tetapi data source harus `MPengguna`.
- Setelah auth pindah, tidak boleh ada sync dua arah `users <-> MPengguna`.

### Pendekatan Teknis

Model `App\Models\Master\Pengguna` harus berubah dari `Model` menjadi auth model:

- extend `Illuminate\Foundation\Auth\User as Authenticatable`
- implement `FilamentUser`
- implement `HasAvatar`
- gunakan `Notifiable`
- tetap gunakan `UsesSqlServerUuid`
- primary key tetap `Id`
- key type string
- incrementing false

Mapping field auth:

| Laravel | MPengguna |
| --- | --- |
| auth identifier | `Id` |
| email/login | `Email` |
| password | `Password` |
| remember token | `RememberToken` |
| email verified at | `EmailTerverifikasiPada` |
| active status | `NonAktif = 0` |
| display name | `NamaPengguna` |
| avatar | `FotoProfilPath` |

Status approval lama dari `users.status` tidak dipertahankan sebagai kolom baru. Status aktif cukup dari `MPengguna.NonAktif`.

Jika tetap perlu alur registrasi menunggu approval, maka registrasi baru dibuat dengan `NonAktif = 1`, lalu admin mengaktifkan dengan mengubah `NonAktif = 0`.

## Detail Tahap Implementasi

### Tahap 1 - Backup dan Checkpoint

1. Backup database sebelum eksekusi.
2. Pastikan production memang tidak aktif.
3. Catat jumlah data awal:
   - `MPengguna`
   - `users`
   - semua tabel `T*`
   - `sessions`
4. Pastikan minimal ada satu record admin aktif di `MPengguna`.

Query pengecekan admin:

```sql
SELECT p.Id, p.NamaPengguna, p.Email, p.NonAktif, r.KodePeran
FROM MPengguna p
LEFT JOIN MPeran r ON r.Id = p.IdPeran
WHERE p.NonAktif = 0;
```

### Tahap 2 - Bersihkan Data Transaksi T*

Buat migration baru, contoh:

`database/migrations/2026_05_06_000003_clear_transaction_tables_before_auth_refactor.php`

Isi migration:

1. Validasi driver `sqlsrv`.
2. Jalankan dalam transaction.
3. Delete data transaksi sesuai urutan child ke parent.
4. Jangan delete `MPengguna`, `M*`, `sessions`, `jobs`, `cache`, atau `migrations`.

Contoh pola SQL:

```sql
SET XACT_ABORT ON;
BEGIN TRANSACTION;

UPDATE TChatD SET IdAiRespon = NULL;
UPDATE TTicket SET DibuatDariPesanId = NULL;

DELETE FROM TTicketDLampiran;
DELETE FROM TTicketDPenugasan;
DELETE FROM TTicketD;
DELETE FROM TChatDCatatanInternal;
DELETE FROM TChatDPenugasan;
DELETE FROM TChatD;
DELETE FROM TAiRespon;
DELETE FROM TAiPermintaan;
DELETE FROM TTicket;
DELETE FROM TChat;
DELETE FROM TLogWebhookWaha;
DELETE FROM TLogIntegrasi;
DELETE FROM TLogError;
DELETE FROM TLogAktivitas;

COMMIT TRANSACTION;
```

Rollback untuk tahap ini tidak realistis tanpa backup. `down()` cukup diberi catatan atau dibuat kosong karena data sudah dihapus.

### Tahap 3 - Ubah Model MPengguna Menjadi Auth Model

Update `app/Models/Master/Pengguna.php`:

1. Extend `Authenticatable`.
2. Tambah contract `FilamentUser`, `HasAvatar`.
3. Tambah trait `Notifiable`.
4. Hapus hook `booted()` yang memanggil `UserPenggunaSyncService`.
5. Tambah method:
   - `canAccessPanel(Panel $panel): bool`
   - `roleCode(): ?string`
   - `permissionCodes(): array`
   - `hasPermissionCode(string $permission): bool`
   - `hasAnyPermissionCode(array $permissions): bool`
   - `getFilamentAvatarUrl(): ?string`
   - `getAuthPasswordName(): string`
   - `getRememberTokenName(): string`
   - `getEmailForPasswordReset(): string`
6. Pastikan password cast memakai `hashed` untuk field `Password`.

Catatan penting:

Laravel login form Filament default memakai field `email`, sedangkan kolom DB adalah `Email`. Karena itu custom `Login` harus mengubah credentials dari `email` menjadi `Email`.

### Tahap 4 - Ubah Auth Provider

Update `config/auth.php`:

1. Ganti import `App\Models\User` menjadi `App\Models\Master\Pengguna`.
2. Provider `users.model` diarahkan ke `Pengguna::class`.
3. Nama provider boleh tetap `users` untuk kompatibilitas config Laravel, tetapi modelnya `Pengguna`.

Contoh target:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => env('AUTH_MODEL', Pengguna::class),
    ],
],
```

### Tahap 5 - Ubah Login

Update `app/Filament/Auth/Login.php`:

1. Import `App\Models\Master\Pengguna`.
2. Ganti semua check `User` menjadi `Pengguna`.
3. Override credentials agar query memakai `Email` dan `Password`.
4. Validasi akun aktif:
   - jika `NonAktif = 1`, login ditolak.
5. Setelah login berhasil, update `LoginTerakhirPada`.

Target perilaku:

- Email/password benar dan `NonAktif = 0`: login berhasil.
- Email/password benar tapi `NonAktif = 1`: login ditolak dengan pesan akun belum aktif/nonaktif.
- Session lama tidak dipakai karena tabel `sessions` akan dibersihkan.

### Tahap 6 - Ubah Register

Update `app/Filament/Auth/Register.php`:

1. Registrasi membuat record langsung ke `MPengguna`.
2. `NamaPengguna` diisi dari name form.
3. `Email` diisi dari email form.
4. `Password` diisi hash password.
5. `IdPeran` memakai default role.
6. `NonAktif = 1` jika tetap ingin menunggu approval admin.
7. Hapus pemanggilan `UserPenggunaSyncService`.

Jika register publik tidak dipakai, opsi yang lebih aman adalah nonaktifkan register dan biarkan admin membuat `MPengguna` dari panel.

### Tahap 7 - Ubah Profile Popup

Update `app/Filament/Actions/EditOwnProfileAction.php`:

1. Ganti model `User` ke `Pengguna`.
2. Form data langsung dari authenticated `MPengguna`.
3. Save langsung ke field:
   - `NamaPengguna`
   - `Alamat`
   - `NomorWhatsappInternal`
   - `FotoProfilPath`
   - `Password` jika diubah
4. Hapus sync ke `users`.

### Tahap 8 - Ubah Permission Helper

Update `app/Support/FilamentAccess.php`:

1. Ganti import `App\Models\User` ke `App\Models\Master\Pengguna`.
2. Check `auth()->user()` harus instance `Pengguna`.
3. Method permission tetap memakai `hasPermissionCode()` dan `hasAnyPermissionCode()`.

### Tahap 9 - Satukan Resource User

Saat ini ada dua resource:

- `System\Users\UserResource`
- `Master\Penggunas\PenggunaResource`

Setelah auth pindah ke `MPengguna`, cukup gunakan satu resource.

Rekomendasi:

1. Pertahankan `PenggunaResource` sebagai resource utama.
2. Pindahkan fitur penting dari `UserResource` ke `PenggunaResource`:
   - create user/login
   - ubah role
   - ubah password
   - aktif/nonaktif
   - foto profil
   - alamat
   - nomor WhatsApp
3. Hapus/hilangkan registrasi navigation untuk `UserResource`.
4. Hapus folder `app/Filament/Resources/System/Users` setelah tidak ada referensi.

### Tahap 10 - Hapus Sync Service dan User Model

Hapus file yang tidak diperlukan:

- `app/Models/User.php`
- `app/Services/Auth/UserPenggunaSyncService.php`
- `database/factories/UserFactory.php`
- `app/Filament/Resources/System/Users/UserResource.php`
- `app/Filament/Resources/System/Users/Pages/ManageUsers.php`

Update file yang sebelumnya import class tersebut.

### Tahap 11 - Migration Drop users dan Bersihkan Session

Buat migration baru, contoh:

`database/migrations/2026_05_06_000004_drop_users_after_pengguna_auth_refactor.php`

Isi migration:

1. Validasi driver `sqlsrv`.
2. Hapus session login lama:
   - `DELETE FROM sessions`
3. Drop FK `FK_MPengguna_users`.
4. Drop index `UX_MPengguna_UserId`.
5. Drop column `MPengguna.UserId`.
6. Drop tabel `users`.
7. Ubah `sessions.user_id` menjadi string/nvarchar agar kompatibel dengan UUID `MPengguna.Id`.
8. Jangan drop `MPengguna`.
9. Jangan drop `password_reset_tokens` kecuali reset password dipastikan tidak dipakai.

Contoh pola SQL:

```sql
SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF OBJECT_ID(N'sessions', 'U') IS NOT NULL
    DELETE FROM sessions;

IF OBJECT_ID(N'FK_MPengguna_users', 'F') IS NOT NULL
    ALTER TABLE MPengguna DROP CONSTRAINT FK_MPengguna_users;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_MPengguna_UserId' AND object_id = OBJECT_ID('MPengguna'))
    DROP INDEX UX_MPengguna_UserId ON MPengguna;

IF COL_LENGTH('MPengguna', 'UserId') IS NOT NULL
    ALTER TABLE MPengguna DROP COLUMN UserId;

IF OBJECT_ID(N'users', 'U') IS NOT NULL
    DROP TABLE users;

COMMIT TRANSACTION;
```

Rollback untuk `users` tidak disarankan. Jika perlu rollback, restore dari backup database.

### Tahap 12 - Update Fresh Install Migration dan Schema SQL

Untuk database baru, jangan sampai tabel `users` dibuat lagi.

Update:

- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/2026_04_27_000001_add_status_to_users_table.php`
- `database/migrations/2026_05_01_000004_link_users_to_pengguna.php`
- `DATABASE_SCHEMA_WACS.sql`

Target:

1. Migration awal tidak membuat `users`.
2. Tetap membuat `sessions`.
3. Tetap membuat `password_reset_tokens` jika reset password masih dipakai.
4. Migration add status users tidak diperlukan.
5. Migration link users to pengguna tidak diperlukan.
6. `DATABASE_SCHEMA_WACS.sql` menghapus:
   - kolom `MPengguna.UserId`
   - FK `FK_MPengguna_users`
   - index `UX_MPengguna_UserId`
   - semua referensi tabel `users`

Catatan:

Jika migration lama sudah pernah jalan di database aktif, jangan mengandalkan edit migration lama untuk existing DB. Existing DB tetap harus memakai migration baru di Tahap 11. Edit migration lama hanya untuk fresh install.

### Tahap 13 - Update Seeder

Update `database/seeders/DatabaseSeeder.php`:

1. Hapus pemakaian `App\Models\User`.
2. Hapus pemakaian `UserPenggunaSyncService`.
3. Seed admin langsung ke `MPengguna`.
4. Pastikan `Password` ter-hash.
5. Pastikan `NonAktif = 0`.
6. Pastikan `IdPeran` admin valid.

Target admin default:

- `NamaPengguna`: Admin VPoint Care
- `Email`: sesuai seed saat ini
- `Password`: hash password default dari env atau nilai seed saat ini
- `IdPeran`: role admin/root
- `NonAktif`: false

### Tahap 14 - Audit Referensi Lama

Setelah coding selesai, jalankan:

```powershell
rg -n "App\\Models\\User|class User|UserFactory|UserResource|UserPenggunaSyncService|\\busers\\b|UserId|status|approved_at|blocked_at|remember_token|email_verified_at" app config database routes resources
```

Referensi yang masih boleh tersisa:

- komentar historis di plan `.md`
- migration drop users baru
- migration lama yang sengaja dipertahankan sebagai history, jika tidak diedit

Referensi runtime tidak boleh tersisa.

### Tahap 15 - Jalankan Migration

Urutan command:

```powershell
php artisan migrate:status --pending
php artisan migrate --pretend
php artisan migrate --force
```

Setelah migration:

```sql
SELECT COUNT(*) FROM MPengguna;
SELECT COUNT(*) FROM sessions;
SELECT OBJECT_ID('users', 'U') AS UsersObjectId;
SELECT COL_LENGTH('MPengguna', 'UserId') AS UserIdColumn;
```

Target:

- `MPengguna` tetap ada dan punya data admin.
- `sessions` kosong.
- `users` tidak ada.
- `MPengguna.UserId` tidak ada.

### Tahap 16 - Verifikasi Aplikasi

Jalankan:

```powershell
php -l app\Models\Master\Pengguna.php
php -l app\Filament\Auth\Login.php
php -l app\Filament\Auth\Register.php
php -l app\Filament\Actions\EditOwnProfileAction.php
php -l app\Support\FilamentAccess.php
php artisan test
php artisan route:list
```

Verifikasi manual:

1. Buka panel admin.
2. Pastikan diarahkan ke login karena session lama sudah kosong.
3. Login dengan admin dari `MPengguna`.
4. Pastikan nama user tampil dari `NamaPengguna`.
5. Pastikan avatar tampil dari `FotoProfilPath`.
6. Buka menu sesuai role/hak akses.
7. Ubah profile.
8. Ubah password.
9. Logout.
10. Login ulang dengan password baru.
11. Cek `LoginTerakhirPada` berubah.
12. Pastikan menu `Users` lama sudah hilang atau sudah menjadi menu `Pengguna`.

## Risiko dan Mitigasi

### Risiko 1 - User Tidak Bisa Login

Penyebab:

- `config/auth.php` belum mengarah ke `Pengguna`.
- Login credentials masih memakai `email` bukan `Email`.
- Password belum hash kompatibel Laravel.
- `NonAktif` admin bernilai `1`.

Mitigasi:

- Siapkan query aktivasi admin.
- Pastikan password admin bisa di-reset langsung di SQL atau lewat tinker.

Query darurat:

```sql
UPDATE MPengguna
SET NonAktif = 0
WHERE Email = 'email_admin';
```

Password darurat sebaiknya dibuat lewat Laravel `Hash::make()` agar format valid.

### Risiko 2 - Filament Menolak Akses Panel

Penyebab:

- `Pengguna` belum implement `FilamentUser`.
- `canAccessPanel()` belum mengembalikan true untuk `NonAktif = 0`.

Mitigasi:

- Test login setelah model auth selesai, sebelum drop users jika memungkinkan.

### Risiko 3 - Hak Akses Kosong

Penyebab:

- Query permission masih mencari `UserId`.
- Join role/hak akses belum langsung memakai `MPengguna.IdPeran`.

Mitigasi:

- Method `roleCode()` dan `permissionCodes()` di `Pengguna` harus memakai data dari record sendiri, bukan join via `UserId`.

### Risiko 4 - Session Driver Database Error

Penyebab:

- Tabel `sessions` ikut terhapus.

Mitigasi:

- Jangan drop `sessions`.
- Hanya `DELETE FROM sessions`.

### Risiko 5 - Data Transaksi Hilang Permanen

Penyebab:

- Tahap bersih data `T*` memang destructive.

Mitigasi:

- Backup database wajib sebelum migration.
- Jalankan hanya karena user sudah menyetujui data transaksi dibersihkan.

## Rollback Strategy

Rollback normal tidak disarankan karena:

- data transaksi `T*` dihapus permanen,
- tabel `users` dihapus,
- session lama sengaja dibuang.

Rollback yang realistis:

1. Restore database dari backup sebelum Tahap 2.
2. Revert perubahan kode dari git.
3. Clear cache Laravel.
4. Login kembali memakai jalur lama.

Command setelah restore:

```powershell
php artisan optimize:clear
php artisan migrate:status
php artisan test
```

## Checklist Eksekusi

- [ ] Backup database.
- [ ] Pastikan production tidak aktif.
- [ ] Pastikan admin aktif ada di `MPengguna`.
- [ ] Buat migration clear data transaksi `T*`.
- [ ] Refactor `Pengguna` menjadi auth model.
- [ ] Update `config/auth.php`.
- [ ] Update custom login.
- [ ] Update register atau matikan register.
- [ ] Update profile popup.
- [ ] Update `FilamentAccess`.
- [ ] Gabungkan/hapus resource `Users`.
- [ ] Hapus `UserPenggunaSyncService`.
- [ ] Hapus `App\Models\User`.
- [ ] Update seeder agar langsung seed `MPengguna`.
- [ ] Buat migration clear sessions dan drop `users`.
- [ ] Update fresh install migrations.
- [ ] Update `DATABASE_SCHEMA_WACS.sql`.
- [ ] Jalankan `php artisan migrate --pretend`.
- [ ] Jalankan `php artisan migrate --force`.
- [ ] Jalankan audit `rg`.
- [ ] Jalankan `php -l` untuk file PHP terdampak.
- [ ] Jalankan `php artisan test`.
- [ ] Test login ulang manual.
- [ ] Test menu/hak akses.
- [ ] Test edit profile dan ubah password.

## Catatan Final Sebelum Eksekusi

Refactor ini lebih besar dari rename tabel chat/ticket karena menyentuh auth inti. Eksekusi sebaiknya dilakukan dalam satu sesi kerja sampai login berhasil kembali. Jangan berhenti setelah migration drop `users` sebelum login admin via `MPengguna` terbukti berhasil.
