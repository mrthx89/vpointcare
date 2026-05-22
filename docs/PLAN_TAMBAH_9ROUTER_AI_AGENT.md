# PLAN: Tambah Opsi 9Router, Test Koneksi, dan Icon AI Agent

## Status

- Tipe pekerjaan: planning dan OpenSpec saja.
- Target modul: AI Agent VPoint Care.
- Tanggal rencana: 2026-05-22.
- Belum ada perubahan kode aplikasi.

## Ringkasan Tujuan

Menambahkan opsi provider `9Router` ke AI Agent agar admin dapat memilih provider tersebut, menyimpan API key secara aman, memakai provider itu untuk auto-reply, mengetes koneksi AI langsung dari halaman AI Agent, dan mempercantik halaman dengan icon/hero visual yang menarik.

## Catatan Penting

Kode saat ini sudah memiliki dukungan `OpenRouter` pada beberapa bagian:

- Preset provider ada di `src/app/Filament/Pages/AiAgent.php`.
- Konfigurasi env contoh ada di `src/.env.example`.
- Config service ada di `src/config/services.php`.
- Service auto-reply sudah mengenali `openrouter` di `src/app/Services/Ai/AiAutoReplyService.php`.

Karena user menyebut `9router`, tahap pertama harus memastikan apakah `9Router` adalah provider baru atau maksudnya `OpenRouter`. Jika maksudnya `OpenRouter`, implementasi terbaik adalah memperbaiki label/UI agar user melihat opsi tersebut dengan jelas, bukan menambah provider duplikat.

## Phase 1 — Discovery Provider

### 1.1 Validasi Nama Provider

- Konfirmasi nama final yang tampil di UI:
  - `9Router`
  - `OpenRouter`
  - `9Router / OpenRouter`
- Konfirmasi internal key yang disimpan di `MPengaturanAi.ProviderAi`:
  - Rekomendasi jika provider baru: `NineRouter` agar aman sebagai string internal dan mudah dipakai di kode.
  - Rekomendasi jika alias: tetap simpan `OpenRouter`, UI boleh menampilkan label tambahan.

### 1.2 Validasi Endpoint dan Model

- Tentukan base URL provider 9Router.
- Tentukan default model.
- Cek apakah response API kompatibel OpenAI chat completions:
  - request `model`
  - request `messages`
  - response `choices[0].message.content`
- Cek apakah perlu header tambahan seperti:
  - `HTTP-Referer`
  - `X-Title`
  - header custom lain dari provider.

### 1.3 Keputusan Env Var

- Hindari env var diawali angka seperti `9ROUTER_API_KEY` karena rawan masalah di shell/tooling.
- Rekomendasi:
  - `NINEROUTER_API_KEY`
  - `NINEROUTER_MODEL`
  - `NINEROUTER_BASE_URL`

## Phase 2 — OpenSpec dan Kontrak Perubahan

### 2.1 Proposal

- File dibuat: `openspec/changes/add-9router-ai-agent/proposal.md`.
- Isi proposal menjelaskan goals, non-goals, impacted area, risks, rollout strategy, dan acceptance criteria.

### 2.2 Tasks

- File dibuat: `openspec/changes/add-9router-ai-agent/tasks.md`.
- Tasks disusun dari discovery sampai testing dan dokumentasi.

### 2.3 Spec Delta

- File dibuat: `openspec/changes/add-9router-ai-agent/specs/vpoint-care/spec.md`.
- Spec menambahkan requirement provider `9Router`, auto-reply via `9Router`, test koneksi AI, dan visual icon AI Agent.

## Phase 3 — Backend Configuration Plan

### 3.1 Update `src/config/services.php`

Tambahkan konfigurasi jika 9Router provider baru:

- `api_key` membaca `NINEROUTER_API_KEY`.
- `model` membaca `NINEROUTER_MODEL`.
- `base_url` membaca `NINEROUTER_BASE_URL`.
- Tambahkan optional metadata jika provider butuh referer/title.

Jika `9Router` hanya alias `OpenRouter`, tidak perlu blok config baru. Cukup pastikan `openrouter` config sudah benar.

### 3.2 Update `src/.env.example`

Tambahkan contoh variabel:

- `NINEROUTER_API_KEY=`
- `NINEROUTER_MODEL=...`
- `NINEROUTER_BASE_URL=...`

Jangan isi API key asli.

## Phase 4 — Database Secret Plan

### 4.1 Audit Tabel `MPengaturanAi`

Cek kolom API key provider yang sudah ada:

- `OpenAiApiKeyTerenkripsi`
- `DeepSeekApiKeyTerenkripsi`
- `OpenRouterApiKeyTerenkripsi`
- kolom lain bila ada.

### 4.2 Migration Jika Provider Baru

Jika 9Router benar-benar provider baru, buat migration SQL Server untuk:

- Menambah `NineRouterApiKeyTerenkripsi` nullable.
- Menggunakan pengecekan `Schema::hasColumn()` atau SQL Server conditional DDL.
- Tidak memodifikasi data provider existing.

Jika 9Router alias OpenRouter, tidak perlu migration baru.

## Phase 5 — Filament AI Agent Page Plan

### 5.1 Preset Provider

Update `src/app/Filament/Pages/AiAgent.php`:

- Tambahkan preset `NineRouter` / `9Router` pada `providerPresets()`.
- Isi label, summary, model, base URL, dan key label.
- Pastikan preset card tampil sejajar dengan OpenAI, DeepSeek, dan OpenRouter.

### 5.2 Default Model dan Base URL

Update helper:

- `defaultModel()` mengenali provider baru.
- `defaultBaseUrl()` mengenali provider baru.
- `normalizeProviderSettings()` tidak mengganti base URL 9Router dengan OpenAI/DeepSeek/OpenRouter secara salah.

### 5.3 API Key State

Update logic API key:

- Saat provider `9Router` dipilih, input API key mengarah ke secret 9Router.
- `apiKeyTerisi` true jika secret 9Router ada.
- Delete API key hanya menghapus secret 9Router.
- Existing API key provider lain tidak berubah.

## Phase 6 — AI Auto Reply Service Plan

### 6.1 Provider Routing

Update `src/app/Services/Ai/AiAutoReplyService.php`:

- Tambahkan kondisi provider `ninerouter` / `9router` / `NineRouter` sesuai internal key final.
- Gunakan `generateChatCompletionReply()` bila API kompatibel.
- Tambahkan provider display name pada log/error.

### 6.2 API Key Resolver

Update mapping resolver API key:

- Config env key: `services.ninerouter.api_key`.
- Database encrypted key: `NineRouterApiKeyTerenkripsi`.
- Fallback config tetap berjalan jika DB secret kosong, sesuai pola provider lain.

### 6.3 Error Handling

- Jika API key kosong, tampilkan error provider 9Router jelas.
- Jika endpoint gagal, log status/body ringkas tanpa secret.
- Jangan mengirim pesan kosong ke WAHA.
- Tetap simpan skip/failure reason untuk audit.

## Phase 7 — Test Koneksi AI Plan

### 7.1 Tujuan Fitur

Tambahkan tombol **Test Koneksi AI** agar admin bisa mengecek provider aktif tanpa menunggu chat customer masuk dan tanpa mengirim pesan ke WAHA.

### 7.2 UI Dialog

Tambahkan di `src/resources/views/filament/pages/ai-agent.blade.php`:

- Tombol **Test Koneksi AI** di hero card atau area provider.
- Modal/dialog test koneksi.
- Input box pertanyaan test.
- Placeholder/default contoh: `Apakah kamu sudah siap? Nama kamu siapa?`.
- Area **Text Result** untuk jawaban AI atau error.
- Loading indicator saat request berjalan.
- Tombol kirim test dan tombol tutup/reset.

### 7.3 Livewire State dan Action

Tambahkan di `src/app/Filament/Pages/AiAgent.php`:

- State `testAiDialogOpen` atau mekanisme modal setara.
- State `testAiPrompt`.
- State `testAiResult`.
- State `testAiLoading` bila diperlukan.
- Method `bukaDialogTestAi()`.
- Method `testKoneksiAi()`.
- Method reset result bila dialog ditutup atau provider diganti.

### 7.4 Service Design

Pilihan implementasi terbaik:

- Ekstrak logic call provider ke method reusable agar auto-reply dan test koneksi memakai jalur yang sama.
- Atau tambah method public khusus di `AiAutoReplyService`, misalnya `testConnection(array|object $settings, string $prompt): array`.
- Method test harus mengembalikan struktur jelas:
  - `success: true/false`
  - `message/result`
  - `error`
  - `provider`
  - `model`

### 7.5 Safety Rules

- Test koneksi tidak boleh membuat row `TChat` atau `TChatD`.
- Test koneksi tidak boleh memanggil `WahaSender`.
- Test koneksi tidak boleh mengubah status tiket/chat.
- Error ditampilkan di Text Result, tetapi API key dan secret harus disensor.
- Jika API key kosong, tampilkan error lokal tanpa memanggil provider.
- Jika base URL/model kosong, tampilkan validasi lokal.
- Gunakan timeout request agar modal tidak menggantung.

### 7.6 Contoh Flow Admin

1. Admin buka halaman AI Agent.
2. Admin pilih provider dan isi API key.
3. Admin klik **Test Koneksi AI**.
4. Dialog muncul.
5. Admin mengetik `Apakah kamu sudah siap? Nama kamu siapa?`.
6. Admin klik kirim.
7. Jika sukses, Text Result menampilkan jawaban AI.
8. Jika gagal, Text Result menampilkan error seperti `API key kosong`, `401 Unauthorized`, `timeout`, atau `response AI kosong`.

## Phase 8 — UI Icon dan Tampilan Menarik

### 8.1 Asset Strategy

Pilihan terbaik:

- Gunakan custom SVG lokal untuk hero visual AI Agent.
- Simpan di lokasi yang stabil, misalnya `src/public/images/ai-agent.svg` atau pakai inline SVG di blade.
- Hindari image remote.

Asset existing yang bisa dipertimbangkan:

- `res/AI-Agent.svg`
- `res/AI.svg`
- `res/AI-Agent_Images/AI-Agent_ImgID1.png`
- `res/AI-Agent_Images/AI-Agent_ImgID2.png`

### 8.2 Hero Card AI Agent

Tambahkan bagian atas halaman `src/resources/views/filament/pages/ai-agent.blade.php`:

- Card gradient lembut biru/ungu/emerald.
- Icon robot/sparkle/AI orb.
- Judul `AI Agent VPoint Care`.
- Subtitle singkat fungsi AI Agent.
- Badge provider aktif.
- Badge status API key.
- Badge status auto-reply.
- Tombol **Test Koneksi AI**.

### 8.3 Navigation Icon

Saat ini AI Agent memakai `heroicon-o-sparkles` melalui:

- `src/app/Filament/Pages/AiAgent.php`
- `src/app/Support/AccessPermissions.php`

Opsi peningkatan:

- Tetap gunakan `sparkles` untuk aman.
- Ganti default ke `heroicon-o-cpu-chip` jika ingin lebih teknis.
- Jika icon dikendalikan database permission/menu, update lewat seeder/data patch yang aman.

## Phase 9 — Localization Plan

Update translation file:

- `src/lang/id/ui.php`
- `src/lang/en/ui.php` jika ada.

Label yang perlu disiapkan:

- `AI Agent VPoint Care`
- deskripsi hero AI Agent
- label provider 9Router
- summary 9Router
- status API key configured/not configured
- provider active badge
- `Test Koneksi AI`
- `Pertanyaan Test`
- `Text Result`
- `Kirim Test`
- `Menunggu jawaban AI...`
- pesan error API key kosong, provider tidak didukung, timeout, dan response kosong

Pastikan tidak ada string baru yang hardcoded kecuali nama brand provider.

## Phase 10 — Testing Plan

### 10.1 Syntax dan Build

- Jalankan `php -l src/app/Filament/Pages/AiAgent.php`.
- Jalankan `php -l src/app/Services/Ai/AiAutoReplyService.php`.
- Jalankan `php artisan config:clear`.
- Jalankan `npm run build` jika perubahan blade/asset/CSS membutuhkan build.

### 10.2 UI Test

- Buka `/admin/ai-agent`.
- Pastikan hero icon tampil rapi.
- Pastikan tombol **Test Koneksi AI** tampil untuk user yang berhak.
- Pilih provider 9Router.
- Simpan setting.
- Reload halaman dan cek pilihan tetap tersimpan.
- Simpan API key baru.
- Hapus API key dan pastikan hanya key 9Router yang hilang.

### 10.3 Test Koneksi AI

- Klik **Test Koneksi AI**.
- Pastikan dialog/modal muncul.
- Isi input dengan `Apakah kamu sudah siap? Nama kamu siapa?`.
- Klik kirim test.
- Jika provider sukses, pastikan jawaban muncul di **Text Result**.
- Jika API key kosong, pastikan Text Result menampilkan error jelas.
- Jika provider error/timeout, pastikan Text Result menampilkan error ringkas tanpa secret.
- Pastikan test koneksi tidak membuat chat baru dan tidak mengirim ke WAHA.

### 10.4 Auto Reply Test

- Set mode `DraftLokal` terlebih dahulu.
- Kirim chat test dari nomor internal/aman.
- Pastikan AI reply tersimpan sebagai draft/result dan tidak terkirim WAHA.
- Setelah aman, test `KirimWaha` pada nomor internal.
- Pastikan response terkirim dan status kegagalan tercatat jika provider error.

### 10.5 Regression Test Provider Existing

- Test OpenAI preset.
- Test DeepSeek preset.
- Test OpenRouter preset.
- Pastikan provider lama tidak berubah base URL/model/API key state-nya.
- Pastikan tombol test koneksi juga bekerja untuk provider existing.

## Phase 11 — Rollback Plan

Jika 9Router atau test koneksi bermasalah:

- Ubah provider aktif kembali ke OpenAI/DeepSeek/OpenRouter dari UI.
- Kosongkan API key 9Router dari UI.
- Nonaktifkan tombol test koneksi sementara lewat conditional UI bila perlu.
- Jika migration sudah ditambahkan, biarkan kolom nullable tetap ada agar rollback non-destruktif.
- Jangan drop kolom secret di production kecuali sudah ada backup dan downtime window.

## Rekomendasi Implementasi

Rekomendasi paling aman adalah implementasi dalam dua langkah:

1. Konfirmasi dulu apakah `9Router` adalah alias untuk `OpenRouter`.
2. Jika memang provider baru, tambahkan `NineRouter` sebagai internal provider key, tetapi label UI tetap `9Router`.
3. Tambahkan test koneksi memakai jalur provider yang sama dengan auto-reply, tetapi pastikan tidak menyentuh WAHA dan tidak membuat row chat.

Dengan cara ini, kode tetap aman untuk PHP/env var, UI tetap sesuai permintaan user, test koneksi berguna untuk debugging, dan provider existing tidak terganggu.
