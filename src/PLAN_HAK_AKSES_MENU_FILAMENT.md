# Plan Hak Akses Menu Filament WACS

## Tujuan

Merapikan menu dan akses layar Filament agar sesuai role user dengan memakai tabel hak akses yang sudah tersedia di database:

- `MPeran`
- `MPengguna`
- `MHakAkses`
- `MPeranHakAkses`

Implementasi harus menutup dua jalur akses:

1. Menu/sidebar hanya tampil jika user punya hak akses.
2. URL langsung dan action penting tetap ditolak jika user tidak punya hak akses.

## Kondisi Saat Ini

- Login Filament hanya mengecek `users.status === approved`.
- Role user tersimpan di `MPengguna.IdPeran`, terhubung ke `MPeran`.
- Form `User Login` sudah bisa memilih `MPeran`.
- `MHakAkses` dan `MPeranHakAkses` sudah ada di schema, tetapi belum diisi oleh seeder dan belum dipakai oleh code.
- Filament Pages/Resources ditemukan otomatis lewat `discoverPages()` dan `discoverResources()`, sehingga page/resource yang belum punya guard akan terlihat untuk semua user aktif.

## Prinsip Desain

- Hak akses utama diambil dari `MHakAkses.KodeHakAkses`.
- Role hanya menjadi kumpulan hak akses melalui `MPeranHakAkses`.
- `ADMIN` selalu diberi semua hak akses lewat seed.
- Jika user aktif belum punya role atau hak akses, akses default harus minimal dan tidak boleh full access.
- Permission code dibuat stabil dan eksplisit agar mudah dimapping ke menu/action.
- Tidak memakai package permission eksternal dulu karena tabel internal sudah tersedia.

## Permission Code

### Dashboard

- `dashboard.view`: melihat dasbor.

### Inbox WhatsApp

- `inbox.view`: melihat menu dan halaman Inbox WhatsApp.
- `inbox.reply`: mengirim balasan WhatsApp dan menyimpan draft balasan.
- `inbox.manage`: mengambil/memperbarui mapping chat, menutup percakapan, catatan internal, dan kontrol operasional chat.

### Ticketing

- `ticket.view`: melihat menu dan halaman Ticketing.
- `ticket.manage`: mengelola ticket operasional.

### AI Agent

- `ai_agent.view`: melihat halaman AI Agent.
- `ai_agent.manage`: menyimpan pengaturan AI Agent, API key, template, dan setting notifikasi.

### Log Data

- `log_data.view`: melihat halaman Log Data.

### Master Customer

- `master_customer.view`: melihat ringkasan dan daftar master customer.
- `master_customer.manage`: create/edit data master customer, nomor WhatsApp, grup WhatsApp, anggota grup, dan sinkron data instansi.

### Knowledge Base AI

- `knowledge.view`: melihat Knowledge Base AI.
- `knowledge.manage`: create/edit Knowledge Base AI.

### Hari Libur

- `holiday.view`: melihat master Hari Libur.
- `holiday.manage`: create/edit master Hari Libur.

### User Management

- `user.view`: melihat daftar user login.
- `user.manage`: approve, block, pending, create, edit, dan assign role user.

### Chat History

- `chat_history.view`: membuka halaman detail riwayat chat dari link history.

## Mapping Awal Role

### ADMIN

Semua permission.

### SUPERVISOR_CS

- `dashboard.view`
- `inbox.view`
- `inbox.reply`
- `inbox.manage`
- `ticket.view`
- `ticket.manage`
- `ai_agent.view`
- `ai_agent.manage`
- `log_data.view`
- `master_customer.view`
- `master_customer.manage`
- `knowledge.view`
- `knowledge.manage`
- `holiday.view`
- `holiday.manage`
- `chat_history.view`

### CS

- `dashboard.view`
- `inbox.view`
- `inbox.reply`
- `ticket.view`
- `ticket.manage`
- `master_customer.view`
- `chat_history.view`

### DEVELOPER

- `dashboard.view`
- `ticket.view`
- `ticket.manage`
- `log_data.view`
- `chat_history.view`

### VIEWER

- `dashboard.view`
- `chat_history.view`

## File yang Akan Dibuat

### `app/Support/AccessPermissions.php`

Berisi daftar permission resmi, label, modul, keterangan, mapping role default, dan helper untuk permission group.

### `app/Support/FilamentAccess.php`

Berisi helper untuk dipakai Page/Resource/action:

- `can(string $permission): bool`
- `canAny(array $permissions): bool`
- `canManageMasterCustomer(): bool`
- `canViewMasterCustomer(): bool`
- `canManageUsers(): bool`

## File yang Akan Diubah

### `database/seeders/DatabaseSeeder.php`

- Seed role default: `ADMIN`, `SUPERVISOR_CS`, `CS`, `DEVELOPER`, `VIEWER`.
- Seed semua `MHakAkses`.
- Seed mapping `MPeranHakAkses`.
- Tetap menjaga admin default existing.
- Seeder harus idempotent dengan `updateOrInsert`.

### `app/Models/User.php`

Tambahkan method:

- `roleCode(): ?string`
- `permissionCodes(): array`
- `hasPermissionCode(string $permission): bool`
- `hasAnyPermissionCode(array $permissions): bool`

Query permission harus lewat `MPengguna -> MPeran -> MPeranHakAkses -> MHakAkses`, dan hanya mengambil data yang `NonAktif = false`.

### Filament Pages

Tambahkan `canAccess()`:

- `Dashboard`: `dashboard.view`
- `InboxWhatsapp`: `inbox.view`
- `Ticketing`: `ticket.view`
- `AiAgent`: `ai_agent.view`
- `LogData`: `log_data.view`
- `MasterCustomer`: `master_customer.view`
- `ViewChatSession`: `chat_history.view`

Tambahkan guard action di `InboxWhatsapp`:

- `saveInternalNote`: `inbox.manage`
- `toggleAutoReplyAi`: `inbox.manage`
- `tutupPercakapan`: `inbox.manage`
- `resetSapaanAi`: `inbox.manage`
- `refreshMappingChat`: `inbox.manage`
- `refreshProfilWaha`: `inbox.manage`
- `simpanBalasanLokal`: `inbox.reply`
- `kirimBalasanWaha`: `inbox.reply`

Tambahkan guard action di `AiAgent`:

- `simpanPengaturan`: `ai_agent.manage`
- method lain yang mengubah setting/API key juga harus memakai `ai_agent.manage`.

### Filament Resources

Tambahkan static permission guard:

- `UserResource`
  - `canViewAny`: `user.view`
  - `canCreate`, `canEdit`, `canDelete`: `user.manage`
  - approve/block/pending action: `user.manage`

- `InstansiResource`, `CustomerResource`, `NomorWhatsappResource`, `GrupWhatsappResource`, `AnggotaGrupWhatsappResource`
  - `canViewAny`: `master_customer.view`
  - `canCreate`, `canEdit`, `canDelete`: `master_customer.manage`
  - create/edit/sync actions mengikuti `master_customer.manage`

- `PengetahuanResource`
  - `canViewAny`: `knowledge.view`
  - `canCreate`, `canEdit`, `canDelete`: `knowledge.manage`

- `HariLiburResource`
  - `canViewAny`: `holiday.view`
  - `canCreate`, `canEdit`, `canDelete`: `holiday.manage`

- `PenggunaResource`
  - tetap tidak tampil di navigation.
  - jika URL resource dibuka langsung, akses dibatasi `user.view` / `user.manage`.

## Tahapan Eksekusi

1. Buat `AccessPermissions` dan `FilamentAccess`.
2. Tambahkan method permission ke `User`.
3. Update seeder untuk `MPeran`, `MHakAkses`, dan `MPeranHakAkses`.
4. Pasang guard `canAccess()` ke Page.
5. Pasang guard static resource ke Resource.
6. Pasang guard pada action penting di Page dan Resource Page header actions.
7. Jalankan validasi syntax PHP.
8. Jalankan test/build yang relevan jika environment memungkinkan.
9. Jalankan atau instruksikan `php artisan db:seed --class=DatabaseSeeder` agar permission masuk ke database.

## Catatan Risiko

- Jika production database belum memiliki tabel `MHakAkses` atau `MPeranHakAkses`, seeder harus melewati bagian tersebut tanpa crash.
- Jika user sudah approved tetapi belum punya `MPengguna`, menu akan sangat terbatas. Ini sengaja agar tidak ada akses penuh tanpa role.
- `ADMIN` harus tetap bisa mengakses semua menu setelah seed.
- Menu yang disembunyikan bukan pengaman tunggal; direct URL tetap harus dijaga dengan `canAccess()` dan `canViewAny()`.

## Verifikasi

- `php -l` untuk semua file PHP yang diubah/dibuat.
- `php artisan test` jika database test tersedia.
- Manual setelah seeding:
  - ADMIN melihat semua menu.
  - SUPERVISOR_CS tidak melihat User Login.
  - CS tidak melihat AI Agent, Log Data, User Login, Hari Libur, Knowledge Base AI.
  - DEVELOPER hanya melihat Dashboard, Ticketing, Log Data, dan history chat.
  - VIEWER hanya melihat Dashboard dan link history jika diberi akses.
  - URL langsung ke menu tanpa permission menghasilkan forbidden/ditolak.
