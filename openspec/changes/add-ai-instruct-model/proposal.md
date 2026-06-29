# Change: Tambah Model Instruct untuk VPoint Assistant dan Inbox WhatsApp

## Summary

Tambahkan konfigurasi **Model Instruct** terpisah pada halaman Pengaturan AI agar **VPoint Assistant** dan fitur opsi jawaban ringan di **Inbox WhatsApp** dapat memakai model yang lebih cepat untuk chat internal, suggestion, draft, dan rekomendasi balasan. **Model Utama** tetap memakai konfigurasi `ModelAi` existing untuk auto-reply/customer-facing flow tanpa perubahan perilaku.

## Background

Saat ini pengaturan AI hanya memiliki satu field model, yaitu `MPengaturanAi.ModelAi`. Field ini dipakai oleh service auto-reply dan VPoint Assistant melalui `AiSettings::get()`. Karena VPoint Assistant dipakai untuk respons cepat ke user internal dan menghasilkan `suggested_replies`, kebutuhan performanya berbeda dari auto-reply utama. Pemisahan model mengurangi risiko menurunkan kualitas auto-reply saat ingin memakai model ringan/cepat untuk assistant internal.

## Goals

- Menambahkan field **Model Instruct** pada Pengaturan AI.
- Mempertahankan **Model Utama** sebagai `ModelAi` existing tanpa perubahan kontrak.
- Mengarahkan VPoint Assistant memakai `ModelInstructAi` bila terisi.
- Mengarahkan fitur opsi jawaban ringan untuk WhatsApp/Inbox memakai `ModelInstructAi` bila terisi.
- Mengarahkan jawaban pertama sesi Inbox WhatsApp memakai `ModelInstructAi` untuk hemat token, lalu sesi lanjutannya memakai `ModelAi`.
- Memberikan fallback aman ke `ModelAi` bila `ModelInstructAi` kosong.
- Menjaga provider, API key, base URL, prompt, working-hour, holiday, dan WAHA flow existing tetap sama.
- Menyediakan label/help text UI bilingual untuk membedakan fungsi kedua model.
- Menjaga semua teks UI tetap melalui translation key `ui.php` untuk Bahasa Indonesia dan English.

## Non-Goals

- Tidak mengganti provider aktif atau base URL existing.
- Tidak mengubah format request provider selain nilai `model` untuk VPoint Assistant dan Inbox WhatsApp.
- Tidak mengubah model auto-reply, test koneksi AI, atau pengiriman WAHA.
- Tidak memakai Model Instruct untuk balasan otomatis yang langsung dikirim ke customer tanpa review user.
- Tidak memecah API key per model.
- Tidak menambah provider baru.
- Tidak mengubah struktur chat, ticket, webhook WAHA, atau permission menu.

## Impacted Areas

- `src/database/migrations/*` untuk kolom `ModelInstructAi` nullable di `MPengaturanAi`.
- `src/app/Filament/Pages/AiAgent.php` untuk load, validate, save, default, dan preset behavior field baru.
- `src/resources/views/filament/pages/ai-agent.blade.php` untuk input **Model Utama** dan **Model Instruct**.
- `src/resources/lang/id/ui.php` dan `src/resources/lang/en/ui.php` untuk label/help text.
- `src/app/Services/Ai/InternalChatbotService.php` untuk memilih model assistant.
- Service/page Inbox WhatsApp terkait opsi jawaban ringan bila fitur suggest reply WhatsApp diimplementasikan/diaktifkan.
- `src/app/Support/AiSettings.php` hanya bila cache key perlu dinaikkan setelah schema berubah.
- Test/validasi manual VPoint Assistant, test koneksi AI, dan auto-reply.

## Current Findings

- `AiAgent` menyimpan setting via array `$pengaturan` dan validasi `pengaturan.ModelAi`.
- Halaman Blade memakai `wire:model="pengaturan.ModelAi"` untuk field model provider.
- `AiSettings::get()` membaca row `MPengaturanAi` dengan cache 5 menit.
- `InternalChatbotService::callProvider()` memakai `$settings->ModelAi` untuk OpenAI Responses API dan chat completions provider lain.
- Pencarian kode saat ini menunjukkan opsi jawaban terstruktur (`suggested_replies`) ada di VPoint Assistant; Inbox WhatsApp belum memiliki generator opsi jawaban ringan terpisah.
- `AiAutoReplyService` juga memakai model utama dari setting untuk flow auto-reply.
- `VPointAssistant` memiliki fallback settings array dengan `ModelAi => null` ketika setting DB tidak tersedia.

## Proposed Design

### Data Model

Tambahkan kolom nullable:

- `MPengaturanAi.ModelInstructAi nvarchar(100) NULL`

Rasional:

- Ukuran mengikuti `ModelAi` existing.
- Nullable menjaga kompatibilitas database lama.
- Kosong berarti pakai fallback `ModelAi`.

### UI Model Naming

- Rename label field existing `Model` menjadi **Model Utama**.
- Tambahkan field baru **Model Instruct** di bawah/sejajar Model Utama.
- Help text **Model Utama**: dipakai auto-reply/customer-facing dan test koneksi AI.
- Help text **Model Instruct**: dipakai VPoint Assistant agar suggestion/draft lebih cepat.

### Runtime Selection

Tambahkan helper kecil di `InternalChatbotService`:

```php
private function assistantModel(object $settings, string $providerKey): string
{
    return trim((string) ($settings->ModelInstructAi ?? ''))
        ?: trim((string) ($settings->ModelAi ?? ''))
        ?: (string) config("services.{$providerKey}.model");
}
```

Catatan OpenAI memakai key config `openai`, `9router` dinormalisasi ke `ninerouter`, sama seperti pola existing.

### Provider Preset Behavior

Saat admin memilih preset provider:

- `ProviderAi`, `ModelAi`, dan `BaseUrl` tetap diisi seperti sekarang.
- `ModelInstructAi` dapat ikut diisi default model instruct per provider bila kosong, atau diset sama dengan preset model agar user sadar nilainya.
- Rekomendasi aman: isi `ModelInstructAi` hanya jika kosong, supaya pilihan custom admin tidak tertimpa saat mengganti preset tanpa sengaja.

### Fallback Rules

- Jika `ModelInstructAi` terisi, VPoint Assistant memakai nilai itu.
- Jika `ModelInstructAi` kosong, VPoint Assistant memakai `ModelAi`.
- Jika keduanya kosong, pakai model default config provider.
- Auto-reply tetap memakai `ModelAi`.
- Jawaban pertama Inbox WhatsApp memakai `ModelInstructAi` hanya untuk pembuka/sapaan/routing ringan; balasan percakapan berikutnya memakai `ModelAi`.
- Test koneksi AI tetap memakai `ModelAi` untuk memvalidasi Model Utama.
- Opsi jawaban ringan di VPoint Assistant dan Inbox WhatsApp memakai `ModelInstructAi` karena hasilnya berupa saran/draft yang direview user sebelum dikirim.

## Risks

- Salah route model bisa membuat auto-reply memakai model cepat yang kualitasnya kurang. Mitigasi: perubahan hanya di `InternalChatbotService`.
- Provider tertentu tidak mendukung model instruct yang diinput. Mitigasi: validasi hanya string; error provider ditampilkan/log sesuai mekanisme existing.
- Cache `AiSettings` bisa menahan row lama beberapa menit. Mitigasi: `simpanPengaturan()` sudah perlu flush; migration/deploy perlu `optimize:clear` bila diperlukan.
- Admin bingung dua model. Mitigasi: label dan help text eksplisit.
- Teks UI hardcoded bisa merusak multilingual. Mitigasi: semua label/help text baru wajib ditambahkan di `src/resources/lang/id/ui.php` dan `src/resources/lang/en/ui.php`.
- Kolom baru belum ada di database production. Mitigasi: migration SQL Server conditional `COL_LENGTH`.

## Rollout Strategy

1. Tambah migration kolom `ModelInstructAi` nullable.
2. Update `AiAgent` untuk memuat, memvalidasi, dan menyimpan field baru.
3. Update UI Pengaturan AI dengan label **Model Utama** dan **Model Instruct**.
4. Update bahasa Indonesia dan English.
5. Update `InternalChatbotService` agar VPoint Assistant memakai `ModelInstructAi` dengan fallback.
6. Pastikan `AiAutoReplyService` dan test koneksi AI tetap memakai `ModelAi`.
7. Jalankan syntax check dan minimal test manual.
8. Deploy migration, clear cache, dan verifikasi di halaman admin.

## Acceptance Criteria

- Admin melihat dua field model di Pengaturan AI: **Model Utama** dan **Model Instruct**.
- Semua label dan helper text baru tampil melalui translation key, bukan hardcoded di Blade/PHP.
- Bahasa Indonesia dan English memiliki key translation yang sama untuk field baru.
- Menyimpan pengaturan menyimpan `ModelAi` dan `ModelInstructAi` tanpa menghapus API key provider.
- `ModelInstructAi` boleh kosong.
- VPoint Assistant memakai `ModelInstructAi` saat field tersebut terisi.
- Opsi jawaban ringan di Inbox WhatsApp memakai `ModelInstructAi` saat field tersebut terisi dan fallback ke `ModelAi` saat kosong.
- Sesi pertama Inbox WhatsApp memakai `ModelInstructAi` untuk initial reply yang ringan; sesi berikutnya memakai `ModelAi` untuk kualitas respons utama.
- VPoint Assistant fallback ke `ModelAi` saat `ModelInstructAi` kosong.
- Auto-reply customer tetap memakai `ModelAi`.
- Test koneksi AI tetap memakai `ModelAi`.
- Provider OpenAI, DeepSeek, OpenRouter, dan 9Router tetap mengikuti base URL/API key existing.
- Error provider tidak membocorkan API key.
- Database migration aman dijalankan berulang pada SQL Server karena memakai conditional column check.





