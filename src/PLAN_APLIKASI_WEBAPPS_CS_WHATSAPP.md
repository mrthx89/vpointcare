# Rencana Pembuatan Webapps VPoint Care

## 1. Tujuan Aplikasi

**VPoint Care** adalah admin panel customer service untuk perusahaan software yang melayani customer melalui WhatsApp. Nomor WhatsApp perusahaan dihubungkan ke WAHA, lalu semua pesan masuk dikelola di satu aplikasi internal agar jelas:

- customer berasal dari perusahaan / instansi / grup mana;
- chat sedang ditangani oleh siapa;
- status percakapan dan status customer terlihat;
- dari chat bisa dibuat ticketing;
- ticketing bisa dieskalasikan sampai ke developer;
- AI Agent bisa membantu klasifikasi, ringkasan, saran balasan, dan pencarian knowledge base;
- data master customer dapat disinkronkan dari API custom milik perusahaan.

## 2. Keputusan Versi Laravel dan PHP

Aplikasi ini dibangun dari nol, jadi arah teknis yang paling sehat adalah memakai stack modern sejak awal.

Keputusan yang disarankan:

- Laravel terbaru: **Laravel 13.x**.
- PHP minimum: **PHP 8.3**.
- Database: **SQL Server**.
- Admin panel: **Filament Panel Builder** di atas Laravel + Livewire.
- Realtime chat: Livewire polling untuk MVP, lalu dapat dinaikkan ke Laravel Reverb / WebSocket.

Catatan penting: PHP 8.0 tidak kompatibel dengan Laravel terbaru. Per 26 April 2026, dokumentasi resmi Laravel menampilkan Laravel 13.x sebagai versi terbaru dan Laravel 13 membutuhkan PHP minimal 8.3. Jika server benar-benar hanya bisa PHP 8.0, maka Laravel terbaru tidak bisa dipakai; versi yang kompatibel dengan PHP 8.0 adalah Laravel 9, tetapi status support resminya sudah berakhir. Karena aplikasi ini baru dan akan dikembangkan jangka panjang, gunakan **PHP 8.3+ dan Laravel 13.x**.

## 3. Stack Teknologi

### Backend

- Laravel 13.x.
- PHP 8.3 atau 8.4.
- SQL Server sebagai database utama.
- Filament Panel Builder untuk admin panel modern.
- Laravel Queue untuk proses background:
  - sinkronisasi pesan WAHA;
  - pengiriman pesan;
  - pemanggilan AI Agent;
  - sinkronisasi master customer;
  - notifikasi internal.
- Laravel Scheduler untuk pekerjaan periodik.
- Laravel Sanctum / session auth untuk admin panel.

### Frontend Admin Panel

Rekomendasi tampilan terbaik untuk admin panel:

- Filament sebagai panel utama.
- Livewire untuk fitur chat, form, table, dashboard widget, dan modal.
- Tailwind CSS mengikuti standar Filament.
- Halaman inbox chat dibuat custom page di Filament:
  - daftar chat di kiri;
  - percakapan di tengah;
  - profil customer, ticket, dan AI suggestion di kanan.
- Untuk MVP, gunakan Livewire polling agar lebih cepat stabil.
- Untuk tahap lanjut, gunakan Laravel Reverb / WebSocket agar chat benar-benar realtime.

### Database

- SQL Server.
- Primary key memakai `uniqueidentifier`.
- Default primary key memakai `NEWSEQUENTIALID()`.
- Semua tabel dibuat dari nol, tidak memakai tabel existing.
- File DDL SQL Server disediakan di `DATABASE_SCHEMA_WACS.sql`.
- Semua tabel memiliki field mandatory:
  - `TglBuat`
  - `DibuatOleh`
  - `TglEdit`
  - `DieditOleh`
- Master tidak dihapus fisik, tetapi memakai `NonAktif`.
- Transaksi tidak dihapus fisik; gunakan status, void, batal, atau audit trail.

### Integrasi WhatsApp

- WAHA free sebagai WhatsApp gateway.
- Laravel menerima webhook dari WAHA untuk pesan masuk.
- Laravel mengirim pesan keluar melalui API WAHA.
- Semua payload WAHA disimpan untuk audit dan debugging.

### AI Agent

AI Agent dapat memakai:

- OpenAI API / ChatGPT API;
- API Codex jika nanti tersedia sesuai kebutuhan workflow coding / agentic development;
- provider lain jika ingin dibuat fleksibel.

AI tidak boleh langsung mengirim balasan otomatis ke customer pada tahap awal. AI sebaiknya menjadi asisten CS:

- membuat ringkasan chat;
- mendeteksi kategori masalah;
- mendeteksi urgensi;
- menyarankan balasan;
- mencari artikel solusi;
- menyarankan apakah perlu dibuat ticket;
- membuat draft ticket untuk developer.

### API Custom Master Customer

Aplikasi harus punya service khusus untuk komunikasi dengan API perusahaan:

- ambil data customer;
- ambil data instansi / perusahaan;
- ambil data kontak customer;
- ambil status kontrak / produk / modul yang dipakai;
- mapping nomor WhatsApp ke customer;
- refresh data customer saat chat masuk.

## 4. Modul Utama

### 4.1 Auth dan User Management

Fitur:

- login admin panel;
- role dan permission;
- user CS;
- user supervisor;
- user developer;
- user admin;
- status aktif / nonaktif user;
- log aktivitas.

Role awal:

- `Admin`: kelola semua konfigurasi.
- `Supervisor CS`: monitoring semua chat dan ticket.
- `CS`: membalas chat dan membuat ticket.
- `Developer`: menerima dan memproses ticket teknis.
- `Viewer / Management`: melihat dashboard dan laporan.

### 4.2 Master Customer

Fitur:

- melihat data customer dari database lokal;
- sinkron dari API custom perusahaan;
- mapping nomor WhatsApp ke customer;
- mapping customer ke instansi / grup / produk;
- status customer aktif / nonaktif;
- histori sinkronisasi.

Prinsip:

- data utama aplikasi disimpan di database lokal;
- API custom perusahaan dipakai untuk sinkronisasi / refresh data;
- aplikasi menyimpan data hasil sinkronisasi agar chat tetap cepat dibuka;
- perubahan data lokal perlu dibatasi agar tidak bentrok dengan sumber data perusahaan;
- jika API tidak tersedia, aplikasi tetap bisa menampilkan data terakhir.

### 4.3 WhatsApp Inbox

Fitur:

- daftar chat masuk;
- filter unread, assigned, unassigned, closed;
- filter customer, instansi, produk, prioritas;
- detail percakapan;
- kirim pesan teks;
- kirim lampiran jika WAHA free mendukung sesuai endpoint yang tersedia;
- assignment chat ke CS;
- transfer chat antar CS;
- catatan internal yang tidak dikirim ke WhatsApp;
- status percakapan.

Status percakapan awal:

- `Baru`
- `MenungguCustomer`
- `MenungguCS`
- `DalamProses`
- `Selesai`
- `Ditutup`

### 4.4 Ticketing

Fitur:

- buat ticket dari chat;
- link ticket ke customer, instansi, nomor WhatsApp, dan pesan sumber;
- kategori masalah;
- prioritas;
- status;
- assignment ke developer / tim;
- komentar internal;
- lampiran;
- timeline ticket;
- SLA;
- reopen ticket;
- close ticket.

Status ticket awal:

- `Draft`
- `Baru`
- `DianalisaCS`
- `ButuhDataCustomer`
- `DiteruskanKeDeveloper`
- `DalamPengerjaan`
- `MenungguDeploy`
- `Selesai`
- `Ditutup`
- `Dibatalkan`

### 4.5 Developer Workflow

Fitur:

- developer melihat ticket yang ditugaskan;
- developer memberi update teknis;
- developer menandai butuh informasi tambahan;
- developer menandai selesai teknis;
- CS menerima hasil dan mengabari customer;
- supervisor dapat memantau bottleneck.

Opsional pengembangan berikutnya:

- integrasi Git issue;
- integrasi project management;
- integrasi deployment note;
- link commit / branch / release.

### 4.6 AI Agent

Fitur awal yang aman:

- ringkasan percakapan;
- rekomendasi kategori;
- rekomendasi prioritas;
- draft balasan;
- draft ticket;
- deteksi sentimen customer;
- deteksi apakah chat sudah perlu eskalasi.

Fitur lanjutan:

- knowledge base internal;
- pencarian solusi berdasarkan histori ticket;
- RAG menggunakan dokumen manual / SOP / FAQ;
- auto-tagging;
- auto-routing ke tim developer tertentu.

Kontrol keamanan:

- semua output AI harus berupa draft;
- CS tetap yang mengirim balasan;
- simpan log prompt dan response;
- sensor data sensitif jika diperlukan;
- batasi konteks yang dikirim ke AI.

### 4.7 Dashboard dan Laporan

Dashboard:

- jumlah chat masuk hari ini;
- chat belum dibalas;
- chat per CS;
- waktu respons rata-rata;
- ticket baru;
- ticket overdue;
- ticket per status;
- customer paling sering komplain;
- kategori masalah terbanyak.

Laporan:

- performa CS;
- performa developer;
- SLA ticket;
- histori customer;
- jumlah chat per customer / instansi;
- ticket per produk / modul.

## 5. Prinsip Pembuatan Database dari Nol

Database dibuat baru dari awal. Tabel lama dari sistem lain tidak dipakai langsung dan tidak perlu dimigrasikan pada tahap awal.

Prinsipnya:

1. **Aplikasi ini punya database sendiri**
   - master customer lokal dibuat sendiri;
   - master instansi lokal dibuat sendiri;
   - nomor WhatsApp customer dibuat sendiri;
   - chat, ticket, AI, dan log seluruhnya milik aplikasi ini.

2. **API custom perusahaan tetap dipakai sebagai sumber sinkronisasi**
   - API custom tidak menjadi tabel langsung;
   - data dari API masuk melalui service sinkronisasi;
   - hasil request dan response disimpan di log;
   - jika API gagal, aplikasi tetap memakai data terakhir yang tersimpan lokal.

3. **Semua aktivitas penting harus ada log**
   - webhook WAHA;
   - pesan masuk dan keluar;
   - pengiriman pesan gagal;
   - request API custom customer;
   - request dan response AI;
   - perubahan status chat;
   - perubahan status ticket;
   - login dan aktivitas user;
   - error aplikasi.

4. **Struktur tabel dibuat untuk fleksibilitas**
   - satu customer bisa punya banyak nomor WhatsApp;
   - satu instansi bisa punya banyak customer;
   - satu chat bisa menghasilkan banyak ticket;
   - satu ticket bisa diteruskan ke beberapa aktivitas developer;
   - AI hanya menyimpan draft, ringkasan, dan rekomendasi, bukan keputusan final.

DDL SQL Server awal dibuat di file:

```text
DATABASE_SCHEMA_WACS.sql
```

## 6. Konvensi Database

### 6.1 Prefix Tabel

- Master data diawali `M`.
- Transaksi data diawali `T`.
- Jika transaksi punya master-detail:
  - header/master transaksi memakai suffix `M`;
  - detail transaksi memakai suffix `D`.

Contoh:

- `MPengguna`
- `MPeran`
- `MCustomer`
- `MInstansi`
- `MNomorWhatsapp`
- `MKategoriTicket`
- `TChatM`
- `TChatD`
- `TTicketM`
- `TTicketD`
- `TTicketKomentar`
- `TLogIntegrasi`

Catatan: untuk menghindari rancu dengan kata "master", prefix `M` dipakai untuk master data, sedangkan suffix `M` pada transaksi berarti header transaksi.

### 6.2 Primary Key

Semua primary key:

```sql
Id uniqueidentifier NOT NULL DEFAULT NEWSEQUENTIALID()
```

Contoh:

```sql
CREATE TABLE MCustomer (
    Id uniqueidentifier NOT NULL DEFAULT NEWSEQUENTIALID(),
    KodeCustomer varchar(50) NOT NULL,
    NamaCustomer varchar(200) NOT NULL,
    NonAktif bit NOT NULL DEFAULT 0,
    TglBuat datetime2 NOT NULL DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MCustomer PRIMARY KEY (Id)
);
```

### 6.3 Field Mandatory

Semua tabel wajib memiliki:

```text
TglBuat
DibuatOleh
TglEdit
DieditOleh
```

Untuk tabel master tambah:

```text
NonAktif
```

Saran tipe data:

- `TglBuat datetime2 NOT NULL DEFAULT SYSDATETIME()`
- `DibuatOleh uniqueidentifier NULL`
- `TglEdit datetime2 NULL`
- `DieditOleh uniqueidentifier NULL`
- `NonAktif bit NOT NULL DEFAULT 0`

### 6.4 Bahasa Field

Semua nama tabel dan field memakai Bahasa Indonesia.

Contoh:

- `NamaCustomer`
- `NamaInstansi`
- `NomorWhatsapp`
- `IsiPesan`
- `TglPesan`
- `StatusChat`
- `StatusTicket`
- `Prioritas`
- `DitugaskanKepada`
- `DibalasOleh`
- `TglDibalas`

## 7. Draft Struktur Tabel Awal

Struktur ini adalah baseline untuk database baru. DDL lengkapnya tersedia di `DATABASE_SCHEMA_WACS.sql`.

### 7.1 Master User dan Role

```text
MPengguna
- Id
- NamaPengguna
- Email
- Password
- NomorWhatsappInternal
- IdPeran
- NonAktif
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

MPeran
- Id
- KodePeran
- NamaPeran
- NonAktif
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

MHakAkses
- Id
- KodeHakAkses
- NamaHakAkses
- NonAktif
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

MPeranHakAkses
- Id
- IdPeran
- IdHakAkses
- NonAktif
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh
```

### 7.2 Master Customer

```text
MCustomer
- Id
- KodeCustomer
- NamaCustomer
- IdInstansi
- Email
- Telepon
- SumberData
- IdExternal
- TglSinkronTerakhir
- NonAktif
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

MInstansi
- Id
- KodeInstansi
- NamaInstansi
- Alamat
- Kota
- IdExternal
- NonAktif
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

MNomorWhatsapp
- Id
- IdCustomer
- IdInstansi
- NomorWhatsapp
- NamaKontak
- JabatanKontak
- SumberData
- IdExternal
- NonAktif
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

MProdukCustomer
- Id
- IdCustomer
- IdInstansi
- KodeProduk
- NamaProduk
- Keterangan
- NonAktif
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh
```

### 7.3 Konfigurasi WAHA

```text
MSesiWhatsapp
- Id
- KodeSesi
- NamaSesi
- BaseUrlWaha
- ApiKey
- StatusSesi
- NomorTerhubung
- NonAktif
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

TLogWebhookWaha
- Id
- IdSesiWhatsapp
- JenisEvent
- PayloadJson
- TglDiterima
- SudahDiproses
- PesanError
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh
```

### 7.4 Chat WhatsApp

```text
TChatM
- Id
- IdSesiWhatsapp
- IdCustomer
- IdInstansi
- IdNomorWhatsapp
- NomorWhatsapp
- NamaKontak
- StatusChat
- Prioritas
- DitugaskanKepada
- TglChatTerakhir
- TglDibalasTerakhir
- DitutupOleh
- TglDitutup
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

TChatD
- Id
- IdChatM
- IdPesanWaha
- ArahPesan
- JenisPesan
- IsiPesan
- UrlMedia
- PayloadJson
- DikirimOlehCustomer
- DibalasOleh
- TglPesan
- TglDibaca
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

TChatCatatanInternal
- Id
- IdChatM
- IsiCatatan
- DibuatOleh
- TglBuat
- TglEdit
- DieditOleh
```

### 7.5 Ticketing

```text
MKategoriTicket
- Id
- KodeKategori
- NamaKategori
- NonAktif
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

MPrioritasTicket
- Id
- KodePrioritas
- NamaPrioritas
- Urutan
- BatasSlaMenit
- NonAktif
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

TTicketM
- Id
- NomorTicket
- IdChatM
- IdCustomer
- IdInstansi
- IdKategoriTicket
- IdPrioritasTicket
- JudulTicket
- DeskripsiMasalah
- StatusTicket
- DibuatDariPesanId
- DitugaskanKepada
- TglDitugaskan
- TglTargetSelesai
- TglSelesai
- TglDitutup
- DitutupOleh
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

TTicketD
- Id
- IdTicketM
- JenisAktivitas
- IsiAktivitas
- StatusSebelum
- StatusSesudah
- DitujukanKepada
- TglAktivitas
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

TTicketLampiran
- Id
- IdTicketM
- NamaFile
- PathFile
- TipeFile
- UkuranFile
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh
```

### 7.6 AI Agent

```text
TAiPermintaan
- Id
- JenisPermintaan
- ProviderAi
- ModelAi
- IdChatM
- IdTicketM
- PromptRingkas
- PromptJson
- StatusPermintaan
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

TAiRespon
- Id
- IdAiPermintaan
- ResponRingkas
- ResponJson
- TokenInput
- TokenOutput
- BiayaEstimasi
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh
```

### 7.7 Integrasi API Custom

```text
MEndpointIntegrasi
- Id
- KodeEndpoint
- NamaEndpoint
- UrlEndpoint
- MetodeHttp
- NonAktif
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

TLogIntegrasi
- Id
- KodeIntegrasi
- UrlEndpoint
- MetodeHttp
- RequestJson
- ResponseJson
- StatusHttp
- Berhasil
- PesanError
- TglRequest
- TglResponse
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh
```

### 7.8 Log dan Audit

Log wajib ada sejak awal agar masalah WAHA, AI, API custom, dan aktivitas user bisa ditelusuri.

```text
TLogAktivitas
- Id
- IdPengguna
- Modul
- Aksi
- Keterangan
- IpAddress
- UserAgent
- DataSebelumJson
- DataSesudahJson
- TglAktivitas
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh

TLogError
- Id
- LevelError
- PesanError
- FileError
- BarisError
- StackTrace
- ContextJson
- TglError
- TglBuat
- DibuatOleh
- TglEdit
- DieditOleh
```

DDL lengkap tabel, foreign key, index, dan seed data awal tersedia di:

```text
DATABASE_SCHEMA_WACS.sql
```

## 8. Alur Kerja Utama

### 8.1 Chat Masuk

1. Customer mengirim pesan ke WhatsApp perusahaan.
2. WAHA mengirim webhook ke Laravel.
3. Laravel menyimpan payload ke `TLogWebhookWaha`.
4. Sistem mencari nomor WhatsApp di `MNomorWhatsapp`.
5. Jika ditemukan, chat dikaitkan ke customer dan instansi.
6. Jika tidak ditemukan, chat masuk sebagai unknown contact.
7. Sistem membuat atau memperbarui `TChatM`.
8. Sistem menyimpan pesan ke `TChatD`.
9. Chat masuk ke queue CS atau assigned otomatis sesuai rule.
10. AI Agent boleh membuat ringkasan dan rekomendasi kategori.

### 8.2 CS Membalas Chat

1. CS membuka inbox.
2. CS mengambil atau menerima assignment chat.
3. CS melihat profil customer, instansi, produk, dan histori ticket.
4. CS mengetik balasan atau memakai draft AI.
5. Laravel mengirim pesan ke WAHA.
6. Sistem menyimpan pesan keluar di `TChatD`.
7. Field `DibalasOleh` dan `TglDibalas` tercatat.

### 8.3 Buat Ticket dari Chat

1. CS memilih pesan / chat.
2. CS klik buat ticket.
3. Sistem mengisi draft judul, deskripsi, customer, instansi, kategori, prioritas.
4. AI boleh membantu membuat ringkasan masalah.
5. CS review dan simpan ticket.
6. Ticket masuk ke antrian CS / developer.

### 8.4 Eskalasi ke Developer

1. CS mengubah status ticket menjadi `DiteruskanKeDeveloper`.
2. Ticket ditugaskan ke developer / tim.
3. Developer memberi update di `TTicketD`.
4. Jika butuh data, status menjadi `ButuhDataCustomer`.
5. CS meminta data ke customer melalui chat.
6. Setelah selesai, developer menandai `MenungguDeploy` atau `Selesai`.
7. CS mengabari customer dan menutup ticket jika disetujui.

## 9. Step-by-Step Pengerjaan

### Fase 0 - Klarifikasi Teknis

- Tetapkan stack final:
  - Laravel 13.x;
  - PHP 8.3+;
  - SQL Server;
  - Filament Panel Builder;
  - Livewire untuk chat dan dashboard interaktif.
- Pastikan aplikasi dibuat sebagai project baru.
- Kumpulkan dokumentasi API custom master customer.
- Pastikan SQL Server version dan driver PHP `sqlsrv` tersedia.
- Pastikan WAHA free sudah bisa menerima dan mengirim pesan.

### Fase 1 - Fondasi Project

- Install Laravel.
- Konfigurasi koneksi SQL Server.
- Install dan konfigurasi Filament.
- Buat auth admin panel dari Filament.
- Buat theme admin panel.
- Buat role dan permission.
- Buat audit field convention.
- Buat base model / trait untuk audit.
- Buat migration awal dari `DATABASE_SCHEMA_WACS.sql`.

### Fase 2 - Master dan Integrasi Customer

- Buat master customer lokal.
- Buat service API custom customer.
- Buat log integrasi.
- Buat sinkronisasi manual dari admin panel.
- Buat pencarian customer.
- Buat mapping nomor WhatsApp ke customer.

### Fase 3 - Integrasi WAHA

- Buat konfigurasi sesi WAHA.
- Buat endpoint webhook WAHA.
- Simpan semua payload masuk.
- Proses pesan masuk ke tabel chat.
- Buat pengiriman pesan keluar.
- Buat retry jika gagal kirim.
- Buat log error.

### Fase 4 - Inbox Customer Service

- Buat daftar chat.
- Buat detail percakapan.
- Buat assignment chat ke CS.
- Buat balas pesan.
- Buat catatan internal.
- Buat filter customer / instansi / status.
- Buat indikator siapa yang membalas chat.

### Fase 5 - Ticketing

- Buat master kategori dan prioritas ticket.
- Buat pembuatan ticket dari chat.
- Buat status ticket.
- Buat assignment ticket.
- Buat komentar / aktivitas ticket.
- Buat lampiran.
- Buat timeline.

### Fase 6 - Workflow Developer

- Buat halaman ticket untuk developer.
- Buat status pengerjaan developer.
- Buat komentar teknis.
- Buat flow butuh data customer.
- Buat close / reopen ticket.

### Fase 7 - AI Agent

- Buat konfigurasi provider AI.
- Buat service AI.
- Buat fitur ringkasan chat.
- Buat draft balasan.
- Buat rekomendasi kategori dan prioritas.
- Buat draft ticket.
- Simpan log prompt dan response.
- Tambahkan guardrail agar AI hanya memberi draft.

### Fase 8 - Dashboard dan Laporan

- Dashboard chat.
- Dashboard ticket.
- Laporan performa CS.
- Laporan SLA.
- Laporan customer dan instansi.
- Export Excel / PDF jika diperlukan.

### Fase 9 - Hardening

- Validasi permission semua menu.
- Rate limit webhook.
- Signature / token untuk webhook WAHA.
- Backup dan retention payload.
- Index database.
- Job retry dan dead-letter handling.
- Monitoring error.
- Testing end-to-end.

## 10. Menu Admin Panel

Menu awal:

```text
Dashboard
Inbox WhatsApp
Ticketing
Customer
Instansi
Nomor WhatsApp
Developer Queue
Laporan
AI Assistant
Konfigurasi
  - Pengguna
  - Role
  - WAHA Session
  - API Customer
  - Kategori Ticket
  - Prioritas Ticket
Log
  - Webhook WAHA
  - Integrasi API
  - AI Request
  - Aktivitas User
```

## 11. Rekomendasi UI

Karena aplikasi ini admin panel operasional, UI sebaiknya padat, jelas, dan cepat dipakai.

Halaman inbox sebaiknya dibagi menjadi:

- kiri: daftar chat;
- tengah: isi percakapan;
- kanan: profil customer, instansi, histori ticket, dan AI suggestion.

Halaman ticket sebaiknya menampilkan:

- nomor ticket;
- status;
- prioritas;
- customer;
- instansi;
- assigned user;
- timeline;
- komentar;
- link ke chat sumber.

## 12. Index Database yang Disarankan

Index penting:

```text
MNomorWhatsapp.NomorWhatsapp
MCustomer.KodeCustomer
MCustomer.NamaCustomer
MInstansi.KodeInstansi
MInstansi.NamaInstansi
TChatM.NomorWhatsapp
TChatM.IdCustomer
TChatM.IdInstansi
TChatM.StatusChat
TChatM.DitugaskanKepada
TChatM.TglChatTerakhir
TChatD.IdChatM
TChatD.IdPesanWaha
TChatD.TglPesan
TTicketM.NomorTicket
TTicketM.IdCustomer
TTicketM.IdInstansi
TTicketM.StatusTicket
TTicketM.DitugaskanKepada
TTicketM.TglTargetSelesai
```

## 13. Risiko dan Mitigasi

### Risiko: PHP 8.0 tidak cocok untuk Laravel terbaru

Mitigasi:

- upgrade PHP ke 8.3+ untuk project baru;
- jangan mulai project baru dengan Laravel lama jika targetnya aplikasi jangka panjang;
- jadikan PHP 8.3 sebagai requirement server.

### Risiko: Data customer dari WhatsApp tidak cocok dengan master

Mitigasi:

- buat mapping nomor WhatsApp;
- buat unknown contact workflow;
- supervisor bisa menghubungkan nomor ke customer;
- simpan histori perubahan mapping.

### Risiko: WAHA free memiliki batasan

Mitigasi:

- isolasi semua komunikasi WAHA di service class;
- jangan sebar kode WAHA ke controller;
- simpan payload mentah;
- siapkan adapter jika nanti pindah provider.

### Risiko: AI memberi jawaban salah

Mitigasi:

- AI hanya memberi draft;
- CS tetap approve;
- log semua prompt dan response;
- gunakan knowledge base internal;
- batasi data sensitif.

### Risiko: Data API custom tidak sama dengan data lokal

Mitigasi:

- tentukan field mana yang boleh di-update dari API;
- simpan `IdExternal` dan `SumberData`;
- simpan log request dan response API;
- tampilkan waktu sinkronisasi terakhir;
- sediakan fitur refresh per customer.

## 14. Urutan Keputusan yang Harus Dipastikan

Sebelum coding, putuskan ini:

1. Apakah PHP final memakai 8.3 atau 8.4?
2. Apakah server production sudah mendukung PHP 8.3+ dan driver `sqlsrv`?
3. Apakah API custom customer menjadi sumber sinkronisasi utama?
4. Field customer apa saja yang boleh diubah manual dari aplikasi?
5. Apakah WAHA berjalan satu sesi atau multi sesi?
6. Apakah developer juga login ke aplikasi yang sama?
7. Apakah ticketing perlu SLA sejak awal?
8. Apakah AI boleh membaca isi chat lengkap?
9. Apakah lampiran WhatsApp perlu disimpan lokal?
10. Apakah perlu integrasi ke sistem issue/development existing?

## 15. MVP yang Disarankan

MVP jangan terlalu besar. Fitur minimal yang sudah berguna:

1. Login admin panel.
2. Master user dan role sederhana.
3. Mapping customer dan nomor WhatsApp.
4. Webhook WAHA menerima pesan.
5. Inbox chat.
6. Assignment chat ke CS.
7. Balas chat dari aplikasi.
8. Buat ticket dari chat.
9. Assignment ticket ke developer.
10. Dashboard sederhana.

Setelah MVP stabil, lanjutkan AI Agent, SLA detail, laporan lengkap, dan integrasi development workflow.

## 16. Struktur Folder Laravel yang Disarankan

```text
app/
  Services/
    Waha/
      WahaClient.php
      WahaWebhookProcessor.php
    CustomerMaster/
      CustomerMasterClient.php
      CustomerMasterSyncService.php
    Ai/
      AiClient.php
      AiChatSummaryService.php
      AiTicketDraftService.php
  Models/
    Master/
    Transaksi/
  Http/
    Controllers/
      Admin/
      Webhook/
  Jobs/
    ProsesWebhookWahaJob.php
    KirimPesanWhatsappJob.php
    SinkronCustomerJob.php
    ProsesAiChatJob.php
  Policies/
  Livewire/
    Inbox/
    Ticket/
```

## 17. Prinsip Implementasi

- Controller tipis, logic utama di service.
- Semua integrasi eksternal harus punya log.
- Semua proses lambat masuk queue.
- Semua perubahan status ticket dicatat di detail / timeline.
- Semua pesan WhatsApp disimpan, baik masuk maupun keluar.
- Jangan hard delete data penting.
- Gunakan transaction database untuk proses yang mengubah banyak tabel.
- Gunakan permission untuk semua menu penting.
- Gunakan index sejak awal untuk tabel chat dan ticket.

## 18. Referensi Resmi

- Laravel Release Notes: https://laravel.com/docs/releases
- Laravel Database SQL Server Configuration: https://laravel.com/docs/database
- Filament Documentation: https://filamentphp.com/docs
- WAHA Documentation: https://waha.devlike.pro/
- OpenAI API Documentation: https://platform.openai.com/docs
