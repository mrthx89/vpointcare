## Context

Inbox WhatsApp WACS sudah mempunyai mode `whatsapp` dan `internal`. Mode `whatsapp` membaca nama raw dari `TChat` dan payload pesan terakhir, sedangkan mode `internal` membaca mapping `MNomorWhatsapp`, `MGrupWhatsapp`, `MCustomer`, dan `MInstansi`. Halaman saat ini sudah bisa menampilkan `@g.us` dan memisahkan pengirim pesan grup, tetapi belum menyimpan hasil pencarian nama kontak/grup dari WAHA sebagai data yang dapat dibaca kembali.

CS memerlukan daftar percakapan yang cepat dipindai. Referensi WAHA Hub menunjukkan pola chat list, header, preview pesan, refresh, dan load more. Perubahan ini mengadaptasi hirarki visual tersebut ke Blade/Filament yang ada, tanpa menyalin komponen Vue atau menambah dependency frontend.

Audit tambahan menemukan dua ketidakkonsistenan. Pertama, foto profil header grup dapat meminta WAHA memakai `MGrupWhatsapp.IdGrupWaha` sebelum raw group JID milik chat aktif sehingga mapping yang stale/salah dapat menghasilkan foto grup lain. Kedua, empat halaman `ManageRecords` ticketing yang dilaporkan belum memakai `HasMenuBreadcrumbs`, walaupun pola tersebut sudah dipakai resource admin lain.

Kendala utama: PHP 8.3+, Laravel 13, Filament 5, Livewire, Tailwind/Vite, dan Microsoft SQL Server harus tetap kompatibel; webhook harus cepat; normalisasi `@c.us`, `@s.whatsapp.net`, `@g.us`, dan `@lid` harus dipertahankan; secret WAHA tidak boleh masuk UI atau log user-facing.

## Goals / Non-Goals

**Goals:**

- Menyediakan snapshot identitas WAHA yang persist pada `TChat` untuk nama kontak, nama grup, status, dan waktu sinkronisasi.
- Menyelesaikan `@lid` menjadi nomor/JID telepon bila WAHA mengembalikannya, tanpa menghilangkan JID LID asli.
- Menampilkan metadata snapshot yang jelas di mode WhatsApp asli, termasuk badge jenis chat dan identifier yang dapat disalin.
- Menjaga mode internal dan mapping master tetap independen dari sinkronisasi WAHA.
- Memproses sinkronisasi metadata melalui queue `webhooks` dengan deduplikasi dan retry terbatas.
- Memastikan avatar percakapan grup selalu berasal dari raw group JID `@g.us` milik chat aktif dan tidak tertukar dengan avatar participant.
- Menyediakan breadcrumb terlokalisasi pada empat route ticketing yang dilaporkan tanpa mengubah permission atau route.

**Non-Goals:**

- Mengganti UI Inbox seluruhnya, mengimpor source WAHA Hub, atau menambah framework frontend.
- Menyinkronkan anggota grup, presence, chat history, atau membuat master contact/group otomatis.
- Menyimpan respons WAHA lengkap, payload mentah, token, atau API key sebagai snapshot identitas.
- Mengubah struktur menu sidebar, permission ticketing, route, atau label navigasi di luar empat halaman yang dilaporkan.

## Decisions

### 1. Snapshot disimpan pada `TChat`

Tambahkan kolom SQL Server nullable pada `TChat`: `NamaKontakWaha`, `NamaGrupWaha`, `TglIdentitasWahaDiambil`, `StatusIdentitasWaha`, dan `PesanErrorIdentitasWaha`. `IdWahaTerdeteksi` dan `NomorWhatsappTerdeteksi` yang sudah ada tetap menjadi JID asli dan nomor hasil deteksi/resolusi. Snapshot nama dipisahkan dari `NamaKontak`, `NamaGrupWhatsapp`, dan tabel master agar data internal tidak tertimpa.

Alternatif fetch WAHA saat render ditolak karena menambah latency dan request berulang. Tabel cache baru ditolak untuk scope ini karena satu `TChat` sudah merepresentasikan percakapan per sesi dan kolom snapshot lebih sederhana.

### 2. Sinkronisasi dilakukan job setelah chat tersimpan

Tambahkan `SyncWahaChatIdentityJob` pada queue `webhooks`, `tries=3`, `timeout=30`, backoff 30/120 detik, dan unique key per chat selama 60 detik. Job dijadwalkan setelah transaksi penyimpanan chat berhasil dan memicu `SendBroadcastDebouncedJob` hanya bila snapshot berubah.

Kegagalan job menyimpan `StatusIdentitasWaha=failed`, pesan error tersanitasi, dan waktu percobaan, tetapi tidak menghapus nama/nomor snapshot terakhir. Queue worker yang sudah ada menjalankan queue `webhooks`; tidak perlu queue Docker baru.

### 3. Resolver mempertahankan raw JID

Untuk grup, job mengambil metadata berdasarkan JID `@g.us` yang dinormalisasi, lalu memilih subject/name valid. Untuk chat pribadi, job mengambil metadata kontak berdasarkan JID raw. Jika JID berakhiran `@lid`, job memakai `getPhoneNumberByLid()` yang sudah ada; info kontak dicari memakai LID terlebih dahulu dan kemudian phone JID bila LID tidak menghasilkan nama.

Urutan nama mode WhatsApp asli: snapshot `NamaGrupWaha` atau `NamaKontakWaha`, nama eksplisit dari payload, field raw legacy, lalu JID/nomor fallback. Mode internal mempertahankan urutan mapping master yang ada dan hanya memakai snapshot WhatsApp sebagai fallback per field.

### 4. UI meniru hirarki WAHA Hub, bukan implementasinya

Pada mode WhatsApp asli, setiap baris daftar chat menampilkan avatar/fallback inisial, primary name, badge jenis, identifier sekunder, preview pesan, waktu, dan unread count. Header chat menampilkan nama, badge, identifier monospace, nomor hasil resolusi LID bila tersedia, sumber snapshot, serta waktu sinkronisasi. Aksi refresh memakai icon Heroicon dengan label aksesibel dan target minimal 44px.

Blade menggunakan komponen Filament dan class Tailwind yang sudah ada. Semua text user-facing masuk ke `lang/id/ui.php` dan `lang/en/ui.php`; warna bukan satu-satunya penanda jenis/status, focus ring tetap terlihat, dan light/dark mode memakai class yang sudah dipakai halaman.

### 5. WAHA adapter menutup variasi respons

`WahaSender` ditambah method metadata kontak dan grup yang menggunakan helper `getJson()` yang ada. Request mengikuti konfigurasi `WAHA_BASE_URL`, `WAHA_API_KEY`, timeout HTTP 8 detik, circuit breaker, dan `TLogIntegrasi`. Parser hanya mengambil nama dan identifier yang diperlukan; body respons tidak diteruskan ke Livewire atau disimpan ke kolom snapshot.

## Risks / Trade-offs

- **Endpoint metadata berbeda pada WAHA production** -> adapter terisolasi, fixture test beberapa bentuk respons, dan verifikasi manual WAHA sebelum rollout.
- **Nama snapshot stale** -> refresh otomatis setelah webhook dan aksi refresh manual pada chat aktif; Inbox menampilkan waktu sinkronisasi.
- **WAHA unavailable** -> UI memakai snapshot terakhir/raw JID; job retry terbatas dan tidak memblokir webhook/render.
- **Job duplikat pada chat ramai** -> deduplikasi per `IdChat` dan broadcast debounce yang sudah ada.
- **Migration SQL Server gagal** -> gunakan `COL_LENGTH`, nullable columns, dan backup wajib sebelum production.
- **Identitas internal tertukar** -> field snapshot terpisah dan mode internal tidak melakukan write dari job metadata.
- **Mapping grup stale menghasilkan foto salah** -> raw group JID `@g.us` pada payload/TChat menjadi sumber utama; mapping master hanya fallback tervalidasi dan tidak ditulis otomatis.
- **Breadcrumb menduplikasi label** -> helper/halaman hanya menambahkan label resource saat berbeda dari label menu parent dan seluruh label berasal dari localization/navigation helper.

## Migration Plan

1. Backup database production dan catat versi deploy sebelumnya.
2. Deploy code, migration SQL Server, schema fresh-install, localization, dan asset hasil `npm run build`.
3. Jalankan `php artisan migrate --force`, lalu `php artisan optimize:clear` dan `php artisan optimize`.
4. Restart worker queue `webhooks` agar `SyncWahaChatIdentityJob` tersedia.
5. Verifikasi manual satu chat personal, satu `@lid`, dan satu grup `@g.us`.
6. Verifikasi empat route ticketing setelah deploy; koreksi breadcrumb dan pemilihan JID foto grup tidak memerlukan migration tambahan.

Rollback: hentikan worker `webhooks`, deploy code sebelumnya, restart worker, rollback migration bila snapshot tidak dibutuhkan, lalu verifikasi Inbox memakai field legacy. Koreksi breadcrumb dan prioritas JID foto grup dapat di-rollback dengan mengembalikan file PHP terkait tanpa perubahan data.

## Additional Decisions

### 6. Kunci chat grup dengan `group_jid`, bukan participant

Parser harus memisahkan tiga identitas: `group_jid` untuk `TChat`, `participant` untuk `TChatD.PengirimNomorWhatsapp`/`PengirimNamaKontak`, dan `message_id` untuk idempotensi. Untuk chat Grup, `findOrCreateChat()` wajib mencari berdasarkan sesi + `group_jid` meskipun `MGrupWhatsapp` belum memiliki mapping. `TChat.NomorWhatsapp` dan `IdWahaTerdeteksi` untuk grup menyimpan group JID; participant tidak boleh menjadi key chat.

Ini menjelaskan gejala chat grup yang hanya tampak berasal dari satu orang: bila participant dipakai sebagai key, pesan dari peserta lain bisa masuk ke chat yang salah atau membuat percakapan terpisah. Test harus mencakup satu grup dengan minimal dua participant, mapped dan unmapped.

### 7. Foto profil participant disimpan per detail pesan

Tambahkan field nullable pada `TChatD` untuk URL foto participant dan waktu snapshot. Job metadata menerima raw participant JID, memakai `WahaSender::getContactProfilePictureUrl()`, dan menulis hasil hanya ke detail pesan terkait. Request perlu dideduplikasi per session + participant JID agar satu participant tidak memicu request untuk setiap pesan. Jika WAHA gagal, avatar memakai fallback inisial dan snapshot lama tidak dihapus.

### 8. Session AI memiliki aturan eksplisit dan audit reason

`AutoReplyJamKerjaBerlanjut` adalah label **All Session**. Saat aktif, setiap incoming customer yang eligible dapat diproses setelah pengecekan duplicate/manual reply, jam kerja, hari libur, dan excluded number. Saat tidak aktif, AI hanya boleh menjawab incoming pertama atau incoming yang datang setelah idle minimal 60 menit dari incoming customer sebelumnya; nilai 60 menit dibuat sebagai setting terverifikasi, bukan asumsi tersembunyi di service.

Flow wajib menghitung `$isFirstReply` sebelum mencatat `TAiPermintaan` atau memilih model. Setiap skip/failure/success/delivery harus memiliki reason code yang dapat dilihat operator tanpa API key, prompt penuh, atau response secret. `KirimKeWaha=false` tetap menghasilkan draft lokal yang jelas statusnya; `KirimKeWaha=true` harus mencatat kegagalan HTTP WAHA secara terpisah.

### 9. Base64 hanya menjadi fallback tampilan

Renderer menentukan `mediaRenderable` dari `UrlMedia`, data URI, atau `WahaMediaPayload` sebelum menampilkan `IsiPesan`. Jika renderable, base64/data URI tidak boleh masuk state tampilan maupun markup. Jika tidak renderable, Inbox menampilkan panel diagnostik localized berisi base64 yang dibatasi panjangnya dan diberi aksi copy/download teks, tanpa menampilkan `PayloadJson` mentah.

### 10. Foto profil grup memakai raw group JID milik chat

Untuk `JenisChat=Grup`, resolver foto profil harus memilih raw group JID berakhiran `@g.us` dari payload chat aktif, `TChat.NomorWhatsapp`, atau `TChat.IdWahaTerdeteksi` sebelum mempertimbangkan `MGrupWhatsapp.IdGrupWaha`. Identifier participant/sender, `@lid`, `@c.us`, `@s.whatsapp.net`, dan nomor personal tidak boleh dikirim ke endpoint profile picture untuk avatar percakapan grup.

Jika mapping `MGrupWhatsapp.IdGrupWaha` berbeda dari raw group JID chat, resolver mempertahankan identitas chat dan tidak memperbarui master mapping secara otomatis. Jika tidak ada group JID valid, refresh menyimpan waktu/status percobaan tanpa mengganti snapshot foto terakhir; UI memakai fallback inisial. Foto participant pada `TChatD` tetap diproses oleh keputusan 7 dan tidak boleh ditulis ke `TChat.UrlFotoProfil`.

### 11. Breadcrumb ticketing mengikuti menu dan halaman aktif

`ManageTickets` menggunakan `HasMenuBreadcrumbs` dengan `AccessPermissions::TICKET_VIEW` sehingga breadcrumb berasal dari group dan label menu yang sama dengan sidebar. `ManageStatusTickets`, `ManagePrioritasTickets`, dan `ManageKategoriTickets` memakai parent menu `ticket.view` serta menambahkan label resource aktif yang terlokalisasi ketika berbeda dari label parent, menghasilkan hirarki `Operasional > Ticket > Halaman Aktif` tanpa hardcode Bahasa Indonesia.

Implementasi boleh memperluas helper breadcrumb existing secara minimal atau melakukan komposisi pada empat page tersebut, tetapi tidak boleh mengubah route, permission `ticket.view`/`ticket.manage`, visibility resource, atau urutan sidebar. Test harus memastikan label tidak duplikat dan locale `id`/`en` mengikuti `NavigationHelper` serta key `ui.ticketing.*` yang sudah ada.

## Open Questions

- Endpoint metadata grup WAHA pada session production harus dikonfirmasi dengan respons tersanitasi sebelum implementasi adapter final.
- Aksi refresh identitas diasumsikan memakai permission `inbox.view`; bila harus dibatasi, ubah ke `inbox.manage` sebelum implementasi.
