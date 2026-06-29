# Tasks: Tambah Model Instruct untuk VPoint Assistant dan Inbox WhatsApp

## 1. Database

- [ ] Buat migration SQL Server conditional untuk `MPengaturanAi.ModelInstructAi nvarchar(100) NULL`.
- [ ] Pastikan migration `up()` memakai `IF OBJECT_ID` dan `COL_LENGTH` agar aman jika tabel/kolom sudah ada.
- [ ] Pastikan migration `down()` drop kolom hanya jika kolom ada.
- [ ] Jangan mengubah data `ModelAi` existing.

## 2. Filament Page State

- [ ] Update default settings di `src/app/Filament/Pages/AiAgent.php` agar `$pengaturan` mengenal `ModelInstructAi`.
- [ ] Update `loadPengaturan()` agar field baru terbaca dari DB dan fallback ke kosong jika kolom belum ada.
- [ ] Update validasi `simpanPengaturan()` dengan `pengaturan.ModelInstructAi => nullable|string|max:100`.
- [ ] Update payload save/update agar `ModelInstructAi` tersimpan hanya jika kolom tersedia.
- [ ] Pastikan `AiSettings::flush()` tetap dipanggil setelah save.

## 3. Provider Preset

- [ ] Tambahkan metadata preset opsional `instruct_model` untuk OpenAI, DeepSeek, OpenRouter, dan 9Router bila ingin default eksplisit.
- [ ] Saat `applyProviderPreset()`, jangan menimpa custom `ModelInstructAi` kecuali field masih kosong.
- [ ] Pastikan `ModelAi` existing tetap diisi seperti sekarang.
- [ ] Pastikan `BaseUrl` dan API key state tidak berubah.

## 4. UI Pengaturan AI

- [ ] Ubah label field `Model` existing menjadi `Model Utama` / `Primary Model`.
- [ ] Tambahkan input text `wire:model="pengaturan.ModelInstructAi"`.
- [ ] Letakkan `Model Utama` dan `Model Instruct` berdekatan di section provider.
- [ ] Tambahkan helper text bahwa `Model Utama` untuk auto-reply/test koneksi.
- [ ] Tambahkan helper text bahwa `Model Instruct` untuk VPoint Assistant dan Inbox WhatsApp/suggestion cepat.
- [ ] Pastikan layout responsive dan dark mode tetap rapi.

## 5. Translation

- [ ] Update `src/resources/lang/id/ui.php` dengan key label/help baru.
- [ ] Update `src/resources/lang/en/ui.php` dengan key label/help baru.
- [ ] Hindari mengganti key lama jika dipakai di tempat lain; tambah key baru lebih aman.
- [ ] Pastikan Blade dan PHP tidak memakai teks hardcoded untuk label/help text baru.
- [ ] Pastikan nama key di bahasa Indonesia dan English sama persis agar fallback locale tidak rusak.
- [ ] Verifikasi halaman AI Agent pada locale `id` dan `en`.

## 6. VPoint Assistant Runtime

- [ ] Update `src/app/Services/Ai/InternalChatbotService.php` agar pemilihan model memakai helper khusus assistant.
- [ ] Untuk OpenAI Responses API, isi request `model` dari `ModelInstructAi` fallback `ModelAi` fallback config.
- [ ] Untuk chat completions provider, isi request `model` dari helper yang sama.
- [ ] Update logging warning agar mencatat model efektif tanpa secret.
- [ ] Pastikan parsing `suggested_replies` tidak berubah.

## 7. Opsi Jawaban Ringan WhatsApp

- [ ] Audit apakah Inbox WhatsApp sudah memiliki service generator opsi jawaban/suggest reply.
- [ ] Jika sudah ada, arahkan pemilihan modelnya ke helper Model Instruct dengan fallback Model Utama.
- [ ] Jika belum ada, catat target implementasi agar fitur opsi jawaban ringan Inbox memakai helper yang sama sejak awal.
- [ ] Pastikan opsi jawaban WhatsApp hanya mengisi draft/saran untuk user, bukan auto-send langsung ke WAHA.
- [ ] Tambahkan rule session pertama Inbox WhatsApp memakai Model Instruct untuk initial reply ringan.
- [ ] Tambahkan rule session lanjutan Inbox WhatsApp tetap memakai Model Utama.
- [ ] Definisikan indikator session pertama, misalnya belum ada balasan AI/agent pada `TChatD` atau status chat baru sesuai struktur existing.
- [ ] Pastikan label/button terkait opsi jawaban tetap memakai translation key `id` dan `en`.

## 8. No-Regression Guard

- [ ] Pastikan `src/app/Services/Ai/AiAutoReplyService.php` tidak berubah memakai `ModelInstructAi`.
- [ ] Pastikan `testKoneksiAi()` tetap membangun settings dengan `ModelAi` sebagai model uji.
- [ ] Pastikan flow `KirimKeWaha`, `ModeKirim`, jam kerja, hari libur, dan exclude number tidak berubah.
- [ ] Pastikan fallback settings di `VPointAssistant` menambahkan `ModelInstructAi => null` bila diperlukan.

## 9. Validation

- [ ] Jalankan `php -l` untuk file PHP yang diubah.
- [ ] Jalankan migration pada database dev/staging.
- [ ] Buka halaman Pengaturan AI dan simpan dua model berbeda.
- [ ] Test VPoint Assistant dengan `ModelInstructAi` terisi dan cek provider menerima model instruct.
- [ ] Test opsi jawaban ringan di Inbox WhatsApp bila fitur sudah tersedia/diimplementasikan dan cek provider menerima model instruct.
- [ ] Test chat Inbox WhatsApp baru: jawaban pertama memakai Model Instruct.
- [ ] Test chat Inbox WhatsApp lanjutan: jawaban berikutnya memakai Model Utama.
- [ ] Kosongkan `ModelInstructAi`, test VPoint Assistant fallback ke `ModelAi`.
- [ ] Test koneksi AI dan pastikan tetap memakai Model Utama.
- [ ] Test satu skenario auto-reply atau dry-run untuk memastikan tetap memakai Model Utama.
- [ ] Jalankan `php artisan view:clear` dan `php artisan optimize:clear` setelah deploy bila cache lama muncul.







