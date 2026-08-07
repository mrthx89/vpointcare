# Spec Delta: Perbaikan Breadcrumbs, Ticket/Task UI, AutoReply AI, dan UI/UX Inbox WhatsApp

## ADDED Requirements

### Requirement: Lengkapi Breadcrumb pada Seluruh Halaman Filament

Sistem SHALL menampilkan breadcrumb pada seluruh halaman Filament agar pengguna dapat mengetahui hierarki navigasi, termasuk grup menu dan label halaman aktif.

#### Scenario: Breadcrumb tampil pada ManageTickets

- GIVEN agen dengan hak akses `TICKET_VIEW` membuka `/admin/tickets`
- WHEN halaman Ticket berhasil dimuat
- THEN sistem SHALL menampilkan breadcrumb dengan label grup menu dan menu "Ticket"

#### Scenario: Breadcrumb tampil pada ManageTasks

- GIVEN admin membuka `/admin/tasks`
- WHEN halaman Task berhasil dimuat
- THEN sistem SHALL menampilkan breadcrumb dengan label grup menu dan menu "Task"

#### Scenario: Breadcrumb tampil pada Master Ticketing Pages

- GIVEN admin membuka halaman `StatusTicket`, `StatusTask`, `KategoriTicket`, atau `PrioritasTicket`
- WHEN halaman master ticketing dimuat
- THEN sistem SHALL menampilkan breadcrumb yang benar berdasarkan menu code relatif masing-masing halaman.

## MODIFIED Requirements

### Requirement: Responsive dan Profesional Ticket / Task Entry Form

Sistem SHALL menampilkan form Ticket dan Task menggunakan layout responsif yang tidak memotong input pada layar desktop, tablet, dan smartphone.

#### Scenario: Form Ticket tampil utuh di layar medium/large

- GIVEN admin membuka modal Create/Edit Ticket
- WHEN form Ticket dimuat di layar tablet atau desktop
- THEN input seperti `JudulTicket`, `DeskripsiMasalah`, Select, Repeater Aktivitas, Repeater Lampiran SHALL ditata menggunakan `Grid` dan `Section` sehingga tidak terpotong/terhimpit.

#### Scenario: Form Task tampil utuh di smartphone

- GIVEN CS agen membuka modal Create/Edit Task di layar smartphone
- WHEN form Task dirender
- THEN sistem SHALL menyusun kolom menjadi satu kolom di layar kecil dan dua kolom di layar medium, memastikan file upload dan checklist tidak terpotong.

### Requirement: Perbaikan Bug AutoReply AI pada Inbox WhatsApp

Sistem SHALL menjalankan balasan AI ketika AI AutoReply diaktifkan secara global atau pada sesi chat individu, tanpa fatal error.

#### Scenario: Chat session dengan AutoReply aktif dibalas meskipun global toggle mati

- GIVEN `MPengaturanAi.AutoReplyAktif = false`
- AND agent mengaktifkan AutoReply pada chat tertentu (`TChat.AutoReplyAiAktif = true`)
- WHEN customer mengirim pesan baru
- THEN sistem SHALL tetap memproses dan menghasilkan balasan auto-reply.

#### Scenario: Pemrosesan AutoReply tidak crash karena variabel undefined

- GIVEN konfigurasi AI provider valid
- WHEN webhook masuk memicu `ProcessAiAutoReplyJob`
- THEN sistem SHALL menginisialisasi `$isFirstReply` sebelum dipakai sehingga tidak terjadi fatal `TypeError`.

#### Scenario: Deduplikasi AutoReply tetap benar pada pesan menit yang sama

- GIVEN dua pesan masuk memiliki `TglPesan` pada detik yang sama
- WHEN sistem mengecek jawaban AI sebelumnya
- THEN sistem SHALL tetap menjalankan auto-reply apabila tidak ada outbound AI setelah pesan terbaru.

### Requirement: Optimasi Performa Inbox WhatsApp

Sistem SHALL memuat list chat Inbox WhatsApp secara instan tanpa N+1 query dan tanpa panggilan HTTP synchronous ke WAHA pada render standar.

#### Scenario: N+1 query payload pesan terakhir dihilangkan

- GIVEN halaman Inbox WhatsApp memuat 50 chat terbaru
- WHEN `loadInbox()` dieksekusi
- THEN sistem SHALL mengambil payload pesan terakhir menggunakan satu query batch terhadap `TChatD`.

#### Scenario: Pencarian foto profil WAHA tidak memblokir UI

- GIVEN agen membuka chat baru
- WHEN `selectChat()` dieksekusi
- THEN sistem SHALL langsung merender header chat tanpa menunggu request HTTP synchronous ke WAHA.

### Requirement: UI/UX Pro Max WhatsApp Inbox

Sistem SHALL menyediakan antarmuka Inbox WhatsApp yang fleksibel layaknya WhatsApp Web / WhatsApp Desktop.

#### Scenario: Chatbox memenuhi tinggi viewport

- GIVEN agen membuka Inbox WhatsApp di layar desktop
- WHEN halaman dimuat
- THEN sistem SHALL menampilkan layout full-height fleksibel yang memenuhi viewport dengan dua pane (sidebar dan area chat).

#### Scenario: Responsive sidebar di smartphone

- GIVEN halaman Inbox WhatsApp dibuka di smartphone
- WHEN pengguna memilih salah satu chat
- THEN sidebar list chat SHALL bersembunyi secara otomatis dan menampilkan tombol kembali agar navigasi mudah.

#### Scenario: Bubble chat profesional dengan kontras tinggi

- GIVEN percakapan memuat pesan dari customer, agen, AI, dan catatan internal
- WHEN chat dirender
- THEN sistem SHALL menampilkan bubble dengan warna berbeda, kontras tinggi, nama pengirim pada grup, timestamp, dan status kirim.
