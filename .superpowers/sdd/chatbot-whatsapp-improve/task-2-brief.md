### Task 2: Adapter Metadata WAHA

**Tujuan:** Menyediakan satu adapter metadata kontak/grup yang bisa dipakai job tanpa fetch saat render Inbox.

**Files:**
- Modify: `src/app/Services/Waha/WahaSender.php`
- Modify: `src/tests/Feature/Waha/WahaChatIdentitySyncTest.php`

**Interfaces:**
- `getContactInfo(string $session, string $contactId): array` mengembalikan `ok`, `name`, `pushname`, `id`, `phone`, `status`, `error`.
- `getGroupInfo(string $session, string $groupId): array` mengembalikan `ok`, `name`, `id`, `status`, `error`.
- Reuse `getContactProfilePictureUrl()` dan `getPhoneNumberByLid()`.

- [ ] **Step 1: Tulis test merah parser response**

Gunakan `Http::fake()` untuk response kontak berisi `name`, `pushname`, `id` dan response grup berisi `subject`. Set `services.waha.base_url` ke `https://waha.test`; assert nama dan nomor ter-normalisasi.

- [ ] **Step 2: Jalankan test merah**

```powershell
cd src
php artisan test --filter=WahaChatIdentitySyncTest
```

Expected: fail karena `getContactInfo()` dan `getGroupInfo()` belum ada.

- [ ] **Step 3: Implementasikan adapter minimum**

Tambahkan method public di `WahaSender` yang memanggil helper GET existing, menormalisasi contact ID dengan `WahaChatHelper`, mengambil nama dari key `name`, `subject`, `pushname`, `shortName`, dan mengembalikan hanya field terproyeksi. Jangan mengembalikan body provider mentah. Untuk grup, endpoint memakai normalized raw `@g.us`; untuk personal `@lid`, pertahankan LID dan gunakan `phone` hasil response bila ada.

- [ ] **Step 4: Jalankan test adapter**

```powershell
cd src
php -l app/Services/Waha/WahaSender.php
php artisan test --filter=WahaChatIdentitySyncTest
```

Expected: adapter lulus dan `TLogIntegrasi` tidak mencatat secret.

---

