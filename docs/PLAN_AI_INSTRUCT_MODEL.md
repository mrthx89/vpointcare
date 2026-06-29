# Plan Implementasi: Model Instruct untuk VPoint Assistant dan Inbox WhatsApp

## Ringkasan

Tujuan perubahan ini adalah menambahkan dua jalur model dalam Pengaturan AI:

- **Model Utama**: tetap memakai kolom existing `ModelAi`; digunakan untuk auto-reply, test koneksi AI, dan flow customer-facing.
- **Model Instruct**: kolom baru `ModelInstructAi`; digunakan khusus oleh VPoint Assistant, opsi jawaban ringan Inbox WhatsApp, dan jawaban pertama sesi Inbox agar lebih cepat serta hemat token.

Perubahan harus kecil dan terisolasi. Auto-reply tidak boleh ikut pindah ke model instruct.
Multilanguage harus tetap dipertahankan: semua teks UI baru wajib lewat translation key di `id` dan `en`, bukan hardcoded.

## Temuan Saat Ini

- Halaman setting AI ada di `src/app/Filament/Pages/AiAgent.php` dan `src/resources/views/filament/pages/ai-agent.blade.php`.
- Field model existing adalah `pengaturan.ModelAi` dan tersimpan di `MPengaturanAi.ModelAi`.
- `AiSettings::get()` membaca row `MPengaturanAi` default dan cache selama 5 menit.
- `InternalChatbotService` saat ini memakai `$settings->ModelAi` untuk VPoint Assistant dan Inbox WhatsApp.
- Opsi jawaban terstruktur saat ini terlihat pada VPoint Assistant melalui `suggested_replies`; Inbox WhatsApp belum terlihat memiliki generator opsi jawaban ringan terpisah dari hasil pencarian kode.
- `AiAutoReplyService` memakai model yang sama untuk auto-reply.
- `testKoneksiAi()` memanggil `AiAutoReplyService::testProviderConnection()`, sehingga saat ini otomatis memakai Model Utama.
- Provider yang perlu tetap kompatibel: OpenAI, DeepSeek, OpenRouter, dan 9Router/NineRouter.

## Prinsip Desain

- Jangan rename kolom `ModelAi`; cukup ubah label UI menjadi **Model Utama**.
- Tambahkan kolom nullable baru, bukan memodifikasi data existing.
- Runtime VPoint Assistant memilih model dengan urutan: `ModelInstructAi` -> `ModelAi` -> default config provider.
- Runtime opsi jawaban ringan WhatsApp, baik dari Assistant maupun Inbox, memakai urutan model yang sama karena hasilnya berupa saran/draft.
- Runtime jawaban pertama Inbox WhatsApp memakai `ModelInstructAi` untuk hemat token; session lanjutan kembali memakai `ModelAi`.
- Runtime auto-reply tetap memilih `ModelAi` -> default config provider.
- Test koneksi AI tetap memvalidasi **Model Utama** karena fitur itu ada di section provider utama.
- Jika kolom baru belum ada saat deploy sebagian, kode sebaiknya fail-safe/fallback.
- Semua label/help text baru wajib memakai `__('ui.pages.ai_agent.*')` agar multilingual tetap berjalan.

## Rencana Teknis Detail

### 1. Migration Database

Buat migration baru, contoh nama:

`src/database/migrations/2026_06_29_000001_add_model_instruct_to_ai_settings.php`

Isi `up()`:

- Cek tabel `MPengaturanAi` ada.
- Cek kolom `ModelInstructAi` belum ada.
- Tambah `ModelInstructAi nvarchar(100) NULL`.

Isi `down()`:

- Cek tabel dan kolom ada.
- Drop kolom `ModelInstructAi`.

Gunakan SQL Server conditional DDL seperti migration existing agar aman untuk production.

### 2. Update State dan Save `AiAgent`

File: `src/app/Filament/Pages/AiAgent.php`

Perubahan:

- Tambahkan default `$pengaturan['ModelInstructAi'] = null` atau string kosong sesuai pola existing.
- Pada `loadPengaturan()`, baca `ModelInstructAi` jika tersedia; fallback kosong.
- Pada validasi `simpanPengaturan()`, tambah:
  - `pengaturan.ModelInstructAi` => `nullable|string|max:100`
- Pada data save/update, sertakan `ModelInstructAi` hanya jika kolom tersedia atau setelah migration diasumsikan wajib ada.
- Pastikan `AiSettings::flush()` tetap dipanggil setelah save.

Catatan aman:

- Jika method sudah memakai `Schema::hasColumn`, ikuti pola itu.
- Jangan mengubah `ProviderAi`, `BaseUrl`, API key terenkripsi, atau template message.

### 3. Update Preset Provider

File: `src/app/Filament/Pages/AiAgent.php`

Perubahan:

- Tambahkan key opsional `instruct_model` pada setiap preset.
- Default aman bisa sama dengan `model` provider terlebih dahulu.
- Jika ingin performa cepat, nilai bisa diambil dari config/env baru pada tahap berikut, tetapi tidak wajib untuk MVP.

Behavior saat klik preset:

- Tetap set `ProviderAi`, `ModelAi`, dan `BaseUrl` seperti sekarang.
- Set `ModelInstructAi` hanya jika sebelumnya kosong.
- Jangan timpa custom instruct model yang sudah diisi admin.

### 4. Update Blade UI

File: `src/resources/views/filament/pages/ai-agent.blade.php`

Perubahan di section provider:

- Ganti label existing `ui.pages.ai_agent.model` menjadi `ui.pages.ai_agent.primary_model`.
- Tambahkan input baru:
  - `wire:model="pengaturan.ModelInstructAi"`
  - label `ui.pages.ai_agent.instruct_model`
  - helper text `ui.pages.ai_agent.instruct_model_help`
- Tambahkan helper untuk Model Utama:
  - `ui.pages.ai_agent.primary_model_help`
- Layout disarankan:
  - `grid gap-4 sm:grid-cols-2` untuk Model Utama dan Model Instruct.
  - Base URL tetap full width atau tetap di bawahnya sesuai layout existing.

### 5. Update Translation

File:

- `src/resources/lang/id/ui.php`
- `src/resources/lang/en/ui.php`

Tambahkan key:

Bahasa Indonesia:

- `primary_model` => `Model Utama`
- `primary_model_help` => `Dipakai untuk auto-reply customer dan test koneksi AI.`
- `instruct_model` => `Model Instruct`
- `instruct_model_help` => `Dipakai VPoint Assistant agar suggestion dan draft lebih cepat. Kosongkan untuk mengikuti Model Utama.`

English:

- `primary_model` => `Primary Model`
- `primary_model_help` => `Used for customer auto-replies and AI connection tests.`
- `instruct_model` => `Instruct Model`
- `instruct_model_help` => `Used by VPoint Assistant for faster suggestions and drafts. Leave empty to follow the Primary Model.`

Aturan multilingual:

- Nama key di `id` dan `en` harus sama persis.
- Blade tidak boleh menulis `Model Utama`, `Model Instruct`, atau helper text langsung sebagai string hardcoded.
- Gunakan `__('ui.pages.ai_agent.primary_model')`, `__('ui.pages.ai_agent.primary_model_help')`, `__('ui.pages.ai_agent.instruct_model')`, dan `__('ui.pages.ai_agent.instruct_model_help')`.
- Jika ada teks tambahan di notification/error, tambahkan juga di kedua file bahasa.

### 6. Update VPoint Assistant Runtime

File: `src/app/Services/Ai/InternalChatbotService.php`

Perubahan:

- Tambahkan helper pemilihan model efektif khusus assistant.
- Pada branch OpenAI Responses API, ubah `model` dari `$settings->ModelAi ?: config(...)` ke helper baru.
- Pada branch chat completions, ubah `model` ke helper baru.
- Logging warning boleh mencatat model efektif helper, bukan hanya `ModelAi`.

Contoh konsep:

```php
$model = $this->assistantModel($settings, $key);
```

Untuk OpenAI, `$key` adalah `openai`. Untuk 9Router/NineRouter, pakai key normalisasi existing `ninerouter`.

### 7. Opsi Jawaban Ringan WhatsApp

Target perilaku:

- Opsi jawaban ringan di VPoint Assistant memakai `ModelInstructAi`.
- Opsi jawaban ringan di Inbox WhatsApp juga memakai `ModelInstructAi` bila fitur/service suggest reply tersedia atau dibuat setelah ini.
- Hasil opsi jawaban harus masuk sebagai saran/draft yang direview user, bukan dikirim otomatis ke WAHA.
- Jawaban pertama sesi Inbox WhatsApp memakai `ModelInstructAi` untuk sapaan/routing ringan agar hemat token.
- Jawaban sesi berikutnya memakai `ModelAi` karena konteks percakapan biasanya lebih berat dan perlu kualitas utama.
- Jika `ModelInstructAi` kosong, fallback ke `ModelAi`.

Implementasi aman:

- Buat helper pemilihan model yang reusable untuk kebutuhan internal/suggestion, misalnya `assistantModel()` atau `lightweightReplyModel()` di service AI terkait.
- Gunakan helper ini di `InternalChatbotService` untuk `suggested_replies`.
- Jika Inbox WhatsApp nanti punya service generator saran balasan, gunakan helper yang sama sejak awal.
- Tambahkan helper penentu sesi pertama, misalnya `isFirstInboxAiReply($chatId)` berdasarkan belum adanya balasan AI/agent di detail chat.
- Gunakan helper model terpisah, misalnya `inboxReplyModel($settings, $isFirstReply)`, dengan aturan first reply: `ModelInstructAi` -> `ModelAi` -> config; next reply: `ModelAi` -> config.
- Jangan panggil helper ini dari `AiAutoReplyService` untuk auto-reply customer-facing.
- Semua teks tombol/label opsi jawaban Inbox wajib memakai translation key `id` dan `en`.

### 8. Update Fallback VPointAssistant Page

File: `src/app/Filament/Pages/VPointAssistant.php`

Perubahan:

- Pada fallback settings object/array, tambahkan `ModelInstructAi => null`.
- Ini mencegah undefined property pada kondisi DB setting kosong.

### 9. Guard Agar Auto-reply Tidak Berubah

File: `src/app/Services/Ai/AiAutoReplyService.php`

Tindakan:

- Tidak perlu mengubah pemilihan model di service ini.
- Review setelah patch bahwa semua request auto-reply masih memakai `ModelAi`.
- Jika ada refactor helper bersama, pastikan helper punya parameter eksplisit agar auto-reply tidak mengambil `ModelInstructAi`.

### 10. Validasi

Jalankan:

```bash
php -l src/app/Filament/Pages/AiAgent.php
php -l src/app/Services/Ai/InternalChatbotService.php
php -l src/app/Filament/Pages/VPointAssistant.php
php artisan migrate
php artisan view:clear
```

Manual test:

1. Buka halaman AI Agent.
2. Isi Model Utama dan Model Instruct berbeda.
3. Simpan pengaturan.
4. Kirim pesan lewat VPoint Assistant.
5. Pastikan provider menerima model instruct.
6. Kosongkan Model Instruct dan simpan.
7. Kirim pesan VPoint Assistant lagi.
8. Pastikan fallback memakai Model Utama.
9. Jika fitur opsi jawaban ringan Inbox WhatsApp tersedia, generate opsi dari Inbox dan pastikan memakai Model Instruct.
10. Test chat Inbox WhatsApp baru dan pastikan jawaban pertama memakai Model Instruct.
11. Test chat Inbox WhatsApp lanjutan dan pastikan jawaban berikutnya memakai Model Utama.
12. Jalankan test koneksi AI.
13. Pastikan test memakai Model Utama.
14. Jalankan skenario auto-reply/dry-run.
15. Pastikan auto-reply tetap memakai Model Utama.

## Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Auto-reply tidak sengaja memakai Model Instruct | Kualitas balasan customer turun | Ubah hanya `InternalChatbotService`, bukan `AiAutoReplyService` |
| Admin bingung dua field model | Salah konfigurasi | Label/help text eksplisit |
| Teks baru hardcoded | Multilingual rusak | Semua teks lewat `src/resources/lang/id/ui.php` dan `src/resources/lang/en/ui.php` |
| Kolom belum ada di production | Error save/load | Migration conditional dan deploy migration sebelum rilis UI |
| Provider menolak model instruct | Assistant error | Fallback manual ke Model Utama dengan mengosongkan field |
| Cache setting lama | Perubahan belum terasa | `AiSettings::flush()`, `php artisan optimize:clear` saat deploy |

## Checklist Keputusan Sebelum Implementasi

- [ ] Nama kolom final: `ModelInstructAi`.
- [ ] Model Instruct default per provider disamakan dengan Model Utama atau dikosongkan.
- [ ] Test koneksi AI tetap untuk Model Utama, atau perlu tombol test khusus Model Instruct di fase berikutnya.
- [ ] Perlu env tambahan seperti `OPENAI_INSTRUCT_MODEL` atau cukup input manual di DB/UI.

## Rekomendasi MVP

Untuk mengurangi bug baru, implementasi MVP sebaiknya:

- Tambah satu kolom `ModelInstructAi` nullable.
- Tidak menambah env/config baru.
- Tidak mengubah `AiAutoReplyService`.
- Tidak mengubah format request provider.
- Hanya ubah `InternalChatbotService` untuk memilih model assistant.
- Biarkan admin mengisi model instruct manual dari UI.








