## Context

Tab WhatsApp Asli di Inbox WhatsApp menampilkan identitas chat dari payload terakhir dan master mapping. Kolom TChat.NamaGrupWaha sudah ada dari migration 2026_07_30_000001_add_waha_identity_snapshot_to_chat.php, tetapi:

1. Job SyncWahaChatIdentityJob belum dispatch untuk mengisi nama grup dari WAHA
2. Fallback di ormatChatRow() memprioritaskan NamaGrupMaster (sering kosong) sebelum snapshot WAHA
3. UI tidak menampilkan icon grup
4. Tidak ada pembedaan visual antara tab WhatsApp Asli (snapshot WAHA) dan Data Internal (master mapping)

## Goals / Non-Goals

**Goals:**
- Nama grup dari WAHA tersimpan di TChat.NamaGrupWaha via background job
- Tab WhatsApp Asli menampilkan nama grup yang benar dengan prioritas: snapshot WAHA > payload > master
- Icon grup ditampilkan di list chat
- Pembedaan jelas antara tab WhatsApp Asli dan Data Internal

**Non-Goals:**
- Tidak mengubah mapping master (MGrupWhatsapp)
- Tidak menyinkronkan anggota grup atau presence
- Tidak mengubah kontrak route atau normalisasi JID
- Tidak menambah dependency frontend baru

## Decisions

### 1. Prioritas Fallback Nama Grup

**Keputusan:** Ubah urutan fallback di ormatChatRow() menjadi:
`
snapshot WAHA (NamaGrupWaha) > payload (group.subject/name) > master (NamaGrupMaster) > raw JID
`

**Rasional:** Snapshot WAHA adalah sumber kebenaran terbaru dari WhatsApp API. Master mapping sering kosong karena grup belum di-map ke instansi/customer. Payload hanya tersedia untuk pesan terakhir.

**Alternatif:** Mempertahankan urutan lama (master > snapshot). Ditolak karena master sering kosong dan snapshot lebih akurat.

### 2. Dispatch Job Sinkronisasi Identitas

**Keputusan:** Dispatch SyncWahaChatIdentityJob setelah webhook berhasil memproses pesan masuk, hanya jika NamaGrupWaha kosong atau stale (>24 jam).

**Rasional:** Menghindari fetch berulang saat render. Job berjalan async di queue webhooks dengan deduplikasi 60 detik.

**Alternatif:** Fetch synchronous saat render. Ditolak karena memperlambat Inbox dan berisiko timeout saat WAHA unavailable.

### 3. Icon Grup di UI

**Keputusan:** Gunakan Heroicon heroicon-o-user-group untuk chat grup dan heroicon-o-user untuk chat pribadi. Tampilkan di sebelah nama chat di list.

**Rasional:** Konsisten dengan desain Filament existing. Tidak perlu dependency ikon baru.

**Alternatif:** Avatar grup dari WAHA. Ditunda karena memerlukan fetch tambahan dan caching kompleks.

### 4. Pembedaan Tab WhatsApp Asli vs Data Internal

**Keputusan:** Tambahkan badge/label kecil di header chat yang menunjukkan sumber data: "WAHA" untuk snapshot, "Internal" untuk master mapping.

**Rasional:** CS perlu tahu apakah nama yang ditampilkan berasal dari WhatsApp atau dari data internal WACS.

**Alternatif:** Warna berbeda. Ditolak karena kurang accessible dan sulit dibedakan di dark mode.

## Risks / Trade-offs

- **WAHA unavailable** ? Job gagal, snapshot tetap kosong. Mitigasi: retry 3x dengan backoff 30/120 detik, fallback ke payload/master.
- **Nama grup berubah di WA** ? Snapshot stale. Mitigasi: refresh otomatis setiap 24 jam atau saat user klik "Refresh Identity".
- **Duplikasi job** ? Multiple dispatch untuk chat sama. Mitigasi: deduplikasi per IdChat selama 60 detik di job.
- **Icon tidak konsisten** ? Heroicon berbeda dari ikon WA asli. Mitigasi: dokumentasikan bahwa ini adalah representasi sistem, bukan ikon resmi WA.

## Migration Plan

1. Deploy code perubahan ormatChatRow() dan blade view
2. Restart queue worker webhooks
3. Job akan mengisi NamaGrupWaha secara bertahap saat pesan masuk
4. Untuk chat existing tanpa snapshot, trigger manual refresh atau tunggu pesan masuk berikutnya

**Rollback:** Revert code perubahan. Kolom database tidak dihapus. UI kembali ke fallback lama.
