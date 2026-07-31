# Change: Fix Inbox WhatsApp Sound Toggle, Tab Selection, and Chat Loading Performance

## Summary
Memperbaiki tiga bug kritis di halaman Inbox WhatsApp (InboxWhatsapp.php): (1) double icon speaker pada tombol toggle sound, (2) radio button filter (Grup/Pribadi) tidak terseleksi dengan benar, dan (3) loading chat yang sangat lambat saat klik kontak di tab [WhatsApp Asli].

## Problem Statement
Setelah commit 4916dd7 (fix-waha-group-name-display) dan perubahan terkait chatbot-whatsappasli-improve, muncul beberapa regresi UI/UX di halaman Inbox WhatsApp:

1. **Double Icon Speaker**: Tombol toggleSound() menampilkan dua icon speaker secara bersamaan (heroicon-o-speaker-wave dan heroicon-o-speaker-x-mark) karena Alpine.js x-show tidak berfungsi dengan benar atau ada duplikasi rendering.
2. **Radio Button Tidak Terseleksi**: Filter tab [WhatsApp Asli] menggunakan komponen Filament radio (heroicon-o-radio, inline) yang tidak menunjukkan state selected setelah page load atau setelah interaksi, menyebabkan user tidak tahu filter mana yang aktif.
3. **Chat Loading Lambat**: Saat user mengklik kontak (Grup atau Pribadi) di tab [WhatsApp Asli], waktu tunggu untuk menampilkan riwayat chat sangat lama (>3 detik), bahkan terkadang chat tidak tampil sama sekali. Ini kemungkinan disebabkan oleh query N+1, missing eager loading, atau blocking operation di method .selectChat() atau .loadHistoryChats().

## Current State
- File utama: src/app/Filament/Pages/InboxWhatsapp.php
- View: src/resources/views/filament/pages/inbox-whatsapp.blade.php
- Method terkait sound: toggleSound() (Alpine.js di blade, line ~12-15)
- Method terkait filter: Property $filterType dan form schema di method orm()
- Method terkait chat loading: .selectChat() (line ~625), .loadHistoryChats() (line ~567)
- Commit terakhir yang menyentuh: 4916dd7 (fix-waha-group-name-display), 2be061, 5bf0e4e

## Goals
- Menghilangkan double icon speaker dengan memastikan hanya satu icon yang dirender berdasarkan state soundOn.
- Memastikan radio button filter (Grup/Pribadi/Semua) selalu menunjukkan state selected yang sesuai dengan property Livewire.
- Mengoptimalkan performa loading chat history sehingga responsif (<1 detik) dan konsisten tampil.
- Mempertahankan kompatibilitas dengan fitur group name display dan identity snapshot yang baru ditambahkan.

## Non-Goals
- Refactor arsitektur Inbox WhatsApp secara keseluruhan.
- Menambah fitur baru di luar perbaikan bug.
- Mengubah kontrak API WAHA atau struktur database.
- Memperbaiki issue di luar scope tiga bug yang disebutkan.

## Proposed Changes
1. **Fix Double Icon Speaker**
   - Ganti dua <x-heroicon> terpisah dengan single icon element yang menggunakan Alpine.js binding dinamis (:class atau x-html) atau pastikan x-show memiliki x-cloak dan transisi yang benar.
   - Validasi bahwa localStorage read/write untuk wacs_sound tidak menyebabkan race condition.

2. **Fix Radio Button Selection**
   - Periksa apakah Filament Radio component di orm() sudah menggunakan wire:model.live atau wire:model.defer yang tepat.
   - Pastikan default value $filterType diset di mount() dan sinkron dengan UI.
   - Cek apakah ada CSS conflict atau missing asset Filament yang menyebabkan visual selected state tidak muncul.

3. **Optimize Chat Loading Performance**
   - Audit query di .selectChat() dan .loadHistoryChats() untuk N+1 problem.
   - Tambahkan eager loading untuk relasi yang diperlukan (misal: wahaIdentitySnapshot, chatbotMessages, mapping).
   - Pertimbangkan caching atau pagination untuk chat history jika volume pesan besar.
   - Pastikan tidak ada blocking call (sync HTTP request ke WAHA) di dalam flow selectChat; pindahkan ke queue/background job jika perlu.
   - Tambahkan loading indicator/spinner di UI saat chat sedang dimuat.

## Impacted Areas
- **Files**: src/app/Filament/Pages/InboxWhatsapp.php, src/resources/views/filament/pages/inbox-whatsapp.blade.php
- **Database**: Tidak ada perubahan schema, tapi query optimization mungkin mempengaruhi index usage.
- **API**: Tidak ada perubahan kontrak WAHA.
- **Permission**: Tidak berubah.
- **Localization**: Key ui.pages.inbox.* sudah ada, tidak perlu tambah baru.
- **Queue**: Tidak ada perubahan queue.
- **Deployment**: Tidak ada migration atau config change.

## Risks and Mitigations
- **Risk**: Optimasi query mengubah hasil data yang ditampilkan. **Mitigation**: Test manual dengan berbagai skenario (grup, pribadi, chat kosong, chat banyak pesan).
- **Risk**: Perubahan Alpine.js breaking existing sound functionality. **Mitigation**: Test toggle sound di multiple browser dan pastikan localStorage persist.
- **Risk**: Eager loading meningkatkan memory usage. **Mitigation**: Monitor memory dan batasi jumlah pesan yang di-load (pagination/lazy load).

## Validation
1. Buka halaman Inbox WhatsApp, verifikasi hanya satu icon speaker yang muncul dan toggle berfungsi.
2. Klik setiap tab filter (Semua/Grup/Pribadi), pastikan radio button menunjukkan selected state.
3. Klik kontak Grup dan Pribadi, ukur waktu sampai chat tampil (target <1s).
4. Test dengan akun yang memiliki >100 chat history untuk memastikan pagination/loading tetap responsif.
5. Jalankan php artisan test --filter=InboxWhatsapp jika ada test terkait.
6. Clear cache view: php artisan view:clear sebelum testing.

## Rollback
Jika terjadi masalah, revert commit ini dengan git revert <commit-hash>. Tidak ada perubahan database atau state persistent yang perlu rollback manual.
