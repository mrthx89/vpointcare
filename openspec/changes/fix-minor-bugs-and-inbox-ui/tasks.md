# Tasks: Perbaikan Breadcrumbs, Form Ticket/Task, AutoReply AI, dan UI/UX Inbox WhatsApp

## 1. Breadcrumbs Implementation
- [x] Implementasikan `HasMenuBreadcrumbs` dan `$breadcrumbMenuCode` di `src/app/Filament/Resources/Operational/Tickets/Pages/ManageTickets.php`.
- [x] Implementasikan `HasMenuBreadcrumbs` dan `$breadcrumbMenuCode` di `src/app/Filament/Resources/Operational/Tasks/Pages/ManageTasks.php`.
- [x] Implementasikan `HasMenuBreadcrumbs` dan `$breadcrumbMenuCode` di `src/app/Filament/Resources/Ticketing/StatusTickets/Pages/ManageStatusTickets.php`.
- [x] Implementasikan `HasMenuBreadcrumbs` dan `$breadcrumbMenuCode` di `src/app/Filament/Resources/Ticketing/StatusTasks/Pages/ManageStatusTasks.php`.
- [x] Implementasikan `HasMenuBreadcrumbs` dan `$breadcrumbMenuCode` di `src/app/Filament/Resources/Ticketing/Kategoris/Pages/ManageKategoriTickets.php`.
- [x] Implementasikan `HasMenuBreadcrumbs` dan `$breadcrumbMenuCode` di `src/app/Filament/Resources/Ticketing/Prioritas/Pages/ManagePrioritasTickets.php`.

## 2. Form Ticket & Task UI/UX Improvements
- [x] Refactor `form()` di `src/app/Filament/Resources/Operational/Tickets/TicketResource.php` dengan `Section`, `Grid` multi-kolom responsif, dan full column span.
- [x] Refactor `form()` di `src/app/Filament/Resources/Operational/Tasks/TaskResource.php` dengan `Section`, `Grid` multi-kolom responsif, dan full column span.
- [x] Set `modalWidth(Width::SevenExtraLarge)` di `ManageTickets.php` dan `ManageTasks.php`.

## 3. AutoReply AI Bug Fixes
- [x] Inisialisasi `$isFirstReply` sebelum pemanggilan `$this->inboxReplyModel(...)` di `src/app/Services/Ai/AiAutoReplyService.php`.
- [x] Perbaiki gate check di `AiAutoReplyService::handleIncomingChat` agar mengizinkan auto-reply jika `$settings->AutoReplyAktif` ATAU `$chat->AutoReplyAiAktif` bernilai true.
- [x] Teruskan parameter `$isFirstReply` ke `generateChatCompletionReply()` untuk provider DeepSeek, OpenRouter, dan 9Router.
- [x] Perbaiki logika `$alreadyAnswered` agar tidak keliru memblokir pesan baru.

## 4. Inbox WhatsApp Performance Optimization
- [x] Batch-load `latestIncomingPayload` di `src/app/Filament/Pages/InboxWhatsapp.php` untuk membasmi N+1 query pada `loadInbox()`.
- [x] Hilangkan HTTP request synchronous ke WAHA dari alur `selectChat()` dan `refreshWahaProfileIfNeeded()` saat render standar.

## 5. Inbox WhatsApp UI/UX Redesign (WhatsApp Web / Desktop Style)
- [x] Redesain layout flex full-height di `src/resources/views/filament/pages/inbox-whatsapp.blade.php`.
- [x] Sempurnakan sidebar list chat dengan pencarian, tab filter, badge unread, dan status AI.
- [x] Buat penanganan responsive mobile/tablet (toggle drawer sidebar & tombol "Kembali").
- [x] Redesain area pesan dengan bubble kontras tinggi (pesan customer, balasan CS, AI auto-reply, catatan internal).
- [x] Redesain floating input bar dengan uploader media/dokumen, multi-line auto-expand textarea, dan tombol Kirim utama.

## 6. Validation & Testing
- [x] Jalankan `php -l` untuk memastikan sintaks PHP valid pada seluruh file yang diubah.
- [x] Jalankan `php artisan test` untuk verifikasi regresi backend.
- [x] Jalankan `npm run build` untuk mengompilasi frontend asset.
