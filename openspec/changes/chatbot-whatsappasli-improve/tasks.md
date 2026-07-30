## 1. Kontrak Data dan Migration SQL Server

- [ ] 1.1 Audit schema `TChat` di `src/script/DATABASE_SCHEMA_WACS.sql` serta migration identitas/profil yang ada, lalu dokumentasikan kolom snapshot WAHA yang belum tersedia.
- [ ] 1.2 Buat migration SQL Server di `src/database/migrations/` yang aman untuk database existing: tambah `NamaKontakWaha`, `NamaGrupWaha`, `TglIdentitasWahaDiambil`, `StatusIdentitasWaha`, dan `PesanErrorIdentitasWaha` hanya bila belum ada.
- [ ] 1.3 Tambahkan index SQL Server yang diperlukan untuk query status/waktu snapshot tanpa mengubah index JID yang sudah ada.
- [ ] 1.4 Sinkronkan definisi fresh-install di `src/script/DATABASE_SCHEMA_WACS.sql` dengan kolom snapshot yang sama.
- [ ] 1.5 Siapkan langkah backup database dan rollback migration yang hanya menghapus kolom snapshot baru, tanpa menghapus chat, pesan, atau mapping master.

## 2. Adapter Metadata WAHA dan Snapshot

- [ ] 2.1 Tambahkan method metadata kontak dan grup pada `src/app/Services/Waha/WahaSender.php` menggunakan `getJson()`, timeout, API key, circuit breaker, dan `TLogIntegrasi` yang sudah ada.
- [ ] 2.2 Buat parser/helper teruji untuk mengambil nama kontak/grup tervalidasi dari variasi respons WAHA serta membatasi panjang field sesuai kolom SQL Server.
- [ ] 2.3 Perluas `src/app/Support/WahaChatHelper.php` bila diperlukan untuk memilih JID grup `@g.us`, mempertahankan JID `@lid`, dan membuat fallback phone JID tanpa mengubah normalisasi yang ada.
- [ ] 2.4 Pastikan respons WAHA mentah, token, API key, dan payload tidak disimpan ke kolom snapshot maupun diekspos ke UI/Livewire.
- [ ] 2.5 Tambahkan unit test untuk metadata kontak, metadata grup, respons kosong/tidak dikenal, dan resolusi `@lid` yang berhasil maupun gagal.

## 3. Queue, Idempotensi, dan Observability

- [ ] 3.1 Buat `src/app/Jobs/SyncWahaChatIdentityJob.php` pada queue `webhooks` dengan `tries=3`, `timeout=30`, backoff 30/120 detik, dan deduplikasi per `IdChat` selama 60 detik.
- [ ] 3.2 Implementasikan update atomik snapshot `TChat`: simpan nama valid, JID/raw identifier, nomor hasil resolusi bila tersedia, status, waktu sinkronisasi, dan error tersanitasi; jangan ubah mapping master.
- [ ] 3.3 Perbarui `src/app/Services/Waha/WahaWebhookProcessor.php` atau post-processing webhook terkait untuk dispatch job setelah transaksi chat berhasil tanpa menunggu request metadata.
- [ ] 3.4 Dispatch `SendBroadcastDebouncedJob` hanya ketika snapshot identitas benar-benar berubah agar Inbox realtime diperbarui tanpa broadcast storm.
- [ ] 3.5 Tambahkan feature test job untuk after-commit dispatch, deduplikasi, retry/final failure, preservasi snapshot lama, dan broadcast setelah update.

## 4. Tampilan WhatsAppAsli pada Inbox

- [ ] 4.1 Refactor minimal proyeksi `Identity.whatsapp` di `src/app/Filament/Pages/InboxWhatsapp.php` agar prioritas nama memakai snapshot database, lalu payload/raw fallback deterministik.
- [ ] 4.2 Pastikan chat grup menampilkan badge Grup WhatsApp, nama snapshot, dan JID `@g.us`; pengirim pesan grup tetap terpisah dari identitas grup.
- [ ] 4.3 Pastikan chat pribadi menampilkan badge Chat Pribadi, nama snapshot kontak, JID `@lid` bila ada, dan nomor hasil resolusi secara terpisah bila tersedia.
- [ ] 4.4 Tambahkan status dan waktu sinkronisasi ringkas pada header/detail chat tanpa mengungkapkan body/error teknis WAHA.
- [ ] 4.5 Tambahkan aksi refresh metadata WAHA pada chat aktif yang mengantre job deduplicated dan tetap mempertahankan snapshot saat refresh berjalan.
- [ ] 4.6 Perbarui `src/resources/views/filament/pages/inbox-whatsapp.blade.php` dengan hirarki list/header ala WAHA Hub menggunakan komponen Filament, Heroicon, Tailwind, dark mode, focus ring, dan target kontrol minimal 44px tanpa dependency baru.
- [ ] 4.7 Pastikan UI responsive tidak menimbulkan horizontal scroll dan identifier panjang `@g.us`/`@lid` memakai wrapping/break-all yang aman.

## 5. Permission, Localization, dan Regression Test

- [ ] 5.1 Tambahkan key Bahasa Indonesia di `src/lang/id/ui.php` untuk status snapshot, refresh identitas WhatsApp, grup/pribadi, LID, waktu sinkronisasi, pending, dan fallback.
- [ ] 5.2 Tambahkan key Bahasa Inggris yang setara di `src/lang/en/ui.php`; jangan hardcode label user-facing baru.
- [ ] 5.3 Pastikan refresh memakai permission Inbox yang telah disepakati dan pengguna tanpa permission tetap tidak dapat memicu refresh atau membuka Inbox.
- [ ] 5.4 Perbarui `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php` untuk list/header snapshot grup, personal, `@lid`, fallback saat WAHA gagal, action refresh, locale `id`/`en`, dan pemisahan mapping internal.
- [ ] 5.5 Tambahkan test aksesibilitas markup: label untuk aksi icon-only, `aria-pressed` pada mode identitas, focus-visible state, dan tidak hanya membedakan Grup/Pribadi melalui warna.

## 6. Validasi dan Deployment

- [ ] 6.1 Jalankan syntax check pada adapter/job/webhook/Inbox dan empat page breadcrumb (`ManageStatusTickets`, `ManagePrioritasTickets`, `ManageKategoriTickets`, `ManageTickets`); hasil yang diharapkan tidak ada syntax error.
- [ ] 6.2 Jalankan targeted test adapter/job, `InboxWhatsappTest`, dan regression test breadcrumb ticketing; hasil yang diharapkan seluruh test relevan lulus.
- [ ] 6.3 Jalankan `cd src; vendor/bin/pint --test`; hasil yang diharapkan file PHP yang berubah memenuhi formatter.
- [ ] 6.4 Jalankan `cd src; npm run build`; hasil yang diharapkan asset produksi berhasil dibuat tanpa error.
- [ ] 6.5 Jalankan `openspec validate chatbot-whatsappasli-improve --strict`; hasil yang diharapkan valid tanpa error/warning.
- [ ] 6.6 Sebelum production: backup SQL Server, deploy migration/schema/code/asset, jalankan `php artisan migrate --force`, `php artisan optimize:clear`, `php artisan optimize`, dan restart worker queue `webhooks`.
- [ ] 6.7 Verifikasi manual dengan WAHA aktif: grup mapped/unmapped `@g.us` termasuk mapping JID yang stale, avatar group versus avatar participant, personal mapped/unmapped, `@lid` dengan/tanpa phone mapping, WAHA timeout, refresh identitas, empat route breadcrumb ticketing, dark mode, serta locale Indonesia/Inggris.
- [ ] 6.8 Dokumentasikan hasil validasi, migration/deployment action, dan rollback yang benar-benar dijalankan sebelum menandai change selesai.

## 7. Koreksi Ingestion GroupWhatsApp dan Foto Participant

- [ ] 7.1 Reproduksi dengan fixture dua participant dalam satu group `@g.us`, baik mapped maupun unmapped, lalu buktikan apakah chat hilang, terpecah, atau salah key.
- [ ] 7.2 Perbaiki `src/app/Services/Waha/WahaWebhookProcessor.php` agar `TChat` grup selalu dicari berdasarkan `IdSesiWhatsapp + group_jid`, sedangkan participant hanya mengisi `TChatD.PengirimNomorWhatsapp` dan `PengirimNamaKontak`.
- [ ] 7.3 Tambahkan guard idempotensi/index SQL Server yang relevan untuk group JID dan test bahwa semua participant masuk ke satu chat grup yang benar.
- [ ] 7.4 Tambahkan kolom `TChatD.UrlFotoProfilPengirim` dan `TChatD.TglFotoProfilPengirimDiambil` pada migration SQL Server serta `src/script/DATABASE_SCHEMA_WACS.sql`.
- [ ] 7.5 Buat job atau perluasan job metadata yang mengambil foto participant via WAHA, deduplicated per session + participant JID, dengan timeout/retry/failure behavior terdokumentasi.
- [ ] 7.6 Perbarui `InboxWhatsapp` dan `inbox-whatsapp.blade.php` agar bubble pesan masuk grup menampilkan foto participant, nama/nomor, fallback inisial, alt text, dan dark-mode-safe layout.

## 8. Koreksi Aturan AI Agent dan Session

- [ ] 8.1 Reproduksi AI no-reply dengan fixture settings aktif, All Session aktif/nonaktif, chat pertama, chat beruntun, idle 60 menit, manual reply, dan queue failure; gunakan `TAiPermintaan`, `TAiRespon`, `TChatD`, dan log sebagai bukti.
- [ ] 8.2 Perbaiki `src/app/Services/Ai/AiAutoReplyService.php` agar `$isFirstReply` dihitung sebelum dipakai pada pemilihan model dan pencatatan `TAiPermintaan`.
- [ ] 8.3 Tambahkan setting `BatasSesiAutoReplyMenit` default 60 pada `MPengaturanAi`, validasi `1..1440`, localization, dan UI AI Agent sebagai batas idle saat All Session tidak aktif.
- [ ] 8.4 Implementasikan policy: All Session aktif memproses setiap incoming eligible; All Session nonaktif memproses hanya incoming pertama atau setelah idle >= batas sesi; semua guard jam kerja, hari libur, excluded number, duplicate, dan manual reply tetap berjalan.
- [ ] 8.5 Tambahkan reason code/status audit untuk skip, provider failure, fallback, draft lokal, WAHA send failure, dan sukses pada service/job/log tanpa secret.
- [ ] 8.6 Perbarui `src/app/Jobs/ProcessAiAutoReplyJob.php` dan broadcast/error flow bila diperlukan agar job failure/retry terlihat operator serta tidak menandai chat seolah sudah dibalas ketika delivery gagal.
- [ ] 8.7 Tambahkan test service/job/UI untuk All Session on/off, idle 60 menit, boundary waktu, manual reply, provider kosong/gagal, `KirimKeWaha` on/off, dan retry queue.

## 9. Koreksi Tampilan Base64 Media

- [ ] 9.1 Audit `src/app/Support/WahaMediaPayload.php`, `src/app/Filament/Pages/InboxWhatsapp.php`, `src/app/Http/Controllers/WahaMediaController.php`, dan Blade untuk membedakan media valid versus base64 mentah.
- [ ] 9.2 Perbarui state/renderer agar base64 tidak ditampilkan ketika preview/download berhasil, termasuk image, sticker, audio, video, PDF, dan dokumen.
- [ ] 9.3 Tambahkan fallback localized untuk base64 yang gagal dikonversi dalam panel diagnostik terbatas; jangan tampilkan `PayloadJson`, raw HTML, API key, webhook token, atau stack trace.
- [ ] 9.4 Tambahkan regression/security test untuk base64 valid, base64 rusak, data URI, media URL valid, dan payload yang berisi string mirip base64.

## 10. Koreksi Foto Profil GroupWhatsApp

- [ ] 10.1 Reproduksi foto grup salah ambil dengan fixture `JenisChat=Grup` yang memiliki raw group JID `@g.us`, participant, dan `MGrupWhatsapp.IdGrupWaha` yang berbeda atau stale; catat contactId yang dipakai resolver saat ini.
- [ ] 10.2 Perbaiki resolver di `src/app/Filament/Pages/InboxWhatsapp.php` agar raw group JID dari payload/TChat menjadi prioritas, tervalidasi berakhiran `@g.us`, dan participant/sender/personal identifier tidak pernah menjadi contactId avatar grup.
- [ ] 10.3 Pastikan fallback mapping `MGrupWhatsapp.IdGrupWaha` hanya dipakai jika valid dan tidak menggantikan identitas raw chat; jangan melakukan update otomatis ke mapping master dan jangan menghapus foto snapshot terakhir saat WAHA gagal.
- [ ] 10.4 Tambahkan regression test pada `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php` untuk raw group JID, mapping stale, participant berbeda, fallback inisial, dan preservasi foto terakhir.

## 11. Koreksi Breadcrumb Halaman Ticketing

- [ ] 11.1 Pasang `HasMenuBreadcrumbs` dan `AccessPermissions::TICKET_VIEW` pada `ManageTickets`, `ManageStatusTickets`, `ManagePrioritasTickets`, dan `ManageKategoriTickets` sesuai pola page resource yang sudah ada.
- [ ] 11.2 Jika diperlukan, perluas minimal `src/app/Support/FilamentBreadcrumbs.php` atau `src/app/Filament/Concerns/HasMenuBreadcrumbs.php` agar tiga halaman master menampilkan parent `Ticket` dan label resource aktif tanpa duplikasi; jangan mengubah route/permission/sidebar.
- [ ] 11.3 Pastikan label memakai `NavigationHelper` dan localization existing untuk Bahasa Indonesia/Inggris, termasuk `Status Ticket`, `Prioritas`, dan `Kategori`.
- [ ] 11.4 Tambahkan feature/page test untuk `/admin/ticketing/status-tickets`, `/admin/ticketing/prioritas/prioritas-tickets`, `/admin/ticketing/kategoris/kategori-tickets`, dan `/admin/operational/tickets`, termasuk permission denied dan locale `id`/`en`.
- [ ] 11.5 Jalankan `cd src; php -l app/Support/FilamentBreadcrumbs.php; php -l app/Filament/Concerns/HasMenuBreadcrumbs.php` serta targeted test ticketing; hasil yang diharapkan semua breadcrumb tampil dan tidak ada perubahan akses.
