# 🚀 Deployment VPoint Care ke Laragon Windows 10

> **Stack:** Laravel 13 · Filament v5 · PHP 8.3+ · SQL Server · Reverb WebSocket  
> **Source:** `D:\GIT VPOINT\2026-WACS\src`  
> **Target App:** `C:\laragon\care_apps`  
> **Target Public:** `C:\laragon\www\vpointcare` (subfolder, bukan root www)

---

## TAHAP 1 — Persiapan Laragon

### 1.1 Pastikan ekstensi PHP aktif di Laragon

Buka **Laragon → Menu → PHP → Extensions**, pastikan yang berikut **dicentang**:

| Ekstensi | Kebutuhan |
|---|---|
| `pdo_sqlsrv` | Koneksi SQL Server |
| `sqlsrv` | Driver SQL Server |
| `gd` / `imagick` | Upload gambar Filament |
| `zip` | Composer |
| `mbstring` | Laravel |
| `openssl` | Enkripsi |
| `pcre` | Regex |
| `tokenizer` | PHP Parser |
| `xml` | Laravel |
| `bcmath` | Filament |
| `curl` | HTTP Client |
| `fileinfo` | MIME detection |

> [!IMPORTANT]
> `sqlsrv` dan `pdo_sqlsrv` harus dari **Microsoft SQLSRV Driver** sesuai versi PHP. Cek di [https://github.com/microsoft/msphpsql/releases](https://github.com/microsoft/msphpsql/releases)

---

## TAHAP 2 — Salin File Aplikasi

### 2.1 Buat folder tujuan

```powershell
New-Item -ItemType Directory -Force "C:\laragon\care_apps"
New-Item -ItemType Directory -Force "C:\laragon\www\vpointcare"
```

### 2.2 Salin seluruh source ke care_apps (kecuali folder tidak perlu)

Salin manual via Windows Explorer **atau** via PowerShell:

```powershell
$src = "D:\GIT VPOINT\2026-WACS\src"
$dst = "C:\laragon\care_apps"

# Salin semua file (kecuali node_modules, .git, vendor akan di-install ulang)
robocopy $src $dst /E /XD ".git" "node_modules" /XF ".env"
```

> [!WARNING]
> **Jangan salin `.env`** dari dev ke production. Buat `.env` baru di Tahap 3.

### 2.3 Salin folder `public` saja ke `www\vpointcare`

```powershell
robocopy "D:\GIT VPOINT\2026-WACS\src\public" "C:\laragon\www\vpointcare" /E
```

---

## TAHAP 3 — Konfigurasi `.env` Production

Buat file baru `C:\laragon\care_apps\.env`:

```ini
APP_NAME="VPoint Care"
APP_ENV=production
APP_KEY=                          # akan diisi di Tahap 4
APP_DEBUG=false
APP_URL=http://vpointcare.test    # sesuaikan domain Laragon

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=sqlsrv
DB_HOST=26.245.185.82\SQL2019
DB_PORT=
DB_DATABASE=DBVPointCare
DB_USERNAME=sa
DB_PASSWORD=Sg1
DB_ENCRYPT=no
DB_TRUST_SERVER_CERTIFICATE=true

FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

SESSION_DRIVER=database
SESSION_LIFETIME=120

CACHE_STORE=file

REVERB_APP_ID=vpoint-care
REVERB_APP_KEY=vpointcare-key
REVERB_APP_SECRET=vpointcare-secret
REVERB_HOST=vpointcare.test
REVERB_PORT=8080
REVERB_SCHEME=http

OPENROUTER_SITE_URL="${APP_URL}"
OPENROUTER_APP_NAME="VPoint Care"
```

> [!TIP]
> Salin nilai `WAHA_*`, `OPENROUTER_API_KEY`, dan variabel lain dari `.env` dev Anda ke file ini.

---

## TAHAP 4 — Setup Aplikasi

Buka **PowerShell** di folder `C:\laragon\care_apps`:

```powershell
cd C:\laragon\care_apps
```

### 4.1 Install dependencies Composer (production mode)

```powershell
composer install --optimize-autoloader --no-dev
```

### 4.2 Generate APP_KEY

```powershell
php artisan key:generate
```

### 4.3 Atur `index.php` agar menunjuk ke care_apps

Edit `C:\laragon\www\vpointcare\index.php` — ubah path require:

```php
// Sebelum (path relatif):
require __DIR__.'/../vendor/autoload.php';

// Sesudah (path absolut ke care_apps):
require 'C:/laragon/care_apps/vendor/autoload.php';
```

Dan bagian `$app`:

```php
// Sebelum:
$app = require_once __DIR__.'/../bootstrap/app.php';

// Sesudah:
$app = require_once 'C:/laragon/care_apps/bootstrap/app.php';
```

### 4.4 Buat Symbolic Link Storage

```powershell
# Hapus folder storage lama di public jika ada
Remove-Item -Force -Recurse "C:\laragon\www\vpointcare\storage" -ErrorAction SilentlyContinue

# Buat symlink: public/storage → care_apps/storage/app/public
cd C:\laragon\care_apps
php artisan storage:link
```

> [!NOTE]
> `artisan storage:link` akan membuat symlink di `C:\laragon\www\vpointcare\storage` yang mengarah ke `C:\laragon\care_apps\storage\app\public`

### 4.5 Build Assets Vite (production)

```powershell
cd C:\laragon\care_apps

# Install node_modules jika belum ada
npm install

# Build production bundle
npm run build
```

Setelah build, salin folder `public/build` hasil Vite ke www:

```powershell
robocopy "C:\laragon\care_apps\public\build" "C:\laragon\www\vpointcare\build" /E
```

### 4.6 Optimize Cache Laravel

```powershell
cd C:\laragon\care_apps

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
```

---

## TAHAP 5 — Virtual Host Laragon

### 5.1 Tambahkan Virtual Host di Laragon

Buka **Laragon → Menu → Apache → sites-enabled → Tambah Virtual Host**

Atau edit langsung file `C:\laragon\etc\apache2\sites-enabled\vpointcare.conf`:

```apache
<VirtualHost *:80>
    ServerName vpointcare.test
    DocumentRoot "C:/laragon/www/vpointcare"

    <Directory "C:/laragon/www/vpointcare">
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog "C:/laragon/logs/apache2/vpointcare-error.log"
    CustomLog "C:/laragon/logs/apache2/vpointcare-access.log" combined
</VirtualHost>
```

### 5.2 Tambahkan hosts entry

Edit `C:\Windows\System32\drivers\etc\hosts` (sebagai Administrator):

```
127.0.0.1    vpointcare.test
```

### 5.3 Pastikan `.htaccess` ada di public

Cek `C:\laragon\www\vpointcare\.htaccess` — isinya harus:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## TAHAP 6 — Database & Seeder

### 6.1 Jalankan migration (jika DB belum ada tabelnya)

```powershell
cd C:\laragon\care_apps
php artisan migrate --force
```

### 6.2 Jalankan seeder (roles, permissions, admin user)

```powershell
php artisan db:seed --force
```

---

## TAHAP 7 — Queue Worker

Aplikasi menggunakan `QUEUE_CONNECTION=database`. Jalankan worker:

### Opsi A: Jalankan manual (untuk testing)

```powershell
cd C:\laragon\care_apps
php artisan queue:work --tries=3 --timeout=120
```

### Opsi B: Buat Task Scheduler Windows (production)

Buat file `C:\laragon\care_apps\start-queue.bat`:

```batch
@echo off
cd /d C:\laragon\care_apps
php artisan queue:work --tries=3 --timeout=120 --sleep=3
```

Daftarkan di **Windows Task Scheduler**:
- Trigger: At system startup
- Action: Run `C:\laragon\care_apps\start-queue.bat`
- Run whether user is logged in or not

---

## TAHAP 8 — Reverb WebSocket (Opsional)

Jika fitur realtime (notifikasi chat) diperlukan:

```powershell
cd C:\laragon\care_apps
php artisan reverb:start --port=8080
```

Atau buat `start-reverb.bat` dan daftarkan di Task Scheduler seperti queue.

---

## TAHAP 9 — Verifikasi Akhir

Buka browser ke `http://vpointcare.test/admin` dan pastikan:

- [ ] Halaman login muncul tanpa error
- [ ] Login berhasil dengan akun admin seeder
- [ ] Sidebar navigasi tampil dengan benar
- [ ] Upload gambar berfungsi (storage symlink OK)
- [ ] DataTable Pengguna, HakAkses bisa dibuka
- [ ] Ganti bahasa ID/EN berfungsi
- [ ] Log Data menampilkan data dari DB

---

## Struktur Folder Akhir

```
C:\laragon\
├── care_apps\          ← Seluruh source Laravel
│   ├── app\
│   ├── bootstrap\
│   ├── config\
│   ├── database\
│   ├── public\         ← TIDAK digunakan Apache langsung
│   ├── resources\
│   ├── routes\
│   ├── storage\        ← Writable oleh Apache/PHP
│   ├── vendor\
│   └── .env            ← Production env
│
└── www\
    └── vpointcare\     ← DocumentRoot Apache
        ├── index.php   ← Diubah pointnya ke care_apps
        ├── .htaccess
        ├── storage\    ← Symlink → care_apps/storage/app/public
        └── build\      ← Hasil npm run build (disalin dari care_apps/public/build)
```

---

## Perintah Ringkas (Urutan)

```powershell
# 1. Salin file
robocopy "D:\GIT VPOINT\2026-WACS\src" "C:\laragon\care_apps" /E /XD ".git" "node_modules" /XF ".env"
robocopy "D:\GIT VPOINT\2026-WACS\src\public" "C:\laragon\www\vpointcare" /E

# 2. Buat .env production di C:\laragon\care_apps\.env (manual)

# 3. Install & setup
cd C:\laragon\care_apps
composer install --optimize-autoloader --no-dev
php artisan key:generate
# Edit index.php di www\vpointcare (Tahap 4.3)
php artisan storage:link
npm install
npm run build
robocopy "C:\laragon\care_apps\public\build" "C:\laragon\www\vpointcare\build" /E

# 4. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components

# 5. DB
php artisan migrate --force
php artisan db:seed --force

# 6. Start services
php artisan queue:work --tries=3
# php artisan reverb:start --port=8080
```
