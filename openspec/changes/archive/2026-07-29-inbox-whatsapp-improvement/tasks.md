## 1. Analisis Payload dan Test Fixture

- [x] 1.1 Kumpulkan sampel payload WAHA tersanitasi untuk image, sticker, video, audio/voice note, PDF, dokumen, file lain, base64 malformed, chat pribadi, dan chat grup tanpa menyimpan secret atau data pribadi.
- [x] 1.2 Buat matriks data provider test untuk sumber UrlMedia, data URI, base64 raw, nested media.*, MIME, nama file, signature, kategori preview, dan hasil download.
- [x] 1.3 Verifikasi dari sampel bahwa identifier grup berasal dari chat/JID @g.us, sedangkan participant, author, dan sender adalah identitas anggota.

## 2. Helper Parsing Media

- [x] 2.1 Tambahkan unit test src/tests/Unit/Support/WahaMediaPayloadTest.php untuk JSON invalid, key kosong, teks biasa, data URI, base64 raw, nested base64, whitespace, base64 malformed, dan hasil decode kosong.
- [x] 2.2 Tambahkan test deteksi MIME berurutan dari TipeMime, data URI, metadata payload, nama file, file signature, hingga application/octet-stream.
- [x] 2.3 Tambahkan test kategori image/sticker, video, audio/voice note, PDF, document, file unknown, serta allowlist preview yang menolak format aktif/berisiko.
- [x] 2.4 Tambahkan test sanitasi nama file untuk path traversal, slash/backslash, quote, CRLF, kontrol, panjang berlebih, Unicode, ekstensi hilang, dan fallback nama.
- [x] 2.5 Implementasikan src/app/Support/WahaMediaPayload.php dengan key eksplisit, data URI, strict base64 decode, MIME resolution, file signature, kategori media, dan filename aman tanpa dependency baru.
- [x] 2.6 Jalankan php -l app/Support/WahaMediaPayload.php dan php artisan test --filter=WahaMediaPayloadTest; hasil yang diharapkan syntax valid dan seluruh unit test helper lulus.

## 3. Endpoint Media dan Download

- [x] 3.1 Tambahkan feature test src/tests/Feature/Http/Controllers/WahaMediaControllerTest.php untuk media URL, data URI, storage lokal, respons JSON WAHA berisi base64, dan fallback PayloadJson saat UrlMedia kosong.
- [x] 3.2 Tambahkan integration test fallback PayloadJson ketika UrlMedia gagal/kosong untuk image, sticker, video, audio/voice note, PDF, document, dan file unknown.
- [x] 3.3 Tambahkan test download=1 untuk Content-Disposition attachment, filename UTF-8/fallback aman, MIME final, Cache-Control private, dan X-Content-Type-Options nosniff.
- [x] 3.4 Tambahkan test inline allowlist untuk image raster/WebP, audio, video, PDF, serta penolakan inline HTML, SVG aktif, executable, dan MIME unknown.
- [x] 3.5 Tambahkan test autentikasi/failure untuk guest, ID tidak ada, base64 malformed, JSON invalid, body kosong, upstream WAHA gagal, dan seluruh fallback gagal.
- [x] 3.6 Perbarui src/app/Http/Controllers/WahaMediaController.php agar memilih sumber media berurutan dan memakai WahaMediaPayload sebagai fallback tanpa mengubah route admin.waha-media.show.
- [x] 3.7 Pastikan log controller hanya memuat ID pesan, sumber/kategori, status, dan alasan aman; jangan mencatat URL lengkap, body, payload, base64, API key, atau token.
- [x] 3.8 Jalankan php -l app/Http/Controllers/WahaMediaController.php dan php artisan test --filter=WahaMediaControllerTest; hasil yang diharapkan seluruh test endpoint lulus.

## 4. Livewire Media dan Proyeksi Identitas

- [x] 4.1 Tambahkan test src/tests/Feature/Filament/Pages/InboxWhatsappTest.php bahwa selectChat() membentuk MediaUrl dan MediaDownloadUrl untuk UrlMedia maupun embedded PayloadJson tanpa menyimpan base64/binary di state messages.
- [x] 4.2 Perbarui query pesan src/app/Filament/Pages/InboxWhatsapp.php agar memilih PayloadJson dan memakai WahaMediaPayload untuk inspeksi kandidat serta metadata presentasi.
- [x] 4.3 Tambahkan properti publik identityDisplayMode = whatsapp dengan validasi nilai whatsapp/internal dan fallback ke whatsapp.
- [x] 4.4 Refactor minimal formatChatRow()/loadChatHeader() agar menghasilkan proyeksi identitas WhatsApp dan internal terpisah tanpa memutus field yang masih dipakai caller.
- [x] 4.5 Pastikan proyeksi WhatsApp grup memakai TChat.NamaGrupWhatsapp dan chat/JID @g.us; proyeksi internal memakai MGrupWhatsapp/MInstansi dengan fallback per field.
- [x] 4.6 Pastikan participant/author/sender tidak menjadi identifier grup dan identifier grup tidak menggantikan TChatD.PengirimNamaKontak/PengirimNomorWhatsapp.
- [x] 4.7 Pastikan chat pribadi memprioritaskan raw WhatsApp pada mode whatsapp dan mapping nomor/customer/instansi pada mode internal.
- [x] 4.8 Tambahkan test bahwa toggle tidak mengubah chat terpilih, filter, urutan pesan, mapping database, atau pencarian raw/internal.
- [x] 4.9 Jalankan php -l app/Filament/Pages/InboxWhatsapp.php dan php artisan test --filter=InboxWhatsappTest; hasil yang diharapkan test Livewire/identitas lulus.

## 5. Blade, Preview, Download, dan Localization

- [x] 5.1 Tambahkan key src/lang/id/ui.php dan src/lang/en/ui.php untuk mode identitas, Grup WhatsApp, Chat Pribadi, pengirim, grup, preview, download, media tidak tersedia, dan format tidak dikenal.
- [x] 5.2 Tambahkan toggle identityDisplayMode pada src/resources/views/filament/pages/inbox-whatsapp.blade.php yang keyboard-accessible, memiliki state aktif jelas, dan localized.
- [x] 5.3 Perbarui daftar, header, dan detail chat agar memilih proyeksi aktif serta menampilkan badge Grup WhatsApp/Chat Pribadi.
- [x] 5.4 Perbarui bubble grup agar nama/nomor pengirim tampil terpisah dari nama dan ID/nomor grup.
- [x] 5.5 Tambahkan preview image/sticker, video, audio/voice note, dan PDF melalui MediaUrl; gunakan file card/link untuk document dan format lain.
- [x] 5.6 Tambahkan aksi download localized untuk setiap media melalui MediaDownloadUrl, termasuk media yang dapat dipreview.
- [x] 5.7 Pastikan media malformed/tidak dikenal memakai fallback localized tanpa merender base64, JSON, raw HTML, atau detail error internal.
- [x] 5.8 Jalankan npm run build; hasil yang diharapkan build Vite/Tailwind selesai tanpa error.

## 6. Regression dan Keamanan

- [x] 6.1 Tambahkan regression test route /admin/waha-media/{message}, middleware auth, dan kompatibilitas media existing tanpa download=1.
- [x] 6.2 Tambahkan regression test Inbox untuk filter pribadi/grup/keduanya, pemilihan chat, unread count, reply, internal note, dan close conversation.
- [x] 6.3 Tambahkan security test path traversal/header injection filename dan pencegahan inline HTML/SVG/script/executable melalui MIME eksternal.
- [x] 6.4 Tambahkan security test bahwa respons/log tidak mengandung base64, PayloadJson, body media, API key WAHA, webhook token, password, access token, atau signed URL.
- [ ] 6.5 Verifikasi manual mode whatsapp/internal pada chat pribadi, grup mapped, dan grup unmapped; pastikan anggota tidak tertukar dengan identitas grup.
- [ ] 6.6 Verifikasi manual preview/download media URL dan embedded: image, WebP sticker, video, voice note, PDF, document, unknown, dan base64 rusak.

## 7. Validasi dan Deployment

- [x] 7.1 Jalankan php -l pada seluruh PHP yang berubah; hasil yang diharapkan tidak ada syntax error.
- [x] 7.2 Jalankan targeted test WahaMediaPayloadTest, WahaMediaControllerTest, dan InboxWhatsappTest; hasil yang diharapkan seluruhnya lulus.
- [x] 7.3 Jalankan php artisan test; dokumentasikan kegagalan unrelated tanpa mengubah scope.
- [x] 7.4 Jalankan vendor/bin/pint --test; hasil yang diharapkan file berubah memenuhi formatter project.
- [x] 7.5 Jalankan npm run build; hasil yang diharapkan build produksi selesai tanpa error.
- [x] 7.6 Jalankan openspec validate inbox-whatsapp-improvement --strict; hasil yang diharapkan valid tanpa error/warning.
- [x] 7.7 Dokumentasikan deployment tanpa migration/backup data, deploy file dan asset, lalu jalankan php artisan optimize:clear; queue, Reverb, dan scheduler tidak perlu restart.
- [x] 7.8 Dokumentasikan rollback dengan mengembalikan file aplikasi dan asset sebelumnya; tidak ada rollback schema/data.


## Catatan Validasi 2026-07-29

- Targeted syntax, PHPUnit (`WahaMediaPayloadTest`, `WahaMediaControllerTest`, `InboxWhatsappTest`), Pint, Vite build, dan OpenSpec strict sudah lulus.
- `php artisan test` penuh sudah dicoba tetapi terblokir sebelum menjalankan test oleh konfigurasi lokal SQL Server ODBC: `Encryption not supported on the client`; ini dicatat sebagai blocker environment, bukan hasil lulus.
- Checklist 6.5 dan 6.6 tetap terbuka karena membutuhkan verifikasi manual dengan WAHA/WhatsApp aktif atau data representatif di browser nyata.

