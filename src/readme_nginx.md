# 🚀 Panduan Deployment VPoint Care (Ubuntu + Nginx Manual)

Panduan ini dikhususkan untuk mendeploy VPoint Care pada OS Linux **Ubuntu 20.04** yang sudah berjalan Nginx (di mana Nginx juga menjalankan aplikasi lain di port yang berbeda). Konfigurasi ini murni via Terminal (tanpa Web Panel).

> **Stack Utama:** Ubuntu · Nginx · PHP 8.5-FPM · SQL Server (Remote) · Supervisor (Queue & Reverb)

---

## TAHAP 1 — Persiapan PHP 8.5

Karena kita menggunakan Laravel versi modern, kita butuh PHP 8.5.

```bash
# Tambahkan repository PHP Ondrej (jika belum ada)
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.5 beserta ekstensi dasar
sudo apt install php8.5-fpm php8.5-cli php8.5-common php8.5-curl php8.5-zip php8.5-mbstring php8.5-xml php8.5-bcmath php8.5-gd php8.5-dev php-pear unixodbc-dev -y
```

> **Perhatian:** Paket `php8.5-dev`, `php-pear`, dan `unixodbc-dev` sangat krusial untuk kompilasi driver SQL Server di langkah berikutnya. Tanpa ini, instalasi `sqlsrv` akan gagal.

---

## TAHAP 2 — Install Driver SQL Server (Paling Krusial)

Secara default, Linux tidak mengenali Microsoft SQL Server. Kita harus menginstal ODBC Driver resmi dari Microsoft lalu menyematkannya ke dalam PHP.

### 2.1 Tambahkan Repository Microsoft (Khusus Ubuntu 20.04)

```bash
# Unduh kunci repository
curl https://packages.microsoft.com/keys/microsoft.asc | sudo tee /etc/apt/trusted.gpg.d/microsoft.asc

# Tambahkan source list untuk Ubuntu 20.04
curl https://packages.microsoft.com/config/ubuntu/20.04/prod.list | sudo tee /etc/apt/sources.list.d/mssql-release.list
sudo apt-get update
```

### 2.2 Install ODBC Driver 18

```bash
sudo ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18
echo 'export PATH="$PATH:/opt/mssql-tools18/bin"' >> ~/.bashrc
source ~/.bashrc
```

### 2.3 Compile Ekstensi PHP SQLSRV (PHP 8.5)

```bash
# Install ekstensi via PECL
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv

# Daftarkan ekstensi ke PHP CLI (Terminal) dan PHP-FPM (Web)
printf "; priority=20\nextension=sqlsrv.so\n" | sudo tee /etc/php/8.5/mods-available/sqlsrv.ini
printf "; priority=20\nextension=pdo_sqlsrv.so\n" | sudo tee /etc/php/8.5/mods-available/pdo_sqlsrv.ini

sudo phpenmod -v 8.5 sqlsrv pdo_sqlsrv

# Restart layanan PHP-FPM agar ekstensi terbaca
sudo systemctl restart php8.5-fpm
```

---

## TAHAP 3 — Setup Source Code Aplikasi

### 3.1 Pindahkan File ke Server

Taruh seluruh *source code* VPoint Care Anda (kecuali `node_modules` & `.env`) ke folder `/var/www/vpointcare`.

```bash
sudo mkdir -p /var/www/vpointcare
# (Pindahkan/Clone file Laravel Anda ke dalam folder ini...)

# Masuk ke direktori
cd /var/www/vpointcare
```

### 3.2 Konfigurasi `.env`

```bash
# Copy env template
cp .env.example .env
nano .env
```

**Bagian Penting di `.env` yang Harus Disesuaikan:**
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://care.vpoint.my.id

# Konfigurasi Database Remote
DB_CONNECTION=sqlsrv
DB_HOST=26.245.185.82\SQL2019
DB_PORT=
DB_DATABASE=DBVPointCare
DB_USERNAME=sa
DB_PASSWORD=Sg1
DB_TRUST_SERVER_CERTIFICATE=true

# --- REVERB WEBSOCKET ---
# Reverb jalan di background Ubuntu menggunakan Port 7060 (Tidak bentrok dengan Nginx)
REVERB_APP_ID=612204
REVERB_APP_KEY=g2zwnuebwgen7zqywz51
REVERB_APP_SECRET=9nkdnjml6pxrnxtrm6jm
REVERB_HOST=127.0.0.1
REVERB_PORT=7060
REVERB_SCHEME=http

# VITE variables digunakan oleh Browser Client. 
# Harus mengarah ke Domain Publik dan Port yang digunakan Nginx (82).
VITE_REVERB_HOST="care.vpoint.my.id"
VITE_REVERB_PORT=82
VITE_REVERB_SCHEME=http
```

### 3.3 Build Aplikasi

```bash
# Install package PHP
composer install --optimize-autoloader --no-dev

# Generate Key & Symlink Storage
php artisan key:generate
php artisan storage:link

# Install Node & Vite (Pastikan Node.js sudah terpasang di Ubuntu)
npm install
npm run build

# Cache konfigurasi (Optimasi)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
```

### 3.4 Izin Akses Direktori (Permissions)

Nginx berjalan atas nama *user* `www-data`. Beri wewenang tulis pada folder yang membutuhkan:

```bash
sudo chown -R www-data:www-data /var/www/vpointcare/storage /var/www/vpointcare/bootstrap/cache
sudo chmod -R 775 /var/www/vpointcare/storage /var/www/vpointcare/bootstrap/cache
```

---

## TAHAP 4 — Konfigurasi Nginx & Reverse Proxy

Kita buat file *Virtual Host* khusus untuk VPoint Care. (Tidak akan mengganggu aplikasi lain di Nginx).

```bash
sudo nano /etc/nginx/sites-available/vpointcare
```

Isi dengan konfigurasi komprehensif berikut:

```nginx
server {
    # Nginx mendengarkan port 82 untuk domain ini
    listen 82;
    server_name care.vpoint.my.id;
    root /var/www/vpointcare/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    # 1. Routing Normal (Request halaman web diteruskan ke index.php Laravel)
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 2. Reverse Proxy untuk WebSocket Reverb (Sangat Penting)
    # Reverb biasanya menggunakan endpoint /app/ saat handshake dari Vite
    location /app/ {
        proxy_pass             http://127.0.0.1:7060; # Lempar ke port 7060 lokal
        proxy_http_version     1.1;
        proxy_set_header       Upgrade $http_upgrade;
        proxy_set_header       Connection "upgrade";
        proxy_set_header       Host $host;
        proxy_set_header       X-Real-IP $remote_addr;
        proxy_set_header       X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header       X-Forwarded-Proto $scheme;
        proxy_read_timeout     60;
        proxy_connect_timeout  60;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # 3. Lemparan PHP ke PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Keamanan: Blokir akses file rahasia (.env, .git)
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Simpan file, lalu aktifkan *Virtual Host* dan *restart* Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/vpointcare /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

> **Info:**
> Karena Anda menggunakan port 82 (bukan port standar web 80/443), untuk mengakses aplikasi ini di browser Anda harus mengetikkan lengkap beserta port-nya: `http://care.vpoint.my.id:82`

---

## TAHAP 5 — Setup Supervisor (Menjaga Background Jobs)

Supervisor di Linux fungsinya sama dengan NSSM di Windows, yaitu memastikan Worker dan Reverb jalan terus 24 jam.

```bash
sudo apt install supervisor -y
```

### 5.1 Buat Konfigurasi Queue Worker

```bash
sudo nano /etc/supervisor/conf.d/vpoint-queue.conf
```
Isi dengan:
```ini
[program:vpoint-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php8.5 /var/www/vpointcare/artisan queue:work --tries=3 --timeout=120 --sleep=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/vpointcare/storage/logs/worker.log
```

### 5.2 Buat Konfigurasi Reverb WebSocket

```bash
sudo nano /etc/supervisor/conf.d/vpoint-reverb.conf
```
Isi dengan:
```ini
[program:vpoint-reverb]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php8.5 /var/www/vpointcare/artisan reverb:start --host=127.0.0.1 --port=7060
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/vpointcare/storage/logs/reverb.log
```

### 5.3 Hidupkan Daemons

Beritahu Supervisor bahwa ada file konfigurasi baru, lalu jalankan:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

Cek apakah keduanya sudah berjalan berstatus **RUNNING**:
```bash
sudo supervisorctl status
```

---

## 🏆 SELESAI! 

Aplikasi VPoint Care kini sudah berjalan stabil di Ubuntu.
*   **Nginx** menyortir trafik dari internet. HTTP diberikan ke PHP-FPM, sedangkan WebSockets `/app/` dibelokkan (*proxy*) secara diam-diam ke port lokal `7060`.
*   Ekstensi **SQLSRV ODBC** bertugas menyeberangkan *query* PHP ke Server Database 26.245...
*   **Supervisor** terus memantau *Queue* dan *Reverb* agar tidak pernah tertidur apalagi mati.
