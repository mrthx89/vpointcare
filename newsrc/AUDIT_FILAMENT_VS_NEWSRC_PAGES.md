# Audit Filament `src` vs ASP.NET `newsrc`

Tanggal audit: 2026-05-05

Tujuan dokumen ini adalah membandingkan modul Filament Laravel lama di `src/app/Filament` dan view-nya di `src/resources/views/filament/pages` dengan page ASP.NET 8 + MudBlazor di `newsrc/src/VPointCare.Web/Components/Pages/Admin`.

## Ringkasan

Secara nama menu, sebagian besar page sudah dibuat di `newsrc`. Tetapi banyak page masih berupa list ringkas atau dashboard statistik, belum setara dengan fitur Filament lama. Gap terbesar ada di:

1. `InboxWhatsapp`
2. `AiAgent`
3. resource master data CRUD
4. `Users` / `Pengguna`
5. `Dashboard`
6. `LogData`

`newsrc` saat ini lebih mirip scaffold awal untuk navigasi dan pembacaan data, belum migrasi penuh behavior Filament.

Update 2026-05-05:
- Semua tabel HTML statis di page admin `newsrc` sudah diganti ke `MudTable`.
- Setiap tabel sekarang minimal punya paging, filter cepat, dan sort per kolom utama.
- Halaman master utama tidak lagi dibatasi `Take(100)` setelah ada pager.
- Untuk log/riwayat besar, daftar masih memakai batas data terbaru sampai dibuat server-side paging.

## Standar Datatable dan Form Entri

Semua tabel data di `newsrc` harus mengikuti standar ini:

1. Pakai `MudTable` atau `MudDataGrid`, bukan `<table>` statis.
2. Wajib ada filter cepat/search di toolbar.
3. Kolom utama wajib sortable.
4. Wajib ada pager dengan opsi 10, 25, 50, 100.
5. Wajib pakai `DataLabel` pada `MudTd` agar tampilan mobile tetap terbaca.
6. List master tidak boleh dipotong dengan `Take(100)` jika pager masih client-side.
7. Untuk tabel besar seperti log, chat, dan riwayat AI, tahap berikutnya perlu server-side paging agar tidak memuat semua data ke memory.

Standar form entri:

1. Entri create/edit sebaiknya memakai dialog atau halaman form dengan grouping field yang sama seperti resource Filament lama.
2. Field wajib mengikuti label, required, max length, helper text, toggle, dan pilihan select dari Filament lama.
3. Field lookup seperti instansi, customer, role, grup, dan nomor WA harus searchable.
4. Tombol utama harus jelas: Simpan, Batal, Nonaktif/Aktif, dan aksi khusus seperti Syncron Data.
5. Form tidak boleh dibuat sekadar field mentah satu kolom panjang; layout harus dikelompokkan agar enak dipakai admin.

## Matrix Page

| Filament lama | ASP.NET baru | Status | Gap utama |
|---|---|---:|---|
| `Pages/Dashboard.php` + `dashboard.blade.php` | `Admin/Dashboard.razor` | Parsial | Filter periode, custom range modal, grafik pesan, score KPI, performa tim/AI, response rate, delivery rate belum lengkap. |
| `Pages/InboxWhatsapp.php` + `inbox-whatsapp.blade.php` | `Admin/InboxWhatsapp.razor` | Sangat kurang | Lama berisi inbox 3 kolom lengkap, load chat dari DB, pilih chat, histori, balas WAHA, attachment, draft lokal, catatan internal, close chat, refresh mapping, refresh profil, AI per sesi. Baru hanya SignalR notification dan CS aktif. |
| `Pages/ViewChatSession.php` + `view-chat-session.blade.php` | `Admin/ViewChatSession.razor` | Parsial | Detail pesan dan catatan sudah ada, tapi tampilan bubble/media belum selengkap blade lama; perlu cek mapping field setelah refactor `TChat/TChatD`. |
| `Pages/AiAgent.php` + `ai-agent.blade.php` | `Admin/AiAgent.razor` | Sangat kurang | Lama punya form edit semua `MPengaturanAi`: jam kerja, hari kerja, prompt, template, provider preset, model/base URL/API key, mode kirim WAHA, batas riwayat, notifikasi chat belum terbalas. Baru hanya baca ringkasan dan permintaan AI terakhir. |
| `Resources/Master/Pengetahuans` | `Admin/KnowledgeBase.razor` | Kurang | Lama full CRUD `MPengetahuan` dengan kode, judul, tag, isi, nonaktif, search/sort/edit/create. Baru hanya list kode/judul/tag/status. |
| `Resources/Master/HariLiburs` | `Admin/MasterHariLibur.razor` | Kurang | Lama full CRUD `MHariLibur` dengan tanggal, nama, keterangan, berlaku tahunan, nonaktif. Baru hanya list. |
| `Pages/MasterCustomer.php` + `master-customer.blade.php` | `Admin/MasterCustomer.razor` | Parsial | Statistik dan link sudah ada, tapi layout/informasi belum sama persis dengan Filament lama. |
| `Resources/Master/Instansis` | `Admin/MasterInstansi.razor` | Kurang | Lama full CRUD `MInstansi` dan action `Syncron Data` untuk dispatch import VToken. Baru hanya list 100 data, tanpa create/edit/filter/search/sync. |
| `Resources/Master/Customers` | `Admin/MasterCustomers.razor` | Kurang | Lama full CRUD `MCustomer`, filter instansi, count nomor WA, toggle nonaktif. Baru hanya list. |
| `Resources/Master/NomorWhatsapps` | `Admin/MasterNomorWhatsapp.razor` | Kurang | Lama full CRUD `MNomorWhatsapp`, field `IdWaha`, jabatan kontak, nomor utama, terverifikasi, nonaktif, filter customer. Baru list terbatas. |
| `Resources/Master/GrupWhatsapps` | `Admin/MasterGrupWhatsapp.razor` | Kurang | Lama full CRUD `MGrupWhatsapp`, filter instansi, deskripsi, jumlah anggota, nonaktif. Baru list ringkas. |
| `Resources/Master/AnggotaGrupWhatsapps` | `Admin/MasterAnggotaGrupWhatsapp.razor` | Kurang | Lama full CRUD anggota grup. Baru hanya list grup/nomor/customer/peran/status. |
| `Pages/LogData.php` + `log-data.blade.php` | `Admin/LogData.razor` | Parsial | Data webhook/integrasi sudah ada sebagian, tapi detail request/response/error JSON dan layout tabel lama belum sama lengkap. |
| `Pages/Ticketing.php` + `ticketing.blade.php` | `Admin/Ticketing.razor` | Parsial | Filament lama lebih banyak berupa UI statis/mock, ASP.NET baru sudah query `TTicket`; tapi belum ada create/edit/detail/assignment/status workflow. |
| `Resources/System/Users` | `Admin/Users.razor` | Kurang | Lama full CRUD `MUser/users`, upload foto, approve/block/pending, sync ke `MPengguna`, filter status. Baru hanya statistik dan list. |
| `Resources/Master/Penggunas` | `Admin/MasterPengguna.razor` | Kurang | Lama full CRUD `MPengguna`, role, WA internal, jabatan, nonaktif. Baru hanya list. |
| `Auth/Login.php` | `Auth/Login.razor` | Perlu cek lanjut | Sudah ada login ASP.NET, perlu bandingkan pesan, redirect, status user, dan sync role. |
| `Auth/Register.php` | `Auth/Register.razor` | Perlu cek lanjut | Perlu bandingkan field register dan proses approval seperti Laravel. |

## Detail Gap Per Modul

### Dashboard

Filament lama menyediakan:
- filter periode cepat: hari ini, 7 hari, 30 hari, bulan ini
- modal periode custom
- ringkasan incoming, reply, closed chat, rata-rata waktu balas, ticket dibuat
- score response rate, delivery rate, speed score, mapping rate
- grafik harian pesan masuk, balasan CS, balasan AI
- tabel performa tim dan AI

`newsrc` saat ini baru menampilkan snapshot ringkas operasional WhatsApp, AI, dan agent aktif. Perlu dibuat ulang query dan UI dashboard agar setara.

### Inbox WhatsApp

Filament lama adalah modul paling kompleks. Fitur lama meliputi:
- daftar chat dari `TChatM` dengan filter teks/status/history
- preview unread, grup, unknown mapping, avatar WAHA, AI badge
- pilih chat dan load detail dari `TChatD`
- bubble chat dengan media image/video/file
- kirim balasan ke WAHA
- simpan draft lokal
- upload/paste attachment
- close chat dengan pesan penutup AI
- refresh mapping chat
- refresh profil WAHA
- catatan internal `TChatCatatanInternal`
- kontrol AI per chat: toggle auto reply, reset sapaan AI
- history chat dan link detail session
- normalisasi WAHA id, LID, grup, dan nomor telepon

`newsrc` saat ini belum memuat chat dari database dan belum punya workflow balas/attachment/close/internal note. Ini perlu jadi prioritas pertama jika ingin aplikasi dipakai operasional.

### AI Agent

Filament lama punya form lengkap untuk `MPengaturanAi`:
- `AutoReplyAktif`
- `AutoReplyDiluarJamKerja`
- `AutoReplyHariLibur`
- `AutoReplyJamKerjaSapaan`
- `AutoReplyJamKerjaBerlanjut`
- `JamKerjaMulai`, `JamKerjaSelesai`, `HariKerja`, `ZonaWaktu`
- `PromptSistem`
- template luar jam kerja, hari libur, sapaan, fallback
- provider preset OpenAI, model, base URL, API key
- `KirimKeWaha`, `ModeKirim`, `BatasRiwayatPesan`
- notifikasi chat belum terbalas: aktif, menit tunggu, jeda, role penerima, template

`newsrc` baru menampilkan setting read-only dan statistik. Perlu dibuat page edit setting.

### Master Data

Resource Filament master umumnya punya pola:
- `CreateAction`
- `EditAction`
- table search/sort/filter
- toggle `NonAktif`
- timestamp hidden/toggleable
- form validasi field sesuai resource

Page ASP.NET baru untuk master data saat ini mayoritas hanya read-only list 100 data. Perlu dibuat komponen CRUD reusable agar tidak mengulang banyak kode.

### User dan Pengguna

Filament lama untuk `UserResource` punya fitur penting:
- create/edit user
- role `IdPeran`
- nomor WA internal, jabatan, foto profil
- status `pending/approved/blocked`
- action approve/block/pending
- sinkronisasi `users` ke `MPengguna`
- filter status

`newsrc` baru read-only. Ini penting karena role dan menu permission sekarang sudah berbasis database, tetapi UI manajemen user/role belum lengkap.

### Log Data

Filament lama menampilkan:
- log integrasi dengan request/response/error JSON
- log webhook WAHA dengan payload dan status
- format tabel detail untuk troubleshooting

`newsrc` sudah membaca log, tetapi detail JSON/request/error perlu disamakan agar debugging WAHA dan integrasi tetap mudah.

## Prioritas Implementasi Lanjut

1. Lengkapi `InboxWhatsapp` sampai setara fitur operasional lama.
2. Lengkapi `AiAgent` sebagai form edit `MPengaturanAi`.
3. Buat base CRUD pattern MudBlazor untuk master data.
4. Terapkan base CRUD ke `Instansi`, `Customer`, `NomorWhatsapp`, `GrupWhatsapp`, `AnggotaGrup`, `HariLibur`, `KnowledgeBase`, `Pengguna`, dan `Users`.
5. Lengkapi `Dashboard` dengan filter periode dan query KPI lama.
6. Lengkapi `LogData` detail request/response/error.
7. Setelah fitur setara, baru poles tampilan agar mendekati Blade/Filament lama.

## Catatan

Menu sudah ada, tetapi menu bukan bukti fitur sudah selesai. Banyak page ASP.NET saat ini dibuat sebagai halaman pembuka/summary, bukan migrasi penuh dari Filament Resource.
