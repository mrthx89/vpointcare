# Change: Tambah Opsi 9Router pada AI Agent

## Summary

Tambahkan opsi provider AI **9Router** pada halaman AI Agent VPoint Care, lengkap dengan preset provider, pengelolaan API key, konfigurasi model/base URL, dukungan service auto-reply, tombol test koneksi AI, dan visual icon AI Agent yang lebih menarik.

> Catatan validasi: istilah user adalah `9router`. Saat implementasi perlu dikonfirmasi apakah yang dimaksud adalah brand/provider baru bernama **9Router** atau alias UI untuk **OpenRouter** yang sudah tersedia di sebagian kode.

## Goals

- Menyediakan pilihan provider `9Router` pada AI Agent tanpa merusak provider existing `OpenAI`, `DeepSeek`, dan `OpenRouter`.
- Menambahkan preset model, base URL, dan label API key khusus `9Router`.
- Memastikan API key `9Router` disimpan aman menggunakan pola enkripsi yang sama dengan provider lain.
- Memastikan auto-reply dapat memanggil endpoint `9Router` dengan format chat completions yang benar.
- Menyediakan tombol **Test Koneksi AI** pada halaman AI Agent untuk mengetes apakah provider terhubung dan merespons dengan benar.
- Membuat tampilan AI Agent lebih menarik dengan icon/hero visual yang konsisten dengan tema Filament.
- Menjaga kompatibilitas SQL Server, route existing, permission existing, dan flow `KirimKeWaha` / `DraftLokal`.

## Non-Goals

- Tidak mengganti provider default existing tanpa persetujuan.
- Tidak menghapus `OpenRouter` existing sebelum jelas bahwa `9Router` adalah pengganti/alias.
- Tidak mengubah kontrak webhook WAHA, route admin, atau struktur chat existing.
- Tidak menyimpan API key hardcoded di source code.
- Tidak mengubah logic holiday/jam kerja selain memastikan provider baru bisa dipakai dalam flow existing.
- Test koneksi AI hanya untuk verifikasi koneksi provider, bukan untuk mengirim pesan ke customer/WAHA.

## Impacted Areas

- `src/app/Filament/Pages/AiAgent.php`
- `src/resources/views/filament/pages/ai-agent.blade.php`
- `src/app/Services/Ai/AiAutoReplyService.php`
- `src/config/services.php`
- `src/.env.example`
- `src/database/migrations/*`
- `src/lang/*/ui.php`
- `src/app/Support/AccessPermissions.php` bila icon navigasi perlu diubah dari definisi permission/menu.
- `res/AI-Agent.svg`, `res/AI.svg`, atau asset baru di `src/public` / `src/resources` untuk icon visual.

## Risks

- Jika `9Router` sebenarnya hanya maksud user untuk `OpenRouter`, implementasi provider baru bisa membuat duplikasi membingungkan.
- Jika endpoint 9Router tidak kompatibel penuh dengan OpenAI chat completions, service perlu adapter khusus.
- Jika kolom database API key terenkripsi belum tersedia, migration harus dibuat hati-hati untuk SQL Server.
- Jika icon menggunakan asset besar atau remote URL, halaman admin bisa lambat atau tidak portable.
- Test koneksi AI yang tidak dibatasi rate-limit bisa memboroskan kuota API key admin.

## Rollout Strategy

1. Validasi nama provider dan endpoint resmi 9Router.
2. Tambahkan konfigurasi `.env.example` dan `config/services.php`.
3. Tambahkan kolom API key terenkripsi bila provider benar-benar baru.
4. Tambahkan preset provider di page AI Agent.
5. Tambahkan branch provider di service auto-reply.
6. Tambahkan UI card/icon AI Agent.
7. Tambahkan tombol dan dialog test koneksi AI.
8. Jalankan validasi syntax, config, dan auto-reply dry-run/manual test.
9. Update dokumentasi README atau spec bila implementasi final sudah disetujui.

## Acceptance Criteria

- Admin melihat opsi `9Router` di halaman AI Agent.
- Memilih preset `9Router` mengisi provider, model, dan base URL sesuai konfigurasi.
- API key `9Router` bisa disimpan, dideteksi sebagai terisi, dan dihapus.
- Auto-reply dengan provider `9Router` menghasilkan response atau error terlog jelas.
- Mode `DraftLokal` tidak mengirim ke WAHA otomatis.
- Mode `KirimWaha` tetap mengirim melalui WAHA setelah response AI berhasil.
- Tampilan AI Agent memiliki icon/visual baru yang menarik, responsive, dan dark-mode friendly.
- Provider existing tetap berjalan setelah perubahan.
- Admin dapat menekan tombol **Test Koneksi AI** di halaman AI Agent.
- Setelah menekan tombol test, muncul dialog/modal dengan input box untuk mengetik pesan test.
- Admin dapat mengetik pertanyaan seperti "Apakah kamu sudah siap? Nama kamu siapa?" di input box.
- Setelah menekan kirim, sistem mengirim pesan ke provider AI yang sedang aktif.
- Jika provider terhubung dan merespons, jawaban AI muncul di text result.
- Jika provider gagal/error, pesan error yang jelas muncul di text result.
- Test koneksi TIDAK mengirim pesan ke WAHA atau customer manapun.
- Dialog test koneksi responsive dan mendukung dark mode.
