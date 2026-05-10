# Bug Analysis: Duplicate TChat Saat Menerima Pesan dari WhatsApp ID yang Sama

## Ringkasan Masalah

Ketika menerima pesan dari nomor/WhatsApp ID yang sama, sistem membuat record `TChat` baru
padahal sudah ada session chat yang belum ditutup (status bukan `DITUTUP`). Akibatnya chat
terlihat dobel di halaman Inbox.

---

## Alur Kode Saat Ini

```
WahaWebhookController::__invoke()
  +-- WahaWebhookProcessor::process()           <-- dibungkus DB::transaction
       |-- parseMessage()                        <-- extract pengirim_jid, pengirim_nomor, jenis_chat
       |-- resolveLidPhoneNumber()                <-- konversi @lid -> nomor telepon
       |-- duplicateMessage()                    <-- cek TChatD.IdPesanWaha (message-level dedup)
       |-- resolveCustomerMapping()               <-- cari MNomorWhatsapp / MGrupWhatsapp
       +-- findOrCreateChat(sessionId, parsed, mapping)
            |-- Cari TChat yang cocok
            |-- Jika ditemukan DAN status != DITUTUP -> update dan return existing Id
            +-- Jika TIDAK ditemukan ATAU status = DITUTUP -> INSERT TChat baru
```

---

## Akar Masalah (Root Causes)

### Bug 1: findOrCreateChat memfilter IdSesiWhatsapp terlalu ketat

**File:** `src/app/Services/Waha/WahaWebhookProcessor.php` baris 531

```php
$query = DB::table('TChat')
    ->where('IdSesiWhatsapp', $sessionId)   // <-- MASALAH
    ->where('JenisChat', $parsed['jenis_chat']);
```

Query mencari TChat hanya di session WAHA yang sama. Jika WAHA di-restart dan kode sesi
berubah (misalnya record MSesiWhatsapp baru dibuat karena ada perbedaan kecil),
maka sessionId berbeda dan chat lama tidak ditemukan sehingga INSERT baru.

Namun ini bukan penyebab utama jika kode sesi selalu sama. Lanjut ke bug berikutnya.

---

### Bug 2: Query lookup NomorWhatsapp tidak mencocokkan NomorWhatsappTerdeteksi

**File:** `src/app/Services/Waha/WahaWebhookProcessor.php` baris 536-542

```php
$query->where(function ($query) use ($parsed): void {
    $query->where('NomorWhatsapp', $parsed['pengirim_nomor'] ?: '-');

    if (Schema::hasColumn('TChat', 'IdWahaTerdeteksi') && ($parsed['pengirim_jid'] ?? null)) {
        $query->orWhere('IdWahaTerdeteksi', $parsed['pengirim_jid']);
    }
});
```

Masalahnya:
- Kolom NomorWhatsappTerdeteksi **tidak dicek** di sini, padahal ChatInitiationService
  menyimpan nomor di kolom itu.
- Jika chat dibuat oleh CS melalui ChatInitiationService::createChat(), nomor disimpan
  di NomorWhatsapp **dan** NomorWhatsappTerdeteksi. Tapi saat webhook masuk, format
  nomor bisa sedikit beda sehingga WHERE NomorWhatsapp tidak cocok.

---

### Bug 3: pengirim_nomor bisa null untuk pengirim @lid yang gagal resolve

**File:** `src/app/Services/Waha/WahaWebhookProcessor.php` baris 264 dan 608-621

```php
// parseMessage:
'pengirim_nomor' => $this->normalisasiNomorWhatsapp($senderJid),

// normalisasiNomorWhatsapp:
if (str_contains($nomor, '@lid')) {
    return null;   // <-- return null jika @lid!
}
```

Jika resolveLidPhoneNumber() gagal (WAHA API error/timeout), maka pengirim_nomor
tetap null. Akibatnya:

1. Di findOrCreateChat, query menjadi WHERE NomorWhatsapp = '-' (karena null ?: '-')
2. Semua chat @lid yang gagal resolve akan match ke nomor '-' atau jika tidak ada
   yang ber-NomorWhatsapp = '-', maka INSERT baru.
3. Kali berikutnya resolve berhasil, pengirim_nomor punya nilai, tidak cocok dengan
   chat lama yang NomorWhatsapp = '-', INSERT baru lagi. DOBEL!

Ini adalah **penyebab paling umum dari bug duplicate chat**.

---

### Bug 4: Tidak ada pencocokan fallback via IdWahaTerdeteksi saat format JID beda

Meskipun ada orWhere('IdWahaTerdeteksi', $parsed['pengirim_jid']), field pengirim_jid
untuk pengirim @lid bisa berupa 123456789@lid sedangkan TChat yang sudah ada menyimpan
IdWahaTerdeteksi = '6281234567890@c.us' (karena pada saat pembuatan, resolve berhasil dan
di-update ke format @c.us oleh baris 560).

Kedua JID ini (@lid vs @c.us) **tidak pernah cocok**, sehingga orWhere juga gagal.

---

### Bug 5: Race condition -- concurrent webhook tanpa row-level locking

**File:** `src/app/Services/Waha/WahaWebhookProcessor.php` baris 23

```php
return DB::transaction(function () use ($payload): array {
```

DB::transaction pada SQL Server default isolation level (READ COMMITTED) **tidak
mencegah** dua request concurrent membaca TChat yang sama, keduanya menemukan belum ada
yang cocok, lalu keduanya INSERT menghasilkan 2 chat baru.

WAHA bisa mengirim webhook beruntun sangat cepat (misal: pesan teks + media dalam <100ms).

---

## Perbandingan: ChatInitiationService vs WahaWebhookProcessor

| Aspek | ChatInitiationService | WahaWebhookProcessor |
|---|---|---|
| Filter session | Tidak filter by session | Filter by IdSesiWhatsapp |
| Cek NomorWhatsapp | Ya | Ya |
| Cek NomorWhatsappTerdeteksi | Ya (orWhere) | Tidak |
| Cek IdNomorWhatsapp | Ya (orWhere) | Tidak |
| Cek IdWahaTerdeteksi | Tidak | Ya (orWhere) |
| Exclude status DITUTUP | Di WHERE clause | Di PHP if-check |
| Race condition guard | Tidak | Tidak |

Kedua service punya logika pencarian yang **berbeda**, sehingga chat yang dibuat oleh salah satu
bisa tidak ditemukan oleh yang lain.

---

## Skenario Reproduksi Bug

### Skenario A: @lid gagal resolve lalu berhasil
1. Pesan pertama masuk dari 123@lid, WAHA API timeout, pengirim_nomor = null
2. findOrCreateChat mencari NomorWhatsapp = '-', tidak ada, INSERT TChat #1 (NomorWhatsapp = '-')
3. Pesan kedua masuk dari 123@lid, WAHA API berhasil, pengirim_nomor = '6281234567890'
4. findOrCreateChat mencari NomorWhatsapp = '6281234567890', tidak cocok TChat #1, INSERT TChat #2
5. **Hasil: 2 chat untuk orang yang sama.**

### Skenario B: CS mulai chat, lalu customer balas
1. CS memulai chat via ChatInitiationService, TChat dibuat (NomorWhatsapp = '6281234567890',
   IdSesiWhatsapp = UUID-A)
2. Customer balas, webhook masuk, WahaWebhookProcessor jalan
3. Session code resolve ke MSesiWhatsapp record yang berbeda (IdSesiWhatsapp = UUID-B)
4. findOrCreateChat filter WHERE IdSesiWhatsapp = UUID-B, tidak menemukan TChat dari step 1
5. INSERT TChat baru. **DOBEL!**

### Skenario C: Race condition
1. Dua webhook untuk pengirim yang sama tiba hampir bersamaan
2. Keduanya menjalankan findOrCreateChat secara parallel
3. Keduanya menemukan belum ada TChat, keduanya INSERT. **DOBEL!**

---

## Rekomendasi Perbaikan

### Fix 1: Perkaya query findOrCreateChat dengan fallback matching

```php
// SEBELUM (baris 536-542):
$query->where(function ($query) use ($parsed): void {
    $query->where('NomorWhatsapp', $parsed['pengirim_nomor'] ?: '-');
    if (Schema::hasColumn('TChat', 'IdWahaTerdeteksi') && ($parsed['pengirim_jid'] ?? null)) {
        $query->orWhere('IdWahaTerdeteksi', $parsed['pengirim_jid']);
    }
});

// SESUDAH:
$query->where(function ($query) use ($parsed): void {
    if ($parsed['pengirim_nomor']) {
        $query->where('NomorWhatsapp', $parsed['pengirim_nomor']);

        if (Schema::hasColumn('TChat', 'NomorWhatsappTerdeteksi')) {
            $query->orWhere('NomorWhatsappTerdeteksi', $parsed['pengirim_nomor']);
        }
    }

    if (Schema::hasColumn('TChat', 'IdWahaTerdeteksi') && ($parsed['pengirim_jid'] ?? null)) {
        $query->orWhere('IdWahaTerdeteksi', $parsed['pengirim_jid']);

        // Juga cocokkan versi @c.us jika pengirim_jid berformat @lid
        if ($parsed['pengirim_nomor'] && str_contains($parsed['pengirim_jid'], '@lid')) {
            $query->orWhere('IdWahaTerdeteksi', $parsed['pengirim_nomor'] . '@c.us');
        }
    }
});
```

### Fix 2: Hapus/longgarkan filter IdSesiWhatsapp

```php
// SEBELUM (baris 531):
$query = DB::table('TChat')
    ->where('IdSesiWhatsapp', $sessionId)
    ->where('JenisChat', $parsed['jenis_chat']);

// SESUDAH -- jangan filter by session, cukup filter by JenisChat:
$query = DB::table('TChat')
    ->where('JenisChat', $parsed['jenis_chat']);
```

Atau alternatif yang lebih aman -- filter by session tapi fallback jika tidak ketemu:

```php
$chat = $this->findChatByIdentifiers($sessionId, $parsed);
if (! $chat) {
    $chat = $this->findChatByIdentifiers(null, $parsed);
}
```

### Fix 3: Tangani kasus pengirim_nomor = null dengan benar

Jangan pernah menyimpan NomorWhatsapp = '-'. Gunakan pengirim_jid sebagai identifier
utama jika nomor tidak tersedia:

```php
// Di findOrCreateChat, jika pengirim_nomor null tapi pengirim_jid ada:
if (! $parsed['pengirim_nomor'] && ($parsed['pengirim_jid'] ?? null)) {
    $query->where(function ($q) use ($parsed) {
        if (Schema::hasColumn('TChat', 'IdWahaTerdeteksi')) {
            $q->where('IdWahaTerdeteksi', $parsed['pengirim_jid']);
        }
    });
}
```

Dan saat INSERT TChat baru tanpa nomor:
```php
'NomorWhatsapp' => $parsed['pengirim_nomor'] ?: ($parsed['pengirim_jid'] ?? '-'),
```

### Fix 4: Tambahkan guard race condition

Opsi A -- Gunakan advisory lock (Cache lock):
```php
$lockKey = 'waha_chat_' . md5(($parsed['pengirim_jid'] ?? '') . '|' . ($parsed['pengirim_nomor'] ?? ''));
return Cache::lock($lockKey, 10)->block(5, function () use (...) {
    return DB::transaction(function () use (...) {
        // existing logic
    });
});
```

Opsi B -- Unique constraint pada tabel TChat:
```sql
-- Unique filtered index (SQL Server)
CREATE UNIQUE INDEX UX_TChat_NomorAktif
ON TChat (NomorWhatsapp)
WHERE IdStatusChat != '<GUID-DITUTUP>' AND JenisChat = 'Pribadi';
```

### Fix 5: Sinkronkan logika antara ChatInitiationService dan WahaWebhookProcessor

Buat satu method/service tunggal findOpenChat() yang digunakan oleh kedua service,
agar logika pencarian selalu konsisten:

```php
class ChatLookupService
{
    public function findOpenChat(
        string $jenisChat,
        ?string $nomor,
        ?string $jid,
        ?string $nomorWhatsappId
    ): ?object {
        $statusDitutupId = DB::table('MStatusChat')
            ->where('KodeStatusChat', 'DITUTUP')->value('Id');

        $query = DB::table('TChat')->where('JenisChat', $jenisChat);

        $query->where(function ($q) use ($nomor, $jid, $nomorWhatsappId) {
            if ($nomor) {
                $q->where('NomorWhatsapp', $nomor);
                if (Schema::hasColumn('TChat', 'NomorWhatsappTerdeteksi')) {
                    $q->orWhere('NomorWhatsappTerdeteksi', $nomor);
                }
            }
            if ($jid && Schema::hasColumn('TChat', 'IdWahaTerdeteksi')) {
                $q->orWhere('IdWahaTerdeteksi', $jid);
                if ($nomor && str_contains($jid, '@lid')) {
                    $q->orWhere('IdWahaTerdeteksi', $nomor . '@c.us');
                }
            }
            if ($nomorWhatsappId) {
                $q->orWhere('IdNomorWhatsapp', $nomorWhatsappId);
            }
        });

        if ($statusDitutupId) {
            $query->where(function ($q) use ($statusDitutupId) {
                $q->where('IdStatusChat', '!=', $statusDitutupId)
                  ->orWhereNull('IdStatusChat');
            });
        }

        return $query->orderByDesc('TglChatTerakhir')->first();
    }
}
```

---

## Prioritas Implementasi

| No | Fix | Dampak | Effort |
|----|-----|--------|--------|
| 1 | Fix 1 -- Tambah NomorWhatsappTerdeteksi + @c.us fallback | Tinggi | Rendah |
| 2 | Fix 3 -- Handle pengirim_nomor = null | Tinggi | Rendah |
| 3 | Fix 2 dan 5 -- Sinkronkan logika lookup | Sedang | Sedang |
| 4 | Fix 4 -- Race condition guard | Sedang | Sedang |
| 5 | Fix 2 alt -- Longgarkan filter session | Rendah | Rendah |

---

## File yang Perlu Diubah

1. `src/app/Services/Waha/WahaWebhookProcessor.php` -- method findOrCreateChat() (utama)
2. `src/app/Services/Chat/ChatInitiationService.php` -- method findActiveChat() (sinkronisasi)
3. (Opsional) Buat `src/app/Services/Chat/ChatLookupService.php` -- shared lookup logic

---

## Catatan Tambahan

- Kolom NomorWhatsappTerdeteksi dan IdWahaTerdeteksi menggunakan Schema::hasColumn()
  check, artinya kolom ini mungkin belum ada di semua environment. Pastikan migration
  sudah dijalankan sebelum deploy fix.
- Data existing yang sudah dobel perlu di-merge secara manual atau dengan script migrasi
  (pindahkan TChatD dari chat duplikat ke chat utama, lalu soft-delete/tutup duplikatnya).
