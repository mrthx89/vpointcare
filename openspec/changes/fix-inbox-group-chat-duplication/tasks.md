## 1. WahaWebhookProcessor

- [x] 1.1 Di `parseMessage()`: tambah `_data.notifyName` sebagai fallback pertama `pengirim_nama`
- [x] 1.2 Di `findOrCreateChat()`: tambah cabang `jenis_chat === "Grup"` tanpa `IdGrupWhatsapp` → lookup via `IdWahaTerdeteksi = group_jid`
- [x] 1.3 Jika lookup gagal: cari row grup legacy via `TChatD.PayloadJson LIKE "%group_jid%"`
- [x] 1.4 Update `IdWahaTerdeteksi` untuk grup: simpan `group_jid` (bukan `pengirim_jid`)
- [x] 1.5 Insert `NomorWhatsapp` untuk grup: simpan `group_jid` (bukan nomor pengirim)

## 2. InboxWhatsapp - Dedup + Aggregasi

- [x] 2.1 Tambah helper `groupJidForChatRow()`: ekstrak group JID dari chat row
- [x] 2.2 Tambah helper `groupSiblingIds()`: cari semua TChat row via IdWahaTerdeteksi, NomorWhatsapp, dan payload
- [x] 2.3 Deduplikasi `loadInbox()`: grup tanpa mapping dikelompokkan 1 room per group JID
- [x] 2.4 `selectChat()`: ambil pesan dari semua sibling row (via `whereIn`)

## 3. InboxWhatsapp - Room Name Display

- [x] 3.1 Tambah helper `payloadContactName($payload)`: fallback nama private via `_data.notifyName` → `sender.pushname` → `notifyName` → `pushName`
- [x] 3.2 Tambah helper `wahaGroupName($groupJid)`: fetch nama grup dari WAHA `/api/{session}/groups/{id}`, cache 6 jam, encoding path literal `@`
- [x] 3.3 Update `formatChatRow()`: 
  - Private: `PrimaryName` = `NamaKontak` → `payloadContactName($payload)` → nomor
  - Grup: `PrimaryName` = `wahaGroupName($groupId)` → `NamaGrupWhatsapp` → group JID
  - Internal mode: tetap memakai master data (`NamaGrupMaster`, `NamaKontakMaster`) sebagai prioritas
- [x] 3.4 Update `messageSenderName()`: tambah `_data.notifyName` dan `payloadContactName($payload)` sebagai fallback

## 4. Validasi

- [x] 4.1 `php -l app/Services/Waha/WahaWebhookProcessor.php`
- [x] 4.2 `php -l app/Filament/Pages/InboxWhatsapp.php`
- [ ] 4.3 Private chat → tampil nama kontak/username, bukan nomor
- [ ] 4.4 Grup unmapped → tampil nama grup dari WAHA, bukan @g.us
- [ ] 4.5 Grup termapping → tampilan tidak regresi
- [ ] 4.6 Pesan grup dari semua anggota → 1 room, semua pesan tampil
- [ ] 4.7 Legacy grup lama → 1 room per grup, pesan tergabung

## 5. Realtime Group Room Consistency

- [x] 5.1 Perbarui `WahaWebhookProcessor::parseMessage()` agar kandidat `@g.us` diprioritaskan sebelum kandidat participant/private
- [x] 5.2 Perbarui `InboxWhatsapp::groupSiblingIds()` agar grup termapping, unmapped, dan legacy memakai canonical group JID yang sama
- [x] 5.3 Perbarui `InboxWhatsapp::handleInboxUpdate()` agar `chatId` event mempertahankan room grup aktif saat reload
- [x] 5.4 Tambahkan regresi test payload participant `@c.us` + group JID `@g.us`
- [x] 5.5 Tambahkan regresi test agregasi pesan sibling untuk grup termapping dan unmapped
- [x] 5.6 Jalankan lint PHP, `php vendor/bin/phpunit --filter "(InboxWhatsappTest|WahaWebhookProcessorTest)"`, `php vendor/bin/phpunit`, dan Pint targeted; seluruh test lulus, sedangkan Pint global masih menemukan pelanggaran formatting legacy di repository
- [ ] 5.7 Setelah deployment, restart worker queue `webhooks` dan `broadcasts`; tidak ada migration database
