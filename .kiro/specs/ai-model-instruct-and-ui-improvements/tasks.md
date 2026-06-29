# Implementation Plan: AI Model Instruct & UI Improvements

## Overview

Implementasi dibagi menjadi empat kelompok berurutan:
- **Kelompok A** — Konfirmasi read-only: verifikasi migrasi dan `InternalChatbotService` sudah benar
- **Kelompok B** — Bug fix PHP: perbaiki `loadHistory()` di `VPointAssistant.php`
- **Kelompok C** — Bug fix & perbaikan UI: edit dua file Blade
- **Tests** — Unit test, property test, dan smoke test

---

## Tasks

- [ ] 1. Kelompok A — Konfirmasi Komponen yang Sudah Ada (Read-Only)
  - [-] 1.1 Verifikasi migrasi `2026_06_29_000001_add_model_instruct_to_ai_settings.php`
    - Buka file migrasi dan konfirmasi guard `COL_LENGTH` SQL Server sudah ada di `up()` dan `down()`
    - Pastikan migrasi sudah terdaftar di `database/migrations/` dan dapat dijalankan idempoten
    - Tidak ada perubahan kode — hanya konfirmasi
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [-] 1.2 Verifikasi `InternalChatbotService.php` — logika pemilihan model
    - Buka `app/Services/Ai/InternalChatbotService.php` dan konfirmasi tiga method sudah benar:
      - `getInstructModel()`: gunakan `ModelInstructAi`, fallback ke `getPrimaryModel()`
      - `getPrimaryModel()`: gunakan `ModelAi`, fallback ke `config("services.{provider}.model")`
      - `getAssistantModel()`: dispatch ke `getInstructModel()` jika `mode === 'light'`, ke `getPrimaryModel()` jika `fast`
    - Pastikan `callProvider()` menggunakan `getAssistantModel()` untuk mengambil model
    - Konfirmasi `property_exists()` check sebelum akses `ModelInstructAi` dan `ModelAi`
    - Tidak ada perubahan kode — hanya konfirmasi
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 12.1, 12.2, 12.3, 12.4, 12.5_

- [~] 2. Checkpoint A — Konfirmasi Kelompok A Selesai
  - Pastikan migrasi dan `InternalChatbotService` sudah terverifikasi. Tanyakan ke user jika ada keraguan.

- [ ] 3. Kelompok B — Bug Fix: `VPointAssistant.php` — `loadHistory()` Suggested Replies
  - [~] 3.1 Perbaiki method `loadHistory()` di `app/Filament/Pages/VPointAssistant.php`
    - Temukan blok di akhir `loadHistory()` yang mencari pesan asisten terakhir dengan `suggested_replies` non-kosong dari `$this->messages`, lalu mengisi `$this->suggestedReplies` dari data tersebut
    - Hapus seluruh blok `$latest = collect(array_reverse(...))...` dan baris `$this->suggestedReplies = ...` di akhir method
    - Ganti dengan satu baris: `$this->suggestedReplies = [];`
    - Pastikan body mapping `->map(function (object $row)` tidak diubah — field `suggested_replies` di dalam array pesan tetap ada (digunakan untuk tampilan history, bukan untuk mengisi `$suggestedReplies`)
    - _Requirements: 6.1, 6.2_

- [~] 4. Checkpoint B — Bug Fix PHP Selesai
  - Jalankan `php artisan test --filter VPointAssistantSuggestedRepliesTest` (jika test sudah ada). Pastikan tidak ada error sintaks di file PHP. Tanyakan ke user jika ada pertanyaan.

- [ ] 5. Kelompok C — Perbaikan UI: `vpoint-assistant.blade.php`
  - [~] 5.1 Hapus `shadow-sm` dari tombol submit di `resources/views/filament/pages/vpoint-assistant.blade.php`
    - Temukan elemen `<button type="submit" ...>` di area input bawah
    - Hapus kelas `shadow-sm` dari atribut `class` tombol tersebut
    - Jika ada blok `<style>` yang menggunakan selector agresif `[class*="shadow-"]` untuk override shadow, hapus juga blok `<style>` tersebut karena efeknya terlalu luas
    - _Requirements: 7.1, 7.2, 7.3_

  - [~] 5.2 Perbaiki `max-height` textarea input pesan di `vpoint-assistant.blade.php`
    - Temukan `<textarea>` input pesan (yang memiliki `wire:model="userMessage"`)
    - Ganti kelas `max-h-[60vh]` menjadi `max-h-[200px]`
    - Di atribut `x-on:input`, ganti `Math.floor(window.innerHeight * 0.6)` menjadi `200`
    - Di atribut `x-effect`, ganti `Math.floor(window.innerHeight * 0.6)` menjadi `200`
    - Pastikan atribut Alpine.js `x-on:input` dan `x-effect` lainnya tidak diubah
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 6. Kelompok C — Perbaikan UI: `ai-agent.blade.php`
  - [~] 6.1 Ubah textarea `PromptSistem` menjadi auto-grow di `resources/views/filament/pages/ai-agent.blade.php`
    - Temukan `<textarea wire:model="pengaturan.PromptSistem" ...>`
    - Hapus kelas `min-h-[220px]` dan `resize-y` dari atribut `class`
    - Tambahkan atribut `rows="1"` dan inline style `style="min-height: 120px;"`
    - Tambahkan `x-on:input="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight, 500)+'px'"`
    - Tambahkan `x-effect="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight, 500)+'px'"`
    - Tambahkan kelas `overflow-y-auto` agar scroll aktif saat konten melampaui batas atas
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

  - [~] 6.2 Kompres keempat textarea template menjadi `min-h-[80px]` di `ai-agent.blade.php`
    - Temukan keempat `<textarea>` yang memiliki kelas `min-h-60` (yaitu: `TemplateDiluarJamKerja`, `TemplateHariLibur`, `TemplateJamKerjaSapaan`, `TemplateFallback`)
    - Ganti `min-h-60` menjadi `min-h-[80px]` pada keempat textarea tersebut
    - Pastikan atribut `resize-y` dipertahankan pada keempat textarea
    - Pastikan container grid `lg:grid-cols-2` tidak berubah
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [~] 7. Checkpoint C — UI Selesai
  - Pastikan tidak ada error sintaks di kedua file Blade. Tanyakan ke user jika ada pertanyaan.

- [ ] 8. Tests — Unit & Property Tests
  - [~] 8.1 Buat `tests/Unit/InternalChatbotServiceModelSelectionTest.php`
    - Tulis test untuk mode `light` dengan `ModelInstructAi` terisi → menggunakan `ModelInstructAi`
    - Tulis test untuk mode `light` dengan `ModelInstructAi` kosong/null → fallback ke `ModelAi`
    - Tulis test untuk mode `fast` → selalu menggunakan `ModelAi`
    - Tulis test untuk mode `fast` dengan `ModelAi` kosong → menggunakan config default
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 12.1, 12.2, 12.3, 12.4_

  - [~] 8.2 Buat property test: `getAssistantModel` selalu mengembalikan string non-kosong
    - **Property 1: getAssistantModel() selalu mengembalikan string non-kosong**
    - Generate kombinasi acak: `ModelInstructAi` ∈ {null, '', string_valid}, `ModelAi` ∈ {null, '', string_valid}, `mode` ∈ {'light', 'fast'}, `ProviderAi` ∈ {'OpenAI', 'DeepSeek', 'OpenRouter', '9Router'}
    - Assert: `strlen(getAssistantModel(settings, mode)) > 0` untuk semua kombinasi
    - Minimum 100 iterasi
    - **Validates: Requirements 12.5, 2.1, 2.2, 2.3, 2.4**

  - [~] 8.3 Buat property test: mode menentukan sumber model secara konsisten
    - **Property 2: Mode menentukan sumber model secara konsisten**
    - Generate: `ModelInstructAi` = string_non_kosong ≠ `ModelAi`
    - Assert: `getAssistantModel(settings, 'light') === ModelInstructAi`
    - Assert: `getAssistantModel(settings, 'fast') === ModelAi`
    - Generate: `ModelInstructAi` kosong atau null
    - Assert: hasil `'light'` sama dengan hasil `'fast'`
    - Minimum 100 iterasi
    - **Validates: Requirements 2.1, 2.2, 2.3, 12.1, 12.2**

  - [~] 8.4 Buat `tests/Unit/VPointAssistantSuggestedRepliesTest.php`
    - Tulis test: `loadHistory()` menghasilkan `$suggestedReplies === []` meski history punya `suggested_replies` non-kosong
    - Tulis test: `useSuggestedReply()` mereset `$suggestedReplies` ke `[]`
    - Tulis test: `sendMessage()` sukses mengisi `$suggestedReplies` dari respons AI
    - Tulis test: `sendMessage()` error menghasilkan `$suggestedReplies === []`
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

  - [~] 8.5 Buat property test: `loadHistory()` tidak pernah mengisi `suggestedReplies`
    - **Property 4: loadHistory tidak mengisi suggestedReplies**
    - Generate riwayat percakapan acak dengan 0–N pesan, setiap pesan asisten memiliki `suggested_replies` ∈ {[], ['opsi1'], ['opsi1','opsi2','opsi3']}
    - Assert: `$suggestedReplies === []` setelah `loadHistory()` untuk semua kombinasi input
    - Minimum 100 iterasi
    - **Validates: Requirements 6.1, 6.2**

  - [~] 8.6 Buat property test: `useSuggestedReply()` selalu mereset ke array kosong
    - **Property 5: useSuggestedReply() selalu mereset suggestedReplies**
    - Generate `$reply` ∈ string_arbitrary (termasuk unicode, kosong, sangat panjang)
    - Assert: `$suggestedReplies === []` setelah `useSuggestedReply($reply)` untuk semua nilai `$reply`
    - Minimum 100 iterasi
    - **Validates: Requirements 6.4, 6.5**

  - [~] 8.7 Buat `tests/Unit/AiAgentTranslationKeysTest.php`
    - Tulis test: semua key `primary_model`, `primary_model_help`, `instruct_model`, `instruct_model_help` ada di `resources/lang/id/ui.php` bagian `pages.ai_agent`
    - Tulis test: key yang sama ada di `resources/lang/en/ui.php`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [~] 8.8 Buat property test: simetri translation keys antar dua bahasa
    - **Property 6: Simetri translation keys antar dua bahasa**
    - Baca semua key dari bagian `pages.ai_agent` di `id/ui.php` dan `en/ui.php`
    - Assert: symmetric difference antara dua set key tersebut harus kosong
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5**

  - [~] 8.9 Buat smoke test Blade: `tests/Feature/VPointAssistantBladeSmoke.php`
    - Render halaman VPoint Assistant, assert tombol submit tidak punya kelas `shadow-sm`
    - Assert textarea input punya kelas `max-h-[200px]`
    - Assert Alpine.js directive menggunakan batas `200` bukan nilai `window.innerHeight`
    - _Requirements: 7.1, 7.2, 8.2, 8.4_

  - [~] 8.10 Buat smoke test Blade: `tests/Feature/AiAgentBladeSmoke.php`
    - Render halaman AI Agent, assert field `ModelInstructAi` ada di halaman
    - Assert `<textarea>` PromptSistem tidak punya kelas `min-h-[220px]` dan punya Alpine.js auto-grow directive
    - Assert keempat textarea template punya kelas `min-h-[80px]`
    - _Requirements: 9.1, 9.2, 9.3, 10.2_

- [~] 9. Checkpoint Final — Semua Tests Lulus
  - Jalankan `php artisan test` dan pastikan semua test lulus. Tanyakan ke user jika ada pertanyaan.

---

## Notes

- Task bertanda `*` bersifat opsional dan dapat dilewati untuk rilis MVP yang lebih cepat
- Setiap task merujuk ke requirement spesifik untuk keterlacakan
- Kelompok A tidak menghasilkan perubahan kode — hanya verifikasi bahwa kode yang sudah ada sudah benar
- Kelompok B dan C adalah perubahan minimal (surgical) — jangan refactor kode di luar scope task
- Property test memerlukan library PBT PHP seperti [`eris/eris`](https://github.com/giorgiosironi/eris); pastikan dependensi tersedia sebelum mengerjakan task `8.2`, `8.3`, `8.5`, `8.6`, `8.8`

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["3.1"] },
    { "id": 2, "tasks": ["5.1", "5.2", "6.1", "6.2"] },
    { "id": 3, "tasks": ["8.1", "8.4", "8.7", "8.9", "8.10"] },
    { "id": 4, "tasks": ["8.2", "8.3", "8.5", "8.6", "8.8"] }
  ]
}
```
