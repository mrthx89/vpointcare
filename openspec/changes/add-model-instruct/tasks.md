# Tasks: Tambahkan Model Instruct untuk VPoint Assistant dan Inbox WhatsApp

## 1. Migration Database
- [x] Buat migration baru dengan nama seperti `2026_06_29_000001_add_model_instruct_to_ai_settings.php` di `src/database/migrations/`.
- [x] Gunakan SQL Server conditional DDL untuk mengecek apakah kolom `ModelInstructAi` sudah ada sebelum menambahkannya.
- [x] Tambahkan kolom `ModelInstructAi nvarchar(100) NULL`.
- [x] Buat down method yang menghapus kolom dengan pengecekan keberadaan tabel dan kolom.

## 2. Update AiAgent.php
- [x] Tambahkan `ModelInstructAi` di `ensureDefaultSettings()` dengan pengecekan `Schema::hasColumn()`.
- [x] Tambahkan pembacaan `ModelInstructAi` di `loadPengaturan()` dengan fallback ke null.
- [x] Tambahkan validasi `pengaturan.ModelInstructAi` di `simpanPengaturan()` sebagai `nullable|string|max:100`.
- [x] Tambahkan `ModelInstructAi` di data yang disimpan ke database.
- [x] Tambahkan `instruct_model` di setiap preset provider di `providerPresets()`.
- [x] Perbarui `applyProviderPreset()` untuk set `ModelInstructAi` hanya jika kosong.
- [x] Perbarui `normalizeProviderSettings()` jika perlu untuk menormalisasi `ModelInstructAi` per provider.

## 3. Update ai-agent.blade.php
- [x] Ubah label "Model" menjadi `__('ui.pages.ai_agent.primary_model')`.
- [x] Tambahkan helper text untuk Model Utama: `__('ui.pages.ai_agent.primary_model_help')`.
- [x] Tambahkan field baru untuk Model Instruct dengan `wire:model="pengaturan.ModelInstructAi"`.
- [x] Gunakan label `__('ui.pages.ai_agent.instruct_model')` dan helper text `__('ui.pages.ai_agent.instruct_model_help')`.
- [x] Gunakan layout grid untuk kedua field model agar terlihat rapi.

## 4. Update Translation Files
- [x] Buka `src/resources/lang/id/ui.php`.
- [x] Tambahkan key:
  ```php
  'primary_model' => 'Model Utama',
  'primary_model_help' => 'Dipakai untuk auto-reply customer dan test koneksi AI.',
  'instruct_model' => 'Model Instruct',
  'instruct_model_help' => 'Dipakai VPoint Assistant agar suggestion dan draft lebih cepat. Kosongkan untuk mengikuti Model Utama.',
  ```
- [x] Buka `src/resources/lang/en/ui.php`.
- [x] Tambahkan key yang sama dengan bahasa Inggris:
  ```php
  'primary_model' => 'Primary Model',
  'primary_model_help' => 'Used for customer auto-replies and AI connection tests.',
  'instruct_model' => 'Instruct Model',
  'instruct_model_help' => 'Used by VPoint Assistant for faster suggestions and drafts. Leave empty to follow the Primary Model.',
  ```

## 5. Update InternalChatbotService.php
- [x] Buat helper method untuk memilih model assistant, contoh:
  ```php
  private function getAssistantModel($settings, $providerKey)
  {
      if (isset($settings->ModelInstructAi) && !empty($settings->ModelInstructAi)) {
          return $settings->ModelInstructAi;
      }
      if (isset($settings->ModelAi) && !empty($settings->ModelAi)) {
          return $settings->ModelAi;
      }
      return config("services.{$providerKey}.model");
  }
  ```
- [x] Gunakan helper ini di `callProvider()` untuk mengganti penggunaan `$settings->ModelAi` untuk VPoint Assistant.
- [x] Pastikan untuk OpenAI dan provider lain menggunakan model yang benar.

## 6. Fix VPoint Assistant UI Bugs
- [x] Buka `src/resources/views/filament/pages/vpoint-assistant.blade.php`.
- [x] Hapus shadow pada container input bagian bawah (hilangkan class `shadow-2xl` atau yang serupa).
- [x] Perbaiki textarea: pastikan `max-h-[60vh]` dan padding sesuai.
- [x] Perbarui `useSuggestedReply()` di `VPointAssistant.php` untuk clear `$this->suggestedReplies` setelah dipilih:
  ```php
  public function useSuggestedReply(string $reply): void
  {
      $this->userMessage = $reply;
      $this->suggestedReplies = []; // tambahkan ini
  }
  ```

## 7. Testing
- [x] Jalankan `php -l` untuk semua file PHP yang diubah.
- [x] Jalankan migration dengan `php artisan migrate`.
- [x] Buka halaman AI Agent dan test simpan Model Utama dan Model Instruct yang berbeda.
- [x] Test VPoint Assistant: kirim pesan dan pastikan provider menerima Model Instruct.
- [x] Kosongkan Model Instruct dan test kembali: pastikan fallback ke Model Utama.
- [x] Test auto-reply customer: pastikan tetap memakai Model Utama.
- [x] Test tombol test koneksi AI: pastikan tetap memakai Model Utama.
- [x] Test suggested replies di VPoint Assistant: klik salah satu, pastikan input terisi dan suggested replies menghilang.
- [x] Periksa UI VPoint Assistant: pastikan tidak ada shadow pada area bawah dan textarea memiliki max-height yang wajar.
