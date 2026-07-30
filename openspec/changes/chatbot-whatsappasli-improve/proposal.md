# Change: Tingkatkan Tab WhatsAppAsli pada Inbox WhatsApp

## Summary

Tingkatkan mode WhatsApp asli pada Inbox WhatsApp agar CS dapat membedakan Grup dan Pribadi serta melihat identitas WAHA dari snapshot database.

## Problem Statement

Mode WhatsApp asli masih bergantung pada payload terakhir dan field TChat. Nama grup yang tidak ada di webhook dan nama kontak untuk JID @lid belum disimpan sebagai snapshot yang dapat digunakan kembali. Fetch langsung saat render akan memperlambat Inbox dan tidak aman saat WAHA unavailable. Foto profil grup juga berisiko salah jika refresh memakai identifier peserta, bukan raw group JID `@g.us`. Selain itu beberapa halaman ticketing tidak memiliki breadcrumb menu yang konsisten sehingga navigasi admin pada route ticketing tertentu belum berjalan.

## Current State

- `src/app/Filament/Pages/InboxWhatsapp.php` sudah memiliki `identityDisplayMode=whatsapp` dan mode internal.
- `formatChatRow()` membaca TChat, mapping master, dan payload pesan terakhir.
- `refreshWahaProfile()` menyegarkan foto profil dan resolusi LID ke nomor, tetapi belum menyimpan nama kontak/grup WAHA.
- `profileContactId()` untuk chat grup mengambil `MGrupWhatsapp.IdGrupWaha` atau fallback identifier; implementasi perlu memastikan foto profil grup hanya memakai raw group JID `@g.us` dari chat/payload, bukan participant/sender.
- `src/app/Services/Waha/WahaSender.php` sudah memiliki request WAHA terautentikasi, timeout, circuit breaker, dan TLogIntegrasi.
- Halaman `ManageStatusTickets`, `ManagePrioritasTickets`, `ManageKategoriTickets`, dan `ManageTickets` masih extend `ManageRecords` tanpa `HasMenuBreadcrumbs` dan tanpa `$breadcrumbMenuCode`.
- Change terarsip `openspec/changes/archive/2026-07-29-inbox-whatsapp-improvement` sudah memperkenalkan toggle identity dan tidak boleh diduplikasi.

## Goals

- Menampilkan list/header chat dengan nama WhatsApp, badge Grup/Pribadi, JID atau nomor, preview, waktu, avatar, dan unread count.
- Menampilkan JID grup `@g.us` dan nama grup dari snapshot WAHA bila mapping/payload tidak menyediakan nama.
- Menampilkan foto profil grup yang benar berdasarkan raw group JID `@g.us`, bukan foto peserta terakhir.
- Menampilkan nama personal dari info kontak WAHA untuk `@lid`, mempertahankan JID LID dan nomor hasil resolusi bila tersedia.
- Menyimpan snapshot di TChat terpisah dari mapping master dan mengaksesnya dari database terlebih dahulu.
- Menjalankan sinkronisasi asynchronous di queue `webhooks` dengan deduplikasi, retry, timeout, logging, dan fallback.
- Mendukung Bahasa Indonesia/Inggris, dark mode, keyboard focus, dan tanpa payload/secret mentah di UI.
- Memulihkan breadcrumb pada halaman master ticketing dan operasional ticket yang dilaporkan.

## Non-Goals

- Tidak mengubah mapping MNomorWhatsapp, MGrupWhatsapp, MAnggotaGrupWhatsapp, customer, atau instansi otomatis.
- Tidak menyinkronkan anggota grup, presence, atau seluruh riwayat chat WAHA.
- Tidak mengubah kontrak route atau normalisasi JID yang ada.
- Tidak menambah dependency frontend atau mengganti Filament/Livewire.

## Proposed Changes

1. Tambahkan kolom snapshot nama kontak/grup, waktu, status, dan error tersanitasi pada TChat.
2. Tambahkan adapter WahaSender untuk metadata kontak/grup dan parser defensif; gunakan resolver LID yang sudah ada.
3. Tambahkan `SyncWahaChatIdentityJob` pada queue `webhooks`, timeout 30 detik, 3 percobaan, backoff 30/120 detik, deduplikasi per chat 60 detik.
4. Dispatch job setelah transaksi webhook berhasil tanpa menunggu request metadata.
5. Prioritaskan snapshot database pada Inbox WhatsAppAsli dan sediakan refresh metadata untuk chat aktif.
6. Tambahkan localization, test, validasi SQL Server, dan instruksi deployment/rollback.
7. Kunci pengambilan foto profil grup ke raw group JID `@g.us`; jika tidak tersedia, gunakan fallback avatar/inisial tanpa memanggil WAHA dengan participant JID.
8. Pasang trait breadcrumb menu pada halaman resource ticketing yang terdampak dengan kode menu `ticket.view` agar breadcrumb mengikuti label/group dari `NavigationHelper`.

## Additional Requested Corrections

- **Pesan grup tidak lengkap:** audit source menunjukkan `findOrCreateChat()` dapat memakai JID/nama pengirim ketika grup belum mapped, padahal identitas chat harus selalu memakai `group_jid` `@g.us`. Perbaikan akan mengunci satu `TChat` per sesi + group JID dan menyimpan setiap peserta sebagai `TChatD.Pengirim*`.
- **Foto profil anggota grup:** ambil foto profil setiap pengirim grup dari WAHA secara asynchronous, simpan snapshot URL dan waktu pengambilan pada detail pesan, lalu tampilkan avatar pada bubble/group participant dengan fallback inisial.
- **Foto profil grup salah ambil:** foto avatar percakapan grup harus di-refresh menggunakan raw group JID `@g.us` dari `TChat`/payload chat, bukan `participant`, `sender`, nomor peserta, atau fallback personal.
- **AI tidak membalas:** tambahkan observability reason/status dari dispatch sampai delivery, perbaiki urutan perhitungan `$isFirstReply` sebelum pemilihan model, dan definisikan ulang session: All Session aktif menjawab setiap incoming eligible; All Session tidak aktif hanya menjawab pesan pertama atau pesan setelah idle minimal 60 menit, dengan aturan jam kerja/hari libur/excluded number tetap berlaku.
- **Base64 media:** jika base64/data URI berhasil dikonversi menjadi preview atau download, teks base64 tidak boleh ditampilkan. Jika konversi gagal, tampilkan fallback base64 dalam panel diagnostik terkontrol tanpa menampilkan payload JSON atau secret.
- **Breadcrumb ticketing:** route `/admin/ticketing/status-tickets`, `/admin/ticketing/prioritas/prioritas-tickets`, `/admin/ticketing/kategoris/kategori-tickets`, dan `/admin/operational/tickets` harus menampilkan breadcrumb berbasis menu seperti halaman admin lain.
- **Sanitasi log WAHA:** `TLogIntegrasi.ResponseJson` dan `PesanError` tidak boleh menyimpan body HTTP mentah. Log hanya menyimpan status dan metadata respons terbatas, sedangkan body return tetap tersedia di memori untuk parser adapter.

## Capabilities

### New Capabilities

- Tidak ada.

### Modified Capabilities

- `vpoint-care`: WhatsApp Inbox menampilkan identitas WAHA tersimpan untuk grup @g.us, personal, dan @lid; halaman ticketing terdampak menampilkan breadcrumb menu yang benar.

## Impacted Areas

- UI: `src/app/Filament/Pages/InboxWhatsapp.php`, `src/resources/views/filament/pages/inbox-whatsapp.blade.php`.
- UI ticketing: `src/app/Filament/Resources/Ticketing/StatusTickets/Pages/ManageStatusTickets.php`, `src/app/Filament/Resources/Ticketing/Prioritas/Pages/ManagePrioritasTickets.php`, `src/app/Filament/Resources/Ticketing/Kategoris/Pages/ManageKategoriTickets.php`, dan `src/app/Filament/Resources/Operational/Tickets/Pages/ManageTickets.php`.
- WAHA: `src/app/Services/Waha/WahaSender.php`, `src/app/Services/Waha/WahaWebhookProcessor.php`, job, dan broadcast.
- SQL Server: migration TChat dan `src/script/DATABASE_SCHEMA_WACS.sql`.
- Queue: `webhooks`, timeout 30 detik, retries 3, backoff 30/120 detik, deduplikasi 60 detik.
- Localization/permission: `src/lang/id/ui.php`, `src/lang/en/ui.php`, permission Inbox yang ada.
- Deployment: backup database, migration, restart `queue-webhooks`, dan `npm run build` bila asset berubah.

## Risks and Mitigations

- WAHA lambat → background job, timeout, retry terbatas, circuit breaker, dan snapshot terakhir.
- Respons berbeda antar versi → parser field eksplisit dan fallback raw JID.
- Duplikasi request → unique job per chat dan broadcast debounce.
- Identitas internal tertimpa → snapshot field terpisah dari master mapping.
- Secret ikut log → simpan metadata/error tersanitasi saja.

- Foto grup salah sumber dimitigasi dengan resolver raw group JID `@g.us` dan regression test yang memastikan participant tidak dipakai sebagai avatar grup.
- Breadcrumb tidak sesuai akses dimitigasi dengan `HasMenuBreadcrumbs` serta `AccessPermissions::TICKET_VIEW` yang sudah menjadi sumber menu sidebar ticketing.

## Validation

- Unit test parser kontak/grup dan resolusi @lid.
- Job test untuk deduplikasi, retry, gagal, dan preservasi snapshot.
- Livewire test untuk Grup/Pribadi, @g.us, @lid, fallback, refresh, locale, dan accessibility markup.
- Regression test untuk avatar grup yang memakai `@g.us`, bukan participant, serta breadcrumb empat route ticketing yang dilaporkan.
- Regression test respons profile-picture yang mengandung secret untuk memastikan body mentah tidak tersimpan di `TLogIntegrasi`.
- Jalankan syntax check, targeted PHPUnit, Pint, Vite build, dan OpenSpec strict validation.
- Verifikasi manual dengan WAHA aktif dan unavailable.

## Rollback

- Backup database sebelum migration production.
- Hentikan worker webhooks, deploy code/asset sebelumnya, dan rollback migration bila snapshot tidak lagi dibutuhkan.
- Rollback hanya menghapus kolom snapshot baru, bukan chat, pesan, atau mapping master.
- Restart worker dan verifikasi Inbox sebelum membuka trafik webhook.
