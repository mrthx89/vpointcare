# Tasks: Fix Inbox WhatsApp Sound, Tab Selection, and Chat Loading

## Kelompok A — Analisis dan Persiapan

- [ ] A1. Baca source code src/app/Filament/Pages/InboxWhatsapp.php method .selectChat(), .loadHistoryChats(), .form(), dan property $filterType untuk memahami flow saat ini
- [ ] A2. Baca view src/resources/views/filament/pages/inbox-whatsapp.blade.php bagian toggle sound button dan filter radio tabs
- [ ] A3. Cek git diff commit 4916dd7 untuk melihat perubahan yang mungkin menyebabkan regresi
- [ ] A4. Identifikasi query N+1 di .loadHistoryChats() dengan menambahkan debug bar atau query logging sementara
- [ ] A5. Dokumentasikan temuan analisis di file ini sebelum mulai implementasi

## Kelompok B — Fix Double Icon Speaker (Blade View)

- [ ] B1. Edit src/resources/views/filament/pages/inbox-whatsapp.blade.php line ~159-162: ganti dua <x-heroicon> terpisah dengan single element yang menggunakan Alpine.js binding dinamis atau pastikan x-show + x-cloak berfungsi benar
- [ ] B2. Test manual: buka halaman, verifikasi hanya satu icon muncul; klik toggle, pastikan transisi instan tanpa double render
- [ ] B3. Test di multiple browser (Chrome, Firefox, Edge) untuk memastikan Alpine.js behavior konsisten
- [ ] B4. Clear view cache: php artisan view:clear setelah perubahan blade

## Kelompok C — Fix Radio Button Selection State (Filament Form)

- [ ] C1. Periksa method orm() di InboxWhatsapp.php untuk komponen Radio/Tab filter; pastikan menggunakan wire:model.live atau wire:model.defer yang tepat
- [ ] C2. Verifikasi default value $filterType diset di mount() dan sinkron dengan UI
- [ ] C3. Cek apakah ada CSS conflict atau missing Filament asset yang menyebabkan visual selected state tidak muncul; inspect element di browser dev tools
- [ ] C4. Jika perlu, tambahkan custom CSS class atau inline style untuk memperkuat visual selected state
- [ ] C5. Test manual: klik setiap tab (Semua/Grup/Pribadi), pastikan selected state terlihat dan daftar chat terfilter benar

## Kelompok D — Optimize Chat Loading Performance (Backend Query)

- [ ] D1. Audit query di .loadHistoryChats(): identifikasi relasi yang di-lazy load dan harus di-eager load (misal: wahaIdentitySnapshot, chatbotMessages.sender, media)
- [ ] D2. Tambahkan eager loading via with() di query builder; contoh: ->with(['wahaIdentitySnapshot', 'chatbotMessages' => fn() => ->orderBy('created_at', 'desc')->limit(50)])
- [ ] D3. Pastikan tidak ada synchronous HTTP call ke WAHA API di dalam .selectChat() atau .loadHistoryChats(); pindahkan ke background job jika ditemukan
- [ ] D4. Tambahkan loading indicator/spinner di blade view: tampilkan saat $wire.loading atau Alpine state isLoading = true
- [ ] D5. Implementasi error handling: jika fetch gagal atau timeout >5s, tampilkan pesan error di area chat dan hilangkan spinner
- [ ] D6. Test performa: ukur waktu loading untuk kontak dengan 10, 50, 100 pesan; target <1s untuk <50 pesan
- [ ] D7. Monitor query count dengan Laravel Debugbar atau DB::listen() untuk memastikan tidak ada N+1

## Kelompok E — Validasi dan Testing

- [ ] E1. Jalankan php artisan test --filter=InboxWhatsapp jika ada test terkait; jika tidak ada, skip
- [ ] E2. Test end-to-end manual: buka inbox, toggle sound, ganti tab filter, klik kontak grup & pribadi, ukur waktu loading
- [ ] E3. Test dengan akun yang memiliki >100 chat history untuk memastikan pagination/lazy load bekerja
- [ ] E4. Verifikasi tidak ada console error di browser dev tools terkait Alpine.js atau Livewire
- [ ] E5. Cek localization key ui.pages.inbox.* sudah ada dan benar di src/resources/lang/id/ui.php dan en/ui.php

## Kelompok F — Dokumentasi dan Cleanup

- [ ] F1. Update proposal.md dan spec.md jika ada perubahan scope selama implementasi
- [ ] F2. Hapus debug code atau temporary logging yang ditambahkan di Kelompok A/D
- [ ] F3. Commit perubahan dengan message yang jelas: ix(inbox): resolve sound toggle double icon, radio selection, and slow chat loading
- [ ] F4. Tandai semua task yang selesai di file ini; jelaskan task yang belum selesai jika ada

## Command Validasi

`powershell
cd src
php artisan view:clear
php artisan config:clear
php artisan test --filter=InboxWhatsapp
vendor/bin/pint --test app/Filament/Pages/InboxWhatsapp.php
npm run build  # jika ada perubahan asset frontend
`

## Rollback Plan

Jika terjadi masalah setelah deploy:
1. Revert commit: git revert <commit-hash>
2. Clear cache: php artisan optimize:clear
3. Tidak ada migration atau data change yang perlu rollback manual
