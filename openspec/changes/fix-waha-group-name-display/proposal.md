# Change: Simpan dan Tampilkan Nama Grup WhatsApp dari WAHA

## Why

Tab **WhatsApp Asli** di Inbox WhatsApp menampilkan identitas dari payload terakhir dan master mapping, tetapi tidak menampilkan nama group yang sebenarnya dari WAHA karena:

1. Kolom `TChat.NamaGrupWaha` kosong (belum diisi dari WAHA)
2. Fallback ke payload `group.subject` juga kosong untuk beberapa group
3. Prioritas fallback `formatChatRow()` tidak memprioritaskan snapshot database
4. Tidak ada penanganan icon group di UI

Perubahan ini diperlukan agar:
- Nama group dari WAHA tersimpan di database dan tidak perlu fetch berulang kali
- Tab WhatsApp Asli menampilkan nama group yang benar dari WAHA
- Icon group ditampilkan dengan benar
- Pembedaan jelas antara tab WhatsApp Asli (snapshot WAHA) dan Data Internal (master mapping)

## What Changes

- Perbaiki `formatChatRow()` di `InboxWhatsapp.php` untuk memprioritaskan `NamaGrupWaha` dari database sebagai fallback
- Dispatch `SyncWahaChatIdentityJob` untuk mengisi nama group dari WAHA
- Tambahkan icon group di blade view
- Simpan nama group dari WAHA ke `TChat.NamaGrupWaha` via job
- Pastikan job hanya dispatch untuk chat yang belum memiliki snapshot nama group

## Capabilities

### New Capabilities

- `waha-identity-storage`: Menyimpan dan menampilkan identitas WAHA (nama kontak/grup) ke database agar tidak perlu fetch berulang kali

### Modified Capabilities

- `vpoint-care`: WhatsApp Inbox menampilkan identitas WAHA tersimpan untuk grup @g.us dengan nama dan icon yang benar

## Impact

- **UI**: `src/app/Filament/Pages/InboxWhatsapp.php` (formatChatRow fallback)
- **UI**: `src/resources/views/filament/pages/inbox-whatsapp.blade.php` (icon group)
- **Job**: `src/app/Jobs/SyncWahaChatIdentityJob.php` (dispatch untuk nama group)
- **Database**: `TChat` (kolom `NamaGrupWaha` sudah ada dari migration sebelumnya)
- **Queue**: `webhooks` (dispatch job sinkronisasi)
