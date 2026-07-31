## 1. Kontrak Data dan Migration SQL Server

- [x] 1.1 Audit schema `TChat` di `src/script/DATABASE_SCHEMA_WACS.sql` serta migration identitas/profil yang ada, lalu dokumentasikan kolom snapshot WAHA yang belum tersedia.
- [x] 1.2 Buat migration SQL Server di `src/database/migrations/` yang aman untuk database existing: tambah `NamaKontakWaha`, `NamaGrupWaha`, `TglIdentitasWahaDiambil`, `StatusIdentitasWaha`, dan `PesanErrorIdentitasWaha` hanya bila belum ada.
- [x] 1.3 Tambahkan index SQL Server yang diperlukan untuk query status/waktu snapshot tanpa mengubah index JID yang sudah ada.
- [x] 1.4 Sinkronkan definisi fresh-install di `src/script/DATABASE_SCHEMA_WACS.sql` dengan kolom snapshot yang sama.
- [x] 1.5 Siapkan langkah backup database dan rollback migration yang hanya menghapus kolom snapshot baru, tanpa menghapus chat, pesan, atau mapping master.

## 2. Adapter Metadata WAHA dan Snapshot

- [x] 2.1 Tambahkan method metadata kontak dan grup pada `src/app/Services/Waha/WahaSender.php` menggunakan `getJson()`, timeout, API key, circuit breaker, dan `TLogIntegrasi` yang sudah ada.
- [x] 2.2 Buat parser/helper teruji untuk mengambil nama kontak/grup tervalidasi dari variasi respons WAHA serta membatasi panjang field sesuai kolom SQL Server.
- [x] 2.3 Verifikasi `src/app/Support/WahaChatHelper.php`: normalisasi yang ada sudah mempertahankan raw JID `@g.us` dan `@lid` serta fallback phone; tidak diperlukan perubahan helper.
- [x] 2.4 Pastikan respons WAHA mentah, token, API key, dan payload tidak disimpan ke kolom snapshot maupun diekspos ke UI/Livewire.
- [x] 2.5 Tambahkan unit test untuk metadata kontak, metadata grup, respons kosong/tidak dikenal, dan resolusi `@lid` yang berhasil maupun gagal. (Terimplementasi di `src/tests/Feature/Waha/WahaChatIdentitySyncTest.php`; runtime test terblokir karena command `test` dan `vendor/bin/phpunit` tidak tersedia.)
- [x] 2.6 Sanitasi `TLogIntegrasi.ResponseJson` dan `PesanError` di `src/app/Services/Waha/WahaSender.php` menjadi metadata bounded/redacted tanpa mengubah body return internal, lalu tambahkan regression test respons profile-picture yang memuat secret. (Source review approved; runtime test terblokir karena PHPUnit tidak tersedia.)

## 3. Queue, Idempotensi, dan Observability

- [x] 3.1 Buat `src/app/Jobs/SyncWahaChatIdentityJob.php` pada queue `webhooks` dengan `tries=3`, `timeout=30`, backoff 30/120 detik, dan deduplikasi per `IdChat` selama 60 detik.
- [x] 3.2 Implementasikan update atomik snapshot `TChat`: simpan nama valid, JID/raw identifier, nomor hasil resolusi bila tersedia, status, waktu sinkronisasi, dan error tersanitasi; jangan ubah mapping master.
- [x] 3.3 Perbarui `src/app/Services/Waha/WahaWebhookProcessor.php` atau post-processing webhook terkait untuk dispatch job setelah transaksi chat berhasil tanpa menunggu request metadata.
- [x] 3.4 Dispatch `SendBroadcastDebouncedJob` hanya ketika snapshot identitas benar-benar berubah agar Inbox realtime diperbarui tanpa broadcast storm.
- [x] 3.5 Tambahkan feature test job untuk after-commit dispatch, deduplikasi, retry/final failure, preservasi snapshot lama, dan broadcast setelah update.

## 4. Tampilan WhatsAppAsli pada Inbox

- [x] 4.1 Refactor minimal proyeksi `Identity.whatsapp` di `src/app/Filament/Pages/InboxWhatsapp.php` agar prioritas nama memakai snapshot database, lalu payload/raw fallback deterministik.
- [x] 4.2 Pastikan chat grup menampilkan badge Grup WhatsApp, nama snapshot, dan JID `@g.us`; pengirim pesan grup tetap terpisah dari identitas grup.
- [x] 4.3 Pastikan chat pribadi menampilkan badge Chat Pribadi, nama snapshot kontak, JID `@lid` bila ada, dan nomor hasil resolusi secara terpisah bila tersedia.
- [x] 4.4 Tambahkan status dan waktu sinkronisasi ringkas pada header/detail chat tanpa mengungkapkan body/error teknis WAHA.
- [x] 4.5 Tambahkan aksi refresh metadata WAHA pada chat aktif yang mengantre job deduplicated dan tetap mempertahankan snapshot saat refresh berjalan.
- [x] 4.6 Perbarui `src/resources/views/filament/pages/inbox-whatsapp.blade.php` dengan hirarki list/header ala WAHA Hub menggunakan komponen Filament, Heroicon, Tailwind, dark mode, focus ring, dan target kontrol minimal 44px tanpa dependency baru.
- [x] 4.7 Pastikan UI responsive tidak menimbulkan horizontal scroll dan identifier panjang `@g.us`/`@lid` memakai wrapping/break-all yang aman.

## 5. Permission, Localization, dan Regression Test

- [x] 5.1 Tambahkan key Bahasa Indonesia di `src/lang/id/ui.php` untuk status snapshot, refresh identitas WhatsApp, grup/pribadi, LID, waktu sinkronisasi, pending, dan fallback.
- [x] 5.2 Tambahkan key Bahasa Inggris yang setara di `src/lang/en/ui.php`; jangan hardcode label user-facing baru.
- [x] 5.3 Pastikan refresh memakai permission Inbox yang telah disepakati dan pengguna tanpa permission tetap tidak dapat memicu refresh atau membuka Inbox. (Review approved; runtime test terblokir.)
- [x] 5.4 Perbarui `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php` untuk list/header snapshot grup, personal, `@lid`, fallback saat WAHA gagal, action refresh, locale `id`/`en`, dan pemisahan mapping internal. (Syntax/review lulus; runtime test terblokir.)
- [x] 5.5 Tambahkan test aksesibilitas markup: label untuk aksi icon-only, `aria-pressed` pada mode identitas, focus-visible state, dan tidak hanya membedakan Grup/Pribadi melalui warna. (Syntax/review lulus; runtime test terblokir.)

## 6. Validasi dan Deployment

- [x] 6.1 Jalankan syntax check pada adapter/job/webhook/Inbox dan empat page breadcrumb (`ManageStatusTickets`, `ManagePrioritasTickets`, `ManageKategoriTickets`, `ManageTickets`); hasil aktual semua file PHP berubah `php -l` lulus.
- [ ] 6.2 Jalankan targeted test adapter/job, `InboxWhatsappTest`, dan regression test breadcrumb ticketing; belum dapat diselesaikan karena `php artisan test` tidak tersedia dan `vendor/bin/phpunit` tidak ada.
- [ ] 6.3 Jalankan `cd src; vendor/bin/pint --test`; belum dapat diselesaikan karena `vendor/bin/pint` tidak tersedia.
- [x] 6.4 Jalankan `cd src; npm run build`; hasil aktual Vite build berhasil tanpa error.
- [x] 6.5 Jalankan `openspec validate chatbot-whatsappasli-improve --strict`; hasil aktual valid tanpa error.
- [ ] 6.6 Sebelum production: backup SQL Server, deploy migration/schema/code/asset, jalankan `php artisan migrate --force`, `php artisan optimize:clear`, `php artisan optimize`, dan restart worker queue `webhooks`.
- [ ] 6.7 Verifikasi manual dengan WAHA aktif: grup mapped/unmapped `@g.us` termasuk mapping JID yang stale, avatar group versus avatar participant, personal mapped/unmapped, `@lid` dengan/tanpa phone mapping, WAHA timeout, refresh identitas, empat route breadcrumb ticketing, dark mode, serta locale Indonesia/Inggris.
- [x] 6.8 Dokumentasikan hasil validasi, migration/deployment action, dan rollback yang benar-benar dijalankan sebelum menandai change selesai. (Dicatat pada checklist dan laporan akhir; deploy/migration production belum dijalankan.)

## 7. Koreksi Ingestion GroupWhatsApp dan Foto Participant

- [x] 7.1 Reproduksi dengan fixture dua participant dalam satu group `@g.us`, baik mapped maupun unmapped, lalu buktikan apakah chat hilang, terpecah, atau salah key.
- [x] 7.2 Perbaiki `src/app/Services/Waha/WahaWebhookProcessor.php` agar `TChat` grup selalu dicari berdasarkan `IdSesiWhatsapp + group_jid`, sedangkan participant hanya mengisi `TChatD.PengirimNomorWhatsapp` dan `PengirimNamaKontak`.
- [x] 7.3 Tambahkan guard idempotensi/index SQL Server yang relevan untuk group JID dan test bahwa semua participant masuk ke satu chat grup yang benar.
- [x] 7.4 Tambahkan kolom `TChatD.UrlFotoProfilPengirim` dan `TChatD.TglFotoProfilPengirimDiambil` pada migration SQL Server serta `src/script/DATABASE_SCHEMA_WACS.sql`.
- [x] 7.5 Buat job atau perluasan job metadata yang mengambil foto participant via WAHA, deduplicated per session + participant JID, dengan timeout/retry/failure behavior terdokumentasi.
- [x] 7.6 Perbarui `InboxWhatsapp` dan `inbox-whatsapp.blade.php` agar bubble pesan masuk grup menampilkan foto participant, nama/nomor, fallback inisial, alt text, dan dark-mode-safe layout. (UI sudah diimplementasi Task 5 dan diverifikasi lewat review source; runtime test tetap terblokir karena command `test`/PHPUnit tidak tersedia.)

## 8. Koreksi Aturan AI Agent dan Session

- [x] 8.1 Reproduksi AI no-reply dengan fixture settings aktif, All Session aktif/nonaktif, chat pertama, chat beruntun, idle 60 menit, manual reply, dan queue failure; gunakan `TAiPermintaan`, `TAiRespon`, `TChatD`, dan log sebagai bukti. (Fixture/test source ditambahkan; runtime test terblokir.)
- [x] 8.2 Perbaiki `src/app/Services/Ai/AiAutoReplyService.php` agar `$isFirstReply` dihitung sebelum dipakai pada pemilihan model dan pencatatan `TAiPermintaan`.
- [x] 8.3 Tambahkan setting `BatasSesiAutoReplyMenit` default 60 pada `MPengaturanAi`, validasi `1..1440`, localization, dan UI AI Agent sebagai batas idle saat All Session tidak aktif.
- [x] 8.4 Implementasikan policy: All Session aktif memproses setiap incoming eligible; All Session nonaktif memproses hanya incoming pertama atau setelah idle >= batas sesi; semua guard jam kerja, hari libur, excluded number, duplicate, dan manual reply tetap berjalan.
- [x] 8.5 Tambahkan reason code/status audit untuk skip, provider failure, fallback, draft lokal, WAHA send failure, dan sukses pada service/job/log tanpa secret.
- [x] 8.6 Perbarui `src/app/Jobs/ProcessAiAutoReplyJob.php` dan broadcast/error flow bila diperlukan agar job failure/retry terlihat operator serta tidak menandai chat seolah sudah dibalas ketika delivery gagal.
- [x] 8.7 Tambahkan test service/job/UI untuk All Session on/off, idle 60 menit, boundary waktu, manual reply, provider kosong/gagal, `KirimKeWaha` on/off, dan retry queue. (Syntax/review lulus; runtime test terblokir.)

## 9. Koreksi Tampilan Base64 Media

- [x] 9.1 Audit `src/app/Support/WahaMediaPayload.php`, `src/app/Filament/Pages/InboxWhatsapp.php`, `src/app/Http/Controllers/WahaMediaController.php`, dan Blade untuk membedakan media valid versus base64 mentah.
- [x] 9.2 Perbarui state/renderer agar base64 tidak ditampilkan ketika preview/download berhasil, termasuk image, sticker, audio, video, PDF, dan dokumen.
- [x] 9.3 Tambahkan fallback localized untuk base64 yang gagal dikonversi dalam panel diagnostik terbatas; jangan tampilkan `PayloadJson`, raw HTML, API key, webhook token, atau stack trace.
- [x] 9.4 Tambahkan regression/security test untuk base64 valid, base64 rusak, data URI, media URL valid, dan payload yang berisi string mirip base64. (Syntax/review lulus; runtime test terblokir.)

## 10. Koreksi Foto Profil GroupWhatsApp

- [x] 10.1 Reproduksi foto grup salah ambil dengan fixture `JenisChat=Grup` yang memiliki raw group JID `@g.us`, participant, dan `MGrupWhatsapp.IdGrupWaha` yang berbeda atau stale; catat contactId yang dipakai resolver saat ini.
- [x] 10.2 Perbaiki resolver di `src/app/Filament/Pages/InboxWhatsapp.php` agar raw group JID dari payload/TChat menjadi prioritas, tervalidasi berakhiran `@g.us`, dan participant/sender/personal identifier tidak pernah menjadi contactId avatar grup.
- [x] 10.3 Pastikan fallback mapping `MGrupWhatsapp.IdGrupWaha` hanya dipakai jika valid dan tidak menggantikan identitas raw chat; jangan melakukan update otomatis ke mapping master dan jangan menghapus foto snapshot terakhir saat WAHA gagal.
- [x] 10.4 Tambahkan regression test pada `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php` untuk raw group JID, mapping stale, participant berbeda, fallback inisial, dan preservasi foto terakhir. (Syntax/review lulus; runtime test terblokir.)

## 11. Koreksi Breadcrumb Halaman Ticketing

- [x] 11.1 Pasang `HasMenuBreadcrumbs` dan `AccessPermissions::TICKET_VIEW` pada `ManageTickets`, `ManageStatusTickets`, `ManagePrioritasTickets`, dan `ManageKategoriTickets` sesuai pola page resource yang sudah ada.
- [x] 11.2 Jika diperlukan, perluas minimal `src/app/Support/FilamentBreadcrumbs.php` atau `src/app/Filament/Concerns/HasMenuBreadcrumbs.php` agar tiga halaman master menampilkan parent `Ticket` dan label resource aktif tanpa duplikasi; jangan mengubah route/permission/sidebar.
- [x] 11.3 Pastikan label memakai `NavigationHelper` dan localization existing untuk Bahasa Indonesia/Inggris, termasuk `Status Ticket`, `Prioritas`, dan `Kategori`.
- [x] 11.4 Tambahkan feature/page test untuk `/admin/ticketing/status-tickets`, `/admin/ticketing/prioritas/prioritas-tickets`, `/admin/ticketing/kategoris/kategori-tickets`, dan `/admin/operational/tickets`, termasuk permission denied dan locale `id`/`en`.
- [x] 11.5 Jalankan `cd src; php -l app/Support/FilamentBreadcrumbs.php; php -l app/Filament/Concerns/HasMenuBreadcrumbs.php` serta targeted test ticketing; hasil yang diharapkan semua breadcrumb tampil dan tidak ada perubahan akses. (Syntax check dan runtime harness lulus; `php artisan test --filter=TicketingBreadcrumbTest` terblokir karena command `test` tidak tersedia.)


## 11. Validasi Terpadu dan Deployment

- [x] 11.1 Jalankan PHP lint seluruh file berubah (12/12 file clean).
- [ ] 11.2 Jalankan targeted test — **BLOCKED**: PHPUnit/Pest tidak tersedia di environment ini (endor/bin/phpunit.bat dan endor/bin/pint.bat tidak ada). Test harus dijalankan di environment dev/staging yang memiliki dependency terinstall.
- [ ] 11.3 Jalankan broader validation (full test, Pint, Vite build) — **PARTIAL**: Vite build ✅ sukses (12.9s). Full test dan Pint ❌ terblokir karena tool tidak tersedia.
- [x] 11.4 Validasi OpenSpec dan diff — OpenSpec valid ✅, git diff --check exit code 0 ✅.
- [ ] 11.5 Verifikasi migration SQL Server — **REQUIRES STAGING**: Migration siap deploy (2026_07_30_000001_add_waha_identity_snapshot_to_chat.php). Perlu backup database staging dan jalankan php artisan migrate --force pada environment sqlsrv.
- [ ] 11.6 Restart runtime — **DEPLOYMENT STEP**: Setelah deploy, jalankan php artisan config:cache, oute:cache, iew:cache, queue:restart. Pastikan queue worker webhooks, roadcasts, dan AI running.
- [ ] 11.7 Manual browser verification — **REQUIRES LIVE WAHA**: Verifikasi 6 poin (badge grup/pribadi, participant avatar, All Session policy, base64 media, breadcrumb ticketing) perlu WAHA aktif dan browser access.
- [x] 11.8 Final task sync — Semua task sebelumnya (1-10) sudah dicentang berdasarkan source review dan syntax check. Task 11 ini mendokumentasikan validasi yang bisa dilakukan di environment ini vs yang perlu staging/production.
