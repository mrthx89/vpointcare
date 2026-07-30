### Task 8: Base64 Media Presentation

**Tujuan:** Jika media base64 berhasil dirender lewat media route, bubble tidak menampilkan teks base64; jika gagal konversi, tampilkan fallback diagnostik terbatas dan localized.

**Files:**
- Modify: `src/app/Support/WahaMediaPayload.php`
- Modify: `src/app/Filament/Pages/InboxWhatsapp.php`
- Modify: `src/app/Http/Controllers/WahaMediaController.php`
- Modify: `src/resources/views/filament/pages/inbox-whatsapp.blade.php`
- Modify: `src/resources/lang/id/ui.php`
- Modify: `src/resources/lang/en/ui.php`
- Modify: `src/tests/Unit/Support/WahaMediaPayloadTest.php`
- Modify: `src/tests/Feature/Http/Controllers/WahaMediaControllerTest.php`
- Modify: `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`

**Interfaces:**
- `WahaMediaPayload::inspectPayload()` mengembalikan metadata saja, tanpa decoded binary/base64.
- `WahaMediaPayload::fromPayloadJson()` mengembalikan contents hanya di controller.
- State message memiliki `HasRenderableMedia`, `MediaUrl`, `MediaCategory`, `ShowTextBody`, dan `Base64Fallback`.

- [ ] **Step 1: Tulis test merah media valid**

Insert message dengan `IsiPesan` base64 image dan payload media valid. Assert Blade memakai `/admin/waha-media/{message}` dan tidak menyertakan string base64 pada response.

- [ ] **Step 2: Tulis test merah media rusak**

Insert base64 rusak tanpa URL media valid. Assert fallback localized tampil dan tidak memuat `PayloadJson`, full base64, raw HTML, API key, token, atau stack trace.

- [ ] **Step 3: Tambahkan presentation state**

Di formatter Inbox, hitung `embeddedMedia`, `hasUrlMedia`, `hasRenderableMedia`, dan `isBase64Text` dengan `base64_decode($candidate, true)` serta ambang panjang minimal 80. Set `ShowTextBody=!($hasRenderableMedia && $isBase64Text)` dan `Base64Fallback=!$hasRenderableMedia && $isBase64Text`.

- [ ] **Step 4: Update render condition**

Render `IsiPesan` hanya saat `ShowTextBody`; render panel warning localized saat `Base64Fallback`; render image/sticker/audio/video/PDF/file melalui route controller saat media valid. State Livewire tidak boleh menyimpan decoded binary atau full payload JSON.

- [ ] **Step 5: Pertahankan controller sebagai binary boundary**

Controller harus memakai decode strict, header aman termasuk `X-Content-Type-Options: nosniff`, dan log hanya `message_id`, source, reason code, serta optional status HTTP.

- [ ] **Step 6: Jalankan media test**

```powershell
cd src
php -l app/Support/WahaMediaPayload.php
php -l app/Http/Controllers/WahaMediaController.php
php -l app/Filament/Pages/InboxWhatsapp.php
php artisan test --filter=WahaMediaPayloadTest
php artisan test --filter=WahaMediaControllerTest
php artisan test --filter=InboxWhatsappTest
```

Expected: base64 valid menjadi media tanpa text mentah; fallback rusak tetap aman dan localized.

---

