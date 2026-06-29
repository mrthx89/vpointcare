# Change: Tambahkan Model Instruct untuk VPoint Assistant dan Inbox WhatsApp

## Summary

Tambahkan kolom **ModelInstructAi** pada pengaturan AI Agent untuk memisahkan model yang dipakai untuk auto-reply customer (menggunakan Model Utama / `ModelAi` existing) dengan model yang dipakai untuk internal assistant seperti VPoint Assistant dan opsi jawaban ringan WhatsApp (menggunakan `ModelInstructAi` baru).

Perubahan ini bertujuan untuk menghemat biaya API dan mempercepat response waktu untuk use case internal, tanpa mengorbankan kualitas balasan customer-facing.

## Goals

- Menambahkan kolom `ModelInstructAi` pada tabel `MPengaturanAi` dengan aman (idempotent).
- Memisahkan konfigurasi model utama (untuk auto-reply customer) dan model instruct (untuk VPoint Assistant dan opsi jawaban ringan WhatsApp).
- Menambahkan UI field baru di halaman AI Agent untuk mengisi `ModelInstructAi` dengan label dan help text yang jelas.
- Memperbarui `InternalChatbotService` agar memilih model dengan urutan: `ModelInstructAi` → `ModelAi` → config provider default.
- Memastikan `AiAutoReplyService` **tidak berubah** dan tetap menggunakan `ModelAi` existing untuk balasan customer.
- Menjaga kompatibilitas semua provider (OpenAI, DeepSeek, OpenRouter, 9Router/NineRouter).
- Menjaga fail-safe: jika kolom `ModelInstructAi` belum ada atau kosong, sistem fallback ke `ModelAi`.
- Memperbaiki tiga bug UI di VPoint Assistant:
  1. Menghilangkan shadow pada area bawah (input container).
  2. Memberikan max-height dan padding yang lebih baik pada textarea input.
  3. Membuat opsi suggested reply menghilang setelah dipilih.
- Memastikan semua teks UI baru menggunakan translation key di `id` dan `en`, bukan hardcoded.

## Non-Goals

- Tidak mengubah nama kolom `ModelAi` yang sudah ada.
- Tidak memodifikasi `AiAutoReplyService` atau logika auto-reply customer.
- Tidak menambahkan env/config baru (MVP memakai manual input di UI).
- Tidak mengubah format request provider atau struktur pesan.
- Tidak membuat tombol test koneksi khusus untuk Model Instruct (test tetap menggunakan Model Utama).

## Impacted Areas

- `src/database/migrations/` - Migration untuk menambah kolom `ModelInstructAi`.
- `src/app/Filament/Pages/AiAgent.php` - Logic state, load, save, dan preset provider.
- `src/resources/views/filament/pages/ai-agent.blade.php` - UI field Model Instruct dan update label Model Utama.
- `src/app/Services/Ai/InternalChatbotService.php` - Pemilihan model untuk VPoint Assistant.
- `src/resources/views/filament/pages/vpoint-assistant.blade.php` - Perbaikan bug UI.
- `src/resources/lang/id/ui.php` dan `src/resources/lang/en/ui.php` - Translation keys baru.
- `src/app/Support/AiSettings.php` (bila ada akses kolom baru di cache).

## Risks

- Jika kolom `ModelInstructAi` belum di-migrate tapi UI sudah ditampilkan, bisa terjadi error SQL. Mitigasi: gunakan `Schema::hasColumn()` di semua akses kolom baru.
- Admin bingung dengan dua field model. Mitigasi: label dan help text yang jelas.
- Auto-reply customer secara tidak sengaja beralih ke Model Instruct. Mitigasi: **tidak mengubah** `AiAutoReplyService` sama sekali.
- Suggested replies masih muncul setelah dipilih. Mitigasi: clear `$this->suggestedReplies` di `useSuggestedReply()`.

## Rollout Strategy

1. Buat migration database idempotent untuk menambah kolom `ModelInstructAi`.
2. Perbarui `AiAgent.php` untuk handle state `ModelInstructAi` dengan `Schema::hasColumn()`.
3. Perbarui `ai-agent.blade.php` untuk menampilkan field baru dan update label lama.
4. Tambahkan translation keys di `id/ui.php` dan `en/ui.php`.
5. Perbarui `InternalChatbotService.php` untuk memilih model sesuai prioritas.
6. Perbaiki bug UI di `vpoint-assistant.blade.php`.
7. Jalankan migration dan test manual.

## Acceptance Criteria

- Admin melihat field **Model Utama** dan **Model Instruct** di halaman AI Agent.
- Admin dapat menyimpan nilai `ModelInstructAi` dan reload tanpa hilang.
- VPoint Assistant memakai `ModelInstructAi` jika terisi, jika tidak fallback ke `ModelAi`.
- Auto-reply customer **tetap** memakai `ModelAi` dan tidak terpengaruh.
- Test koneksi AI tetap memakai `Model Utama`.
- Suggested replies di VPoint Assistant menghilang setelah salah satu dipilih.
- Area input VPoint Assistant tidak memiliki shadow dan memiliki max-height yang wajar.
- Semua teks UI baru menggunakan translation key.
- Provider existing tetap berjalan normal.
