# Proposal: AI Model Instruct & UI Improvements

**Status:** Draft  
**Tanggal:** 2026-06-29  
**Terkait Spec:** `.kiro/specs/ai-model-instruct-and-ui-improvements/`

---

## Ringkasan

Tiga kelompok perbaikan pada VPoint Care untuk meningkatkan fleksibilitas konfigurasi model AI dan kenyamanan antarmuka:

1. **Kelompok A — Model Instruct AI:** Tambah kolom `ModelInstructAi` di DB dan routing model ke `InternalChatbotService` agar mode Ringan VPoint Assistant menggunakan model yang berbeda dari auto-reply.

2. **Kelompok B — Bug Fix VPoint Assistant:** (a) Hapus shadow berlebih di area input; (b) Batasi max-height textarea ke 200px; (c) Fix suggested replies agar tidak muncul saat load history.

3. **Kelompok C — UI AI Agent:** Auto-grow textarea PromptSistem (min 120px) dan compact template pesan (min-h 80px, 2 kolom).

---

## Motivasi

- Model Utama yang dipakai untuk auto-reply WhatsApp biasanya model besar (lebih lambat, lebih mahal). VPoint Assistant untuk tim internal bisa menggunakan model yang lebih kecil/cepat untuk operasi ringan.
- Beberapa bug UI yang mengganggu pengalaman pengguna: shadow berlebih, textarea yang membesar terlalu besar, dan suggested replies yang muncul saat reload halaman.

---

## Dampak

### File yang Diubah

| File | Kelompok | Tipe |
|---|---|---|
| `database/migrations/2026_06_29_000001_add_model_instruct_to_ai_settings.php` | A | Konfirmasi (sudah ada) |
| `app/Filament/Pages/VPointAssistant.php` | B | Bug fix |
| `resources/views/filament/pages/vpoint-assistant.blade.php` | B | Bug fix |
| `resources/views/filament/pages/ai-agent.blade.php` | C | UI improvement |

### File yang Tidak Diubah

- `app/Services/Ai/InternalChatbotService.php` — sudah benar
- `app/Services/Ai/AiAutoReplyService.php` — tidak terpengaruh
- `app/Filament/Pages/AiAgent.php` — sudah benar
- `resources/lang/id/ui.php`, `resources/lang/en/ui.php` — sudah benar

---

## Risiko

| Risiko | Mitigasi |
|---|---|
| Migrasi pada DB production yang sudah punya kolom | Guard `COL_LENGTH` SQL Server mencegah error duplikat |
| `loadHistory()` fix mengubah perilaku saat ini | Perubahan minimal: hapus 4 baris; perilaku yang hilang adalah bug, bukan fitur |
| Auto-grow PromptSistem kehilangan resize handle | User masih bisa resize (Alpine.js mengikuti konten); alternatif: beri cap 500px |

---

## Keputusan Teknis

1. **Satu API call untuk suggested replies:** Tidak dibuat call terpisah untuk generate suggested replies menggunakan model instruct. Suggested replies dihasilkan dari response yang sama dengan main reply. Ini menjaga latensi dan biaya tetap rendah.

2. **`property_exists()` di getInstructModel():** Menggunakan `property_exists()` sebelum akses `ModelInstructAi` memastikan kode tidak crash jika settings object berasal dari cache yang dibuat sebelum migrasi dijalankan.

3. **Max-height 200px bukan 60vh:** 200px setara ~12-13 baris teks — cukup untuk pesan panjang namun tidak memenuhi layar. Ini mengikuti pola ChatGPT.
