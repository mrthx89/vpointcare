### Task 5: Inbox WhatsAppAsli Snapshot dan Avatar Benar

**Tujuan:** UI WhatsAppAsli memakai snapshot database dan avatar grup dari raw group JID, sambil tetap jelas bagi CS: Grup/Pribadi, nama, JID, LID/nomor, preview, waktu, dan unread.

**Files:**
- Modify: `src/app/Filament/Pages/InboxWhatsapp.php`
- Modify: `src/resources/views/filament/pages/inbox-whatsapp.blade.php`
- Modify: `src/resources/lang/id/ui.php`
- Modify: `src/resources/lang/en/ui.php`
- Modify: `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`

**Interfaces:**
- `formatChatRow()` supplies `PrimaryName`, `Badge`, `ChatId`, `GroupId`, `ContactNumber`, `ResolvedNumber`, `SnapshotStatus`, and `SnapshotError` per display mode.
- `profileContactId()` returns raw `@g.us` for group only; participant/sender JID is never a group avatar candidate.
- `messageSenderAvatarUrl()` returns participant photo for incoming group message, AI logo for outgoing AI, and existing CS profile for outgoing CS.

- [ ] **Step 1: Tulis test merah prioritas nama snapshot**

Tambahkan fixture chat grup dengan `NomorWhatsapp='120363111@g.us'`, `NamaGrupWhatsapp='Legacy Payload Name'`, `NamaGrupWaha='Nama Grup WAHA'`, serta `IdGrupWaha='999@g.us'`. Assert identity mode WhatsApp memakai `Nama Grup WAHA` dan group ID `120363111@g.us`.

- [ ] **Step 2: Tulis test merah avatar grup**

Fake endpoint foto WAHA dan panggil action refresh yang digunakan Inbox. Assert request memakai `contactId=120363111@g.us`, bukan `628111@c.us`, bukan sender terakhir, dan bukan mapping stale `999@g.us`.

- [ ] **Step 3: Tambahkan kolom snapshot ke query**

Di `loadChats()` select `c.NamaKontakWaha`, `c.NamaGrupWaha`, `c.TglIdentitasWahaDiambil`, `c.StatusIdentitasWaha`, dan `c.PesanErrorIdentitasWaha`. Di query messages select `d.PengirimIdWaha`, `d.UrlFotoProfilPengirim`, dan `d.TglFotoProfilPengirimDiambil`. Gunakan `SchemaCache::hasColumn()` bila diperlukan oleh database yang belum dimigrasi atau fixture lama.

- [ ] **Step 4: Terapkan urutan identity mode**

Gunakan urutan berikut dalam `formatChatRow()`:

```php
$whatsappPrimaryName = $isGroup
    ? ($row->NamaGrupWaha ?: $rawGroupName ?: $rawGroupId)
    : ($row->NamaKontakWaha ?: $rawContactName ?: $rawContactNumber ?: $detectedWahaId);

$internalPrimaryName = $isGroup
    ? ($mappedGroupName ?: $row->NamaGrupWaha ?: $rawGroupName ?: $rawGroupId)
    : ($mappedContactName ?: $row->NamaKontakWaha ?: $rawContactName ?: $mappedContactNumber ?: $rawContactNumber);
```

Mode internal tetap mengutamakan mapping master dan snapshot hanya fallback.

- [ ] **Step 5: Kunci resolver avatar grup**

Ganti branch grup di `profileContactId()` agar hanya mengembalikan candidate yang dinormalisasi dan berakhiran `@g.us`, dalam urutan raw chat JID, `IdWahaTerdeteksi`, raw payload group JID, lalu `IdGrupWaha` valid. Jika tidak ada, return `null` dan tampilkan fallback inisial; jangan call WAHA dengan participant.

- [ ] **Step 6: Render avatar participant**

Untuk incoming group message, gunakan `UrlFotoProfilPengirim` (path public storage atau URL WAHA) sebelum fallback inisial. Avatar memiliki `alt` nama sender, focus state pada bubble tetap terlihat, dan kelas dark mode konsisten (`h-7 w-7 rounded-full object-cover`).

- [ ] **Step 7: Terapkan pola UI list/header minimal**

Pertahankan Blade/Filament/Tailwind existing. Baris chat dan header wajib menampilkan avatar, nama, badge Grup/Pribadi, identifier monospace, preview, waktu, unread. Jangan mengimpor komponen WAHA Hub atau dependency frontend. Gunakan spacing konsisten 8px, label teks bukan ikon saja, focus ring, kontras dark mode, dan `alt` untuk gambar bermakna.

- [ ] **Step 8: Jalankan targeted UI test**

```powershell
cd src
php -l app/Filament/Pages/InboxWhatsapp.php
php artisan test --filter=InboxWhatsappTest
```

Expected: nama snapshot, `@g.us`, `@lid`/nomor hasil resolusi, badge, avatar yang benar, dan test Inbox lama lulus.

---

