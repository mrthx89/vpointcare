# Tasks: Tambah Opsi 9Router pada AI Agent

## 1. Discovery dan Keputusan Provider

- [ ] Pastikan apakah `9router` adalah provider baru atau alias/nama tampilan untuk `OpenRouter`.
- [ ] Tentukan label final UI: `9Router`, `OpenRouter`, atau `9Router / OpenRouter`.
- [ ] Tentukan env var final:
  - `NINEROUTER_API_KEY` / `9ROUTER_API_KEY` tidak ideal karena env var diawali angka kurang aman linting.
  - Rekomendasi: `NINEROUTER_API_KEY`, `NINEROUTER_MODEL`, `NINEROUTER_BASE_URL`.
- [ ] Tentukan default model dan endpoint resmi 9Router.
- [ ] Tentukan apakah perlu header tambahan seperti OpenRouter (`HTTP-Referer`, `X-Title`) atau tidak.
- [ ] Tentukan apakah endpoint test koneksi memakai prompt sistem default AI Agent atau prompt test khusus yang ringkas.

## 2. Konfigurasi Service

- [ ] Update `src/config/services.php` dengan blok `ninerouter`.
- [ ] Tambahkan `api_key`, `model`, `base_url`, dan bila perlu metadata `site_url` / `site_name`.
- [ ] Update `src/.env.example` dengan variabel 9Router tanpa nilai rahasia.
- [ ] Pastikan konfigurasi tetap aman untuk production dan tidak membaca API key hardcoded.

## 3. Database dan Secret Storage

- [ ] Cek struktur tabel `MPengaturanAi` saat ini.
- [ ] Jika provider baru perlu secret terpisah, buat migration SQL Server untuk kolom `NineRouterApiKeyTerenkripsi`.
- [ ] Gunakan nullable string/nvarchar yang konsisten dengan kolom API key provider lain.
- [ ] Buat migration idempotent dengan pengecekan kolom agar aman di SQL Server.
- [ ] Pastikan delete API key mengosongkan kolom 9Router saja.

## 4. Filament Page Logic

- [ ] Update `src/app/Filament/Pages/AiAgent.php` pada `providerPresets()`.
- [ ] Tambahkan preset `NineRouter` atau `9Router` sesuai keputusan discovery.
- [ ] Update `normalizeProviderSettings()` agar base URL/model tidak tertukar dengan provider existing.
- [ ] Update `defaultModel()` dan `defaultBaseUrl()`.
- [ ] Update mapping API key provider agar status `apiKeyTerisi` akurat.
- [ ] Pastikan validasi `ProviderAi`, `ModelAi`, dan `BaseUrl` menerima provider baru.
- [ ] Pastikan provider existing tidak berubah saat settings lama diload.

## 5. Auto Reply Service

- [ ] Update `src/app/Services/Ai/AiAutoReplyService.php` untuk mengenali provider 9Router.
- [ ] Tambahkan branch provider pada `generateAiReply()`.
- [ ] Tambahkan mapping config API key di resolver API key.
- [ ] Tambahkan mapping kolom encrypted API key di resolver secret database.
- [ ] Gunakan helper `generateChatCompletionReply()` jika 9Router kompatibel OpenAI chat completions.
- [ ] Jika header khusus diperlukan, tambahkan secara kondisional tanpa mengganggu provider lain.
- [ ] Pastikan error log menyebut provider `9Router` agar debugging jelas.

## 6. Test Koneksi AI

- [ ] Tambahkan tombol **Test Koneksi AI** pada halaman `src/resources/views/filament/pages/ai-agent.blade.php`.
- [ ] Tombol hanya aktif untuk user dengan permission manage AI Agent.
- [ ] Saat tombol ditekan, tampilkan dialog/modal test koneksi.
- [ ] Dialog berisi input box pertanyaan test dengan contoh: `Apakah kamu sudah siap? Nama kamu siapa?`.
- [ ] Dialog berisi area **Text Result** untuk menampilkan jawaban sukses atau pesan error.
- [ ] Tambahkan state Livewire di `src/app/Filament/Pages/AiAgent.php` untuk input test, loading state, result text, dan error text.
- [ ] Tambahkan method action, misalnya `testKoneksiAi()`, yang memanggil provider aktif tanpa mengirim ke WAHA.
- [ ] Reuse logic provider/API key/base URL/model dari service existing agar hasil test sama dengan auto-reply asli.
- [ ] Pastikan prompt test tidak memakai riwayat chat customer, tidak menyimpan hasil ke `TChatD`, dan tidak mengubah status chat.
- [ ] Tampilkan error di Text Result jika API key kosong, provider tidak didukung, base URL invalid, timeout, rate limit, auth gagal, atau response kosong.
- [ ] Sanitasi output error agar tidak menampilkan API key atau secret.
- [ ] Tambahkan timeout request yang wajar agar UI tidak menggantung.
- [ ] Tambahkan indikator loading saat test sedang berjalan.

## 7. UI Icon dan Visual Polish

- [ ] Audit asset existing `res/AI-Agent.svg`, `res/AI.svg`, dan `src/res`.
- [ ] Tentukan strategi icon:
  - memakai Heroicon `sparkles`/`cpu-chip` untuk navigation icon; atau
  - memakai custom SVG/logo AI Agent pada halaman; atau
  - keduanya.
- [ ] Tambahkan hero card di `src/resources/views/filament/pages/ai-agent.blade.php` bagian atas halaman.
- [ ] Tampilkan icon AI Agent dengan gradient, badge provider aktif, status API key, dan status auto-reply.
- [ ] Letakkan tombol **Test Koneksi AI** di hero card atau section provider agar mudah ditemukan.
- [ ] Pastikan UI responsive untuk desktop/tablet dan dark mode.
- [ ] Hindari remote image URL agar deployment Laragon/Docker tetap stabil.
- [ ] Jika asset baru dibuat, simpan di path yang bisa dibuild/diakses konsisten oleh Vite/public.

## 8. Localization

- [ ] Tambahkan label Indonesia untuk provider, hero title, hero subtitle, API key, bantuan 9Router, tombol test koneksi, dialog test, input test, text result, dan pesan loading/error di `src/lang/id/ui.php`.
- [ ] Tambahkan label Inggris ekuivalen di `src/lang/en/ui.php` bila file tersedia.
- [ ] Pastikan tidak ada hardcoded string baru pada UI utama kecuali nama brand provider.

## 9. Permission dan Menu Icon

- [ ] Cek `AccessPermissions::AI_AGENT_VIEW` dan konfigurasi icon saat ini.
- [ ] Jika ingin icon navigasi lebih menarik, update default icon menu AI Agent ke pilihan yang lebih cocok.
- [ ] Pastikan `NavigationHelper::iconFor()` tetap menghormati konfigurasi permission/menu existing.
- [ ] Jika icon disimpan di database permission/menu, siapkan data update/seeder yang aman.

## 10. Testing

- [ ] Jalankan `php -l` untuk file PHP yang diubah.
- [ ] Jalankan migration pada database development setelah backup bila menyentuh schema.
- [ ] Jalankan `php artisan config:clear` dan cek `php artisan config:show services` bila tersedia.
- [ ] Test UI AI Agent: pilih provider 9Router, simpan setting, reload halaman.
- [ ] Test simpan API key baru dan hapus API key.
- [ ] Test tombol **Test Koneksi AI** dengan pertanyaan `Apakah kamu sudah siap? Nama kamu siapa?`.
- [ ] Pastikan jawaban AI tampil di Text Result saat provider sukses.
- [ ] Pastikan error API key kosong tampil di Text Result tanpa crash.
- [ ] Pastikan error auth/rate-limit/timeout tampil di Text Result tanpa membocorkan secret.
- [ ] Pastikan test koneksi tidak membuat row chat dan tidak mengirim ke WAHA.
- [ ] Test auto-reply mode `DraftLokal` tanpa mengirim WAHA.
- [ ] Test auto-reply mode `KirimWaha` hanya pada nomor aman/internal.
- [ ] Test provider existing `OpenAI`, `DeepSeek`, dan `OpenRouter` tetap tidak error.
- [ ] Jalankan `npm run build` bila view/CSS/asset frontend berubah.

## 11. Documentation

- [ ] Update `openspec/specs/vpoint-care/spec.md` bila change diterima.
- [ ] Update README bagian AI provider bila ada.
- [ ] Catat env var 9Router di dokumentasi deployment.
- [ ] Catat cara memakai tombol **Test Koneksi AI**.
- [ ] Catat rollback: pilih provider lama dan kosongkan API key 9Router.
