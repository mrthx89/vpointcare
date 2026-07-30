### Task 10: Localization dan OpenSpec Task Sync

**Tujuan:** Semua string user-facing tersedia di Bahasa Indonesia/Inggris, dan checkbox OpenSpec hanya mencerminkan pekerjaan yang benar-benar selesai.

**Files:**
- Modify: `src/resources/lang/id/ui.php`
- Modify: `src/resources/lang/en/ui.php`
- Modify: `openspec/changes/chatbot-whatsappasli-improve/tasks.md`

**Interfaces:**
- Key baru berada di subtree existing `ui.pages.inbox` dan `ui.pages.ai_agent`.
- Tidak ada string baru yang hanya tersedia satu bahasa.

- [ ] **Step 1: Tambahkan key Inbox**

Tambahkan pasangan `id`/`en` untuk status sync identity, group/personal badge, identifier, participant avatar alt/fallback, refresh metadata, dan media base64 unavailable. Gunakan copy singkat dan actionable; jangan menampilkan error provider mentah.

- [ ] **Step 2: Tambahkan key AI Agent**

Tambahkan `all_session`, `idle_session_limit_minutes`, dan helper text yang menjelaskan: All Session aktif menjawab setiap incoming eligible; jika off, AI menjawab first/idle sesuai batas menit.

- [ ] **Step 3: Centang task OpenSpec yang sudah verified**

Edit `openspec/changes/chatbot-whatsappasli-improve/tasks.md` setelah masing-masing command lulus. Jangan centang migration deployment/manual verification bila belum dilakukan pada environment target.

- [ ] **Step 4: Jalankan syntax localization**

```powershell
cd src
php -l resources/lang/id/ui.php
php -l resources/lang/en/ui.php
```

Expected: no syntax errors dan semua key yang dipakai Blade/PHP tersedia di dua locale.

---

