# Change: Tingkatkan Media dan Identitas Inbox WhatsApp

## Summary

Tingkatkan Inbox WhatsApp agar media pesan masuk yang dikirim WAHA sebagai base64 tetap dapat dipratinjau atau diunduh, serta tambahkan pilihan sumber identitas percakapan antara data WhatsApp asli dan data internal WACS. Mode WhatsApp asli menjadi default agar CS selalu dapat mengenali apakah percakapan berasal dari grup atau chat pribadi tanpa bergantung pada mapping master data.

## Problem Statement

Inbox saat ini hanya membuat URL media bila `TChatD.UrlMedia` terisi. WAHA juga dapat mengirim media sebagai base64 di payload webhook. Payload asli memang tersimpan di `TChatD.PayloadJson`, tetapi nilai base64 tersebut tidak dicari oleh `InboxWhatsapp` maupun oleh jalur awal `WahaMediaController`; akibatnya pesan video, audio, atau dokumen tertentu tampil sebagai media tidak tersedia.

Identitas chat juga lebih memprioritaskan hasil mapping internal: `formatChatRow()` mengganti nama grup/kontak dan nomor yang ditampilkan dengan nilai `MGrupWhatsapp` atau `MNomorWhatsapp` bila tersedia. CS tidak memiliki cara untuk beralih ke nama grup, JID, nomor, dan nama pengirim persis sebagaimana diterima dari WhatsApp. Untuk grup yang belum dipetakan, hal ini membuat asal grup kurang jelas.

## Current State

Verifikasi source aktual:

- `src/app/Services/Waha/WahaWebhookProcessor.php` menyimpan payload pesan asli di `TChatD.PayloadJson`; parser hanya mengisi `UrlMedia` dari key URL seperti `media.url`, `media.downloadUrl`, `mediaUrl`, dan `downloadUrl`.
- `src/app/Filament/Pages/InboxWhatsapp.php` membaca `UrlMedia`, `TipeMime`, dan `NamaFileMedia`. Renderer mengirim `MediaUrl` ke route `/admin/waha-media/{message}` hanya saat `UrlMedia` tidak kosong.
- `src/app/Http/Controllers/WahaMediaController.php` sudah dapat memproses data URL dan respons JSON WAHA yang berisi base64, tetapi tidak membaca base64 langsung dari `TChatD.PayloadJson` ketika `UrlMedia` kosong.
- `TChatD.UrlMedia` adalah `varchar(1000)`, sedangkan payload media disimpan di `TChatD.PayloadJson` (`nvarchar(max)`); menyimpan base64 langsung ke `UrlMedia` bukan solusi aman untuk data existing maupun file besar.
- `InboxWhatsapp::formatChatRow()` sudah mempunyai data mentah WhatsApp (`NamaKontak`, `NamaGrupWhatsapp`, `NomorWhatsapp`, `IdWahaTerdeteksi`, `NomorWhatsappTerdeteksi`) dan data internal dari join `MNomorWhatsapp`/`MGrupWhatsapp`, tetapi menggabungkannya menjadi satu tampilan.
- Route penting `GET /admin/waha-media/{message}` sudah dilindungi middleware `auth` dan harus dipertahankan.

## Goals

- Media base64 yang sudah tersimpan pada payload pesan dapat diakses lewat route media internal, ditampilkan sesuai jenisnya, dan diunduh oleh pengguna Inbox yang terautentikasi.
- Video, audio/voice note, gambar, sticker, PDF, dokumen, dan file lain memperoleh fallback aman saat media berasal dari URL, data URL, atau base64 mentah/nested dalam payload WAHA.
- MIME media ditentukan secara berurutan dari `TChatD.TipeMime`, data URI, metadata payload, nama file, file signature, lalu fallback `application/octet-stream`.
- CS dapat memilih tampilan **WhatsApp asli** atau **data internal WACS**; mode WhatsApp asli menjadi nilai awal halaman.
- Kedua mode secara konsisten membedakan chat `Grup` dan `Pribadi` pada daftar chat, header chat, serta detail identitas.
- Tidak ada perubahan schema, migration, data historis, atau kontrak route yang ada.

## Non-Goals

- Tidak menyimpan ulang atau memigrasikan base64 menjadi file pada disk/object storage.
- Tidak menambah sinkronisasi anggota grup, rename grup, atau perubahan mapping `MGrupWhatsapp`.
- Tidak mengubah webhook token, kontrak payload WAHA, queue, auto-reply AI, atau alur pengiriman pesan keluar.
- Tidak membuat preferensi mode tampilan permanen per pengguna; pilihan berlaku pada state halaman Livewire dan kembali ke WhatsApp asli saat halaman dimuat ulang.
- Tidak mengubah permission Inbox atau menambah akses baru ke endpoint media di luar middleware `auth` yang ada.

## Proposed Changes

1. **Deteksi media embedded terpusat.** Tambahkan helper ringan untuk membaca `TChatD.PayloadJson`, mengenali data URL dan kandidat base64 WAHA yang digunakan controller saat ini (`base64`, `data`, `file`, `body`, serta nested `media.*`), dan membedakan payload yang benar-benar dapat didekode dari teks biasa. Helper menentukan MIME secara berurutan dari kolom pesan, data URI, metadata payload, nama file, dan file signature tanpa menulis data atau mengubah struktur tabel.

2. **Fallback media pada endpoint yang sama.** Perbarui `WahaMediaController` agar saat `UrlMedia` kosong atau bukan sumber media yang dapat digunakan, controller memeriksa `PayloadJson`, mendekode base64 secara ketat, menentukan MIME dari `TipeMime` atau metadata payload, lalu mengembalikan respons privat. Tambahkan opsi query `?download=1` yang mengirim `Content-Disposition: attachment`; tanpa opsi tersebut respons tetap `inline` agar gambar/video/audio dapat dirender browser.

3. **Renderer Inbox yang mengenali base64.** Saat memuat pesan, `InboxWhatsapp` membuat `MediaUrl` apabila sumber URL atau kandidat media embedded tersedia. Blade menyediakan preview inline untuk gambar/sticker, video, audio/voice note, dan PDF yang aman didukung browser; dokumen atau format lain ditampilkan sebagai file. Setiap media mendapat aksi unduh melalui route media dengan `download=1`. Media malformed atau tidak dapat didekode menampilkan status tidak tersedia tanpa mengekspos isi payload.

4. **Dua proyeksi identitas chat.** Pisahkan data identitas raw WhatsApp dan data internal dalam row inbox. Tambahkan state Livewire `identityDisplayMode` dengan nilai `whatsapp` sebagai default dan `internal` sebagai alternatif. Tombol/selector berlabel localized mengubah daftar, header, dan panel detail tanpa mengubah mapping atau data database.

5. **Identitas grup eksplisit.** Pada mode WhatsApp, grup memakai nama raw `TChat.NamaGrupWhatsapp` dan JID/nomor grup WAHA yang tersedia dengan badge/label `Grup WhatsApp`. Pada mode internal, nama grup, instansi, dan nomor berasal dari mapping internal dengan fallback raw bila mapping belum lengkap. Nama dan nomor pengirim setiap pesan grup tetap berasal dari `TChatD.PengirimNamaKontak` dan `TChatD.PengirimNomorWhatsapp`; identifier anggota tidak boleh dipakai sebagai identifier grup atau sebaliknya.

6. **Observability aman.** Bila decoding media gagal, log hanya menyimpan ID pesan, kategori media, dan alasan singkat; base64, payload lengkap, API key WAHA, dan URL bertanda tangan tidak boleh dicatat.

## Impacted Areas

| Area | Detail |
| --- | --- |
| File baru | `src/app/Support/WahaMediaPayload.php` untuk deteksi/ekstraksi media embedded tanpa menyimpan ulang payload. |
| Backend | `src/app/Http/Controllers/WahaMediaController.php` dan `src/app/Filament/Pages/InboxWhatsapp.php`. |
| UI | `src/resources/views/filament/pages/inbox-whatsapp.blade.php` untuk selector identitas dan aksi unduh media. |
| Localization | `src/lang/id/ui.php` dan `src/lang/en/ui.php` untuk label mode identitas, WhatsApp asli, data internal, unduh media, dan fallback media. |
| Database | Tidak ada migration/schema/data change; tetap memakai `TChatD.UrlMedia`, `TChatD.PayloadJson`, `TChatD.TipeMime`, dan `TChatD.NamaFileMedia`. |
| API/Route | Kontrak `GET /admin/waha-media/{message}` dipertahankan; ditambah query opsional `download=1`. |
| Permission/Security | Route tetap membutuhkan `auth`; tidak menambah permission baru. Konten media tetap `private` dan tidak mencatat payload sensitif. |
| Queue/Event/Deployment | Tidak ada job, queue, event, scheduler, atau environment variable baru. |

## Risks and Mitigations

| Risiko | Mitigasi |
| --- | --- |
| Base64 besar membebani memori PHP saat diunduh | Decode hanya saat endpoint media dipanggil, bukan saat daftar inbox dimuat; jangan menyalin payload ke `UrlMedia` atau state Livewire. |
| Teks biasa salah dianggap base64 | Gunakan `base64_decode(..., true)`, validasi hasil tidak kosong, dan hanya cek key media yang eksplisit. |
| MIME hilang atau keliru | Prioritaskan `TChatD.TipeMime`, data URI, metadata payload, nama file, lalu hasil `finfo`/file signature; gunakan `application/octet-stream` bila tetap tidak dikenal. |
| Raw WhatsApp tidak lengkap pada data lama | Mode WhatsApp memiliki fallback ke identitas tersedia, sementara mode internal tetap memakai mapping bila lengkap. |
| Pengguna salah memahami sumber identitas | Selector dan badge menjelaskan mode aktif serta label `Grup WhatsApp`/`Chat Pribadi` secara konsisten. |
| Kebocoran payload dalam log | Log hanya metadata aman dan alasan decoding; jangan log base64 atau `PayloadJson`. |

## Validation

- Unit test helper media untuk data URL, base64 raw, base64 nested WAHA, image/video/audio/PDF/document/sticker, deteksi MIME dan nama file, teks non-media, serta base64 malformed.
- Feature test endpoint media untuk respons `inline`, `attachment` (`download=1`), MIME fallback, dan media tidak tersedia.
- Test/coverage Inbox untuk URL media saat payload embedded tersedia, pilihan mode `whatsapp`/`internal`, identitas grup, dan pemisahan identifier grup dari identifier pengirim.
- Jalankan `php -l` pada setiap PHP yang berubah, `php artisan test --filter=InboxWhatsapp`, `php artisan test --filter=WahaMedia`, `vendor/bin/pint --test`, dan `npm run build` karena Blade berubah.
- Verifikasi manual menggunakan pesan grup dan pribadi, serta contoh gambar/video/audio/dokumen yang berasal dari URL dan base64.

## Rollback

- Tidak ada migration atau perubahan data untuk di-rollback.
- Rollback cukup dengan mengembalikan file aplikasi, Blade, translation, dan test pada deploy sebelumnya.
- Tidak ada queue worker, Reverb, scheduler, atau cache media baru yang harus dibersihkan; setelah rollback jalankan `php artisan optimize:clear` bila deployment telah melakukan cache konfigurasi/view.
