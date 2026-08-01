# Change: Fix Inbox WhatsApp Group Chat Duplication + Realtime Room Updates

## Summary

Perbaiki empat masalah sekaligus:
1. Chat grup tanpa mapping di MGrupWhatsapp pecah jadi banyak baris TChat (1 per anggota).
2. Room name untuk private chat hanya menampilkan nomor telepon, bukan nama kontak/username.
3. Room name untuk grup menampilkan group JID (@g.us) alih-alih nama grup.
4. Isi room grup tidak memperbarui pesan baru secara realtime seperti private chat.

## Problem Statement

- **Pecah room grup**: findOrCreateChat() lookup grup tanpa mapping pakai nomor pengirim, bukan group_jid.
- **Nama room private**: fallback chain PrimaryName tidak mencakup nama dari payload WAHA (`_data.notifyName`).
- **Nama room grup**: payload WAHA message tidak mengandung nama grup, perlu fetch dari WAHA group API.
- **Realtime room grup**: parser memilih kandidat remote ID pertama dan resolver pesan aktif tidak selalu menggabungkan sibling TChat termapping, sehingga event dapat memperbarui row yang berbeda dari room yang sedang dibuka.

## Current State

- findOrCreateChat() lookup grup hanya pakai `IdGrupWhatsapp` (mapping), fallback ke personal chat.
- formatChatRow() PrimaryName untuk private = `NamaKontak` ? nomor. Payload pushName tidak dicek.
- formatChatRow() PrimaryName untuk grup = `NamaGrupWhatsapp` (kosong jika unmapped) ? group JID.
- Webhook processor hanya cek `sender.pushname`, `notifyName`, `pushName` — tidak cek `_data.notifyName`.
- Tidak ada helper untuk fetch nama grup dari WAHA API.
- `parseMessage()` menentukan chat dari kandidat ID pertama tanpa memprioritaskan kandidat `@g.us`.
- `groupSiblingIds()` hanya menggabungkan sibling untuk grup tanpa mapping.

## Goals

1. Group chat tanpa mapping menggunakan group_jid sebagai kunci ? 1 room per grup.
2. Room name private: tampilkan nama kontak dari master data, atau `_data.notifyName` dari payload.
3. Room name grup: tampilkan nama grup dari WAHA group API (cached), atau master data.
4. Pesan baru + data legacy tetap konsisten.
5. Room grup termapping, unmapped, dan legacy memperbarui daftar pesan ketika event realtime diterima.

## Proposed Changes

### 1. WahaWebhookProcessor::parseMessage()
Tambah `_data.notifyName` sebagai fallback pertama saat ekstrak `pengirim_nama`.

### 2. WahaWebhookProcessor::findOrCreateChat()
Lookup grup tanpa mapping via `IdWahaTerdeteksi = group_jid`, fallback ke `NomorWhatsapp = group_jid`.
Jika masih tidak ketemu, cari row lama via `TChatD.PayloadJson LIKE '%group_jid%'`.
Update/insert `IdWahaTerdeteksi` dan `NomorWhatsapp` pakai `group_jid` untuk grup.

### 3. InboxWhatsapp - FormatChatRow Name Resolution
- Tambah `payloadContactName($payload)`: cek `_data.notifyName`, `sender.pushname`, `notifyName`, `pushName`.
- Tambah `wahaGroupName($groupJid)`: fetch dari `GET /api/{session}/groups/{id}`, cache 6 jam.
- `$rawContactName` = `NamaKontak` ? `payloadContactName($payload)`.
- `$rawGroupName` = `NamaGrupWhatsapp` (from master) ? `wahaGroupName($groupJid)` ? group JID.
- `$whatsappPrimaryName` dan `$internalPrimaryName` gunakan chain fallback di atas.

### 4. InboxWhatsapp - Dedup + Aggregasi Pesan
- `loadInbox()`: dedupe chat rows grup berdasarkan group JID.
- `selectChat()`: `whereIn('d.IdChat', groupSiblingIds($chatId))` untuk ambil pesan dari semua sibling row.

### 5. Realtime Group Room Consistency
- `parseMessage()` memprioritaskan kandidat remote ID yang berakhiran `@g.us` sebelum menentukan `JenisChat`.
- `groupSiblingIds()` menggabungkan grup termapping dan unmapped berdasarkan canonical group JID, session, kolom identity, serta payload legacy.
- `handleInboxUpdate()` memakai `chatId` event untuk mempertahankan canonical room grup yang sedang aktif.

## Impacted Areas

- WahaWebhookProcessor.php — parseMessage(), findOrCreateChat()
- InboxWhatsapp.php — formatChatRow(), messageSenderName(), loadInbox(), selectChat(), 3 helper baru
- InboxWhatsapp.php — `handleInboxUpdate()`, `loadInbox()`, `selectChat()`, `groupSiblingIds()` untuk konsistensi realtime room grup
- InboxWhatsappTest.php dan test webhook terkait — regresi parser group JID dan agregasi sibling
- **Database**: Tidak ada perubahan migration
- **Permission / Localization**: Tidak tersentuh

## Validation

1. Private chat: tampilkan nama kontak/username, bukan nomor.
2. Grup unmapped: tampilkan nama grup dari WAHA, bukan @g.us.
3. Grup termapping: tampilan tetap konsisten.
4. Pesan dari semua anggota grup tampil di 1 room.
