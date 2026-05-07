# PLAN - Fitur Mulai Chat Terlebih Dahulu

## 1. Tujuan

Membuat fitur agar CS/admin bisa memulai chat WhatsApp terlebih dahulu dari aplikasi, tanpa menunggu customer mengirim pesan masuk.

Fitur ini harus tetap menyatu dengan alur operasional yang sudah ada:

- Chat yang dibuat CS tetap masuk ke `TChat`.
- Pesan awal yang diketik CS tetap masuk ke `TChatD`.
- Jika pesan dikirim langsung via WAHA, pengiriman tetap memakai service WAHA yang sudah ada.
- Jika customer membalas, balasan harus masuk ke sesi chat yang sama di Inbox WhatsApp, bukan membuat sesi baru selama masih ada sesi aktif untuk nomor tersebut.
- Chat yang sudah ditutup tetap menjadi histori; jika customer membalas setelah sesi ditutup, aplikasi boleh membuat sesi aktif baru sesuai pola Inbox yang sekarang.

## 2. Keputusan Lokasi Fitur

### Rekomendasi utama

Fitur ini sebaiknya masuk ke menu `/admin/inbox-whatsapp`, bukan dibuat menu terpisah.

Alasannya:

- Setelah chat dibuat, chat tersebut secara operasional adalah sesi Inbox.
- CS perlu langsung melihat thread, draft, balasan, status kirim, dan pesan masuk di tempat yang sama.
- Jika dibuat menu terpisah, ada risiko CS bingung karena chat dimulai di satu menu tetapi jawabannya masuk ke Inbox.
- Inbox WhatsApp sudah menjadi pusat kerja CS untuk menerima, membalas, mengambil alih, dan menutup chat.
- Histori Chat sebaiknya tetap untuk pencarian dan pembacaan sesi lama, bukan untuk memulai percakapan.

### Bentuk UI yang disarankan

Tambahkan tombol `Buat Chat` berbentuk FAB seperti pola Android.

- Label Indonesia untuk tooltip/modal: `Buat Chat`
- Label Inggris untuk tooltip/modal: `Create Chat`
- Bentuk tombol: bulat, hanya icon plus.
- Posisi: pojok kanan bawah di dalam panel `Daftar Chat`.
- Tombol tidak ditempatkan di header agar daftar chat tetap bersih dan mudah discan.
- Tombol harus tetap memakai tooltip/title multilingual agar icon plus tetap dapat dipahami.

FAB ini membuka dialog/modal Filament di halaman Inbox yang sama.

Tidak ada redirect dan tidak membuka halaman baru.

## 3. Alur Pengguna

### 3.1 CS membuat chat baru

1. CS membuka `/admin/inbox-whatsapp`.
2. CS klik FAB icon plus `Buat Chat` di pojok kanan bawah panel `Daftar Chat`.
3. Aplikasi membuka dialog/modal di halaman Inbox yang sama.
4. CS memilih tujuan chat:
   - Dari kontak/nomor yang sudah ada di `MNomorWhatsapp`.
   - Atau input nomor manual jika diizinkan.
5. CS memilih sesi WAHA pengirim jika ada lebih dari satu sesi aktif.
6. CS mengisi pesan awal.
7. CS memilih mode:
   - `Kirim sekarang`
   - `Simpan sebagai draft`
8. CS klik submit.
9. Aplikasi mencari apakah sudah ada sesi aktif untuk nomor tersebut.
10. Jika sesi aktif ditemukan, aplikasi memakai sesi tersebut.
11. Jika belum ada sesi aktif, aplikasi membuat record baru di `TChat`.
12. Aplikasi menyimpan pesan awal ke `TChatD`.
13. Jika mode `Kirim sekarang`, aplikasi mengirim pesan via WAHA.
14. Setelah berhasil, aplikasi menutup modal, reload daftar Inbox, dan langsung membuka sesi chat tersebut.

### 3.2 Jika nomor yang sama sudah punya chat aktif

Jika CS memulai chat ke nomor yang sudah ada sesi aktif:

- Jangan membuat `TChat` baru.
- Pakai `TChat.Id` yang sudah aktif.
- Tambahkan pesan CS ke `TChatD`.
- Pilih/open sesi tersebut di Inbox.

Ini penting agar satu customer tidak memiliki dua thread aktif paralel.

### 3.3 Jika nomor pernah chat tetapi sudah ditutup

Jika sesi sebelumnya berstatus tutup:

- Buat sesi aktif baru.
- Sesi lama tetap ada di Histori Chat.
- Sesi baru muncul di Inbox WhatsApp.

Ini konsisten dengan pola saat customer membalas setelah sesi lama ditutup.

### 3.4 Jika customer membalas chat yang dimulai CS

Balasan customer harus masuk ke sesi Inbox yang sama.

Aturan matching webhook:

- Normalisasi nomor WhatsApp customer ke format angka, misalnya `628xxxxxxxxxx`.
- Cari `TChat` aktif berdasarkan:
  - `NomorWhatsapp`
  - `IdNomorWhatsapp` jika tersedia
  - `IdSesiWhatsapp` atau sesi WAHA yang relevan jika struktur saat ini membedakan sesi
  - status belum ditutup
- Jika ditemukan, insert pesan masuk ke `TChatD` dengan `IdChat` yang sama.
- Update `TChat.TglChatTerakhir`.
- Update jumlah pesan belum dibaca sesuai pola Inbox yang sudah ada.
- Chat tetap muncul di Inbox WhatsApp.

Jadi jawabannya: jika nomor tersebut membalas, tempat yang benar adalah `Inbox WhatsApp`, pada sesi chat yang sama yang sebelumnya dibuat oleh CS.

## 4. Data dan Tabel

### 4.1 TChat

Dipakai sebagai header sesi chat.

Untuk chat yang dimulai CS, field yang perlu diisi atau dipastikan:

- `Id`
- `JenisChat`
- `NomorWhatsapp`
- `IdNomorWhatsapp` jika nomor berasal dari master nomor
- `IdCustomer` jika bisa diturunkan dari master nomor
- `IdInstansi` jika bisa diturunkan dari customer/nomor
- `IdStatusChat`
- `TglChatTerakhir`
- `TglDibalasTerakhir`
- `DiambilOleh` jika chat langsung dianggap diambil CS pembuat
- `JumlahPesanBelumDibaca` default `0`
- `NamaKontak` atau field display lain jika tersedia
- `IdWahaTerdeteksi` bila ada informasi JID WAHA

### 4.2 TChatD

Dipakai untuk detail pesan.

Untuk pesan awal dari CS:

- `IdChat`
- isi pesan
- arah pesan keluar / dari CS sesuai pola field yang sekarang
- user pengirim CS
- waktu pesan
- status lokal/draft/terkirim/gagal sesuai pola field yang sekarang
- metadata WAHA response jika sudah tersedia di struktur sekarang

### 4.3 TAiPermintaan

Untuk chat yang dibuat manual oleh CS, tidak perlu membuat `TAiPermintaan` otomatis.

Alasannya:

- `TAiPermintaan` berhubungan dengan proses AI.
- Chat manual dari CS bukan permintaan AI.
- Jika nanti ada fitur "buatkan draft AI", itu bisa menjadi fase lanjutan.

### 4.4 MNomorWhatsapp

Dipakai untuk pencarian target.

Jika nomor dipilih dari master:

- Ambil nomor normal dari `MNomorWhatsapp.NomorWhatsapp`.
- Ambil `IdCustomer` jika tersedia.
- Ambil `IdInstansi` dari relasi customer/instansi jika sudah ada pola di model.
- Ambil nama kontak untuk display.

Jika nomor manual diizinkan:

- Normalisasi nomor menjadi angka saja.
- Validasi minimal panjang.
- Opsional: minta CS mengisi nama kontak sementara.

### 4.5 MPengaturanAi.ExcludeNomorWhatsapp

Nomor yang masuk daftar exclude tidak boleh dibuatkan chat baru melalui fitur ini.

Aturan:

- Sebelum membuat `TChat`, cek nomor target terhadap `ExcludeNomorWhatsapp`.
- Jika cocok, tampilkan validasi:
  - ID: `Nomor ini masuk daftar exclude dan tidak dapat dibuatkan chat.`
  - EN: `This number is excluded and cannot be used to start a chat.`
- Jangan insert ke `TChat`.
- Jangan insert ke `TChatD`.
- Jangan kirim ke WAHA.
- Jangan membuat `TAiPermintaan`.

Ini menjaga kebijakan exclude tetap konsisten antara pesan masuk dan chat yang dimulai dari aplikasi.

## 5. Status Chat

Ada dua opsi.

### Opsi A - Tambah status baru

Tambahkan status baru di `MStatusChat`, misalnya:

- Kode: `DIINISIASI_CS`
- ID: `Diinisiasi CS`
- EN: `Started by CS`

Kelebihan:

- Lebih jelas membedakan chat yang dimulai CS.
- Mudah difilter di histori/report.
- Mudah dianalisis untuk audit.

Kekurangan:

- Perlu migration/seeder idempotent.
- Perlu memastikan query Inbox memasukkan status ini sebagai sesi aktif.

### Opsi B - Pakai status aktif yang sudah ada

Gunakan status aktif yang sekarang dipakai Inbox, lalu bedakan dari pesan pertama di `TChatD`.

Kelebihan:

- Perubahan lebih kecil.
- Tidak perlu status baru.

Kekurangan:

- Report dan audit lebih sulit.
- Status tidak menjelaskan bahwa chat dimulai oleh CS.

### Rekomendasi

Gunakan Opsi A jika struktur `MStatusChat` aman ditambah idempotently.

Jika risiko perubahan status dianggap besar, gunakan Opsi B dulu untuk tahap pertama.

## 6. Service yang Disarankan

Buat service baru:

`app/Services/Chat/ChatInitiationService.php`

Tanggung jawab service:

- Normalisasi nomor.
- Resolve target dari `MNomorWhatsapp` atau input manual.
- Validasi exclude number.
- Cari sesi `TChat` aktif yang sudah ada.
- Buat sesi `TChat` baru jika belum ada.
- Simpan pesan awal ke `TChatD`.
- Kirim pesan via `WahaSender` jika mode `Kirim sekarang`.
- Mengembalikan hasil ke UI:
  - `chat_id`
  - `message_id`
  - `was_existing_chat`
  - `sent_to_waha`
  - `send_error`

Alasan dibuat service:

- Logic tidak menumpuk di Livewire page.
- Bisa dipakai ulang dari Inbox, Master Nomor WhatsApp, atau fitur lain.
- Lebih mudah dites.
- Mengurangi risiko beda perilaku antara chat manual dan webhook.

## 7. Perubahan di InboxWhatsapp Page

File utama:

- `app/Filament/Pages/InboxWhatsapp.php`
- `resources/views/filament/pages/inbox-whatsapp.blade.php`

Perubahan yang disarankan:

- Tambah FAB icon plus `Buat Chat` di pojok kanan bawah panel daftar chat.
- Gunakan dialog/modal Filament agar style konsisten, bukan halaman baru.
- Tambah form state untuk:
  - `target_nomor_whatsapp_id`
  - `target_nomor_manual`
  - `target_nama_manual`
  - `id_sesi_whatsapp`
  - `pesan_awal`
  - `mode_pengiriman`
- Setelah submit:
  - panggil `ChatInitiationService`
  - reload inbox
  - set selected chat ke `chat_id` hasil service
  - panggil `loadMessages()`
  - tampilkan notification Filament

Catatan UI:

- Jangan memakai input custom plain HTML jika Filament sudah menyediakan komponen yang sesuai.
- Pertahankan multilanguage lewat file `resources/lang/id/ui.php` dan `resources/lang/en/ui.php`.
- Validasi error juga dibuat multilingual.

## 8. Matching Webhook Agar Tidak Duplikat

Bagian ini penting supaya customer reply masuk ke sesi yang benar.

Di `app/Services/Waha/WahaWebhookProcessor.php`, pastikan fungsi pencarian chat aktif melakukan hal berikut:

1. Resolve `@lid` ke nomor asli jika pesan masuk memakai LID.
2. Normalisasi nomor menjadi angka.
3. Cek exclude number sebelum membuat chat.
4. Cari `TChat` aktif dengan nomor tersebut.
5. Jika ada sesi aktif hasil inisiasi CS, pakai sesi itu.
6. Insert pesan masuk ke `TChatD` dengan `IdChat` yang sama.
7. Update unread count dan timestamp.
8. Broadcast/refresh Inbox seperti pesan masuk biasa.

Jika tidak ada sesi aktif:

- Buat `TChat` baru seperti flow webhook saat ini.

Jika sesi lama sudah ditutup:

- Jangan reopen record lama kecuali memang kebijakan bisnis meminta reopen.
- Buat sesi baru agar histori tetap bersih.

## 9. Nomor LID dan Nomor 628

Untuk chat yang dimulai dari aplikasi, input umumnya berupa nomor `628...`.

Saat mengirim:

- `WahaSender` dapat mengubah nomor menjadi format WAHA/JID sesuai kebutuhan.
- Simpan `TChat.NomorWhatsapp` tetap angka normal, misalnya `628xxxxxxxxxx`.

Saat menerima balasan:

- Jika WAHA mengirim sender sebagai `@lid`, processor harus resolve LID ke nomor asli dulu.
- Setelah nomor asli ditemukan, cocokkan dengan `TChat.NomorWhatsapp`.
- Dengan begitu balasan dari kontak LID tetap masuk ke sesi yang dibuat CS dari nomor `628...`.

## 10. Notifikasi dan Realtime

Untuk chat yang dimulai CS:

- Tidak perlu menaikkan jumlah pesan belum dibaca karena pesan pertama berasal dari CS.
- Chat tetap perlu muncul di Inbox agar bisa dilanjutkan.
- Jika ada realtime event untuk update Inbox, trigger event setelah `TChat`/`TChatD` dibuat.

Untuk customer reply:

- Treat sebagai pesan masuk biasa.
- Naikkan unread count sesuai pola saat ini.
- Tampilkan notifikasi sesuai aturan Inbox.
- Jangan tampilkan notifikasi jika nomor masuk exclude, karena flow exclude harus sudah return sebelum insert.

## 11. Permission

Gunakan permission existing terlebih dahulu.

Rekomendasi:

- CS yang boleh membalas chat juga boleh `Mulai Chat`.
- Jika ada permission `inbox.reply`, gunakan itu.
- Jika belum ada permission yang spesifik, tambahkan permission baru:
  - `inbox.start-chat`

Untuk input nomor manual:

- Bisa dibatasi permission lebih tinggi:
  - `inbox.start-chat-manual-number`

Alasannya, memilih nomor dari master lebih aman daripada mengetik nomor bebas.

## 12. Multilanguage

Semua label, helper text, validasi, dan notification harus masuk ke language file.

Tambahan key yang disarankan:

```php
'create_chat' => 'Buat Chat',
'create_chat_desc' => 'Mulai percakapan WhatsApp dari Inbox tanpa membuka halaman baru.',
'target_contact' => 'Kontak tujuan',
'manual_number' => 'Nomor manual',
'manual_contact_name' => 'Nama kontak manual',
'initial_message' => 'Pesan awal',
'delivery_mode' => 'Mode pengiriman',
'send_now' => 'Kirim sekarang',
'save_as_draft' => 'Simpan sebagai draft',
'chat_started' => 'Chat berhasil dibuat.',
'chat_reused' => 'Sesi chat aktif sudah ada dan dibuka.',
'number_is_excluded' => 'Nomor ini masuk daftar exclude dan tidak dapat dibuatkan chat.',
'message_send_failed' => 'Chat dibuat, tetapi pengiriman WAHA gagal.',
```

Buat versi Inggris di `resources/lang/en/ui.php`.

## 13. Validasi

Validasi minimal:

- Pesan awal wajib diisi.
- Nomor wajib dipilih atau diinput.
- Jika input manual:
  - hanya angka, spasi, plus, strip, dan kurung yang boleh diterima sebelum normalisasi
  - hasil normalisasi minimal 10 digit
  - jika diawali `0`, konversi ke `62` bila pola aplikasi sudah menggunakan aturan ini
- Sesi WAHA wajib valid dan aktif.
- Nomor tidak boleh masuk `ExcludeNomorWhatsapp`.
- Jika nomor ada di master tetapi `NonAktif`, jangan izinkan.

Validasi duplikasi:

- Cari sesi aktif dulu sebelum membuat baru.
- Jika sesi aktif ditemukan, beri pesan bahwa sesi yang ada dibuka.

## 14. Pengiriman WAHA

Gunakan service yang sudah ada:

- `app/Services/Waha/WahaSender.php`
- Method utama: `sendText(...)`

Flow `Kirim sekarang`:

1. Buat atau ambil `TChat`.
2. Kirim pesan ke WAHA.
3. Insert `TChatD`.
4. Simpan status berhasil/gagal.
5. Jika WAHA gagal, chat tetap bisa dibuka agar CS melihat status dan bisa retry.

Catatan transaksi:

- Jangan membungkus HTTP WAHA yang lama di transaksi database panjang.
- Pola aman:
  - buat/find chat dalam transaksi kecil
  - kirim WAHA
  - simpan detail pesan dan status hasil

Jika ingin menjaga agar pesan yang gagal tetap tercatat:

- Insert `TChatD` dengan status `Gagal WAHA`.
- Tampilkan tombol retry pada fase lanjutan.

## 15. Draft

Mode `Simpan sebagai draft` berguna jika CS ingin menyiapkan pesan tanpa mengirim.

Aturan draft:

- Buat atau ambil `TChat`.
- Simpan `TChatD` sebagai draft/outgoing lokal sesuai pola field yang ada.
- Jangan panggil WAHA.
- Chat muncul di Inbox.
- CS bisa lanjut mengirim dari area reply yang sudah ada.

Jika struktur `TChatD` saat ini tidak memiliki status draft yang jelas:

- Gunakan pola yang sudah dipakai oleh method `simpanBalasanLokal()`.
- Jangan menambah kolom baru kecuali benar-benar diperlukan.

## 16. Perubahan Database yang Mungkin Dibutuhkan

Tahap pertama sebaiknya meminimalkan perubahan database.

Kemungkinan migration:

1. Seeder idempotent untuk status `DIINISIASI_CS`, jika dipilih.
2. Index tambahan jika query matching aktif lambat:
   - `TChat.NomorWhatsapp`
   - `TChat.IdNomorWhatsapp`
   - `TChat.IdStatusChat`
   - `TChat.TglChatTerakhir`

Jangan tambah tabel baru untuk fitur ini pada tahap pertama.

## 17. Testing yang Perlu Dibuat

### 17.1 Unit/service test

Test `ChatInitiationService`:

- Membuat chat baru dari master nomor.
- Reuse chat aktif jika nomor sama.
- Membuat chat baru jika sesi lama sudah ditutup.
- Menolak nomor exclude.
- Menolak nomor invalid.
- Menyimpan draft tanpa memanggil WAHA.
- Mencatat gagal WAHA tanpa menghapus chat.

### 17.2 Webhook test

Test `WahaWebhookProcessor`:

- Customer reply ke chat yang diinisiasi CS masuk ke `IdChat` yang sama.
- Customer reply dengan sender `@lid` tetap cocok setelah resolve ke nomor `628...`.
- Customer reply setelah sesi ditutup membuat sesi baru.
- Nomor exclude tidak insert ke `TChat`, `TChatD`, dan `TAiPermintaan`.

### 17.3 UI/manual test

Checklist manual:

- Tombol FAB `Buat Chat` muncul di pojok kanan bawah panel Daftar Chat.
- Modal tampil rapi di desktop.
- Modal tampil rapi di mobile.
- Select kontak bisa search.
- Nomor manual tervalidasi.
- Pesan awal bisa dikirim.
- Setelah submit, sesi langsung terbuka.
- Jika customer reply, pesan muncul di thread yang sama.
- Language ID/EN berubah sesuai locale.

## 18. Tahapan Implementasi

### Fase 1 - Fondasi

- Audit field `TChat` dan `TChatD` yang dipakai reply flow saat ini.
- Audit status aktif dan status tutup di `MStatusChat`.
- Pastikan pola nomor normal sama dengan webhook.
- Tentukan apakah memakai status baru atau status existing.

Output fase 1:

- Keputusan status.
- Mapping field final untuk insert `TChat`.
- Mapping field final untuk insert `TChatD`.

### Fase 2 - Service

- Buat `ChatInitiationService`.
- Buat method normalize/resolve target.
- Buat method find active chat.
- Buat method create chat.
- Buat method save initial message.
- Integrasikan `WahaSender` untuk mode kirim langsung.
- Tambahkan validasi exclude number.

Output fase 2:

- Logic inti bisa dites tanpa UI.

### Fase 3 - UI Inbox

- Tambah FAB/modal `Buat Chat`.
- Tambah form Filament.
- Tambah translation key ID/EN.
- Setelah submit, reload Inbox dan buka sesi.
- Tambah notification sukses/gagal.

Output fase 3:

- CS bisa memulai chat dari Inbox.

### Fase 4 - Webhook Matching

- Pastikan webhook mencari sesi aktif yang dibuat CS.
- Pastikan LID resolve terjadi sebelum matching.
- Pastikan nomor exclude tetap berhenti sebelum insert.
- Pastikan tidak ada duplikasi `TChat`.

Output fase 4:

- Balasan customer masuk ke sesi yang benar.

### Fase 5 - Verification

- Jalankan `php -l` untuk file PHP yang berubah.
- Jalankan route check jika ada route/action baru.
- Jalankan test terkait jika tersedia.
- Jalankan manual test Inbox.
- Pastikan `npm run build` hanya jika ada perubahan CSS/asset.

## 19. Risiko dan Mitigasi

### Risiko 1 - Duplikasi sesi

Penyebab:

- Chat dibuat manual, lalu webhook membuat `TChat` baru karena matching berbeda.

Mitigasi:

- Matching wajib berdasarkan nomor normal.
- Sesi aktif wajib dicari sebelum create.
- LID wajib di-resolve sebelum matching.

### Risiko 2 - Nomor exclude tetap bisa dikirim

Penyebab:

- Exclude hanya dicek di webhook, bukan saat CS memulai chat.

Mitigasi:

- `ChatInitiationService` wajib memakai helper exclude yang sama atau logic normalisasi yang sama.

### Risiko 3 - UI jadi bercabang

Penyebab:

- Ada menu baru khusus mulai chat, sedangkan balasan masuk ke Inbox.

Mitigasi:

- Entry point utama tetap di Inbox.
- Jika nanti ada shortcut dari master kontak, setelah submit tetap redirect/open Inbox.

### Risiko 4 - Status chat membingungkan

Penyebab:

- Chat inisiasi CS memakai status yang sama dengan chat masuk customer.

Mitigasi:

- Tambahkan status `DIINISIASI_CS` jika aman.
- Jika tidak, report bisa menentukan dari pesan pertama.

### Risiko 5 - WAHA gagal tetapi database sudah berubah

Penyebab:

- HTTP WAHA gagal setelah `TChat` dibuat.

Mitigasi:

- Tetap simpan chat dan pesan dengan status gagal.
- Beri notification jelas.
- Sediakan retry di fase lanjutan.

## 20. Rekomendasi Final

Implementasi terbaik untuk tahap pertama:

1. Buat fitur `Buat Chat` di `/admin/inbox-whatsapp` sebagai FAB icon plus di panel Daftar Chat.
2. Jangan buat menu terpisah.
3. Gunakan `TChat` dan `TChatD` yang sudah ada.
4. Buat service `ChatInitiationService`.
5. Reuse sesi aktif jika nomor sama.
6. Jika customer membalas, masuk ke sesi Inbox yang sama.
7. Jika sesi lama sudah ditutup, buat sesi aktif baru.
8. Terapkan exclude number juga untuk chat yang dimulai CS.
9. Pertahankan multilanguage.
10. Tambahkan status `DIINISIASI_CS` hanya jika struktur status aman ditambah.

Dengan desain ini, fitur baru tidak memecah alur kerja CS dan tetap konsisten dengan Inbox, Histori Chat, webhook WAHA, exclude number, dan notifikasi.
