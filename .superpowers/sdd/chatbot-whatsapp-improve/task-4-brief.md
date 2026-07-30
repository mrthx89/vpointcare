### Task 4: Root Cause Ingestion GroupWhatsApp

**Tujuan:** Semua participant pada grup masuk ke satu `TChat` berdasarkan sesi + raw `group_jid @g.us`, bukan berdasarkan sender pertama atau mapping stale.

**Files:**
- Modify: `src/app/Services/Waha/WahaWebhookProcessor.php`
- Create: `src/tests/Feature/Waha/WahaWebhookGroupIngestionTest.php`

**Interfaces:**
- `parseMessage()` mempertahankan `group_jid` normalized `@g.us`.
- `findOrCreateChat(string $sessionId, array $parsed, array $mapping): string` mencari group berdasarkan `IdSesiWhatsapp`, `JenisChat='Grup'`, dan raw group JID.
- `TChatD.PengirimIdWaha` menyimpan participant JID; `PengirimNomorWhatsapp` tetap menyimpan nomor ternormalisasi.

- [ ] **Step 1: Tulis test merah dua participant**

Proses dua payload dengan `120363111@g.us`, participant `628111@c.us` dan `628222@c.us`. Assert `ok`, `chat_id` sama, satu `TChat` grup, dua `TChatD`, `TChat.NomorWhatsapp='120363111@g.us'`, dan dua `PengirimIdWaha` berbeda.

- [ ] **Step 2: Perbaiki parser raw group**

Normalisasi remote ID dengan `WahaChatHelper::normalizeChatId()`. Tandai grup bila hasil berakhiran `@g.us` atau flag `isGroup` aktif; set `group_jid=$remoteId` dan `senderJid=$participant` untuk grup. Jangan memakai participant sebagai remote chat ID.

- [ ] **Step 3: Perbaiki query `findOrCreateChat()`**

Scope query dengan `IdSesiWhatsapp`. Untuk grup, cari `NomorWhatsapp=$parsed['group_jid']` atau `IdWahaTerdeteksi=$parsed['group_jid']`, baru fallback `IdGrupWhatsapp`. Saat insert/update, `NomorWhatsapp` dan `IdWahaTerdeteksi` grup selalu memakai `group_jid`; personal tetap memakai sender.

- [ ] **Step 4: Simpan participant detail**

Saat `$chatMessage` dibuat, jika `SchemaCache::hasColumn('TChatD', 'PengirimIdWaha')`, isi dengan `parsed['pengirim_jid']`. Pertahankan `PengirimNamaKontak`, nomor, dan payload existing.

- [ ] **Step 5: Jalankan test ingestion**

```powershell
cd src
php -l app/Services/Waha/WahaWebhookProcessor.php
php artisan test --filter=WahaWebhookGroupIngestionTest
```

Expected: dua participant menghasilkan satu chat grup dan dua detail pesan.

---

