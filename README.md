# CareDesk / Care Desk System

**CareDesk** (**Care Desk System**) adalah aplikasi customer-service terpadu berbasis Laravel untuk mengelola percakapan WhatsApp, respons agent, auto-reply AI, ticketing, data master customer, dan integrasi WAHA. Dibuat oleh **[YHDev](https://yhdev.click)**. Aplikasi ini menggunakan panel admin Filament, PostgreSQL sebagai database utama (siap SaaS), Laravel Reverb untuk realtime event, queue worker untuk pekerjaan background, serta Vite/Tailwind untuk asset frontend.

## Ringkasan Aplikasi

Aplikasi ini dibuat untuk membantu tim customer service menangani komunikasi pelanggan melalui WhatsApp secara terpusat. Pesan masuk diterima dari webhook WAHA, dinormalisasi ke tabel chat internal, ditampilkan pada inbox admin, lalu dapat dibalas manual oleh agent atau diproses oleh auto-reply AI sesuai pengaturan.

Fokus utama aplikasi:

- Inbox WhatsApp customer-service dengan status realtime.
- Integrasi WAHA untuk webhook pesan masuk, pengiriman balasan, profile/media WhatsApp, dan mapping nomor/grup.
- AI Agent untuk membuat balasan otomatis atau draft lokal menggunakan OpenAI, DeepSeek, OpenRouter, atau 9Router.
- Ticketing untuk tindak lanjut masalah customer.
- Master data customer, instansi, pengguna, hak akses, nomor WhatsApp, grup WhatsApp, hari libur, dan pengetahuan AI.
- Log operasional untuk aktivitas webhook, integrasi, error, dan histori chat.
- Sinkronisasi data instansi/customer dari VToken.
- Scheduler berbasis database untuk menjalankan command terjadwal.
- Multi bahasa Indonesia dan Inggris pada UI admin.

## Teknologi Utama

| Area | Teknologi |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13 |
| Admin panel | Filament 5 |
| Database | PostgreSQL 16+ (`pgsql` - Open Source, Free for SaaS) |
| Realtime | Laravel Reverb, Laravel Echo, Pusher protocol |
| Queue | Laravel database / redis queue |
| Frontend build | Node.js, npm, Vite 8, Tailwind CSS 4 |
| WhatsApp gateway | WAHA |
| AI provider | OpenAI Responses API, DeepSeek Chat Completions, OpenRouter/9Router Chat Completions |
| Bahasa | Indonesia (`id`) dan Inggris (`en`) |

## Struktur Repository

```text
.
├── README.md                  # Dokumentasi utama repository
├── docs/                      # Dokumentasi tambahan proyek
├── openspec/                  # Spesifikasi kebutuhan dan kemampuan aplikasi
├── res/                       # Resource pendukung repository
└── src/                       # Source code Laravel CareDesk
    ├── app/                   # Controller, Filament page/resource, service, model, job, command
    ├── config/                # Konfigurasi Laravel, service, Reverb, database
    ├── database/              # Migration dan seeder
    ├── public/                # Public web root dan asset publik
    ├── resources/             # Blade view, CSS, JS, terjemahan
    ├── routes/                # Route web, channel broadcast, console schedule
    └── storage/               # Log, cache, upload, file runtime
```

## Modul Aplikasi

### Dashboard dan Admin Panel

- Panel utama tersedia di `/admin`.
- Login dan registrasi memakai model pengguna internal `MPengguna`, bukan tabel `users` default Laravel.
- Sidebar, breadcrumb, label menu, dan hak akses diambil dari konfigurasi permission serta data `MHakAkses`.
- Brand panel menggunakan identitas Care Desk dan mendukung dark mode.

### Inbox WhatsApp

- Menampilkan percakapan WhatsApp customer dan grup.
- Mendukung chat baru, balasan manual, catatan internal, draft, penutupan percakapan, reset greeting AI, dan mapping customer/instansi.
- Menerima update realtime melalui channel broadcast `waha-agents` dan event inbox.
- Mengambil media/profile WAHA melalui endpoint internal yang diamankan auth.

### Webhook WAHA

- Endpoint webhook: `POST /webhooks/waha/{token?}`.
- Token webhook dapat dikirim melalui path token atau konfigurasi environment.
- Processor webhook menyimpan pesan masuk, memperbaiki identitas WAHA, memproses chat customer/grup, dan memicu event lanjutan.
- Pengiriman pesan keluar dilakukan melalui service `WahaSender` ke endpoint WAHA.

### AI Agent

- Pengaturan AI tersedia dari menu admin AI Agent.
- Provider yang didukung: OpenAI, DeepSeek, dan OpenRouter.
- AI dapat membuat draft lokal atau langsung mengirim balasan ke WhatsApp melalui WAHA jika `KirimKeWaha` aktif.
- Auto-reply mempertimbangkan jam kerja, hari libur, sesi chat, customer, instansi, knowledge base, dan nomor yang dikecualikan.
- API key provider disimpan melalui pengaturan aplikasi dan harus dijaga sebagai secret.

### Ticketing

- Dashboard `/admin/ticketing` menampilkan statistik ticket baru, aktif, overdue, selesai, ticket pengguna aktif, dan antrean terbaru dari database.
- Resource Ticket mendukung CRUD, status, kategori, prioritas/SLA, customer, instansi, target selesai, penugasan, filter "Ticket Saya", catatan progres, dan lampiran privat.
- Perubahan status dicatat ke `TTicketD`; perubahan assignee dicatat ke `TTicketDPenugasan` dan mengirim notifikasi database kepada pengguna yang memiliki `ticket.view`.
- Lampiran disimpan pada disk privat `attachments`, maksimal 3 MB per file, dan hanya dapat diunduh melalui route terautentikasi.

### Task

- Resource Task menyediakan action item mandiri atau terkait ticket dengan nomor `TSK-YYYYMMDD-NNN`.
- Task mendukung status, prioritas, assignee, target selesai, estimasi, filter "Task Saya", checklist, komentar progres, dan lampiran privat.
- Perubahan assignee dicatat ke `TTaskDPenugasan` dan mengirim notifikasi database kepada pengguna yang memiliki `task.view`.
- Master status ticket/task, kategori, dan prioritas dikelola melalui resource Filament khusus pengguna dengan `ticket.manage`.

### Master Data

Master data yang dikelola aplikasi:

- `MCustomer`: data customer.
- `MInstansi`: data instansi/customer dari VToken.
- `MPengguna`: pengguna/admin/agent aplikasi.
- `MHakAkses` dan `MPeran`: role dan permission.
- `MNomorWhatsapp`: nomor WhatsApp yang dipakai sistem.
- `MGrupWhatsapp` dan `MAnggotaGrupWhatsapp`: mapping grup WhatsApp.
- `MHariLibur`: kalender hari libur untuk logika auto-reply dan notifikasi.
- `MPengetahuan`: knowledge base AI.

### Scheduler dan Queue

- Queue default menggunakan driver database.
- Scheduler membaca command aktif dari tabel `job_schedules`.
- Command notifikasi chat belum terbalas: `php artisan desk:kirim-notifikasi-chat-belum-terbalas`.
- Proses production wajib menjalankan queue worker dan scheduler agar fitur background berjalan.

## Prasyarat Development

Install komponen berikut di komputer development:

- PHP 8.3 atau lebih baru.
- Composer 2.
- Node.js dan npm versi aktif/LTS.
- PostgreSQL 14+ / 16+ (atau Docker container PostgreSQL).
- PHP extension database: `pdo_pgsql`, `pgsql`, `pdo_mysql`.
- PHP extension umum Laravel: `openssl`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `fileinfo`, `zip`, `intl`.
- WAHA server/container jika ingin menguji webhook dan kirim WhatsApp.
- Git.

Contoh stack lokal yang cocok di Windows:

- Laragon atau PHP manual untuk web server lokal.
- PostgreSQL 16 / Docker PostgreSQL container.
- WAHA berjalan di `http://127.0.0.1:3000`.

## Instalasi Development

Jalankan dari root repository:

```powershell
cd src
composer install
npm install
copy .env.example .env
php artisan key:generate
```

Edit `.env` sesuai environment lokal:

```env
APP_NAME="CareDesk"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8008
APP_SERVE_HOST=127.0.0.1
APP_SERVE_PORT=8008

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=caredesk
DB_USERNAME=postgres
DB_PASSWORD=postgres

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
BROADCAST_CONNECTION=reverb

WAHA_BASE_URL=http://127.0.0.1:3000
WAHA_API_KEY=
WAHA_WEBHOOK_TOKEN=change-this-token
WAHA_WEBHOOK_HMAC_KEY=

OPENAI_API_KEY=
OPENAI_MODEL=gpt-5
```

Siapkan database SQL Server:

```sql
CREATE DATABASE DBCareDesk;
```

Jalankan migration dan seeder:

```powershell
php artisan migrate --seed
```

Build asset atau jalankan Vite development server:

```powershell
npm run dev
```

Jalankan aplikasi development lengkap:

```powershell
composer run dev
```

Script `composer run dev` menjalankan proses berikut secara paralel:

- `php artisan serve:desk` pada host/port dari `.env`.
- `php artisan queue:listen --tries=1 --timeout=0`.
- `npm run dev`.
- `php artisan reverb:start`.

Buka aplikasi:

```text
http://127.0.0.1:8008/admin
```

Akun seed awal jika belum diubah:

```text
Email    : mrthx.89@gmail.com
Password : Ell1t3s3rv
```

Segera ubah password akun awal setelah login pertama, terutama jika database akan dipakai bersama.

## Konfigurasi WAHA Development

Pastikan WAHA dapat diakses dari aplikasi Laravel:

```env
WAHA_BASE_URL=http://127.0.0.1:3000
WAHA_MEDIA_BASE_URL="${WAHA_BASE_URL}"
WAHA_SEND_TEXT_PATH=/api/sendText
WAHA_NOTIFICATION_SESSION=default
WAHA_WEBHOOK_TOKEN=change-this-token
```

Daftarkan webhook WAHA ke URL aplikasi:

```text
http://127.0.0.1:8008/webhooks/waha/change-this-token
```

Jika WAHA berjalan di Docker dan Laravel berjalan di host Windows, sesuaikan host callback agar container dapat mengakses aplikasi, misalnya memakai IP host LAN atau reverse proxy lokal.

## Konfigurasi Realtime/Reverb

Untuk fitur realtime, gunakan Reverb sebagai broadcast connection:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=care-desk
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Jalankan Reverb:

```powershell
php artisan reverb:start
```

Jika tidak membutuhkan realtime saat development, `BROADCAST_CONNECTION=log` dapat dipakai, tetapi indikator realtime dan event UI tidak akan berjalan penuh.

## Command Penting

| Command | Fungsi |
| --- | --- |
| `composer run dev` | Menjalankan server, queue, Vite, dan Reverb untuk development. |
| `composer run start` | Menjalankan server Laravel, queue, dan Reverb tanpa Vite. |
| `composer run laragon` | Menjalankan queue dan Reverb untuk deployment Laragon yang web server-nya dikelola Apache/Nginx. |
| `npm run dev` | Menjalankan Vite development server. |
| `npm run build` | Membuat asset production ke `public/build`. |
| `php artisan migrate --seed` | Membuat/memperbarui schema database dan seed data awal. |
| `php artisan queue:work` | Menjalankan queue worker production. |
| `php artisan schedule:run` | Menjalankan scheduler Laravel. |
| `php artisan desk:kirim-notifikasi-chat-belum-terbalas` | Mengirim notifikasi internal chat belum terbalas. |
| `php artisan optimize:clear` | Membersihkan cache config/route/view. |
| `php artisan optimize` | Membuat cache optimasi production. |

## Testing dan Quality Check

Jalankan test Laravel:

```powershell
php artisan test
```

Jalankan script test dari Composer:

```powershell
composer run test
```

Format kode PHP jika diperlukan:

```powershell
vendor\bin\pint
```

Build asset production sebelum publish:

```powershell
npm run build
```

## Instalasi Production

### 1. Siapkan server

Server production minimal harus memiliki:

- PHP 8.3+ dengan extension SQL Server.
- Composer 2.
- Node.js/npm untuk proses build, atau asset sudah dibuild dari mesin CI/development.
- SQL Server yang dapat diakses oleh aplikasi.
- Web server Apache/Nginx/IIS dengan document root mengarah ke `src/public`.
- Process manager untuk queue, scheduler, dan Reverb.
- WAHA server yang stabil dan dapat diakses dari aplikasi.

### 2. Ambil source code

```powershell
git clone <repository-url> care-desk
cd care-desk\src
```

Atau update dari checkout yang sudah ada:

```powershell
git pull
cd src
```

### 3. Install dependency production

```powershell
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Jika build asset dilakukan di CI, folder `public/build` dapat dipublish bersama artifact dan `npm ci` tidak wajib dijalankan di server production.

### 4. Buat konfigurasi `.env`

```powershell
copy .env.example .env
php artisan key:generate
```

Untuk update aplikasi existing, jangan generate ulang `APP_KEY` karena dapat merusak data terenkripsi/session lama. Gunakan `APP_KEY` production yang sudah ada.

Contoh `.env` production:

```env
APP_NAME="CareDesk"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://care.example.com
APP_FORCE_HTTPS=true
TRUSTED_PROXIES=*
APP_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=sqlsrv
DB_HOST=sqlserver-host
DB_PORT=1433
DB_DATABASE=DBCareDesk
DB_USERNAME=app_user
DB_PASSWORD=strong_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb

WAHA_BASE_URL=https://waha.example.com
WAHA_MEDIA_BASE_URL=https://waha.example.com
WAHA_API_KEY=production_secret
WAHA_WEBHOOK_TOKEN=production_webhook_token
WAHA_WEBHOOK_HMAC_KEY=production_hmac_secret
WAHA_SEND_TEXT_PATH=/api/sendText
WAHA_NOTIFICATION_SESSION=default

VTOKEN_OPEN_CUSTOMERS_URL=https://vtoken.desk.my.id/api/open/customers

OPENAI_API_KEY=production_secret
OPENAI_MODEL=gpt-5
DEEPSEEK_API_KEY=
OPENROUTER_API_KEY=

REVERB_APP_ID=production-app-id
REVERB_APP_KEY=production-app-key
REVERB_APP_SECRET=production-app-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=care.example.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

### 5. Jalankan migration production

Backup database terlebih dahulu, lalu jalankan:

```powershell
php artisan down
php artisan migrate --force
php artisan db:seed --force
php artisan optimize:clear
php artisan optimize
php artisan up
```

Seeder aman dijalankan ulang untuk memastikan role, permission, menu, dan job schedule dasar tersedia. Tetap lakukan backup karena migration mengubah struktur SQL Server.

### 6. Konfigurasi web server

Document root wajib mengarah ke:

```text
<path-to-repo>/src/public
```

Pastikan folder berikut writable oleh user web server:

```text
src/storage
src/bootstrap/cache
```

Untuk Apache, aktifkan rewrite dan arahkan virtual host ke `src/public`. Untuk Nginx/IIS, gunakan pola Laravel standard: semua request non-file diarahkan ke `public/index.php`.

### 7. Jalankan worker, scheduler, dan Reverb

Production tidak cukup hanya web server. Jalankan proses background berikut:

```powershell
php artisan queue:work --sleep=3 --tries=3 --timeout=120
php artisan schedule:work
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Gunakan NSSM, Supervisor, systemd, Windows Service, atau process manager lain agar proses otomatis restart saat gagal atau server reboot.

### 8. Konfigurasi webhook WAHA production

Set webhook WAHA ke URL production:

```text
https://care.example.com/webhooks/waha/production_webhook_token
```

Pastikan URL dapat diakses dari server WAHA, HTTPS valid, dan firewall membuka port yang diperlukan.

## Publish / Deployment Update

Gunakan langkah ini setiap kali publish versi baru ke production:

```powershell
cd <path-to-repo>
git pull
cd src
php artisan down
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
php artisan up
```

Restart process manager untuk queue, scheduler, dan Reverb jika tidak otomatis:

```powershell
php artisan queue:restart
# restart service queue worker
# restart service scheduler
# restart service reverb
```

Checklist setelah publish:

- `/admin` dapat dibuka.
- Login admin berhasil.
- Asset CSS/JS termuat dari `public/build`.
- Database migration tidak error.
- Queue worker aktif.
- Scheduler aktif.
- Reverb/websocket aktif jika realtime dipakai.
- WAHA webhook menerima pesan masuk.
- Agent dapat mengirim balasan WhatsApp.
- AI Agent hanya aktif jika API key dan pengaturan sudah benar.

## Deployment Laragon / Split Public Folder

Jika memakai Laragon dengan web root di luar source, gunakan prinsip berikut:

- Folder web server mengarah ke `src/public` atau salinan isi `src/public`.
- File `public/index.php` harus menunjuk ke path `vendor/autoload.php` dan `bootstrap/app.php` yang benar.
- Jalankan proses background dari folder `src`:

```powershell
composer run laragon
```

Script tersebut menjalankan queue listener dan Reverb, sedangkan HTTP request dilayani Apache/Nginx Laragon.

## Environment Variable Penting

| Variable | Keterangan |
| --- | --- |
| `APP_URL` | URL utama aplikasi. Harus sesuai domain production. |
| `APP_FORCE_HTTPS` | Paksa URL HTTPS jika berada di belakang proxy SSL. |
| `TRUSTED_PROXIES` | Proxy tepercaya untuk membaca scheme/IP asli. |
| `DB_*` | Koneksi SQL Server. |
| `QUEUE_CONNECTION` | Disarankan `database`. |
| `BROADCAST_CONNECTION` | Gunakan `reverb` untuk realtime. |
| `WAHA_BASE_URL` | Base URL WAHA API. |
| `WAHA_API_KEY` | API key WAHA jika server WAHA memakai auth. |
| `WAHA_WEBHOOK_TOKEN` | Token URL webhook. |
| `WAHA_WEBHOOK_HMAC_KEY` | Secret verifikasi HMAC webhook jika dipakai. |
| `VTOKEN_OPEN_CUSTOMERS_URL` | Endpoint import customer/instansi VToken. |
| `OPENAI_API_KEY` | API key OpenAI untuk AI Agent. |
| `DEEPSEEK_API_KEY` | API key DeepSeek jika memakai DeepSeek. |
| `OPENROUTER_API_KEY` | API key OpenRouter jika memakai OpenRouter. |
| `NINEROUTER_API_KEY` | API key 9Router jika memakai 9Router. |
| `NINEROUTER_MODEL` / `NINEROUTER_BASE_URL` | Model dan endpoint 9Router. |
| `REVERB_*` | Konfigurasi Reverb server/client. |

## Database

Aplikasi menggunakan SQL Server dan schema bisnis dengan prefix tabel:

- `M*` untuk master data.
- `T*` untuk transaksi/log.
- `job_schedules`, `jobs`, `cache`, dan tabel Laravel pendukung lain.

Migration utama membaca file schema SQL Server `src/DATABASE_SCHEMA_WACS.sql`. Karena itu file tersebut harus ikut dipublish ke production. Migration akan gagal jika database connection bukan `sqlsrv`.

## Keamanan Operasional

- Jangan commit `.env` ke Git.
- Jangan menyimpan API key WAHA/AI di README, issue, atau chat publik.
- Ganti password admin seed setelah setup awal.
- Gunakan HTTPS di production.
- Batasi akses database production hanya dari server aplikasi.
- Lindungi endpoint WAHA dengan token kuat dan HMAC jika tersedia.
- Jalankan backup database sebelum migration production.
- Pastikan `APP_DEBUG=false` di production.

## Troubleshooting

### Aplikasi tidak bisa konek SQL Server

Periksa:

- Extension `sqlsrv` dan `pdo_sqlsrv` aktif di PHP CLI dan PHP web server.
- ODBC Driver SQL Server terinstall.
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` benar.
- SQL Server menerima koneksi TCP/IP.

### Pesan WhatsApp tidak masuk

Periksa:

- URL webhook WAHA mengarah ke `/webhooks/waha/{token}`.
- Token pada URL sama dengan `WAHA_WEBHOOK_TOKEN`.
- WAHA dapat mengakses domain aplikasi.
- Log Laravel di `src/storage/logs`.
- Tabel log webhook/integrasi untuk error payload.

### Balasan WhatsApp gagal terkirim

Periksa:

- `WAHA_BASE_URL` benar.
- Session WAHA aktif dan login.
- `WAHA_SEND_TEXT_PATH` sesuai versi WAHA.
- Nomor tujuan sudah dinormalisasi ke format WAHA yang benar.
- `WAHA_API_KEY` benar jika WAHA memakai auth.

### Realtime tidak jalan

Periksa:

- `BROADCAST_CONNECTION=reverb`.
- `php artisan reverb:start` berjalan.
- `REVERB_*` dan `VITE_REVERB_*` sesuai domain/port yang diakses browser.
- Port websocket tidak diblokir firewall/reverse proxy.
- Asset sudah dibuild ulang setelah mengubah `VITE_*`.

### AI tidak membalas

Periksa:

- Pengaturan AI Agent aktif.
- Provider dan API key valid.
- Jam kerja/hari libur tidak sedang memblokir auto-reply.
- Nomor tidak termasuk daftar pengecualian.
- `KirimKeWaha` aktif jika ingin langsung terkirim, bukan hanya draft lokal.

## Dokumentasi Spesifikasi

OpenSpec proyek tersedia di:

- `openspec/project.md`
- `openspec/specs/care-desk/spec.md`

Gunakan OpenSpec sebagai acuan requirement saat menambah fitur, memperbaiki flow WhatsApp, atau mengubah behavior AI/ticketing.

