# Change: Perbaikan Breadcrumbs, Form Ticket/Task, AutoReply AI, dan UI/UX Inbox WhatsApp

## Summary

Change ini menyelesaikan 5 isu utama yang ditemukan pada aplikasi Care Desk:
1. **Breadcrumbs Lengkap**: Seluruh halaman Filament (termasuk Resource Ticket, Task, dan Master Ticketing) dikonfigurasi menggunakan `HasMenuBreadcrumbs` / `getBreadcrumbs()` agar selalu menampilkan label breadcrumb dan navigasi grup yang konsisten.
2. **Perbaikan Layout & UI Entri Ticket & Task**: Memperbaiki form input Ticket dan Task yang sebelumnya terpotong pada desktop, hp, dan tablet dengan menerapkan `Section`, `Grid` multi-kolom yang responsif, `columnSpanFull()` untuk textarea dan repeater, serta memperluas lebar modal `ManageRecords`.
3. **Perbaikan Bug AutoReply AI**: Memperbaiki bug di mana AI AutoReply tidak membalas chat masuk meskipun sesi atau setting AI telah diaktifkan, disebabkan oleh fatal `TypeError` (penggunaan `$isFirstReply` sebelum inisialisasi), perbaikan gate global vs session-level auto-reply (`AutoReplyAktif` vs `AutoReplyAiAktif`), serta penanganan timestamp deduplikasi jawaban.
4. **Optimasi Performa Inbox WhatsApp**: Membasmi N+1 SQL query pada `loadInbox()` dengan batching payload pesan terbaru, serta menghilangkan panggilan HTTP synchronous ke WAHA API pada alur render Livewire.
5. **Redesain UI/UX Inbox WhatsApp (Pro Max)**: Mengubah tampilan Inbox WhatsApp menjadi layout dual-pane fleksibel setinggi viewport (mirip WhatsApp Web / WhatsApp Desktop), dengan header sticky, bubble chat berkontras tinggi (membedakan pesan customer, CS agent, AI auto-reply, dan catatan internal), sidebar list chat responsif (drawer/toggle pada mobile/tablet), serta footer input bar yang kaya fitur (attachment, multi-line auto-expand, dan indikator status AI).

## Problem Statement

Berdasarkan analisis source code dan penelusuran runtime:
1. Beberapa Filament Resource Page (khususnya `TicketResource`, `TaskResource`, `StatusTicketResource`, `StatusTaskResource`, `KategoriTicketResource`, `PrioritasTicketResource`) belum menggunakan `HasMenuBreadcrumbs` / `getBreadcrumbs()`, sehingga navigasi breadcrumb tidak tampil atau tidak lengkap.
2. Form input modal `TicketResource` dan `TaskResource` hanya berisi kumpulan field tanpa pembagian `Section` atau `Grid` responsif. Pada layar desktop sedang/kecil, tablet, dan smartphone, field repeater (activities, assignments, attachments, checklist) terhimpit secara horizontal dan teks/elemen input terpotong ("cut off").
3. Di `AiAutoReplyService::handleIncomingChat()`, baris 108 memanggil `$this->inboxReplyModel($settings, $isFirstReply)` sebelum `$isFirstReply` diinisialisasi pada baris 124. Passing `null` ke tipe parameter `bool $isFirstReply` memicu fatal `TypeError` di PHP 8.3 sebelum try-catch block, menyebabkan job background `ProcessAiAutoReplyJob` selalu gagal. Selain itu, pengecekan awal `if (! (bool) $settings->AutoReplyAktif)` memblokir balasan pada chat yang secara khusus telah diaktifkan `AutoReplyAiAktif = true` oleh agent jika toggle global AI sedang mati.
4. Di `InboxWhatsapp.php`, metode `loadInbox()` mengeksekusi query SQL `latestIncomingPayload()` secara individual untuk setiap row chat di dalam loop `formatChatRow()` (N+1 query problem). Selain itu, `selectChat()` mengeksekusi request HTTP synchronous ke API WAHA (`getContactProfilePictureUrl`, `getPhoneNumberByLid`) pada setiap navigasi chat, yang memblokir render UI hingga beberapa detik.
5. Tampilan `inbox-whatsapp.blade.php` belum fleksibel: chatbox memiliki tinggi yang kaku, bubble chat kurang kontras, tidak ramah mobile/tablet, serta kurang merepresentasikan antarmuka WhatsApp Web / Desktop yang profesional.

## Current State

- `src/app/Filament/Resources/Operational/Tickets/Pages/ManageTickets.php` dan `ManageTasks.php` belum memiliki method `getBreadcrumbs()` maupun trait `HasMenuBreadcrumbs`.
- `TicketResource.php` dan `TaskResource.php` menumpuk komponen form tanpa `Section` dan `Grid`.
- `AiAutoReplyService.php` memiliki fatal `TypeError` pada `$isFirstReply`, gate check `AutoReplyAktif` yang kaku, dan parameter `$isFirstReply` yang tidak diteruskan pada provider non-OpenAI.
- `InboxWhatsapp.php` mengeksekusi 50+ query `TChatD` per render dan HTTP request synchronous ke WAHA saat memilih chat.
- `inbox-whatsapp.blade.php` belum menggunakan layout WhatsApp Web/Desktop full-height flex modern.

## Goals

- Seluruh halaman Filament memiliki breadcrumb lengkap yang menyelaraskan menu code dan group label.
- Form Ticket dan Task tampil bersih, responsif, tidak terpotong di desktop, tablet, maupun smartphone.
- AutoReply AI membalas chat secara handal baik ketika AI AutoReply global diaktifkan maupun ketika sesi chat tertentu diaktifkan oleh agent.
- Halaman Inbox WhatsApp merespons secara instan tanpa N+1 query dan tanpa blocking HTTP request ke WAHA.
- UI Inbox WhatsApp mengadopsi standar WhatsApp Web / Desktop modern dengan kustomisasi UI/UX kelas profesional.

## Non-Goals

- Tidak mengubah skema database, tabel, atau menambahkan kolom baru.
- Tidak mengubah kontrak route WAHA webhook `/webhooks/waha/{token?}`.
- Tidak mengubah autentikasi `MPengguna` atau hak akses `AccessPermissions`.

## Proposed Changes

### 1. Breadcrumbs
- Tambahkan trait `HasMenuBreadcrumbs` dan properti `$breadcrumbMenuCode` pada `ManageTickets`, `ManageTasks`, `ManageStatusTickets`, `ManageStatusTasks`, `ManageKategoriTickets`, dan `ManagePrioritasTickets`.

### 2. Form Ticket & Task UI/UX
- Refactor `form()` di `TicketResource.php` dan `TaskResource.php` dengan `Section::make()`, `Grid::make(['sm' => 1, 'md' => 2, 'lg' => 3])`, serta `columnSpanFull()` pada Judul, Deskripsi, dan Repeaters.
- Pada `ManageTickets` dan `ManageTasks`, atur `modalWidth` ke `Width::SevenExtraLarge` atau `Width::SixExtraLarge`.

### 3. AutoReply AI Bug Fix
- Di `AiAutoReplyService.php`, hitung `$isFirstReply = $this->isFirstInboxAiReply($chatId);` sebelum membentuk `TAiPermintaan`.
- Sesuaikan gate check: izinkan auto-reply apabila `$settings->AutoReplyAktif` bernilai true ATAU `$chat->AutoReplyAiAktif` bernilai true.
- Teruskan parameter `$isFirstReply` ke `generateChatCompletionReply()` untuk provider DeepSeek, OpenRouter, dan 9Router.
- Sempurnakan filter `$alreadyAnswered` agar akurat terhadap pesan terbaru.

### 4. Performa Inbox WhatsApp
- Di `InboxWhatsapp.php`, jalankan batch query untuk mengambil `latestIncomingPayload` seluruh `chatIds` sekaligus di `loadInbox()`.
- Pindahkan pencarian/update foto profil WAHA ke background job / event asinkron agar `selectChat()` tidak memblokir HTTP request.

### 5. UI/UX Pro Max Inbox WhatsApp
- Redesain `inbox-whatsapp.blade.php` dengan container flex full-height (`h-[calc(100vh-6.5rem)]`).
- Buat sidebar list chat dengan pencarian, filter tab (Semua, Pribadi, Grup), badge pesan belum dibaca, dan indikator status AI.
- Buat toggle responsif untuk mobile/tablet sehingga sidebar dapat ditutup dan menampilkan tombol "Kembali" saat percakapan dibuka.
- Buat header chat sticky yang menampilkan informasi kontak/grup, badge mode identitas (WhatsApp / Internal), status AI AutoReply, dan aksi cepat.
- Buat area pesan bergaya WhatsApp Web (bubble hijau/putih berkontras tinggi, sender name pada grup, timestamp, status kirim, dan catatan internal berlatar amber).
- Buat input footer melayang dengan uploader lampiran, textarea auto-expand, dan tombol Kirim utama.

## Impacted Areas

- `src/app/Filament/Resources/Operational/Tickets/TicketResource.php`
- `src/app/Filament/Resources/Operational/Tickets/Pages/ManageTickets.php`
- `src/app/Filament/Resources/Operational/Tasks/TaskResource.php`
- `src/app/Filament/Resources/Operational/Tasks/Pages/ManageTasks.php`
- `src/app/Filament/Resources/Ticketing/*/Pages/*.php`
- `src/app/Services/Ai/AiAutoReplyService.php`
- `src/app/Filament/Pages/InboxWhatsapp.php`
- `src/resources/views/filament/pages/inbox-whatsapp.blade.php`

## Risks and Mitigations

- **Risiko**: Perubahan layout Blade dapat memengaruhi tampilan pada screen resolution tertentu.
  - **Mitigasi**: Gunakan kelas Tailwind CSS responsif (`hidden md:flex`, `w-full md:w-80`, `h-full`) dan uji di tampilan desktop, tablet, dan smartphone.
- **Risiko**: Pengubahan query `loadInbox` dapat me-miss data tertentu.
  - **Mitigasi**: Pertahankan seluruh atribut data hasil format `formatChatRow()` dan jalankan testing.

## Validation

- `php -l` pada seluruh file PHP yang diubah.
- `php artisan test` untuk memastikan seluruh fitur backend berjalan.
- `npm run build` untuk memverifikasi kompilasi frontend asset.

## Rollback

- Perubahan bersifat code-only tanpa migration database. Rollback dapat dilakukan via `git checkout` / `git revert`.
