## Context

Inbox WhatsApp menyimpan detail pesan pada TChatD. Source aktual memiliki UrlMedia untuk URL/data URI dan PayloadJson untuk payload webhook WAHA asli. InboxWhatsapp::selectChat() hanya membentuk route media saat UrlMedia terisi. WahaMediaController sudah dapat memproses data URI serta body JSON dari URL WAHA yang berisi base64, tetapi belum memakai PayloadJson saat UrlMedia kosong.

TChat dan TChatD menyimpan identitas raw yang diterima dari WhatsApp. Join MNomorWhatsapp, MGrupWhatsapp, MCustomer, dan MInstansi menyediakan identitas internal. InboxWhatsapp::formatChatRow() saat ini memprioritaskan mapping internal sehingga CS tidak dapat konsisten melihat data WhatsApp asli. Untuk grup, identifier grup dan identifier anggota pengirim harus dipisahkan agar nama/nomor anggota tidak menggantikan JID atau nama grup.

Perubahan mencakup helper parsing, controller media, state Livewire, Blade, localization, dan test. Route /admin/waha-media/{message} serta middleware auth dipertahankan. Tidak ada migration, dependency, queue, webhook contract, atau environment variable baru.

## Goals / Non-Goals

**Goals:**

- Menyediakan fallback PayloadJson untuk image, sticker, video, audio/voice note, PDF, dokumen, dan file lain ketika UrlMedia kosong atau tidak dapat digunakan.
- Menentukan MIME dan nama file secara konservatif tanpa mengekspos payload media.
- Memberikan preview inline hanya untuk format browser yang aman serta download untuk semua media valid.
- Menyediakan mode identitas whatsapp sebagai default dan internal sebagai alternatif tanpa mutasi mapping database.
- Menampilkan identitas grup dan anggota pengirim secara terpisah.
- Mempertahankan kompatibilitas PHP 8.3+, Laravel 13, Filament 5, dan SQL Server.

**Non-Goals:**

- Memindahkan base64 ke storage, mengubah schema, atau memigrasikan data historis.
- Menambah sinkronisasi anggota grup atau mengubah master grup/nomor WhatsApp.
- Menyimpan preferensi mode identitas per pengguna.
- Mengubah autentikasi, permission Inbox, webhook WAHA, queue, auto-reply AI, atau pengiriman pesan keluar.
- Menambahkan dependency baru.

## Decisions

### 1. Helper WahaMediaPayload menjadi parser media embedded tunggal

Tambahkan src/app/Support/WahaMediaPayload.php. Helper menerima PayloadJson dan metadata TChatD, lalu menghasilkan hasil inspeksi terstruktur: kandidat media, sumber kandidat, MIME tervalidasi, nama file aman, kategori media, dan binary decode hanya ketika diminta controller.

Parser hanya memeriksa key media eksplisit yang relevan dengan payload WAHA: dataUrl, data_url, base64, data, file, body, dan varian nested media.*. Parser tidak mencari setiap string secara rekursif karena teks biasa dapat menyerupai base64. Data URI dipisahkan menjadi metadata dan konten. Base64 mentah hanya dinormalisasi dengan whitespace ASCII yang sah dan didekode dalam mode strict. JSON tidak valid, kandidat kosong, karakter/padding invalid, dan hasil decode kosong diperlakukan sebagai media tidak tersedia.

Alternatif menyimpan base64 di UrlMedia ditolak karena UrlMedia bertipe varchar(1000), tidak cocok untuk file besar, dan akan menggandakan data. Alternatif parsing terpisah di controller dan Livewire ditolak karena menyebabkan aturan MIME dan keamanan berbeda.

### 2. MIME memakai hierarki deterministik dan fallback aman

MIME diselesaikan berurutan dari TChatD.TipeMime, MIME data URI, metadata payload seperti mimetype/mimeType/contentType termasuk nested media.*, ekstensi NamaFileMedia atau nama file payload, hasil finfo_buffer(FILEINFO_MIME_TYPE) terhadap binary decode, lalu application/octet-stream.

MIME dinormalisasi ke bentuk type/subtype, parameter dibuang, dan karakter kontrol ditolak. Kategori renderer memakai MIME final dengan JenisPesan sebagai petunjuk sekunder. WebP sticker adalah image; ptt/voice dengan MIME audio adalah audio. Nilai MIME payload tidak dipercaya tanpa normalisasi dan file signature hanya menjadi fallback/verifikasi.

### 3. Nama file disanitasi sebelum Content-Disposition

Nama file berasal dari NamaFileMedia, metadata payload, lalu fallback berbasis kategori dan MIME. Sanitasi mengambil basename, menghapus path separator, quote, kontrol, dan karakter header; merapikan whitespace; serta membatasi panjang. Ekstensi yang hilang dapat ditambahkan dari MIME final. Nama kosong/tidak aman memakai fallback whatsapp-image, whatsapp-video, whatsapp-audio, whatsapp-document, atau whatsapp-media.

Controller menggunakan mekanisme disposition Symfony/Laravel, bukan membangun header secara manual. Pendekatan ini mencegah path traversal dan header injection sekaligus memberi fallback filename ASCII/UTF-8.

### 4. WahaMediaController mempertahankan satu route dan pipeline sumber media

Controller memuat UrlMedia, PayloadJson, TipeMime, dan NamaFileMedia lalu mencoba sumber berikut secara berurutan: data URI; storage lokal; URL WAHA/proxy termasuk respons JSON berisi media; dan fallback embedded PayloadJson bila sumber URL kosong atau tidak menghasilkan binary valid. Bila semua sumber gagal, controller hanya mengembalikan respons media tidak tersedia yang generik.

Parameter download=1 menghasilkan attachment. Tanpa parameter, controller menghasilkan inline hanya untuk image raster termasuk WebP, audio, video, dan application/pdf. HTML, SVG, script, executable, dan MIME tidak dikenal diperlakukan sebagai attachment. Respons valid memakai Cache-Control private, X-Content-Type-Options nosniff, MIME final ter-normalisasi, dan nama file aman. Route tetap auth-only tanpa permission baru.

### 5. Livewire mendeteksi kandidat, controller melakukan decode penuh

selectChat() ikut memilih PayloadJson dan membentuk MediaUrl serta MediaDownloadUrl jika UrlMedia tersedia atau helper menemukan kandidat embedded. Array messages tidak menyimpan base64 atau binary; hanya ID pesan, metadata presentasi, dan route. Decode penuh berlangsung saat endpoint dipanggil agar daftar chat tidak memegang salinan binary besar. Controller tetap menjadi validator final; kandidat yang malformed menghasilkan media tidak tersedia tanpa payload bocor.

### 6. Renderer memakai allowlist preview dan selalu memberi download

Blade memakai image untuk gambar/sticker, video untuk video, audio untuk audio/voice note, dan presentasi/link browser-kompatibel untuk PDF. Document serta format lain memakai file card/link. Semua media dengan MediaUrl memiliki aksi download melalui MediaDownloadUrl. UI tidak merender base64, JSON payload, atau detail exception.

### 7. Dua proyeksi identitas dipisahkan dalam row Inbox

InboxWhatsapp menambahkan properti publik identityDisplayMode dengan nilai awal whatsapp. Hanya whatsapp dan internal yang valid; nilai lain kembali ke whatsapp.

formatChatRow() membentuk proyeksi whatsapp dari NamaGrupWhatsapp, NamaKontak, NomorWhatsapp, IdWahaTerdeteksi, NomorWhatsappTerdeteksi, serta identifier chat payload. Proyeksi internal memprioritaskan MGrupWhatsapp, MNomorWhatsapp, MCustomer, dan MInstansi, dengan fallback per field ke proyeksi whatsapp. Blade memilih proyeksi aktif untuk daftar, header, dan detail. Toggle tidak mengubah database, mapping, chat terpilih, filter, atau urutan; pencarian tetap mencakup raw dan internal.

### 8. Identifier grup dan pengirim pesan tidak boleh tertukar

Untuk JenisChat Grup, identitas chat hanya berasal dari field grup atau JID chat berakhiran @g.us. Participant, author, sender.id, dan _data.author tidak boleh menjadi IdWaha atau nomor grup. Bubble pesan masuk menggunakan TChatD.PengirimNamaKontak dan TChatD.PengirimNomorWhatsapp; fallback metadata pengirim hanya ketika kedua kolom belum tersedia. Header dan daftar chat selalu menampilkan grup, bukan anggota terakhir.

Mode whatsapp memberi badge localized Grup WhatsApp atau Chat Pribadi. Pada grup, UI menampilkan nama grup dan JID/nomor grup terpisah dari nama serta nomor pengirim.

### 9. Observability tidak membocorkan media atau secret

Kegagalan parsing/decode hanya boleh menyimpan metadata aman: ID pesan, sumber kandidat, kategori, dan kode/alasan pendek. Log dan respons tidak boleh memuat base64, PayloadJson, body media, URL signed lengkap, API key WAHA, token webhook, password, atau access token.

## Risks / Trade-offs

- **[Base64 besar memakai memori saat download]** → Decode hanya pada endpoint media dan jangan bawa binary ke state Livewire.
- **[Inspeksi Livewire bukan validasi binary final]** → Controller melakukan strict decode dan failure path aman.
- **[Variasi payload WAHA]** → Gunakan key eksplisit yang dibuktikan oleh fixture tersanitasi; perluasan key wajib disertai test.
- **[MIME dipalsukan]** → Normalisasi, fallback file signature, nosniff, dan allowlist inline.
- **[Nama file berbahaya]** → Sanitasi basename dan gunakan disposition Symfony/Laravel.
- **[JID grup tertukar dengan peserta]** → Wajibkan @g.us untuk identifier grup payload dan pisahkan extractor grup/pengirim.
- **[Preview PDF berbeda antar browser]** → Download selalu tersedia; preview hanya enhancement.

## Migration Plan

1. Tambahkan test helper, controller, Livewire, dan renderer sebelum perubahan perilaku produksi.
2. Deploy file aplikasi dan asset build tanpa migration atau perubahan environment.
3. Jalankan build asset serta optimize:clear sesuai prosedur deployment.
4. Verifikasi satu chat pribadi, grup mapped, grup unmapped, media URL, dan media embedded untuk semua kategori yang didukung.
5. Rollback dengan mengembalikan file aplikasi dan asset sebelumnya; tidak ada data/schema yang perlu dipulihkan.

Tidak diperlukan restart queue, Reverb, atau scheduler karena change tidak mengubah worker maupun event contract.

## Open Questions

Tidak ada keputusan produk terbuka. Implementasi wajib mengumpulkan sampel payload WAHA tersanitasi untuk mengonfirmasi key aktual; key baru hanya boleh ditambahkan melalui helper dan test keamanan yang sama.
