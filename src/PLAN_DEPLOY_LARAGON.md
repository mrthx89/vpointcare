# Deployment VPoint Care ke Laragon Windows 10

> **Stack:** Laravel 13 - Filament v5 - PHP 8.3+ - SQL Server - Reverb WebSocket
> **Source:** `D:\GIT VPOINT\2026-WACS\src`
> **Target App:** `C:\laragon\care_apps`
> **Target Public:** `C:\laragon\www` (root langsung)

---

## TAHAP 1 - Persiapan Laragon

### 1.1 Pastikan ekstensi PHP aktif di Laragon

Buka **Laragon - Menu - PHP - Extensions**, pastikan yang berikut **dicentang**:

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

> **PENTING:** `sqlsrv` dan `pdo_sqlsrv` harus dari **Microsoft SQLSRV Driver** sesuai versi PHP.
> Download di: https://github.com/microsoft/msphpsql/releases

---

## TAHAP 2 - Salin File Aplikasi

### 2.1 Buat folder tujuan

```powershell
New-Item -ItemType Directory -Force "C:\laragon\care_apps"
```

> Folder `C:\laragon\www` biasanya sudah ada di Laragon.

### 2.2 Salin seluruh source ke care_apps

```powershell
$src = "D:\GIT VPOINT\2026-WACS\src"
$dst = "C:\laragon\care_apps"

# Salin semua file (kecuali .git, node_modules - vendor akan di-install ulang)
robocopy $src $dst /E /XD ".git" "node_modules" /XF ".env"
```

> **PERINGATAN:** Jangan salin `.env` dari dev ke production. Buat `.env` baru di Tahap 3.

### 2.3 Salin folder `public` langsung ke `C:\laragon\www`

```powershell
robocopy "D:\GIT VPOINT\2026-WACS\src\public" "C:\laragon\www" /E
```

> Ini akan menyalin `index.php`, `.htaccess`, dan file public lainnya langsung ke root `www`.

---

## TAHAP 3 - Konfigurasi `.env` Production

Buat file baru `C:\laragon\care_apps\.env`:

```ini
APP_NAME="VPoint Care"
APP_ENV=production
APP_KEY=                          # akan diisi otomatis di Tahap 4.2
APP_DEBUG=false
APP_URL=https://care.vpoint.my.id
APP_FORCE_HTTPS=true
TRUSTED_PROXIES=*

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

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=612204
REVERB_APP_KEY=g2zwnuebwgen7zqywz51
REVERB_APP_SECRET=9nkdnjml6pxrnxtrm6jm
REVERB_HOST=care.vpoint.my.id
REVERB_PORT=7060
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

OPENROUTER_SITE_URL="${APP_URL}"
OPENROUTER_APP_NAME="VPoint Care"
# Salin juga: WAHA_*, OPENROUTER_API_KEY, dll dari .env dev
```

---

## TAHAP 4 - Setup Aplikasi

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

### 4.3 Edit `index.php` agar menunjuk ke care_apps

Edit `C:\laragon\www\index.php` - ubah 2 baris path:

```php
// Sebelum (path relatif):
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Sesudah (path absolut ke care_apps):
require 'C:/laragon/care_apps/vendor/autoload.php';
$app = require_once 'C:/laragon/care_apps/bootstrap/app.php';
```

### 4.4 Buat Symbolic Link Storage

```powershell
# Hapus folder storage lama di public jika ada
Remove-Item -Force -Recurse "C:\laragon\www\storage" -ErrorAction SilentlyContinue

# Buat symlink: public/storage - care_apps/storage/app/public
cd C:\laragon\care_apps
php artisan storage:link
```

> Symlink dibuat di `C:\laragon\www\storage` mengarah ke `C:\laragon\care_apps\storage\app\public`

### 4.5 Izin Write pada Storage

```powershell
icacls "C:\laragon\care_apps\storage" /grant "Everyone:(OI)(CI)F"
icacls "C:\laragon\care_apps\bootstrap\cache" /grant "Everyone:(OI)(CI)F"
```

### 4.6 Build Assets Vite (production)

```powershell
cd C:\laragon\care_apps

# Install node_modules jika belum ada
npm install

# Build production bundle
npm run build

# Salin hasil build ke www
robocopy "C:\laragon\care_apps\public\build" "C:\laragon\www\build" /E
```

### 4.7 Optimize Cache Laravel

```powershell
cd C:\laragon\care_apps

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
```

---

## TAHAP 5 - Virtual Host Laragon

### 5.1 Tambahkan Virtual Host

Edit atau buat file `C:\laragon\etc\apache2\sites-enabled\care-vpoint.conf`:

```apache
# Redirect HTTP ke HTTPS
<VirtualHost *:80>
    ServerName care.vpoint.my.id
    Redirect permanent / https://care.vpoint.my.id/
</VirtualHost>

# HTTPS Virtual Host
<VirtualHost *:443>
    ServerName care.vpoint.my.id
    DocumentRoot "C:/laragon/www"

    SSLEngine on
    SSLCertificateFile     "C:/laragon/etc/ssl/care.vpoint.my.id.crt"
    SSLCertificateKeyFile  "C:/laragon/etc/ssl/care.vpoint.my.id.key"

    <Directory "C:/laragon/www">
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog "C:/laragon/logs/apache2/care-vpoint-error.log"
    CustomLog "C:/laragon/logs/apache2/care-vpoint-access.log" combined
</VirtualHost>
```

> **CATATAN SSL:** Pastikan modul `mod_ssl` aktif di Apache Laragon.
> Aktifkan dengan cara: **Laragon - Menu - Apache - modules - ssl**

### 5.1.a Mendapatkan Sertifikat SSL Gratis (Let's Encrypt via win-acme)

**Prasyarat:** Domain `care.vpoint.my.id` sudah diarahkan ke IP publik server
dan port **80** sudah bisa diakses dari internet.

**Langkah 1 — Download win-acme**

Download dari: https://github.com/win-acme/win-acme/releases
Pilih file `wacs.trimmed.x64.zip`, ekstrak ke `C:\laragon\bin\win-acme\`

**Langkah 2 — Jalankan win-acme (sebagai Administrator)**

```powershell
cd C:\laragon\bin\win-acme
.\wacs.exe
```

Pilih menu interaktif:
```
N  : Create certificate (default settings)
    Domain: care.vpoint.my.id
    Validation: [2] Save verification files to a website folder
    Web root: C:\laragon\www
    Store: [1] PEM encoded files (untuk Apache)
    PEM folder: C:\laragon\etc\ssl\
```

Setelah selesai, file berikut akan dibuat otomatis:
```
C:\laragon\etc\ssl\care.vpoint.my.id-crt.pem   -> isi: sertifikat
C:\laragon\etc\ssl\care.vpoint.my.id-key.pem   -> isi: private key
C:\laragon\etc\ssl\care.vpoint.my.id-chain.pem -> isi: chain (CA)
```

**Langkah 3 — Sesuaikan path di Virtual Host**

Ubah konfigurasi Apache menjadi:

```apache
SSLCertificateFile     "C:/laragon/etc/ssl/care.vpoint.my.id-crt.pem"
SSLCertificateKeyFile  "C:/laragon/etc/ssl/care.vpoint.my.id-key.pem"
SSLCertificateChainFile "C:/laragon/etc/ssl/care.vpoint.my.id-chain.pem"
```

**Langkah 4 — win-acme auto-renew**

win-acme secara otomatis membuat **Windows Scheduled Task** untuk memperbarui
sertifikat setiap 60 hari — tidak perlu diurus manual.

---

### 5.1.b Alternatif: Ambil dari Panel Hosting (jika domain di-manage hPanel/cPanel)

Jika domain `vpoint.my.id` dikelola di hosting panel (misal Hostinger hPanel):

1. Masuk ke hPanel - **SSL** - **Let's Encrypt**
2. Issue sertifikat untuk subdomain `care.vpoint.my.id`
3. Setelah issued, klik **Details** - salin isi:
   - **Certificate** → simpan sebagai `care.vpoint.my.id.crt`
   - **Private Key** → simpan sebagai `care.vpoint.my.id.key`
4. Upload/taruh di `C:\laragon\etc\ssl\`

---

### 5.2 Catatan DNS

Karena `care.vpoint.my.id` adalah domain publik, **tidak perlu** mengubah file `hosts`.
Pastikan DNS subdomain `care.vpoint.my.id` sudah diarahkan ke **IP publik server**
menggunakan record **A** di panel DNS domain.

> Jika menggunakan **Cloudflare** sebagai DNS:
> - Mode **DNS only** (awan abu-abu) = koneksi langsung ke server, SSL ditangani Apache
> - Mode **Proxied** (awan oranye) = Cloudflare jadi proxy, SSL Cloudflare aktif otomatis
>   (tidak perlu sertifikat di server, tapi Reverb WebSocket harus pakai port yang didukung CF)

### 5.3 Pastikan `.htaccess` ada di www root

File `C:\laragon\www\.htaccess` harus berisi:

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

### 5.4 Restart Laragon Apache

Klik **Laragon - Reload** atau restart Apache dari tray icon.

---

## TAHAP 6 - Database & Seeder

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

## TAHAP 7 - Windows Services (Auto-start, Tanpa CMD)

Agar **Reverb WebSocket** dan **Queue Worker** berjalan otomatis saat PC nyala, **tanpa jendela Command Prompt**, gunakan **NSSM (Non-Sucking Service Manager)** yang membungkus process PHP menjadi Windows Service sejati.

### 7.1 Download & Install NSSM

1. Download NSSM dari: https://nssm.cc/download
2. Ekstrak dan salin `nssm.exe` (ambil dari folder `win64`) ke:
   ```
   C:\laragon\bin\nssm\nssm.exe
   ```

---

### 7.2 Cari path PHP yang dipakai Laragon

```powershell
# Cari PHP yang aktif di Laragon:
Get-Command php | Select-Object Source
```

Catat path lengkapnya, contoh: `C:\laragon\bin\php\php-8.5.5-Win32-vs17-x64\php.exe`

---

### 7.3 Buat Service: Queue Worker

Buka **PowerShell sebagai Administrator**, jalankan:

```powershell
$nssm   = "C:\laragon\bin\nssm\nssm.exe"
$php    = "C:\laragon\bin\php\php-8.5.5-Win32-vs17-x64\php.exe"   # sesuaikan versi PHP Anda
$appDir = "C:\laragon\care_apps"

# Install service
& $nssm install VPointCareQueue $php
& $nssm set VPointCareQueue AppParameters "$appDir\artisan queue:work --tries=3 --timeout=120 --sleep=3 --max-jobs=500"
& $nssm set VPointCareQueue AppDirectory $appDir
& $nssm set VPointCareQueue DisplayName "VPoint Care - Queue Worker"
& $nssm set VPointCareQueue Description "Laravel Queue Worker untuk VPoint Care"
& $nssm set VPointCareQueue Start SERVICE_AUTO_START

# Sembunyikan window CMD sepenuhnya
& $nssm set VPointCareQueue AppNoConsole 1

# Redirect log ke file
& $nssm set VPointCareQueue AppStdout "$appDir\storage\logs\queue-stdout.log"
& $nssm set VPointCareQueue AppStderr "$appDir\storage\logs\queue-stderr.log"
& $nssm set VPointCareQueue AppStdoutCreationDisposition 4
& $nssm set VPointCareQueue AppStderrCreationDisposition 4

# Auto-restart jika crash, tunggu 5 detik
& $nssm set VPointCareQueue AppRestartDelay 5000
& $nssm set VPointCareQueue AppExit Default Restart
```

---

### 7.4 Buat Service: Reverb WebSocket Server

```powershell
$nssm   = "C:\laragon\bin\nssm\nssm.exe"
$php    = "C:\laragon\bin\php\php-8.5.5-Win32-vs17-x64\php.exe"   # sesuaikan versi PHP Anda
$appDir = "C:\laragon\care_apps"

# Install service
& $nssm install VPointCareReverb $php
& $nssm set VPointCareReverb AppParameters "$appDir\artisan reverb:start --host=0.0.0.0 --port=7060"
& $nssm set VPointCareReverb AppDirectory $appDir
& $nssm set VPointCareReverb DisplayName "VPoint Care - Reverb WebSocket"
& $nssm set VPointCareReverb Description "Laravel Reverb WebSocket Server untuk VPoint Care (port 7060)"
& $nssm set VPointCareReverb Start SERVICE_AUTO_START

# Sembunyikan window CMD sepenuhnya
& $nssm set VPointCareReverb AppNoConsole 1

# Redirect log
& $nssm set VPointCareReverb AppStdout "$appDir\storage\logs\reverb-stdout.log"
& $nssm set VPointCareReverb AppStderr "$appDir\storage\logs\reverb-stderr.log"
& $nssm set VPointCareReverb AppStdoutCreationDisposition 4
& $nssm set VPointCareReverb AppStderrCreationDisposition 4

# Auto-restart jika crash
& $nssm set VPointCareReverb AppRestartDelay 5000
& $nssm set VPointCareReverb AppExit Default Restart
```

---

### 7.5 Jalankan Services

```powershell
$nssm = "C:\laragon\bin\nssm\nssm.exe"

& $nssm start VPointCareQueue
& $nssm start VPointCareReverb
```

---

### 7.6 Verifikasi Service Berjalan

```powershell
# Cek status
Get-Service VPointCareQueue
Get-Service VPointCareReverb

# Atau via Services panel Windows:
# Tekan Win+R - ketik services.msc - cari "VPoint Care"
```

Kedua service harusnya terlihat statusnya **Running** dan Startup Type **Automatic**.

---

### 7.7 Perintah Manajemen Service

```powershell
$nssm = "C:\laragon\bin\nssm\nssm.exe"

# Stop service
& $nssm stop VPointCareQueue
& $nssm stop VPointCareReverb

# Restart service
& $nssm restart VPointCareQueue
& $nssm restart VPointCareReverb

# Edit konfigurasi (membuka GUI NSSM)
& $nssm edit VPointCareQueue
& $nssm edit VPointCareReverb

# Hapus service (jika perlu reinstall)
& $nssm stop VPointCareQueue; & $nssm remove VPointCareQueue confirm
& $nssm stop VPointCareReverb; & $nssm remove VPointCareReverb confirm
```

---

### 7.8 Apache/Laragon (Web Server)

Apache sudah dikelola oleh Laragon sendiri sebagai Windows Service.
Pastikan di **Laragon - Preferences - General - Run On Startup** dicentang
Sehingga Apache otomatis jalan saat Windows boot.

---

## TAHAP 8 - Verifikasi Akhir

Buka browser ke `https://care.vpoint.my.id/admin` dan pastikan:

- [ ] Halaman login muncul tanpa error
- [ ] Login berhasil dengan akun admin (dari seeder)
- [ ] Sidebar navigasi tampil dengan benar
- [ ] Upload gambar berfungsi (storage symlink OK)
- [ ] DataTable Pengguna, HakAkses bisa dibuka
- [ ] Ganti bahasa ID/EN berfungsi
- [ ] Log Data menampilkan data dari DB
- [ ] Notifikasi realtime muncul (WebSocket port 7060 aktif)
- [ ] Di `services.msc` kedua service VPoint Care statusnya **Running**

---

## Struktur Folder Akhir

```
C:\laragon\
|-- bin\
|   `-- nssm\
|       `-- nssm.exe              <- Service manager
|
|-- care_apps\                    <- Seluruh source Laravel
|   |-- app\
|   |-- bootstrap\
|   |-- config\
|   |-- database\
|   |-- public\                   <- TIDAK digunakan Apache langsung
|   |-- resources\
|   |-- routes\
|   |-- storage\
|   |   `-- logs\
|   |       |-- queue-stdout.log
|   |       |-- queue-stderr.log
|   |       |-- reverb-stdout.log
|   |       `-- reverb-stderr.log
|   |-- vendor\
|   `-- .env                      <- Production env
|
`-- www\                          <- DocumentRoot Apache (root langsung)
    |-- index.php                 <- Path diubah ke care_apps
    |-- .htaccess
    |-- storage\                  <- Symlink ke care_apps/storage/app/public
    `-- build\                    <- Hasil npm run build
```

---

## Perintah Ringkas - Deploy Baru

```powershell
# === SALIN FILE ===
robocopy "D:\GIT VPOINT\2026-WACS\src" "C:\laragon\care_apps" /E /XD ".git" "node_modules" /XF ".env"
robocopy "D:\GIT VPOINT\2026-WACS\src\public" "C:\laragon\www" /E

# === SETUP ===
cd C:\laragon\care_apps
composer install --optimize-autoloader --no-dev
php artisan key:generate
# Edit index.php di C:\laragon\www (Tahap 4.3)
php artisan storage:link
icacls "C:\laragon\care_apps\storage" /grant "Everyone:(OI)(CI)F"
icacls "C:\laragon\care_apps\bootstrap\cache" /grant "Everyone:(OI)(CI)F"
npm install
npm run build
robocopy "C:\laragon\care_apps\public\build" "C:\laragon\www\build" /E
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components

# === DATABASE ===
php artisan migrate --force
php artisan db:seed --force

# === INSTALL WINDOWS SERVICES (jalankan sekali saja) ===
$nssm = "C:\laragon\bin\nssm\nssm.exe"
$php  = "C:\laragon\bin\php\php-8.5.5-Win32-vs17-x64\php.exe"   # sesuaikan
$dir  = "C:\laragon\care_apps"

& $nssm install VPointCareQueue $php
& $nssm set VPointCareQueue AppParameters "$dir\artisan queue:work --tries=3 --timeout=120 --sleep=3"
& $nssm set VPointCareQueue AppDirectory $dir
& $nssm set VPointCareQueue Start SERVICE_AUTO_START
& $nssm set VPointCareQueue AppNoConsole 1
& $nssm set VPointCareQueue AppStdout "$dir\storage\logs\queue-stdout.log"
& $nssm set VPointCareQueue AppStderr "$dir\storage\logs\queue-stderr.log"
& $nssm set VPointCareQueue AppRestartDelay 5000

& $nssm install VPointCareReverb $php
& $nssm set VPointCareReverb AppParameters "$dir\artisan reverb:start --host=0.0.0.0 --port=7060"
& $nssm set VPointCareReverb AppDirectory $dir
& $nssm set VPointCareReverb Start SERVICE_AUTO_START
& $nssm set VPointCareReverb AppNoConsole 1
& $nssm set VPointCareReverb AppStdout "$dir\storage\logs\reverb-stdout.log"
& $nssm set VPointCareReverb AppStderr "$dir\storage\logs\reverb-stderr.log"
& $nssm set VPointCareReverb AppRestartDelay 5000

& $nssm start VPointCareQueue
& $nssm start VPointCareReverb
```

---

## Perintah Ringkas - Update Setelah Deploy

```powershell
# Saat ada update kode:
cd C:\laragon\care_apps

# Salin file terbaru
robocopy "D:\GIT VPOINT\2026-WACS\src" "C:\laragon\care_apps" /E /XD ".git" "node_modules" /XF ".env"

# Rebuild assets
npm run build
robocopy "C:\laragon\care_apps\public\build" "C:\laragon\www\build" /E /PURGE

# Composer jika ada perubahan
composer install --optimize-autoloader --no-dev

# Clear & re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
php artisan migrate --force

# Restart services agar pakai kode terbaru
$nssm = "C:\laragon\bin\nssm\nssm.exe"
& $nssm restart VPointCareQueue
& $nssm restart VPointCareReverb
```

