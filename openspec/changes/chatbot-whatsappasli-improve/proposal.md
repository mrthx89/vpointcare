# Change: Tingkatkan Tab WhatsAppAsli pada Inbox WhatsApp

## Summary

Tingkatkan mode WhatsApp asli pada Inbox WhatsApp agar CS dapat membedakan Grup dan Pribadi serta melihat identitas WAHA dari snapshot database.

## Problem Statement

Mode WhatsApp asli masih bergantung pada payload terakhir dan field TChat. Nama grup yang tidak ada di webhook dan nama kontak untuk JID @lid belum disimpan sebagai snapshot yang dapat digunakan kembali. Fetch langsung saat render akan memperlambat Inbox dan tidak aman saat WAHA unavailable.

## Current State

- `src/app/Filament/Pages/InboxWhatsapp.php` sudah memiliki `identityDisplayMode=whatsapp` dan mode internal.
- `formatChatRow()` membaca TChat, mapping master, dan payload pesan terakhir.
- `refreshWahaProfile()` menyegarkan foto profil dan resolusi LID ke nomor, tetapi belum menyimpan nama kontak/grup WAHA.
- `src/app/Services/Waha/WahaSender.php` sudah memiliki request WAHA terautentikasi, timeout, circuit breaker, dan TLogIntegrasi.
- Change terarsip `openspec/changes/archive/2026-07-29-inbox-whatsapp-improvement` sudah memperkenalkan toggle identity dan tidak boleh diduplikasi.

## Goals

- Menampilkan list/header chat dengan nama WhatsApp, badge Grup/Pribadi, JID atau nomor, preview, waktu, avatar, dan unread count.
- Menampilkan JID grup `@g.us` dan nama grup dari snapshot WAHA bila mapping/payload tidak menyediakan nama.
- Menampilkan nama personal dari info kontak WAHA untuk `@lid`, mempertahankan JID LID dan nomor hasil resolusi bila tersedia.
- Menyimpan snapshot di TChat terpisah dari mapping master dan mengaksesnya dari database terlebih dahulu.
- Menjalankan sinkronisasi asynchronous di queue `webhooks` dengan deduplikasi, retry, timeout, logging, dan fallback.
- Mendukung Bahasa Indonesia/Inggris, dark mode, keyboard focus, dan tanpa payload/secret mentah di UI.

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

## Capabilities

### New Capabilities

- Tidak ada.

### Modified Capabilities

- `vpoint-care`: WhatsApp Inbox menampilkan identitas WAHA tersimpan untuk grup @g.us, personal, dan @lid.

## Impacted Areas

- UI: `src/app/Filament/Pages/InboxWhatsapp.php`, `src/resources/views/filament/pages/inbox-whatsapp.blade.php`.
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

## Validation

- Unit test parser kontak/grup dan resolusi @lid.
- Job test untuk deduplikasi, retry, gagal, dan preservasi snapshot.
- Livewire test untuk Grup/Pribadi, @g.us, @lid, fallback, refresh, locale, dan accessibility markup.
- Jalankan syntax check, targeted PHPUnit, Pint, Vite build, dan OpenSpec strict validation.
- Verifikasi manual dengan WAHA aktif dan unavailable.

## Rollback

- Backup database sebelum migration production.
- Hentikan worker webhooks, deploy code/asset sebelumnya, dan rollback migration bila snapshot tidak lagi dibutuhkan.
- Rollback hanya menghapus kolom snapshot baru, bukan chat, pesan, atau mapping master.
- Restart worker dan verifikasi Inbox sebelum membuka trafik webhook.
