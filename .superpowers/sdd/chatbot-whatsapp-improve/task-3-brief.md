### Task 3: Queue Sinkronisasi Identitas Chat

**Tujuan:** Mengisi snapshot nama/foto WAHA setelah webhook tersimpan, dengan dedupe, retry, timeout, dan fallback yang tidak menghapus snapshot terakhir.

**Files:**
- Create: `src/app/Jobs/SyncWahaChatIdentityJob.php`
- Modify: `src/app/Jobs/ProcessWebhookJob.php`
- Modify: `src/tests/Feature/Waha/WahaChatIdentitySyncTest.php`

**Interfaces:**
- `SyncWahaChatIdentityJob::dispatchDebounced(string $chatId): void`.
- Queue `webhooks`, `$tries = 3`, `$timeout = 30`, `backoff(): array` mengembalikan `[30, 120]`.
- Cache dedupe `waha:identity-sync:{chatId}` selama 60 detik.

- [ ] **Step 1: Tulis test merah job group dan LID**

Fixture grup memakai `NomorWhatsapp='120363111@g.us'` dan mapping stale/null; fake group info dan profile picture. Assert `NamaGrupWaha`, `UrlFotoProfil`, dan status sukses. Fixture personal memakai `IdWahaTerdeteksi='111@lid'`; fake LID resolver/contact info; assert LID tetap tersimpan dan nomor hasil resolusi masuk ke `NomorWhatsappTerdeteksi`.

- [ ] **Step 2: Implementasikan job**

Job mengambil `TChat` + `MSesiWhatsapp` + mapping group. Untuk grup, urutan raw ID hanya `TChat.NomorWhatsapp`, `TChat.IdWahaTerdeteksi`, payload raw group, lalu mapping `IdGrupWaha` bila valid dan berakhiran `@g.us`; participant tidak pernah menjadi avatar group ID. Untuk personal, gunakan `IdWahaTerdeteksi`, nomor terdeteksi, lalu nomor chat. Update `StatusIdentitasWaha=success|failed`, waktu percobaan, dan error maksimal 500 karakter. Saat WAHA gagal, pertahankan `Nama*Waha`, `UrlFotoProfil`, dan nomor terakhir.

- [ ] **Step 3: Dispatch setelah webhook sukses**

Di `ProcessWebhookJob::handle()`, setelah `SendBroadcastDebouncedJob::dispatchDebounced($chatId)` dan sebelum `ProcessAiAutoReplyJob`, panggil `SyncWahaChatIdentityJob::dispatchDebounced($chatId)`. Jangan dispatch untuk duplicate/ignored webhook.

- [ ] **Step 4: Jalankan test job**

```powershell
cd src
php -l app/Jobs/SyncWahaChatIdentityJob.php
php -l app/Jobs/ProcessWebhookJob.php
php artisan test --filter=WahaChatIdentitySyncTest
```

Expected: snapshot sukses, failure tersanitasi, queue/dedupe sesuai kontrak.

---

