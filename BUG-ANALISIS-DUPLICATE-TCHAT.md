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

## Rekomendasi Perbaikan (Telah Diimplementasi)

### Fix 1: Perkaya query findOrCreateChat dengan fallback matching

```php
// SEBELUM:
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
// SEBELUM:
$query = DB::table('TChat')
    ->where('IdSesiWhatsapp', $sessionId)
    ->where('JenisChat', $parsed['jenis_chat']);

// SESUDAH:
$query = DB::table('TChat')
    ->where('JenisChat', $parsed['jenis_chat']);
```

### Fix 3: Tangani kasus pengirim_nomor = null dengan benar

```php
// Saat INSERT TChat baru tanpa nomor:
'NomorWhatsapp' => $parsed['pengirim_nomor'] ?: ($parsed['pengirim_jid'] ?? '-'),
```

### Fix 4: Menyesuaikan Logika ChatInitiationService

Pencarian duplikat chat di `ChatInitiationService` sudah sinkron dan kini memperhitungkan fallback ke `@c.us` format layaknya `WahaWebhookProcessor`.

```php
if (Schema::hasColumn('TChat', 'IdWahaTerdeteksi')) {
    $query->orWhere('IdWahaTerdeteksi', $number . '@c.us');
}
```

---
