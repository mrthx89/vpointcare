### Task 6: Participant Avatar Sync

**Tujuan:** Menampilkan foto profile user-user di GroupWhatsApp tanpa WAHA call pada saat Blade/Livewire dirender.

**Files:**
- Modify: `src/app/Jobs/SyncWahaChatIdentityJob.php`
- Modify: `src/tests/Feature/Waha/WahaChatIdentitySyncTest.php`
- Modify: `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`

**Interfaces:**
- Job memperbarui `TChatD.UrlFotoProfilPengirim` dan `TglFotoProfilPengirimDiambil` untuk participant grup yang belum punya foto atau stale satu hari.
- Cache dedupe participant: `waha:participant-profile:{session}:{sha1(participantJid)}`, TTL 1 jam.

- [ ] **Step 1: Tulis test merah avatar participant**

Buat satu `TChat` grup, satu `TChatD` incoming dengan `PengirimIdWaha='628111@c.us'`, dan fake foto `https://cdn.test/budi.jpg`. Assert participant photo diperbarui, sementara `TChat.UrlFotoProfil` grup tidak berubah.

- [ ] **Step 2: Tambahkan sync participant ke job**

Tambahkan private method yang mengambil maksimal 20 pesan masuk grup terbaru dengan JID participant dan foto null/stale. Untuk setiap participant yang belum terkena cache, panggil `getContactProfilePictureUrl()`, lalu update seluruh `TChatD` pada chat + participant JID yang sama. Failure tidak menghapus foto terakhir dan tidak mengubah foto grup.

- [ ] **Step 3: Panggil hanya untuk chat grup**

Di `SyncWahaChatIdentityJob::handle()`, jalankan sync participant hanya bila `JenisChat='Grup'`, setelah metadata group sinkron. Tetap dispatch satu `SendBroadcastDebouncedJob` untuk refresh UI.

- [ ] **Step 4: Jalankan test**

```powershell
cd src
php -l app/Jobs/SyncWahaChatIdentityJob.php
php artisan test --filter=WahaChatIdentitySyncTest
php artisan test --filter=InboxWhatsappTest
```

Expected: participant avatar tersimpan pada detail pesan; avatar percakapan grup tetap memakai raw group JID.

---

