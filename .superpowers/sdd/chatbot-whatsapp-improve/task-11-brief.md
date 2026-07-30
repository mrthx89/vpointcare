### Task 11: Validasi Terpadu dan Deployment

**Tujuan:** Membuktikan implementasi sesuai OpenSpec dan menyediakan langkah deployment aman.

**Files:**
- Modify: `openspec/changes/chatbot-whatsappasli-improve/tasks.md`
- Reference: `deploy-update-server.bat`

**Interfaces:**
- Final handoff mencatat command yang dijalankan, hasil aktual, action migration/restart, risiko, dan task tersisa.

- [ ] **Step 1: Jalankan PHP lint seluruh file berubah**

```powershell
cd src
php -l app/Services/Waha/WahaSender.php
php -l app/Services/Waha/WahaWebhookProcessor.php
php -l app/Jobs/SyncWahaChatIdentityJob.php
php -l app/Jobs/ProcessWebhookJob.php
php -l app/Services/Ai/AiAutoReplyService.php
php -l app/Jobs/ProcessAiAutoReplyJob.php
php -l app/Filament/Pages/InboxWhatsapp.php
php -l app/Filament/Pages/AiAgent.php
php -l app/Support/WahaMediaPayload.php
php -l app/Http/Controllers/WahaMediaController.php
php -l app/Support/FilamentBreadcrumbs.php
php -l app/Filament/Concerns/HasMenuBreadcrumbs.php
```

Expected: semua file melaporkan `No syntax errors detected`.

- [ ] **Step 2: Jalankan targeted test**

```powershell
cd src
php artisan test --filter=WahaChatIdentitySyncTest
php artisan test --filter=WahaWebhookGroupIngestionTest
php artisan test --filter=InboxWhatsappTest
php artisan test --filter=AiAutoReplySessionPolicyTest
php artisan test --filter=WahaMediaPayloadTest
php artisan test --filter=WahaMediaControllerTest
php artisan test --filter=TicketingBreadcrumbTest
```

Expected: seluruh targeted test lulus.

- [ ] **Step 3: Jalankan broader validation**

```powershell
cd src
php artisan test
vendor/bin/pint --test
npm run build
```

Expected: full test, Pint check, dan Vite build lulus. Jangan memperbaiki kegagalan unrelated; catat terpisah.

- [ ] **Step 4: Validasi OpenSpec dan diff**

```powershell
cd ..
openspec validate chatbot-whatsappasli-improve --strict
git diff --check
```

Expected: OpenSpec valid, `git diff --check` exit code 0.

- [ ] **Step 5: Verifikasi migration SQL Server**

Pada environment staging/target yang memakai `sqlsrv`, backup database lalu jalankan:

```powershell
cd src
php artisan migrate --force
```

Expected: migration menambah kolom sekali, aman bila dijalankan ulang melalui status migration, existing rows tetap utuh, default AI idle menjadi 60.

- [ ] **Step 6: Restart runtime**

Setelah deploy:

```powershell
cd src
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Pastikan process manager menjalankan queue `webhooks`, `broadcasts`, dan queue AI existing. Restart Reverb hanya sesuai deployment process existing; tidak ada queue baru.

- [ ] **Step 7: Manual browser verification**

```text
1. WhatsAppAsli menampilkan badge Grup/Pribadi, nama snapshot, @g.us, dan @lid plus nomor hasil resolusi bila ada.
2. Dua participant grup masuk ke satu conversation; nama dan avatar participant tampil pada bubble.
3. Avatar conversation grup tidak berubah menjadi foto sender terakhir.
4. All Session on membalas setiap incoming eligible; off membalas first/idle dan skip active session.
5. Media base64 valid tampil sebagai preview/download tanpa teks base64; fallback rusak tetap aman.
6. Empat route ticketing menampilkan breadcrumb yang benar pada locale id dan en.
```

- [ ] **Step 8: Final task sync**

Centang task OpenSpec sesuai bukti validasi. Sisakan task yang memerlukan production/staging bila environment lokal tidak tersedia, lalu jelaskan di final report.

---

## Self-Review

**Spec coverage:**
- WhatsAppAsli database snapshot: Tasks 1, 2, 3, 5, 10.
- Group/personal identity dan `@g.us`/`@lid`: Tasks 3, 4, 5.
- Missing group chats dari banyak participant: Task 4.
- Group profile photo salah: Tasks 3 dan 5.
- Participant profile photo: Task 6.
- AI no-reply dan All Session/idle: Task 7.
- Base64 media presentation: Task 8.
- Ticketing breadcrumbs: Task 9.
- Localization, security, observability, deployment: Tasks 7, 8, 10, 11.

**Placeholder scan:** Tidak ada marker placeholder atau langkah implementasi terbuka. Setiap task memiliki file, interface, test gate, command, dan expected result.

**Type consistency:** Nama kolom, job, method, queue, route, permission, dan setting mengikuti OpenSpec serta source yang diperiksa pada 30 Juli 2026.

## Execution Options

Plan complete and saved to `docs/superpowers/plans/2026-07-30-chatbot-whatsapp-improve.md`.

1. **Subagent-Driven (recommended)** â€” fresh worker per task dan review di antara task.
2. **Inline Execution** â€” eksekusi di session ini dengan checkpoint.

Jangan implementasikan sebelum pengguna memilih dan menyetujui opsi eksekusi.
