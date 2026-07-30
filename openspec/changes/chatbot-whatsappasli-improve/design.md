## Context

Inbox WhatsApp WACS sudah mempunyai mode `whatsapp` dan `internal`. Mode `whatsapp` membaca nama raw dari `TChat` dan payload pesan terakhir, sedangkan mode `internal` membaca mapping `MNomorWhatsapp`, `MGrupWhatsapp`, `MCustomer`, dan `MInstansi`. Halaman saat ini sudah bisa menampilkan `@g.us` dan memisahkan pengirim pesan grup, tetapi belum menyimpan hasil pencarian nama kontak/grup dari WAHA sebagai data yang dapat dibaca kembali.

CS memerlukan daftar percakapan yang cepat dipindai. Referensi WAHA Hub menunjukkan pola chat list, header, preview pesan, refresh, dan load more. Perubahan ini mengadaptasi hirarki visual tersebut ke Blade/Filament yang ada, tanpa menyalin komponen Vue atau menambah dependency frontend.

Kendala utama: PHP 8.3+, Laravel 13, Filament 5, Livewire, Tailwind/Vite, dan Microsoft SQL Server harus tetap kompatibel; webhook harus cepat; normalisasi `@c.us`, `@s.whatsapp.net`, `@g.us`, dan `@lid` harus dipertahankan; secret WAHA tidak boleh masuk UI atau log user-facing.

## Goals / Non-Goals

**Goals:**

- Menyediakan snapshot identitas WAHA yang persist pada `TChat` untuk nama kontak, nama grup, status, dan waktu sinkronisasi.
- Menyelesaikan `@lid` menjadi nomor/JID telepon bila WAHA mengembalikannya, tanpa menghilangkan JID LID asli.
- Menampilkan metadata snapshot yang jelas di mode WhatsApp asli, termasuk badge jenis chat dan identifier yang dapat disalin.
- Menjaga mode internal dan mapping master tetap independen dari sinkronisasi WAHA.
- Memproses sinkronisasi metadata melalui queue `webhooks` dengan deduplikasi dan retry terbatas.

**Non-Goals:**

- Mengganti UI Inbox seluruhnya, mengimpor source WAHA Hub, atau menambah framework frontend.
- Menyinkronkan anggota grup, presence, chat history, atau membuat master contact/group otomatis.
- Menyimpan respons WAHA lengkap, payload mentah, token, atau API key sebagai snapshot identitas.

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

## Migration Plan

1. Backup database production dan catat versi deploy sebelumnya.
2. Deploy code, migration SQL Server, schema fresh-install, localization, dan asset hasil `npm run build`.
3. Jalankan `php artisan migrate --force`, lalu `php artisan optimize:clear` dan `php artisan optimize`.
4. Restart worker queue `webhooks` agar `SyncWahaChatIdentityJob` tersedia.
5. Verifikasi manual satu chat personal, satu `@lid`, dan satu grup `@g.us`.

Rollback: hentikan worker `webhooks`, deploy code sebelumnya, restart worker, rollback migration bila snapshot tidak dibutuhkan, lalu verifikasi Inbox memakai field legacy.

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

## Open Questions

- Endpoint metadata grup WAHA pada session production harus dikonfirmasi dengan respons tersanitasi sebelum implementasi adapter final.
- Aksi refresh identitas diasumsikan memakai permission `inbox.view`; bila harus dibatasi, ubah ke `inbox.manage` sebelum implementasi.
