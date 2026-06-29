# Tasks: AI Model Instruct & UI Improvements

Urutan implementasi yang direkomendasikan berdasarkan risiko dan dependensi.

---

## Kelompok A — Model Instruct AI (Konfirmasi)

- [ ] A1. Jalankan dan verifikasi migrasi `2026_06_29_000001` di environment dev
  - Pastikan kolom `ModelInstructAi nvarchar(100) NULL` berhasil dibuat
  - Jalankan dua kali, pastikan tidak ada error duplikat
- [ ] A2. Verifikasi `AiAgent.loadPengaturan()` — baca kolom dengan `Schema::hasColumn` guard
- [ ] A3. Verifikasi `AiAgent.simpanPengaturan()` — tulis kolom dengan `Schema::hasColumn` guard
- [ ] A4. Verifikasi `AiAgent.applyProviderPreset()` — isi `ModelInstructAi` hanya jika kosong
- [ ] A5. Verifikasi `InternalChatbotService.getAssistantModel()` — routing mode light/fast sudah benar
- [ ] A6. Verifikasi translation keys `primary_model`, `primary_model_help`, `instruct_model`, `instruct_model_help` ada di `id/ui.php` dan `en/ui.php`

---

## Kelompok B — Bug Fix VPoint Assistant

- [ ] B1. **`VPointAssistant.php`** — Fix `loadHistory()`: hapus blok `$latest` dan ganti dengan `$this->suggestedReplies = []`
  - File: `app/Filament/Pages/VPointAssistant.php`
  - Hapus baris: `$latest = collect(array_reverse($this->messages))->first(...);`
  - Hapus baris: `$this->suggestedReplies = is_array($latest) ? ... : [];`
  - Tambah: `$this->suggestedReplies = [];`

- [ ] B2. **`vpoint-assistant.blade.php`** — Hapus `shadow-sm` dari tombol submit
  - File: `resources/views/filament/pages/vpoint-assistant.blade.php`
  - Cari class `shadow-sm` pada tombol submit, hapus

- [ ] B3. **`vpoint-assistant.blade.php`** — Ganti max-height textarea ke 200px
  - `max-h-[60vh]` → `max-h-[200px]`
  - `Math.floor(window.innerHeight * 0.6)` → `200` (dua tempat: `x-on:input` dan `x-effect`)

- [ ] B4. **`vpoint-assistant.blade.php`** — Hapus/bersihkan blok `<style>` override shadow yang terlalu luas
  - Hapus selector `[class*="shadow-"]` dan `[class*="ring-"]` dari blok `<style>`
  - Pertahankan style untuk `.vpoint-ai-markdown` dan `[x-cloak]`

---

## Kelompok C — UI AI Agent

- [ ] C1. **`ai-agent.blade.php`** — Auto-grow textarea `PromptSistem`
  - Hapus `min-h-[220px]` dan `resize-y`
  - Tambah `x-on:input`, `x-effect` Alpine.js dengan batas 500px
  - Tambah `style="min-height: 120px;"` inline
  - Tambah `rows="1"` dan `overflow-y-auto`

- [ ] C2. **`ai-agent.blade.php`** — Compact template min-h-[80px]
  - Ganti `min-h-60` → `min-h-[80px]` di 4 textarea template
  - Pertahankan `resize-y` di semua textarea template

---

## Pengujian

- [ ] T1. Buka halaman AI Agent, isi PromptSistem panjang — pastikan auto-grow bekerja
- [ ] T2. Buka halaman AI Agent, lihat layout template dua kolom — pastikan tidak terlalu tinggi
- [ ] T3. Buka VPoint Assistant, kirim pesan — pastikan suggested replies muncul
- [ ] T4. Refresh halaman VPoint Assistant — pastikan suggested replies TIDAK muncul
- [ ] T5. Klik salah satu suggested reply — pastikan suggested replies hilang setelah klik
- [ ] T6. Ketik pesan panjang di VPoint Assistant — pastikan textarea tidak melebihi 200px
- [ ] T7. Cek area input VPoint Assistant — pastikan tidak ada shadow berlebih
- [ ] T8. Konfirmasi `AiAutoReplyService` masih berjalan normal (test koneksi di AI Agent)
