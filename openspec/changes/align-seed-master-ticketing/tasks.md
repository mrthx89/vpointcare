# Tasks: Satukan Sumber Seed Master Ticketing

## 0. Persiapan dan Data Safety

- [ ] Backup penuh database `DBCareDesk` sebelum menjalankan migration apa pun. **Wajib** karena change ini mengubah data master production.
- [ ] Jalankan audit pemakaian kode non-kanonik pada database target dan simpan hasilnya sebagai lampiran deployment:
  ```sql
  SELECT s.KodeStatusTicket, COUNT(t.Id) AS JumlahTicket
  FROM MStatusTicket s LEFT JOIN TTicket t ON t.IdStatusTicket = s.Id
  WHERE s.KodeStatusTicket IN ('ANALISA','DIKERJAKAN') GROUP BY s.KodeStatusTicket;

  SELECT k.KodeKategori,
         (SELECT COUNT(*) FROM TTicket WHERE IdKategoriTicket = k.Id) AS JumlahTicket,
         (SELECT COUNT(*) FROM TTask   WHERE IdKategoriTicket = k.Id) AS JumlahTask
  FROM MKategoriTicket k WHERE k.KodeKategori IN ('UMUM','TEKNIS','LAYANAN');
  ```
- [x] Keputusan nilai SLA `RENDAH` = 2880 menit (2 hari) sudah disetujui pada 2026-07-23; nilai 4320 dari file schema ditinggalkan dan tidak ada nilai SLA berjalan yang berubah.

## 1. Schema Script

- [ ] Buka `src/script/DATABASE_SCHEMA_WACS.sql`.
- [ ] Hapus blok `INSERT INTO MStatusTicket (...) VALUES ...` beserta `GO` penutupnya (sekitar baris 793-805).
- [ ] Hapus blok `INSERT INTO MPrioritasTicket (...) VALUES ...` beserta `GO` penutupnya (sekitar baris 807-813).
- [ ] Hapus blok `INSERT INTO MKategoriTicket (...) VALUES ...` beserta `GO` penutupnya (sekitar baris 815-822).
- [ ] **Jangan** menghapus `INSERT INTO MPeran`, `INSERT INTO MStatusChat`, dan `INSERT INTO MPengaturanAi`.
- [ ] Tambahkan komentar pada header file yang menyatakan master ticketing (`MStatusTicket`, `MKategoriTicket`, `MPrioritasTicket`) dimiliki `DatabaseSeeder` dan wajib dijalankan dengan `php artisan migrate --seed`.
- [ ] Verifikasi: file tidak lagi mengandung tiga blok INSERT tersebut, dan sisa isinya tidak berubah.

## 2. Seeder Kanonik

- [ ] Buka `src/database/seeders/DatabaseSeeder.php`, method `seedTicketingMasters()`.
- [ ] Ganti daftar `MStatusTicket` menjadi 10 baris kanonik: `DRAFT`/Draft/10/0/gray, `BARU`/Baru/20/0/info, `DIANALISA_CS`/Dianalisa CS/30/0/warning, `BUTUH_DATA_CUSTOMER`/Butuh Data Customer/40/0/warning, `DITERUSKAN_DEVELOPER`/Diteruskan ke Developer/50/0/primary, `DALAM_PENGERJAAN`/Dalam Pengerjaan/60/0/primary, `MENUNGGU_DEPLOY`/Menunggu Deploy/70/0/warning, `SELESAI`/Selesai/80/1/success, `DITUTUP`/Ditutup/90/1/gray, `DIBATALKAN`/Dibatalkan/100/1/danger.
- [ ] Ganti daftar `MKategoriTicket` menjadi: `BUG`/Bug Aplikasi, `DATA`/Masalah Data, `AKSES`/Masalah Akses, `REQUEST`/Permintaan Fitur, `KONSULTASI`/Konsultasi, beserta `Keterangan` seperti pada schema SQL lama.
- [ ] Pastikan `MPrioritasTicket` memakai `RENDAH`=2880, `NORMAL`=1440, `TINGGI`=480, `KRITIS`=120.
- [ ] Jangan mengubah daftar `MStatusTask`.
- [ ] Pertahankan pola `updateOrInsert` berbasis kode dan penjaga `Schema::hasTable()` yang sudah ada.
- [ ] Verifikasi: `php -l database/seeders/DatabaseSeeder.php` lulus.

## 3. Migration Penonaktifan

- [ ] Buat `src/database/migrations/2026_07_23_000001_deactivate_non_canonical_ticketing_masters.php`.
- [ ] Definisikan konstanta daftar non-kanonik: status `['ANALISA','DIKERJAKAN']`, kategori `['UMUM','TEKNIS','LAYANAN']`.
- [ ] Bungkus seluruh operasi dengan `Schema::hasTable()` untuk `MStatusTicket`, `MKategoriTicket`, `TTicket`, dan `TTask`.
- [ ] Untuk setiap kode status non-kanonik: hitung referensi pada `TTicket.IdStatusTicket`. Bila 0 dan baris saat ini `NonAktif = 0`, set `NonAktif = 1` dan `TglEdit = now()`, lalu catat kodenya.
- [ ] Untuk setiap kode kategori non-kanonik: hitung referensi pada `TTicket.IdKategoriTicket` **dan** `TTask.IdKategoriTicket`. Bila total 0 dan baris saat ini `NonAktif = 0`, set `NonAktif = 1` dan catat kodenya.
- [ ] Bila referensi > 0, jangan ubah baris; tuliskan peringatan berisi kode dan jumlah referensi ke output migration.
- [ ] Batasi efek migration hanya pada baris yang sebelumnya `NonAktif = 0`, sehingga kode yang memang sudah nonaktif tidak ikut diaktifkan kembali saat rollback. Dokumentasikan batasan ini pada docblock migration.
- [ ] Implementasikan `down()` yang mengembalikan `NonAktif = 0` untuk kode non-kanonik yang tidak direferensikan, dan tidak menyentuh kode lain.
- [ ] Pastikan migration idempotent: menjalankannya dua kali tidak menghasilkan perubahan tambahan maupun error.
- [ ] Pastikan migration memakai query builder Laravel tanpa sintaks khusus SQL Server.
- [ ] Verifikasi: `php -l database/migrations/2026_07_23_000001_deactivate_non_canonical_ticketing_masters.php` lulus.

## 4. Test

- [ ] Buat `src/tests/Feature/TicketingMasterSeedTest.php`.
- [ ] Test: setelah seeder dijalankan, `MStatusTicket` aktif berisi tepat 10 kode kanonik.
- [ ] Test: setelah seeder dijalankan, `MKategoriTicket` aktif berisi tepat 5 kode kanonik.
- [ ] Test: `MPrioritasTicket` memiliki `RENDAH = 2880`, `NORMAL = 1440`, `TINGGI = 480`, `KRITIS = 120`.
- [ ] Test: menjalankan seeder dua kali tidak menambah jumlah baris (idempotensi).
- [ ] Test: kode non-kanonik tanpa referensi menjadi `NonAktif = 1` setelah migration.
- [ ] Test: kode non-kanonik yang direferensikan `TTicket` tetap `NonAktif = 0` setelah migration.
- [ ] Test: `MStatusTask` tidak berubah oleh change ini.
- [ ] Verifikasi: `php artisan test --filter=TicketingMasterSeed` lulus.

## 5. Validasi Menyeluruh

- [ ] Jalankan `php artisan test` dan catat hasilnya.
- [ ] Jalankan `vendor\bin\pint --test` untuk file yang diubah.
- [ ] `npm run build` **tidak** diperlukan karena tidak ada perubahan asset frontend.

## 6. Verifikasi pada Staging

- [ ] Restore salinan database production ke staging.
- [ ] Jalankan `php artisan migrate --force` lalu `php artisan db:seed --force`.
- [ ] Jalankan seluruh query verifikasi pada bagian Validation di `proposal.md` dan bandingkan dengan hasil yang diharapkan.
- [ ] Buka resource Ticket → pastikan dropdown status berisi 10 status kanonik dan tidak ada duplikasi makna.
- [ ] Buka dashboard `/admin/ticketing` → pastikan angka baru/aktif/overdue/selesai konsisten dengan query SQL.
- [ ] Ubah status satu ticket uji ke `SELESAI` → pastikan `TglSelesai`/`TglDitutup`/`DitutupOleh` terisi dan `TTicketD` mencatat perubahan status.
- [ ] Jalankan `php artisan migrate:rollback --step=1` di staging → pastikan kode non-kanonik kembali aktif dan tidak ada data ticket yang berubah.
- [ ] Jalankan ulang `php artisan migrate --force` untuk mengembalikan kondisi staging.

## 7. Dokumentasi

- [ ] Tambahkan tabel pemetaan status/kategori lama → kanonik pada `docs/` (lampiran change ini) untuk dipakai tim operasional.
- [ ] Perbarui `docs/PRD_CARE_DESK_WACS.md`: tandai TD-11 sebagai teratasi dan sesuaikan daftar master ticketing pada Bagian 7 dan 8.15.
- [ ] Bila hasil audit menunjukkan ada ticket yang masih memakai kode lama, catat daftar tersebut pada catatan deployment beserta rencana pemetaan manualnya.

## 8. Deployment

- [ ] Backup database production (ulangi bila backup pada task 0 sudah kedaluwarsa).
- [ ] `php artisan down`.
- [ ] Deploy kode.
- [ ] `php artisan migrate --force`.
- [ ] `php artisan db:seed --force`.
- [ ] `php artisan optimize:clear` lalu `php artisan optimize`.
- [ ] `php artisan up`.
- [ ] Restart queue/scheduler/Reverb **tidak** diperlukan karena tidak ada perubahan job, event, atau command.
- [ ] Verifikasi pasca-deploy: dashboard Ticketing terbuka, dropdown status benar, dan tidak ada ticket yang statusnya hilang dari tampilan.
