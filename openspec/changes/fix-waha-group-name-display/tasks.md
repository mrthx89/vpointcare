## 1. Perbaiki Prioritas Fallback Nama Grup di InboxWhatsapp.php

- [ ] 1.1 Ubah urutan fallback di method \ormatChatRow()\: prioritaskan \NamaGrupWaha\ > payload > \NamaGrupMaster\ > raw JID
- [ ] 1.2 Tambahkan badge sumber data (WAHA/Payload/Internal/JID) di array return \ormatChatRow()\
- [ ] 1.3 Pastikan \NamaKontakWaha\ juga mendapat perlakuan serupa untuk chat pribadi

## 2. Update Blade View untuk Icon dan Badge Sumber Data

- [ ] 2.1 Tampilkan icon \heroicon-o-user-group\ untuk grup dan \heroicon-o-user\ untuk pribadi di list chat
- [ ] 2.2 Tambahkan badge kecil yang menunjukkan sumber data (WAHA/Internal) di header chat
- [ ] 2.3 Pastikan styling responsive dan compatible dengan dark mode

## 3. Dispatch Job Sinkronisasi Identitas WAHA

- [ ] 3.1 Audit \SyncWahaChatIdentityJob\ existing untuk memastikan sudah menangani nama grup
- [ ] 3.2 Update webhook processor untuk dispatch job jika \NamaGrupWaha\ atau \NamaKontakWaha\ kosong/stale
- [ ] 3.3 Pastikan deduplikasi per \IdChat\ selama 60 detik berfungsi

## 4. Localization dan Permission

- [ ] 4.1 Tambahkan key localization untuk badge sumber data di \src/lang/id/ui.php\ dan \src/lang/en/ui.php\
- [ ] 4.2 Verifikasi permission \INBOX_MANAGE\ untuk aksi refresh identity manual

## 5. Validasi

- [ ] 5.1 Jalankan PHP lint pada file yang diubah
- [ ] 5.2 Verifikasi tampilan di browser dengan chat grup yang belum memiliki nama
- [ ] 5.3 Test dispatch job dengan pesan masuk baru
- [ ] 5.4 Sync OpenSpec dengan implementasi final
