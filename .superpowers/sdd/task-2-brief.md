### Task 2: Implementasikan Ekstraksi Data URI dan Base64 Strict

**Tujuan:** Membuat parser stateless yang menemukan kandidat media tanpa membawa Base64 ke Livewire dan mendekode konten hanya pada endpoint media.

**Dependency:** Task 1.

**Files:**
- Create: src/app/Support/WahaMediaPayload.php
- Modify: src/tests/Unit/Support/WahaMediaPayloadTest.php

**Interfaces:**
- Consumes: interface Task 1.
- Produces untuk Task 3-7: inspectPayload(), fromPayloadJson(), fromDataUri(), dan fromBinary() dengan return shape konsisten.

**Langkah implementasi:**

- [ ] **Step 1: Buat class final dan signature public yang tetap**

~~~php
<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class WahaMediaPayload
{
    public static function inspectPayload(
        ?string $payloadJson,
        ?string $declaredMime = null,
        ?string $declaredFileName = null,
        ?string $messageType = null,
    ): ?array;

    public static function fromPayloadJson(
        ?string $payloadJson,
        ?string $declaredMime = null,
        ?string $declaredFileName = null,
        ?string $messageType = null,
    ): ?array;

    public static function fromDataUri(
        string $dataUri,
        ?string $declaredMime = null,
        ?string $declaredFileName = null,
        ?string $messageType = null,
    ): ?array;

    public static function fromBinary(
        string $contents,
        ?string $declaredMime = null,
        ?string $payloadMime = null,
        ?string $declaredFileName = null,
        ?string $payloadFileName = null,
        ?string $messageType = null,
        string $source = 'binary',
    ): array;
}
~~~

Jangan membuat interface, DTO, factory, service provider, config, atau exception class baru. Satu helper array-based cukup untuk scope ini.

- [ ] **Step 2: Implementasikan candidate() private dengan urutan deterministik**

Urutan key: dataUrl, data_url, media.dataUrl, media.data_url, base64, media.base64, media.data, media.file, file.data, file.base64, data, file, body. Ambil metadata MIME dari media.mimetype, media.mimeType, media.contentType, mimetype, mimeType, contentType, file.mimetype, file.mimeType, dan _data.mimetype. Ambil filename dari media.filename, media.fileName, filename, fileName, file.filename, file.fileName, dan _data.filename.

Gunakan Arr::get(). Terima string non-kosong. Untuk data/file/body root, wajibkan konteks media. Jangan lakukan array_walk_recursive() terhadap seluruh payload.

- [ ] **Step 3: Implementasikan inspectPayload() tanpa decode binary**

inspectPayload() melakukan json_decode($payloadJson, true), memilih candidate, memisahkan header data URI bila ada, lalu memanggil fromBinary() dengan contents string kosong hanya untuk metadata. Return tidak boleh memiliki key contents, encoded, base64, payload, atau raw.

- [ ] **Step 4: Implementasikan fromPayloadJson() dan fromDataUri() dengan strict decode**

Normalisasi Base64 hanya dengan preg_replace('/\s+/', '', $encoded). Gunakan base64_decode($encoded, true). Return null untuk input kosong, decode false, atau contents kosong. Data URI harus cocok dengan pola data:<mime>;base64,<data>; data URI non-Base64 tetap mengikuti perilaku existing dengan rawurldecode agar tidak meregresi media lama.

- [ ] **Step 5: Jalankan unit test parser**

Run:

~~~powershell
cd src
php -l app/Support/WahaMediaPayload.php
php artisan test --filter=WahaMediaPayloadTest
~~~

Expected: syntax valid; test ekstraksi dan Base64 strict PASS. Test MIME/signature Task 3 belum ditambahkan.

---

### Task 3: Tambahkan MIME Detection, Filename Aman, dan Preview Allowlist
