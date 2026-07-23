# Spec Delta: Sumber Tunggal Master Ticketing

## ADDED Requirements

### Requirement: Sumber Tunggal Master Ticketing

Sistem SHALL menetapkan `Database\Seeders\DatabaseSeeder` sebagai satu-satunya sumber seed untuk `MStatusTicket`, `MKategoriTicket`, dan `MPrioritasTicket`, dan SHALL NOT menduplikasi seed ketiganya pada `src/script/DATABASE_SCHEMA_WACS.sql`.

#### Scenario: Instalasi baru

- **GIVEN** database `DBVPointCare` masih kosong
- **WHEN** `php artisan migrate --seed` dijalankan pada koneksi `sqlsrv`
- **THEN** `MStatusTicket` berisi tepat 10 kode kanonik: `DRAFT`, `BARU`, `DIANALISA_CS`, `BUTUH_DATA_CUSTOMER`, `DITERUSKAN_DEVELOPER`, `DALAM_PENGERJAAN`, `MENUNGGU_DEPLOY`, `SELESAI`, `DITUTUP`, `DIBATALKAN`
- **AND** `MKategoriTicket` berisi tepat 5 kode: `BUG`, `DATA`, `AKSES`, `REQUEST`, `KONSULTASI`
- **AND** `MPrioritasTicket` berisi tepat 4 kode: `RENDAH`, `NORMAL`, `TINGGI`, `KRITIS`
- **AND** tidak ada kode status yang bermakna sama dengan kode lain

#### Scenario: Seeder dijalankan berulang

- **GIVEN** master ticketing sudah terisi kanonik
- **WHEN** `php artisan db:seed --force` dijalankan kembali
- **THEN** jumlah baris pada ketiga tabel tidak bertambah
- **AND** nilai `Urutan`, `StatusFinal`, `Warna`, dan `BatasSlaMenit` tetap sama

#### Scenario: File schema tidak lagi menyeed master ticketing

- **WHEN** `src/script/DATABASE_SCHEMA_WACS.sql` ditelusuri
- **THEN** tidak ditemukan `INSERT INTO MStatusTicket`, `INSERT INTO MKategoriTicket`, maupun `INSERT INTO MPrioritasTicket`
- **AND** `INSERT INTO MPeran`, `INSERT INTO MStatusChat`, dan `INSERT INTO MPengaturanAi` tetap ada dan tidak berubah

### Requirement: Nilai SLA Prioritas Deterministik

Sistem SHALL menetapkan `MPrioritasTicket.BatasSlaMenit` dengan nilai tetap yang tidak bergantung pada urutan eksekusi migration dan seeder.

#### Scenario: Nilai SLA setelah migrate dan seed

- **WHEN** migration dan seeder selesai dijalankan dalam urutan apa pun
- **THEN** `RENDAH` memiliki `BatasSlaMenit = 2880`
- **AND** `NORMAL` memiliki `BatasSlaMenit = 1440`
- **AND** `TINGGI` memiliki `BatasSlaMenit = 480`
- **AND** `KRITIS` memiliki `BatasSlaMenit = 120`

### Requirement: Penonaktifan Aman Master Non-Kanonik

Sistem SHALL menonaktifkan kode master ticketing non-kanonik pada database existing tanpa menghapus baris dan tanpa memutus referensi ticket/task yang sudah ada.

#### Scenario: Kode non-kanonik tidak dipakai ticket mana pun

- **GIVEN** `MStatusTicket` berisi kode `ANALISA` dan tidak ada baris `TTicket` yang `IdStatusTicket`-nya menunjuk kode tersebut
- **WHEN** migration penonaktifan dijalankan
- **THEN** baris `ANALISA` di-set `NonAktif = 1`
- **AND** baris tersebut tidak dihapus dari database
- **AND** kode tersebut tidak lagi muncul pada dropdown dan filter status di resource Ticket

#### Scenario: Kode non-kanonik masih dipakai ticket

- **GIVEN** `MStatusTicket` berisi kode `DIKERJAKAN` dan terdapat minimal satu `TTicket` yang menunjuk kode tersebut
- **WHEN** migration penonaktifan dijalankan
- **THEN** baris `DIKERJAKAN` tetap `NonAktif = 0`
- **AND** migration menuliskan peringatan yang menyebut kode tersebut beserta jumlah ticket yang masih memakainya
- **AND** tidak ada ticket yang dipindahkan statusnya secara otomatis

#### Scenario: Kategori non-kanonik masih dipakai ticket atau task

- **GIVEN** `MKategoriTicket` berisi kode `TEKNIS` dan terdapat minimal satu `TTicket` atau `TTask` yang menunjuk kode tersebut
- **WHEN** migration penonaktifan dijalankan
- **THEN** baris `TEKNIS` tetap aktif
- **AND** migration melaporkannya sebagai perlu pemetaan manual

#### Scenario: Migration dijalankan ulang

- **GIVEN** migration penonaktifan sudah pernah dijalankan
- **WHEN** migration dijalankan kembali
- **THEN** tidak terjadi error
- **AND** tidak ada baris tambahan yang berubah statusnya

#### Scenario: Rollback migration

- **GIVEN** migration menonaktifkan kode `ANALISA` dan `UMUM`, sementara kode `LAYANAN` memang sudah nonaktif sebelumnya
- **WHEN** `php artisan migrate:rollback --step=1` dijalankan
- **THEN** `ANALISA` dan `UMUM` dikembalikan menjadi `NonAktif = 0`
- **AND** `LAYANAN` tetap `NonAktif = 1`

### Requirement: Kompatibilitas Konsumen Master Ticketing

Sistem SHALL mempertahankan kode dan flag yang dipakai logika aplikasi setelah penyelarasan master.

#### Scenario: Dashboard Ticketing tetap valid

- **GIVEN** master ticketing sudah kanonik
- **WHEN** halaman `/admin/ticketing` dibuka
- **THEN** statistik "ticket baru" dihitung dari kode `BARU` yang tetap ada
- **AND** statistik aktif, overdue, dan selesai dihitung dari flag `StatusFinal` yang tetap konsisten

#### Scenario: Penutupan ticket tetap berjalan

- **GIVEN** sebuah ticket berstatus non-final
- **WHEN** statusnya diubah ke `SELESAI`
- **THEN** `TglSelesai`, `TglDitutup`, dan `DitutupOleh` terisi
- **AND** `TTicketD` mencatat `JenisAktivitas = 'PerubahanStatus'` dengan status sebelum dan sesudah
