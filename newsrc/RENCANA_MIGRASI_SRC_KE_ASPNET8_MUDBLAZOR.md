# Rencana Migrasi VPoint Care dari `src` ke ASP.NET Core 8 + MudBlazor

Tanggal audit: 2026-05-04  
Source lama: `D:\GIT VPOINT\2026-WACS\src`  
Target baru: `D:\GIT VPOINT\2026-WACS\newsrc`  
Target stack: ASP.NET Core 8, Blazor Server Interactive, MudBlazor, Entity Framework Core, SQL Server
Keputusan runtime tambahan:

- WebSocket server lama Laravel Reverb diganti menjadi SignalR Server di ASP.NET Core.
- WebSocket client lama Laravel Echo/Pusher diganti menjadi SignalR Client di Blazor.
- Queue/job lama Laravel yang sekarang dijalankan melalui Windows Task Scheduler + `cmd php artisan ...` diganti menjadi Hangfire.
- Tabel auth lama Laravel `users` diganti menjadi tabel master `MUser` di target ASP.NET.

Status eksekusi awal per 2026-05-04:

- Solution ASP.NET Core 8 dibuat di `newsrc/VPointCare.sln` dengan project `src/VPointCare.Web`.
- MudBlazor, EF Core SQL Server, SignalR, Hangfire SQL Server, dan BCrypt sudah terpasang.
- Schema SQL disalin ke `newsrc/database/DATABASE_SCHEMA_WACS.sql`; tabel auth `users` sudah diganti menjadi `MUser`, dan FK `MPengguna.UserId` diarahkan ke `MUser(id)`.
- Asset logo SVG dari `src/public/images` sudah disalin ke `newsrc/src/VPointCare.Web/wwwroot/images`.
- Program startup sudah memuat Razor Components interactive server, MudBlazor, cookie auth, EF Core, SignalR hub `/hubs/waha-inbox`, controller webhook `POST /webhooks/waha/{token?}`, dan Hangfire opsional jika `ConnectionStrings:Hangfire` diisi.
- Implementasi awal sudah dibuat untuk `MUser` auth, `MPengguna`, tracking CS aktif, SignalR notification, WAHA webhook processor, Hangfire job skeleton, dashboard, inbox realtime, dan route placeholder modul besar.
- `appsettings.json` sudah diisi dari `src/.env`: SQL Server WACS, WAHA, VToken, OpenAI, DeepSeek, OpenRouter, dan URL publik aplikasi.
- Koneksi Hangfire dipisahkan ke database `DBVPointCare_Hangfire`, tidak memakai tabel domain WACS.
- Serilog sudah ditambahkan dengan rolling file harian `Logs/wacs-.log`.
- Command Laravel `vpoint:import-instansi-vtoken` sudah dikonversi ke `VTokenSyncJob` Hangfire dengan validasi `jsonResult/jsonValue`, log `TLogIntegrasi`, dan upsert `MInstansi`.
- Command Laravel `vpoint:kirim-notifikasi-chat-belum-terbalas` sudah dikonversi ke `UnansweredChatNotificationJob` Hangfire dengan aturan `MPengaturanAi`, jam kerja, `MHariLibur`, penerima role, template notifikasi, dan pengiriman WAHA.
- `AiAutoReplyService.php` sudah mulai dikonversi ke `Services/Ai/AiAutoReplyService.cs` dan dipanggil dari webhook WAHA setelah pesan masuk berhasil diproses.
- Validasi terakhir: `dotnet build newsrc\VPointCare.sln` berhasil tanpa warning dan error.

## 1. Prinsip Wajib Migrasi

1. Schema database harus sama persis dengan source lama.
   - Nama tabel, nama kolom, tipe SQL Server, nullability, primary key, foreign key, unique constraint, default constraint, index, dan seed awal tidak boleh diganti.
   - `DATABASE_SCHEMA_WACS.sql` di `src` menjadi sumber kebenaran utama.
   - EF Core hanya menjadi mapping aplikasi, bukan alat untuk mendesain ulang schema.
   - Pengecualian yang sudah diputuskan: tabel auth lama `users` dirubah menjadi `MUser` di `newsrc`. Semua relasi dan auth mapping yang sebelumnya menunjuk `users` harus menunjuk `MUser`.

2. Tampilan harus sama persis secara struktur dan perilaku.
   - Admin shell tetap memakai brand `VPoint Care`, logo yang sama, sidebar, grouping menu, dark mode, footer, dan document-level `notranslate`.
   - Halaman operasional harus mengikuti layout lama, terutama Inbox WhatsApp 3 kolom: daftar chat, percakapan, panel profil/aksi.
   - MudBlazor dipakai sebagai komponen dasar, tetapi CSS custom harus dibuat untuk menyamai tampilan Filament lama.

3. Business flow tidak boleh berubah.
   - Endpoint webhook WAHA tetap `POST /webhooks/waha/{token?}`.
   - Token dan HMAC webhook tetap opsional sesuai konfigurasi lama.
   - Chat status broadcast tetap diabaikan sebelum membuat chat.
   - Duplicate message tetap dicegah memakai `TChatD.IdPesanWaha`.
   - Data nomor WhatsApp pribadi harus tetap memakai nomor HP asli jika WAHA memberi `@lid`.
   - Auto reply AI, hari libur, jam kerja, dan notifikasi internal harus memakai tabel dan field lama.

4. Runtime background dan realtime harus mengikuti stack ASP.NET.
   - Reverb/Echo tidak dibawa ke `newsrc`.
   - SignalR menjadi satu-satunya realtime transport untuk inbox dan active agent.
   - Laravel Queue, `php artisan queue:listen`, dan command artisan periodik tidak dibawa ke `newsrc`.
   - Hangfire menjadi satu-satunya job scheduler/queue untuk pekerjaan background.

5. Migrasi dilakukan modul demi modul, bukan rewrite bebas.
   - Setiap modul selesai hanya jika data lama bisa dibaca, operasi CRUD/set status berjalan, UI serupa, dan query hasilnya cocok dengan Laravel.

## 2. Temuan Source Lama

### 2.1 Stack dan entrypoint

Source lama berbentuk Laravel/Filament dengan database SQL Server. Walaupun user mengklarifikasi folder ini adalah source lama dengan konteks PHP 7.4, file saat ini berisi aplikasi Laravel/Filament yang sudah menjadi acuan perilaku modul. Target baru tidak perlu mempertahankan PHP, tetapi wajib mempertahankan kontrak data dan UI.

Entrypoint penting:

- `routes/web.php`
  - `/` redirect ke `/admin`
  - `POST /webhooks/waha/{token?}` -> webhook WAHA
  - `GET /admin/waha-media/{message}` -> proxy media WAHA
  - `GET /profile-storage/{path}` -> fallback serving foto profil
- `app/Providers/Filament/AdminPanelProvider.php`
  - path admin `/admin`
  - brand `VPoint Care`
  - sidebar collapsible
  - footer copyright
  - global `notranslate`
  - theme warna blue/slate/emerald/amber/red

### 2.2 Schema database

File utama: `src/DATABASE_SCHEMA_WACS.sql`.

Tabel yang harus dipertahankan atau dimigrasikan:

- Auth legacy:
  - Source lama: `users`.
  - Target baru: `MUser`.
  - Perubahan yang disetujui hanya nama tabel. Kolom dan perilaku auth tetap mengikuti `users` lama agar migrasi tidak melebar.
  - Kolom minimal yang harus ada di `MUser`: `id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `status`, `approved_at`, `blocked_at`, `created_at`, `updated_at`.
  - Tipe `MUser.id` tetap mengikuti `users.id` lama, yaitu `bigint identity`, supaya `MPengguna.UserId bigint` tidak perlu diubah.
- Auth/cache/job Laravel legacy compatibility yang tidak menjadi runtime utama: `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.
  - Tabel Laravel job tetap ada karena bagian dari schema lama, tetapi tidak dipakai sebagai queue runtime di `newsrc`.
  - Hangfire tidak boleh mencampur perubahan ke tabel domain WACS. Gunakan database Hangfire terpisah, atau schema SQL Server terpisah bernama `Hangfire` dengan keputusan eksplisit sebelum implementasi.
- Master: `MPeran`, `MHakAkses`, `MPeranHakAkses`, `MPengguna`, `MInstansi`, `MCustomer`, `MNomorWhatsapp`, `MGrupWhatsapp`, `MAnggotaGrupWhatsapp`, `MProdukCustomer`, `MStatusChat`, `MKategoriTicket`, `MPrioritasTicket`, `MStatusTicket`, `MSesiWhatsapp`, `MEndpointIntegrasi`, `MAiProvider`, `MHariLibur`, `MPengaturanAi`, `MPengetahuan`.
- Log: `TLogAktivitas`, `TLogError`, `TLogIntegrasi`, `TLogWebhookWaha`.
- Chat: `TChatM`, `TChatD`, `TChatPenugasan`, `TChatCatatanInternal`.
- Ticket: `TTicketM`, `TTicketD`, `TTicketPenugasan`, `TTicketLampiran`.
- AI: `TAiPermintaan`, `TAiRespon`.

Catatan schema penting:

- Primary key domain memakai `uniqueidentifier DEFAULT NEWSEQUENTIALID()`.
- `MPengguna.UserId` FK ke `MUser.id` dan unique filtered index `UX_MPengguna_UserId`.
- FK target sebaiknya bernama `FK_MPengguna_MUser`.
- `TChatD.IdAiRespon` FK ke `TAiRespon.Id`.
- Banyak tabel memakai audit: `TglBuat`, `DibuatOleh`, `TglEdit`, `DieditOleh`.
- Master memakai soft disable `NonAktif`, bukan hard delete.
- Nama field tetap Bahasa Indonesia.

### 2.3 Modul dan perilaku lama

#### Auth dan user approval

Source:

- `app/Models/User.php`
- `app/Models/Master/Pengguna.php`
- `app/Services/Auth/UserPenggunaSyncService.php`
- `app/Filament/Auth/Login.php`
- `app/Filament/Auth/Register.php`
- `app/Filament/Resources/System/Users/UserResource.php`

Perilaku yang harus dipindahkan:

- Status user: `pending`, `approved`, `blocked`.
- Login hanya boleh untuk `approved`.
- Register membuat user `pending` dan menyinkronkan ke `MPengguna`.
- `MPengguna` dan `MUser` harus tetap sinkron berdasarkan `UserId` atau `Email`.
- Avatar user memakai `MPengguna.FotoProfilPath` dan route storage fallback.

#### Master Data

Source resources:

- `InstansiResource`: Klien / Instansi, termasuk action `Syncron Data`.
- `CustomerResource`: Kontak Customer.
- `NomorWhatsappResource`: Nomor WhatsApp, normalisasi angka, `IdWaha`.
- `GrupWhatsappResource`: mapping grup WAHA ke instansi.
- `AnggotaGrupWhatsappResource`: anggota grup dan auto isi `IdCustomer` dari nomor.
- `PenggunaResource`: pengguna internal, foto profil, nomor WA internal, peran.
- `HariLiburResource`: kalender libur untuk AI dan notifikasi.
- `PengetahuanResource`: knowledge base AI.
- `MasterCustomer`: ringkasan master dan shortcut link.

Perilaku yang harus dipindahkan:

- Semua form, label, kolom, filter, search, sort, toggle `NonAktif`, dan pagination harus disamakan.
- Normalisasi `MNomorWhatsapp.NomorWhatsapp` hanya angka.
- `MNomorWhatsapp.IdInstansi` otomatis ikut customer jika dipilih.
- `MGrupWhatsapp.IdGrupWaha` di-trim.
- `MAnggotaGrupWhatsapp.IdCustomer` otomatis dari nomor.

#### Dashboard

Source:

- `app/Filament/Pages/Dashboard.php`
- `resources/views/filament/pages/dashboard.blade.php`

Perilaku yang harus dipindahkan:

- Filter periode default hari ini.
- Quick range: hari ini, 7 hari, 30 hari, bulan ini.
- Polling 15 detik.
- Summary: pesan masuk, sesi chat, balasan CS/AI, belum terjawab, unread total, failed/sent WAHA, ticket dibuat, rata-rata waktu balas, chat aktif, chat selesai.
- Indeks kepuasan pelanggan dari response rate, delivery rate, speed score, mapping rate.
- Tren pesan harian, performa tim/AI, customer teraktif.

#### Inbox WhatsApp

Source:

- `app/Filament/Pages/InboxWhatsapp.php`
- `resources/views/filament/pages/inbox-whatsapp.blade.php`
- `resources/js/echo.js`
- `app/Events/WahaInboxUpdated.php`

Perilaku yang harus dipindahkan:

- Layout 3 kolom dengan tinggi viewport:
  - kiri: daftar chat, search, filter pribadi/grup/keduanya, sound toggle, status realtime/polling.
  - tengah: header chat, messages bubble, preview media, composer, attachment.
  - kanan: informasi customer/mapping, action AI, history chat, catatan internal.
- Stats: tim aktif, total chat, belum dibaca, chat grup, belum dipetakan.
- Polling fallback 60 detik.
- Signal realtime lama Reverb/Echo harus diganti SignalR Server/SignalR Client di ASP.NET, tetapi event behavior tetap sama.
  - Server lama: Laravel Reverb broadcast channel `waha-inbox` dan presence `waha-agents`.
  - Target server: ASP.NET Core SignalR hub, misalnya `/hubs/waha-inbox`.
  - Client lama: `resources/js/echo.js` memakai Laravel Echo/Pusher.
  - Target client: Blazor memakai `HubConnection` dari `Microsoft.AspNetCore.SignalR.Client`.
- Auto-claim chat ke `TChatM.DiambilOleh` saat CS memilih chat.
- Simpan catatan internal ke `TChatCatatanInternal`.
- Toggle auto reply AI per chat: `AutoReplyAiAktif`, `ModeAutoReplyAi`.
- Tutup percakapan:
  - set status `DITUTUP`
  - isi `DitutupOleh` dan `TglDitutup` jika kolom ada
  - kirim pesan penutup AI
- Reset sapaan AI: `AiSudahMenyapa = false`.
- Refresh mapping dari nomor/grup/IdWaha/payload.
- Refresh profil WAHA dan resolve `@lid`.
- Simpan draft lokal dengan `StatusKirim = Draft Lokal`.
- Kirim ke WAHA:
  - text: `sendText`
  - media: `sendImage`, `sendVideo`, atau `sendFile`
  - simpan `TChatD.StatusKirim` `Terkirim WAHA` atau `Gagal WAHA`
  - reset unread dan update `TglDibalasTerakhir`.

#### WAHA webhook dan media

Source:

- `app/Http/Controllers/Webhook/WahaWebhookController.php`
- `app/Services/Waha/WahaWebhookProcessor.php`
- `app/Services/Waha/WahaSender.php`
- `app/Http/Controllers/WahaMediaController.php`

Perilaku yang harus dipindahkan:

- Token optional dari config.
- HMAC optional header:
  - `X-Webhook-Hmac`
  - `X-Webhook-Hmac-Algorithm = sha512`
- Setiap webhook masuk insert ke `TLogWebhookWaha`.
- `MSesiWhatsapp` dibuat otomatis jika belum ada.
- Parse banyak bentuk payload WAHA untuk `chatId`, `from`, `id._serialized`, `_data`, `key.remoteJid`, `groupId`, `participant`, `author`.
- Ignore status broadcast: `status@broadcast` dan `_status@broadcast_`.
- Dedupe `TChatD.IdPesanWaha`.
- Mapping:
  - private: `MNomorWhatsapp.NomorWhatsapp` atau `MNomorWhatsapp.IdWaha`
  - group: `MGrupWhatsapp.IdGrupWaha`
- Buat/update `TChatM` dan insert `TChatD`.
- `@lid` resolve via `GET /api/{session}/lids/{lid}` tanpa menghilangkan suffix `@lid`.
- WahaSender harus log semua request ke `TLogIntegrasi`.
- Media proxy harus mendukung absolute URL, localhost URL yang diubah ke base URL, data URL, local `/storage`, response JSON base64, dan header `X-Api-Key`.

#### AI Agent

Source:

- `app/Filament/Pages/AiAgent.php`
- `app/Services/Ai/AiAutoReplyService.php`
- `app/Services/Ai/ChatBelumTerbalasNotifier.php`
- `resources/views/filament/pages/ai-agent.blade.php`

Perilaku yang harus dipindahkan:

- Pengaturan default di `MPengaturanAi` dengan `KodePengaturan = DEFAULT`.
- Provider: OpenAI, DeepSeek, OpenRouter.
- API key bisa dari database terenkripsi atau environment.
- Mode auto reply:
  - aktif/nonaktif global
  - luar jam kerja
  - hari libur
  - sapaan awal jam kerja
  - berlanjut per sesi/global
- Hari libur memakai `MHariLibur`, termasuk `BerlakuTahunan`.
- Template hari libur memakai placeholder:
  - `{nama_hari_libur}`
  - `{tanggal_libur}`
  - `{tanggal_masuk_kerja}`
- AI membuat record `TAiPermintaan` dan `TAiRespon`.
- Jika API gagal/kosong, fallback template tetap disimpan.
- Balasan AI bisa draft lokal atau langsung WAHA sesuai `KirimKeWaha`.
- Notifikasi chat belum terbalas hanya saat jam kerja dan bukan hari libur.

#### Ticketing

Source UI sekarang masih prototype:

- `app/Filament/Pages/Ticketing.php`
- `resources/views/filament/pages/ticketing.blade.php`

Target migrasi:

- Tahap awal harus menyalin tampilan prototype agar sama.
- Setelah itu implementasi data real memakai `TTicketM`, `TTicketD`, `TTicketPenugasan`, `TTicketLampiran`, `MKategoriTicket`, `MPrioritasTicket`, `MStatusTicket`.
- Flow yang harus disediakan: buat ticket dari chat, assignment developer, timeline, komentar, status, prioritas, SLA.

#### Log Data

Source:

- `app/Filament/Pages/LogData.php`
- `resources/views/filament/pages/log-data.blade.php`

Perilaku yang harus dipindahkan:

- Tampilkan 30 log integrasi terakhir dari `TLogIntegrasi`.
- Tampilkan 15 log webhook WAHA terakhir dari `TLogWebhookWaha`.
- Short text maksimal sekitar 500 karakter.
- Status sukses/gagal harus mudah dibaca.

#### Integrasi VToken

Source:

- `app/Jobs/ImportVTokenCustomersToInstansi.php`
- `app/Console/Commands/ImportInstansiVToken.php`
- `app/Filament/Resources/Master/Instansis/Pages/ManageInstansis.php`

Perilaku yang harus dipindahkan:

- Konfigurasi URL wajib dari environment `VTOKEN_OPEN_CUSTOMERS_URL`.
- Tidak boleh hardcode fallback endpoint.
- Response valid jika `jsonResult === true` dan data ada di `jsonValue`.
- Mapping row ke `MInstansi`:
  - `kode` -> `KodeInstansi`
  - `namaPerusahaan` atau `appName` -> `NamaInstansi`
  - `alamat`, `kota`
  - `SumberData = vtoken`
  - `noID` -> `IdExternal`
  - `TglSinkronTerakhir = now`
- Existing update by `KodeInstansi`; missing insert.
- Semua request/response dicatat ke `TLogIntegrasi`.
- UI action tetap bernama `Syncron Data`.
- Eksekusi job target:
  - Action `Syncron Data` enqueue Hangfire job, bukan dispatch Laravel queue.
  - Pengganti `php artisan vpoint:import-instansi-vtoken --sync` adalah trigger Hangfire manual dari UI atau endpoint/admin command internal ASP.NET.
  - Jika masih perlu Windows Task Scheduler, tugas scheduler cukup memanggil endpoint trigger ASP.NET yang aman atau membangunkan service, bukan menjalankan `php artisan`.

## 3. Arsitektur Target di `newsrc`

Struktur project yang disarankan:

```text
newsrc/
  VPointCare.sln
  src/
    VPointCare.Web/
      Components/
        Layout/
        Pages/
          Dashboard/
          InboxWhatsapp/
          AiAgent/
          Master/
          Ticketing/
          Monitoring/
      Controllers/
        Webhooks/
        Media/
        Storage/
      Hubs/
        WahaInboxHub.cs
        WahaAgentsHub.cs
      Jobs/
        VTokenSyncJob.cs
        UnansweredChatNotificationJob.cs
        AiAutoReplyJob.cs
      Services/
        Auth/
        Waha/
        Ai/
        VToken/
        Dashboard/
        MasterData/
        Ticketing/
        Storage/
      wwwroot/
        css/
        images/
    VPointCare.Data/
      VPointCareDbContext.cs
      Entities/
      Configurations/
      MigrationsDisabled/
    VPointCare.Worker/                  # opsional jika Hangfire Server dipisah dari web app
      HangfireServerHost.cs
      Jobs/
  database/
    DATABASE_SCHEMA_WACS.sql
    schema-check.sql
  docs/
    screenshots/
    migration-checklist.md
```

Catatan: boleh satu ASP.NET Core project saja untuk awal, tetapi namespace harus tetap dipisahkan seperti di atas agar tidak sulit saat modul membesar.

### 3.1 Keputusan SignalR

Target SignalR:

- Server hub:
  - `/hubs/waha-inbox` untuk event chat baru/update.
  - `/hubs/waha-agents` atau group di hub yang sama untuk active CS/presence.
- Client Blazor:
  - `HubConnectionBuilder` dari `Microsoft.AspNetCore.SignalR.Client`.
  - Event client minimal:
    - `InboxUpdated(chatId)`
    - `AgentsUpdated(count)`
    - `Connected/Disconnected` untuk indikator UI.
- Fallback tetap ada:
  - Inbox tetap polling 60 detik.
  - Active agents tetap punya heartbeat/cache fallback bila koneksi SignalR putus.

Harapan operasional SignalR:

1. Saat WAHA mengirim webhook dan sistem berhasil menyimpan pesan ke `TLogWebhookWaha`, `TChatM`, dan `TChatD`, server harus langsung mengirim event SignalR ke semua user yang sedang membuka aplikasi admin.
2. User yang sedang membuka aplikasi harus menerima notifikasi realtime tanpa refresh manual:
   - toast/snackbar di dalam aplikasi;
   - suara notifikasi jika toggle suara aktif;
   - badge/unread count di daftar chat;
   - update otomatis daftar chat dan selected chat jika chat yang sedang dibuka menerima pesan baru;
   - optional browser notification hanya jika user sudah memberi izin browser.
3. User yang tidak sedang membuka aplikasi tidak dihitung sebagai agent aktif dan tidak wajib menerima SignalR event. Notifikasi WhatsApp internal ke CS tetap ditangani job Hangfire `UnansweredChatNotificationJob`.
4. Jumlah agent/CS aktif dihitung sebagai jumlah user unik yang masih punya koneksi SignalR aktif, bukan jumlah tab/browser.
5. Jika satu CS membuka beberapa tab, tetap dihitung 1 agent.
6. Jika koneksi putus tanpa `OnDisconnectedAsync` yang bersih, heartbeat timeout harus membersihkan agent itu dari daftar aktif.

Desain event SignalR:

| Event server ke client | Tujuan | Payload minimal |
| --- | --- | --- |
| `InboxUpdated` | Refresh daftar chat setelah webhook WAHA atau aksi chat | `chatId`, `jenisChat`, `namaKontak`, `namaInstansi`, `preview`, `unreadCount`, `occurredAt` |
| `NewMessageNotification` | Memunculkan toast/sound untuk user yang sedang membuka app | `chatId`, `title`, `message`, `isGroup`, `senderName`, `receivedAt` |
| `AgentsUpdated` | Update indikator jumlah CS aktif | `count`, `agents` |
| `Connected` / `Disconnected` | Status koneksi client | `connectionId`, `serverTime` |

Desain server:

- `WahaWebhookController` atau service setelah `WahaWebhookProcessor.ProcessAsync(...)` sukses memanggil `IHubContext<WahaInboxHub>`.
- Broadcast diarahkan ke group admin/inbox, misalnya `Clients.Group("waha-inbox-viewers")`.
- Hub `OnConnectedAsync`:
  - ambil user login dari cookie auth;
  - resolve `MUser` dan `MPengguna`;
  - daftarkan `ConnectionId` ke `ActiveAgentTracker`;
  - masukkan koneksi ke group `waha-inbox-viewers`;
  - broadcast `AgentsUpdated`.
- Hub `OnDisconnectedAsync`:
  - hapus `ConnectionId`;
  - jika user tidak punya koneksi lain, user tidak aktif;
  - broadcast `AgentsUpdated`.
- `ActiveAgentTracker` harus menyimpan per user:
  - `MUser.id`;
  - `MPengguna.Id`;
  - nama user;
  - daftar `ConnectionId`;
  - `LastSeenAt`.
- Untuk deployment single server, `IMemoryCache` cukup.
- Untuk deployment multi server, gunakan Redis/Azure SignalR agar presence tidak terpecah antar proses. Jika belum ada Redis, tetap sediakan polling/heartbeat fallback agar angka tidak kosong.

Desain client Blazor:

- Buat service scoped/singleton per circuit, misalnya `WahaSignalRClient`.
- Service membuat `HubConnection` ke `/hubs/waha-inbox`.
- Register handler:
  - `On<InboxUpdatedPayload>("InboxUpdated", ...)`
  - `On<NewMessagePayload>("NewMessageNotification", ...)`
  - `On<AgentsUpdatedPayload>("AgentsUpdated", ...)`
- Komponen `InboxWhatsapp.razor` subscribe ke event service:
  - refresh data chat;
  - update `activeAgents`;
  - panggil `ISnackbar.Add(...)`;
  - panggil JS audio notification jika toggle aktif.
- Jika SignalR disconnect:
  - tampilkan status `Polling 60s`;
  - tetap jalankan timer refresh tiap 60 detik;
  - reconnect otomatis dengan backoff.

Mapping lama ke target:

| Laravel lama | ASP.NET target |
| --- | --- |
| `php artisan reverb:start` | SignalR hub berjalan di ASP.NET Core app |
| `resources/js/echo.js` | Blazor `HubConnection` SignalR client |
| channel `waha-inbox` | hub/group `waha-inbox` |
| presence `waha-agents` | hub/group active agents |
| event `.inbox.updated` | method `InboxUpdated` |
| Alpine sound notification | Blazor JS interop audio service + MudBlazor snackbar |

### 3.2 Keputusan Hangfire

Target Hangfire:

- Hangfire Server berjalan di ASP.NET Core app yang sama untuk awal, atau di `VPointCare.Worker` sebagai Windows Service jika produksi butuh proses terpisah.
- Hangfire Dashboard hanya boleh diakses admin.
- Storage Hangfire:
  - Rekomendasi utama: database terpisah, misalnya `VPointCareHangfire`, agar schema WACS tetap sama persis.
  - Alternatif: schema `Hangfire` di database yang sama, tetapi ini harus dianggap pengecualian infrastruktur dan tidak boleh menyentuh tabel WACS.
- Laravel tables `jobs`, `job_batches`, `failed_jobs` tetap legacy, tidak dipakai untuk queue target.

Mapping job lama ke target:

| Laravel lama / Task Scheduler | Hangfire target |
| --- | --- |
| `php artisan queue:listen --tries=1 --timeout=0` | Hangfire Server |
| `php artisan vpoint:import-instansi-vtoken` | `BackgroundJob.Enqueue<VTokenSyncJob>()` |
| `php artisan vpoint:import-instansi-vtoken --sync` | trigger manual job sync/admin action di ASP.NET |
| `php artisan vpoint:kirim-notifikasi-chat-belum-terbalas` | `RecurringJob.AddOrUpdate<UnansweredChatNotificationJob>(...)` |
| Task Scheduler menjalankan `cmd php artisan ...` | Hangfire recurring schedule atau Task Scheduler memanggil endpoint ASP.NET yang meng-enqueue Hangfire job |

Job awal yang harus dibuat:

- `VTokenSyncJob`
  - pengganti `ImportVTokenCustomersToInstansi`.
  - bisa dijalankan manual dari tombol `Syncron Data`.
- `UnansweredChatNotificationJob`
  - pengganti command `KirimNotifikasiChatBelumTerbalas`.
  - recurring sesuai interval operasional.
- `AiAutoReplyJob`
  - opsional untuk memindahkan auto reply dari request webhook ke background agar response webhook cepat.
- `WahaSendRetryJob`
  - opsional untuk retry pengiriman gagal bila dibutuhkan.

## 4. Strategi Database dan EF Core

1. Copy `src/DATABASE_SCHEMA_WACS.sql` ke `newsrc/database/DATABASE_SCHEMA_WACS.sql` sebagai canonical schema.
2. Buat database dari SQL tersebut, bukan dari EF migration bebas.
3. Scaffold EF Core dari database:

```text
dotnet ef dbcontext scaffold "CONNECTION_STRING" Microsoft.EntityFrameworkCore.SqlServer --context VPointCareDbContext --output-dir Entities --context-dir . --data-annotations false --use-database-names
```

4. Setelah scaffold, rapikan mapping dengan Fluent API tanpa mengganti schema:
   - `ToTable("MCustomer")`, bukan pluralized name.
   - `HasColumnName("NamaCustomer")`.
   - `HasColumnType("varchar(200)")`, `nvarchar(max)`, `datetime2`, `time(0)`, `bit`.
   - `ValueGeneratedOnAdd().HasDefaultValueSql("NEWSEQUENTIALID()")`.
   - `HasKey(e => e.Id)`.
   - `HasIndex(...).HasDatabaseName("IX_...")`.
   - Filtered unique index `UX_MPengguna_UserId`.
5. Jangan memakai ASP.NET Identity default schema karena akan membuat tabel baru yang berbeda.
   - Pilihan aman: custom auth service yang membaca `MUser` + `MPengguna`.
   - Password lama Laravel bcrypt tetap bisa diverifikasi di .NET dengan BCrypt.Net.
   - Session/auth cookie ASP.NET boleh baru, tetapi tabel domain tetap lama.
6. Buat script migrasi auth dari `users` ke `MUser`.
   - Copy semua akun lama.
   - Pertahankan hash password apa adanya.
   - Isi `MPengguna.UserId` dengan `MUser.id`.
   - Drop/recreate FK lama jika sebelumnya masih mengarah ke `users`.
7. Buat `schema-check.sql` untuk membandingkan target dengan source:
   - `INFORMATION_SCHEMA.COLUMNS`
   - `sys.default_constraints`
   - `sys.foreign_keys`
   - `sys.indexes`
   - `sys.key_constraints`
8. Definition of Done database:
   - Jumlah tabel sama.
   - Jumlah kolom per tabel sama.
   - Tipe dan panjang sama.
   - FK/index/unique/default sama.
   - Pengecualian schema diff yang diterima hanya `users` -> `MUser` dan FK terkait `MPengguna.UserId`.
   - Tidak ada tabel `AspNetUsers`, `AspNetRoles`, atau tabel baru lain tanpa persetujuan.

## 5. Strategi Tampilan MudBlazor agar Sama

1. Buat admin shell:
   - `/admin`
   - `MudLayout`
   - sidebar kiri collapsible
   - appbar atas
   - footer copyright
   - dark mode
   - `notranslate` global di `App.razor`/layout host
2. Copy asset logo:
   - `src/public/images/logo_primary.svg`
   - `src/public/images/logo_secondary.svg`
   - `src/public/images/logo_ai.svg`
   - semua asset di `res` yang dipakai.
3. Buat CSS token agar MudBlazor mendekati Filament:
   - radius 8px
   - border `#e2e8f0`
   - warna utama blue, slate, emerald, amber, red
   - dark background `#111827`
   - typography Inter/system
4. Hindari tampilan MudBlazor default yang terlalu berbeda.
   - Komponen MudBlazor dipakai untuk behavior, tetapi class/CSS dibuat mengikuti HTML lama.
5. Untuk setiap page, buat screenshot pembanding source lama dan target baru:
   - desktop 1440px
   - tablet landscape
   - mobile jika page mendukung
6. Definition of Done UI:
   - Menu, label, order, group, icon, badge, empty state, tombol, warna status, dan layout harus sama.
   - Inbox tetap 3 kolom pada desktop.
   - Dashboard cards dan table tetap sama.
   - AI Agent tetap memakai section, checkbox mode, provider cards, template textareas.

## 6. Mapping Route Target

| Source lama | Target ASP.NET 8 | Catatan |
| --- | --- | --- |
| `/` | redirect `/admin` | Sama |
| `/admin` | Blazor Admin Shell | Sama |
| `/admin/inbox-whatsapp` | Blazor page Inbox WhatsApp | Sama |
| `/admin/ai-agent` | Blazor page AI Agent | Sama |
| `/admin/master-customer` | Blazor page Master Customer | Sama |
| `/admin/master/instansis` | MudBlazor CRUD Instansi | URL boleh disamakan |
| `/admin/master/customers` | MudBlazor CRUD Customer | URL boleh disamakan |
| `/admin/master/nomor-whatsapps` | MudBlazor CRUD Nomor WA | URL boleh disamakan |
| `/admin/master/grup-whatsapps` | MudBlazor CRUD Grup WA | URL boleh disamakan |
| `/admin/master/anggota-grup-whatsapps` | MudBlazor CRUD Anggota Grup | URL boleh disamakan |
| `/admin/master/hari-liburs` | MudBlazor CRUD Hari Libur | URL harus ada |
| `/admin/master/pengetahuans` | MudBlazor CRUD Knowledge Base | URL boleh disamakan |
| `/admin/ticketing` | Blazor page Ticketing | Sama |
| `/admin/log-data` | Blazor page Log Data | Sama |
| `POST /webhooks/waha/{token?}` | Minimal API/Controller | Harus sama |
| `GET /admin/waha-media/{message}` | Controller media proxy | Harus sama |
| `GET /profile-storage/{path}` | Controller storage fallback | Harus sama |

## 7. Urutan Implementasi

### Fase 0 - Baseline dan bukti pembanding

1. Jalankan source lama lokal jika dependency tersedia.
2. Ambil screenshot halaman:
   - login
   - dashboard
   - inbox whatsapp
   - AI Agent
   - master customer
   - log data
   - ticketing
3. Export metadata schema source ke file pembanding.
4. Simpan sample payload WAHA dan sample row chat untuk test.

### Fase 1 - Project ASP.NET 8

1. Buat solution `VPointCare.sln`.
2. Buat Blazor Server app .NET 8.
3. Tambah package:
   - `MudBlazor`
   - `Microsoft.EntityFrameworkCore.SqlServer`
   - `Microsoft.EntityFrameworkCore.Design`
   - `Microsoft.AspNetCore.SignalR.Client`
   - `Hangfire.AspNetCore`
   - `Hangfire.SqlServer`
   - `BCrypt.Net-Next`
   - `Polly` jika perlu retry HTTP
4. Konfigurasi `appsettings.json` dan environment:
   - SQL Server connection string
   - WAHA base URL, media base URL, API key, token, HMAC key
   - OpenAI, DeepSeek, OpenRouter
   - VToken URL
   - Hangfire connection string/storage
5. Pasang auth cookie custom.
6. Pasang SignalR hub route.
7. Pasang Hangfire Server dan Hangfire Dashboard admin-only.

### Fase 2 - Database-first EF Core

1. Copy SQL schema.
2. Scaffold EF entities.
3. Buat partial classes atau DTO tanpa mengubah generated mapping.
4. Buat repository/query services untuk modul:
   - DashboardQueryService
   - InboxQueryService
   - MasterDataService
   - AiSettingsService
   - LogQueryService
5. Jalankan schema diff sampai sama.

### Fase 3 - Auth dan admin shell

1. Implement login memakai `MUser.email`, `MUser.password`, `MUser.status`.
2. Verifikasi bcrypt Laravel.
3. Blokir status selain `approved`.
4. Implement register `pending`.
5. Sync `MUser` <-> `MPengguna`.
6. Implement user approval/block/pending UI.
7. Implement layout admin, sidebar, brand, footer, dark mode, notranslate.

### Fase 4 - Master Data

1. Implement CRUD Instansi.
2. Implement CRUD Customer.
3. Implement CRUD Nomor WhatsApp dengan normalisasi nomor dan `IdWaha`.
4. Implement CRUD Grup WhatsApp.
5. Implement CRUD Anggota Grup dengan auto isi customer.
6. Implement CRUD Pengguna internal.
7. Implement CRUD Hari Libur.
8. Implement CRUD Knowledge Base AI.
9. Implement page Ringkasan Customer dan shortcut menu.

### Fase 5 - Dashboard

1. Port semua query dashboard dari Laravel ke LINQ atau SQL raw EF.
2. Pastikan hasil numeric cocok untuk periode yang sama.
3. Implement quick range dan date range dialog.
4. Polling 15 detik.
5. Implement tampilan cards, progress, trend bars, tables.

### Fase 6 - WAHA webhook, SignalR, Hangfire, dan media

1. Implement `WahaWebhookController`.
2. Port parser payload WAHA.
3. Port status broadcast guard.
4. Port duplicate guard.
5. Port session auto create.
6. Port private/group mapping.
7. Port LID resolver.
8. Implement `WahaSender`.
9. Implement media proxy.
10. Implement SignalR Server hub:
    - `/hubs/waha-inbox`
    - active agents/presence replacement.
11. Implement SignalR Client di Blazor:
    - connect/disconnect indicator.
    - handler `InboxUpdated`.
    - handler `NewMessageNotification` untuk toast, sound, dan badge unread.
    - handler `AgentsUpdated` untuk jumlah agent/CS aktif.
    - fallback polling tetap aktif.
12. Implement `ActiveAgentTracker`:
    - hitung user unik, bukan jumlah tab.
    - simpan daftar connection id per user.
    - heartbeat timeout untuk koneksi yang mati tidak bersih.
13. Broadcast setelah webhook berhasil seperti event lama, tetapi payload harus cukup untuk notification UI.
14. Jika auto reply dibuat background, enqueue `AiAutoReplyJob` via Hangfire setelah webhook berhasil.

### Fase 7 - Inbox WhatsApp

1. Implement query daftar chat dan stats.
2. Implement filter text dan jenis chat.
3. Implement selected chat, messages, media category, sender name, avatar.
4. Implement auto-claim.
5. Implement internal notes.
6. Implement history chats.
7. Implement refresh mapping.
8. Implement refresh profil WAHA.
9. Implement draft lokal.
10. Implement send WAHA text/media.
11. Implement toggle sound dan SignalR client status.
12. Implement notification UI:
    - MudBlazor snackbar/toast untuk pesan masuk baru.
    - sound notification jika aktif.
    - unread badge bertambah tanpa refresh manual.
13. Implement jumlah `Tim Aktif (Online)` dari event `AgentsUpdated`.
14. Implement action close chat dengan pesan penutup AI.

### Fase 8 - AI Agent

1. Implement AI settings page.
2. Implement encrypted API key storage.
3. Implement provider presets.
4. Implement working hour, holiday, next working date.
5. Implement knowledge search.
6. Implement OpenAI Responses API path lama.
7. Implement DeepSeek/OpenRouter chat completions.
8. Implement fallback behavior.
9. Implement unanswered chat notifier sebagai Hangfire recurring job.
10. Pastikan notifikasi internal hanya saat jam kerja dan bukan hari libur.

### Fase 9 - Ticketing

1. Replikasi tampilan prototype dulu.
2. Implement data real `TTicketM` dan detail.
3. Implement buat ticket dari chat.
4. Implement status, prioritas, kategori.
5. Implement assignment developer.
6. Implement timeline dan lampiran.
7. Implement close/reopen.

### Fase 10 - Monitoring dan Hangfire jobs

1. Implement Log Data page.
2. Implement VToken sync job sebagai Hangfire job.
3. Implement UI action `Syncron Data`.
4. Implement Hangfire strategy untuk:
   - VToken sync
   - notifikasi chat belum terbalas
   - retry WAHA jika nanti diperlukan
5. Implement Hangfire Dashboard admin-only.
6. Tentukan deployment:
   - Hangfire Server in-process di web app, atau
   - Hangfire Server sebagai Windows Service terpisah.
7. Migrasi Task Scheduler:
   - hentikan task lama yang menjalankan `cmd php artisan ...` setelah Hangfire job terverifikasi.
   - jika scheduler eksternal masih dibutuhkan, arahkan ke endpoint ASP.NET yang aman untuk enqueue Hangfire, bukan ke PHP artisan.
8. Semua request integrasi harus masuk `TLogIntegrasi`.

### Fase 11 - Validasi akhir

1. Schema diff source vs target.
2. Query diff dashboard source vs target.
3. Webhook sample replay.
4. Kirim WAHA text/media test.
5. AI auto reply test:
   - jam kerja sapaan
   - luar jam kerja
   - hari libur
   - fallback API key kosong
6. UI screenshot diff.
7. Build:

```text
dotnet build
dotnet test
```

## 8. Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| MudBlazor default tidak sama dengan Filament | Buat CSS compatibility layer dan screenshot diff sebelum modul dianggap selesai |
| ASP.NET Identity membuat schema baru | Gunakan custom auth ke tabel `MUser`/`MPengguna`, jangan scaffold Identity default |
| EF migration mengubah schema | Database-first, migration disabled untuk domain schema, schema diff wajib |
| Password Laravel tidak cocok | Gunakan BCrypt.Net untuk hash bcrypt Laravel |
| SignalR presence tidak sama dengan Reverb | Implement hub khusus active agents dan fallback heartbeat seperti Laravel cache |
| Jumlah agent aktif salah karena satu user membuka banyak tab | Hitung user unik berdasarkan `MUser.id`/`MPengguna.Id`, simpan banyak connection id per user |
| User tidak menerima notifikasi saat webhook masuk | Webhook success path wajib memanggil `IHubContext<WahaInboxHub>` dan client wajib handle `NewMessageNotification` |
| SignalR disconnect membuat UI terlihat mati | Reconnect otomatis, status indikator jelas, dan polling 60 detik tetap aktif |
| Hangfire menambah tabel dan mengganggu syarat schema sama persis | Pakai database Hangfire terpisah, atau schema `Hangfire` terpisah dengan persetujuan eksplisit |
| Task Scheduler lama masih menjalankan `php artisan` setelah cutover | Buat daftar task lama, matikan bertahap setelah Hangfire recurring job dan dashboard terverifikasi |
| WAHA payload shape berubah | Parser harus port dari Laravel, bukan parser minimal |
| `@lid` kembali tersimpan | LID resolver wajib di webhook dan refresh profil/inbox |
| AI menjawab di hari libur/jam nonkerja tidak sesuai | `MHariLibur` dan `MPengaturanAi` jadi satu-satunya rule source |
| Media WAHA tidak tampil karena localhost URL | Media proxy harus normalize localhost ke configured media base URL |
| VToken URL hardcoded | Hanya baca dari environment, tanpa fallback |

## 9. Checklist Definition of Done

- [ ] `newsrc` memiliki solution ASP.NET Core 8 yang build.
- [ ] SQL schema target identik dengan `src/DATABASE_SCHEMA_WACS.sql`, kecuali keputusan rename auth `users` ke `MUser`.
- [ ] Tabel target `MUser` tersedia dan data akun lama dari `users` termigrasi.
- [ ] `MPengguna.UserId` mengarah ke `MUser.id`, bukan `users.id`.
- [ ] Tidak ada tabel ASP.NET Identity default yang mengubah kontrak database.
- [ ] Login/register/approval/block sama dengan source lama.
- [ ] Sidebar, brand, footer, menu group, dan label sama.
- [ ] Master Data CRUD sama dan tetap memakai field lama.
- [ ] Dashboard angka sama untuk periode yang sama.
- [ ] Inbox 3 kolom sama, dengan realtime SignalR dan polling fallback.
- [ ] Reverb/Echo sudah tidak dipakai; realtime memakai SignalR Server dan SignalR Client.
- [ ] Webhook WAHA yang sukses memunculkan notification realtime ke user yang sedang membuka aplikasi.
- [ ] Notification realtime mencakup toast/snackbar, sound optional, unread badge, dan refresh daftar chat.
- [ ] Jumlah `Tim Aktif (Online)` dihitung dari user CS unik yang punya koneksi SignalR aktif.
- [ ] Webhook WAHA endpoint dan response behavior sama.
- [ ] Status broadcast dan duplicate event tidak membuat chat.
- [ ] `@lid` diselesaikan menjadi nomor HP jika WAHA menyediakan mapping.
- [ ] AI Agent memakai `MPengaturanAi`, `MHariLibur`, `MPengetahuan`, `TAiPermintaan`, `TAiRespon`.
- [ ] Notifikasi internal hanya jam kerja dan bukan hari libur.
- [ ] VToken sync membaca `VTOKEN_OPEN_CUSTOMERS_URL` dan menulis `TLogIntegrasi`.
- [ ] VToken sync dan notifikasi chat belum terbalas berjalan lewat Hangfire.
- [ ] Task Scheduler lama yang menjalankan `cmd php artisan ...` sudah punya mapping Hangfire dan rencana cutover.
- [ ] Media proxy WAHA dan profile-storage fallback berfungsi.
- [ ] Screenshot UI target sudah dibandingkan dengan source lama.

## 10. Catatan Implementasi Teknis Penting

- Gunakan `DateTime`/`DateTimeOffset` dengan hati-hati; kolom lama adalah `datetime2`, jadi mapping EF sebaiknya `DateTime`.
- Gunakan `Guid` untuk `uniqueidentifier`.
- Jangan ubah `varchar` menjadi `nvarchar` kecuali schema memang `nvarchar`.
- Jangan rename property publik yang dipakai query/DTO tanpa mapping jelas ke nama kolom lama.
- Untuk query dashboard/inbox kompleks, SQL raw dengan parameter lebih aman dibanding LINQ panjang jika hasil harus 100% sama.
- Untuk delete master, gunakan update `NonAktif = 1`, bukan physical delete.
- Untuk file upload foto profil, simpan path kompatibel dengan `MPengguna.FotoProfilPath`.
- Untuk secret AI database, gunakan Data Protection API ASP.NET; sediakan migrasi/adapter jika perlu membaca encrypted string Laravel lama.
- Semua service eksternal harus punya log sukses/gagal di `TLogIntegrasi`.

## 11. Modul yang Belum Sepenuhnya Real di Source Lama

Ticketing di source lama masih terlihat sebagai page prototype dengan data statis pada Blade. Saat dipindahkan ke ASP.NET 8:

1. Tahap pertama harus menyalin tampilan prototype agar "sama persis".
2. Tahap kedua baru menghubungkan ke tabel ticketing real yang sudah tersedia di schema.
3. Perubahan ini perlu diberi tanda jelas saat implementasi agar tidak dianggap source lama sudah punya semua flow ticketing live.

## 12. Kesimpulan Arah Migrasi

Migrasi paling aman adalah database-first dan UI-compatibility-first. `newsrc` harus membaca/menulis database dengan schema yang sama persis, lalu menyalin perilaku Laravel/Filament satu modul per satu modul ke Blazor Server + MudBlazor. EF Core dipakai sebagai mapper dan query layer, bukan untuk mengganti desain database. MudBlazor dipakai sebagai engine UI, tetapi tampilan operasional harus dikunci dengan CSS, layout, label, dan screenshot pembanding dari source lama.
