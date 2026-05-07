# Plan Perbaikan MHakAkses, Sidebar, dan UI Hak Akses

## Tujuan

Menjadikan `MHakAkses` sebagai sumber data untuk:

- Label hak akses ID/EN.
- Struktur menu sidebar dan grouping menu.
- Urutan menu dan group sidebar melalui kolom `SortOrder`.
- Icon sidebar melalui kolom `IconString`.
- UI `/admin/master/hak-akses` yang rapi seperti datatable `Pengguna`: paging, filter, sort, status, dan edit via dialog.

Dashboard tetap menjadi menu sidebar tanpa grouping.

## Kondisi Kode Saat Ini

- `HakAksesResource` sudah ada, tetapi tampilannya belum sepenuhnya mengikuti pola datatable `Pengguna`.
- `DatabaseSeeder` sudah memanggil `seedNavigationSort()`, tetapi method itu belum ada sehingga perlu diperbaiki sebelum seeder aman dijalankan.
- Ada `NavigationHelper` awal yang masih memakai konsep `GroupSortOrder`, sedangkan requirement terbaru hanya memakai `SortOrder` dan relasi `IdHakAkses`.
- Ada migration `SortOrder` yang masih menambah `GroupSortOrder`, lalu migration berikutnya mencoba menghapusnya. Ini perlu dirapikan agar struktur final jelas.
- Ada dua migration bilingual dengan nama sangat mirip:
  - `2026_05_06_000006_add_multilanguage_columns_to_hak_akses.php`
  - `2026_05_06_000006_add_multilingual_columns_to_hak_akses.php`

## Struktur Final `MHakAkses`

Kolom yang dipakai:

- `Id`: primary key.
- `IdHakAkses`: nullable self-reference ke `MHakAkses.Id`.
- `KodeHakAkses`: kode permission/menu.
- `NamaHakAksesId`: label Indonesia.
- `NamaHakAksesEn`: label English.
- `ModulId`: nama modul/group Indonesia.
- `ModulEn`: nama modul/group English.
- `KeteranganId`: deskripsi Indonesia.
- `KeteranganEn`: deskripsi English.
- `SortOrder`: urutan group atau item.
- `IconString`: nama icon sidebar, contoh `heroicon-o-shield-check`.
- `NonAktif`: status aktif/nonaktif.
- Kolom legacy `NamaHakAkses`, `Modul`, `Keterangan` tetap disinkronkan dari versi ID untuk kompatibilitas query lama.
- Untuk group sidebar, `KeteranganId` dan `KeteranganEn` tidak dirender di sidebar, tetapi tetap diisi multilanguage sebagai penjelasan admin di halaman Hak Akses.

Interpretasi record:

- Group sidebar: `IdHakAkses = NULL` dan `KodeHakAkses = NULL`.
- Menu sidebar dalam group: `IdHakAkses` berisi `Id` group, `KodeHakAkses` berisi permission code.
- Dashboard: `IdHakAkses = NULL` dan `KodeHakAkses = dashboard.view`, diperlakukan sebagai menu sidebar tanpa group.

Catatan schema:

- Jika group rows membutuhkan `KodeHakAkses = NULL`, constraint unique lama `UQ_MHakAkses_KodeHakAkses` perlu diganti menjadi filtered unique index untuk kode yang tidak null.
- `GroupSortOrder` tidak dipakai lagi.

## Rencana Migrasi Database

1. Tambah atau pastikan kolom `IdHakAkses uniqueidentifier NULL`.
2. Tambah atau pastikan kolom `SortOrder int NULL`.
3. Tambah atau pastikan kolom `IconString varchar(100) NULL`.
4. Tambah migration susulan idempotent untuk database yang sudah pernah menjalankan migration `SortOrder` versi lama tanpa `IconString`.
5. Hapus penggunaan `GroupSortOrder` dari migration/helper/resource.
5. Ubah constraint unique `KodeHakAkses` agar support banyak row group dengan `KodeHakAkses NULL`.
6. Update `DATABASE_SCHEMA_WACS.sql` supaya fresh install sama dengan migration.
7. Backfill data:
   - Buat row group untuk `Operasional`, `Asisten`, `Master Data`, `Monitoring`, `Pengaturan`, dan `Sistem`.
   - Set `IdHakAkses` menu-menu existing ke group yang sesuai.
   - Set Dashboard tetap `IdHakAkses = NULL`.
   - Isi `SortOrder` group dan item.
   - Isi `IconString` default untuk group dan menu sidebar.
   - Isi `KeteranganId` dan `KeteranganEn` default untuk group sidebar jika masih kosong.

## Rencana Seeder

1. `AccessPermissions` tetap menjadi daftar permission resmi dan bilingual source.
2. `DatabaseSeeder::seedPermissions()` harus:
   - Upsert row group sidebar bilingual.
   - Upsert row permission/menu bilingual.
   - Set `IdHakAkses` menu ke group.
   - Set `SortOrder`.
   - Set `IconString`.
   - Tetap mengisi role permission di `MPeranHakAkses`.
3. Tambahkan method yang sekarang hilang:
   - `seedNavigationSort()` atau ganti dengan method yang lebih tepat seperti `seedSidebarStructure()`.
4. Jangan menghapus perubahan label user dari database saat seed jika nantinya user sudah edit label manual, kecuali untuk kode bawaan yang memang perlu disinkronkan. Perlu diputuskan:
   - Opsi aman: seeder hanya isi default jika kosong.
   - Opsi sinkron penuh: seeder selalu overwrite label bawaan.

Rekomendasi saya: seeder mengisi struktur, sort, dan default bilingual; untuk label yang sudah diedit user, sebaiknya tidak dioverwrite kecuali user menjalankan command khusus reset.

## Rencana Sidebar Filament

1. Buat `NavigationHelper` final yang membaca `MHakAkses`.
2. Item sort:
   - Tiap Page/Resource memakai `getNavigationSort()` dari `NavigationHelper::sortFor(permissionCode)`.
   - Hapus atau abaikan property `protected static ?int $navigationSort = ...` yang hardcode.
   - Tiap Page/Resource memakai `getNavigationIcon()` dari `NavigationHelper::iconFor(permissionCode)`.
3. Group label:
   - `getNavigationGroup()` mengambil label group dari `MHakAkses` sesuai locale aktif.
   - Dashboard return `null` atau tidak masuk group.
4. Group ordering:
   - `AdminPanelProvider` memakai `navigationGroups()` dari `NavigationHelper::buildGroups()`.
   - Urutan group memakai `SortOrder` record group.
5. Akses menu tetap memakai permission existing:
   - `FilamentAccess::can(...)` untuk page/resource biasa.
   - `/admin/master/hak-akses` hanya `ADMIN` dan `SUPERVISOR_CS`, sesuai rule yang sudah diminta.

## Rencana UI `/admin/master/hak-akses`

Datatable disamakan pola dengan `PenggunaResource`:

- Paging: 10, 25, 50, 100.
- Default page size: 10.
- Searchable columns:
  - `KodeHakAkses`
  - `NamaHakAksesId`
  - `NamaHakAksesEn`
  - `ModulId`
  - `ModulEn`
  - `KeteranganId`
  - `KeteranganEn`
- Sortable columns:
  - `SortOrder`
  - `ModulId`
  - `NamaHakAksesId`
  - `KodeHakAkses`
  - `TglEdit`
- Filters:
  - Status `NonAktif`.
  - Tipe record: `Group`, `Menu`, `Dashboard`.
  - Group sidebar.
- Grouping table:
  - Tampilkan group sidebar sebagai grouping visual.
  - Dashboard tampil sebagai item tanpa group.
  - Menu yang `IdHakAkses` terisi tampil di group terkait.

Kolom list yang disarankan:

- `SortOrder`
- `IconString`
- `Tipe`
- `Group`
- `KodeHakAkses`
- `NamaHakAksesId`
- `NamaHakAksesEn`
- `ModulId`
- `ModulEn`
- `Status`
- `TglEdit`

## Rencana Dialog Edit

Edit tetap dialog, tidak create/delete.

Field editable:

- `IdHakAkses`
  - Select group sidebar.
  - Kosong berarti group/sidebar root.
  - Dashboard dikunci agar tetap tanpa group.
- `SortOrder`
  - Numeric input.
  - Required untuk group dan menu.
- `IconString`
  - Text input.
  - Contoh: `heroicon-o-shield-check`.
  - Maksimum 100 karakter.
- `NamaHakAksesId`
- `NamaHakAksesEn`
- `ModulId`
- `ModulEn`
- `KeteranganId`
- `KeteranganEn`
- `NonAktif`

Indikator panjang karakter:

- `NamaHakAksesId`: `0/150 karakter`
- `NamaHakAksesEn`: `0/150 karakter`
- `ModulId`: `0/100 karakter`
- `ModulEn`: `0/100 karakter`
- `KeteranganId`: `0/255 karakter`
- `KeteranganEn`: `0/255 karakter`
- `IconString`: `0/100 karakter`

Validasi backend:

- Tidak boleh set parent ke dirinya sendiri.
- Tidak boleh membuat parent chain lebih dari 1 level jika struktur hanya group -> menu.
- Dashboard tidak boleh diberi `IdHakAkses`.
- Group row tidak boleh punya duplicate label/sort yang membuat sidebar membingungkan.
- Row `NonAktif` tidak tampil sebagai sidebar item, tetapi permission code tetap ada untuk audit historis.

## Rencana Multilanguage

- Semua label list, filter, dialog, helper text, dan status tetap lewat `resources/lang/id/ui.php` dan `resources/lang/en/ui.php`.
- Data menu/hak akses di `MHakAkses` memakai kolom ID/EN.
- Sidebar membaca label dari DB sesuai `LocaleManager::current()`.
- Seeder mengisi ID/EN dari resource lang default agar fresh install lengkap.

## Rencana Verifikasi

1. `php -l` untuk file PHP yang diubah.
2. `php artisan migrate --no-interaction`.
3. `php artisan db:seed --class=DatabaseSeeder`.
4. `php artisan route:list --path=admin/master/hak-akses`.
5. Login sebagai:
   - `ADMIN`: menu terlihat dan bisa edit.
   - `SUPERVISOR_CS`: menu terlihat dan bisa edit.
   - `CS`: menu tidak terlihat.
6. Cek sidebar:
   - Dashboard tampil tanpa group.
   - Group sidebar mengikuti `SortOrder`.
   - Item menu dalam group mengikuti `SortOrder`.
   - Label berubah sesuai pilihan bahasa ID/EN.
7. Cek `/admin/master/hak-akses`:
   - Paging berfungsi.
   - Search berfungsi.
   - Filter status/type/group berfungsi.
   - Sort order kolom berfungsi.
   - Grouping datatable sesuai struktur `IdHakAkses`.
   - Dialog edit menampilkan counter karakter dan menyimpan perubahan.

## Urutan Implementasi

1. Rapikan migration final `MHakAkses`.
2. Rapikan `DATABASE_SCHEMA_WACS.sql`.
3. Rapikan model `HakAkses`.
4. Rapikan `AccessPermissions` dan seeder struktur sidebar.
5. Finalisasi `NavigationHelper`.
6. Pasang helper ke semua Page/Resource sidebar.
7. Rapikan UI `HakAksesResource`.
8. Tambahkan/rapikan translation ID/EN.
9. Jalankan verifikasi.

## Catatan Sebelum Implementasi

- Perlu hati-hati karena ada migration duplicate untuk bilingual columns. Saat implementasi, tentukan apakah migration lama sudah pernah jalan di database target. Jika sudah pernah jalan, kita buat migration baru yang idempotent untuk memperbaiki struktur tanpa menghapus data.
- Perubahan sidebar akan menyentuh banyak Page/Resource, jadi implementasi harus kecil dan terukur agar tidak memutus akses menu existing.
- Struktur `MHakAkses` akan menjadi master menu sekaligus permission; role permission tetap harus membaca hanya row yang punya `KodeHakAkses`.
