# 🐳 Rencana Deployment VPoint Care (menggunakan Docker)

Docker adalah solusi *silver bullet* (sapu jagat) untuk kasus server lama seperti Ubuntu 20.04. 
Dengan Docker, kita akan membungkus **PHP 8.5, Nginx, Driver SQL Server, Queue Worker, dan Reverb** ke dalam "kotak kontainer" yang berjalan mandiri di dalam server Anda. 

Docker **TIDAK AKAN** menyentuh, merusak, atau mengganggu instalasi Apache & PHP 7.4 Anda yang sudah ada!

---

## TAHAP 1 — Pastikan Docker Terinstal di Ubuntu 20.04 Anda

Jalankan perintah ini di terminal Ubuntu Anda untuk mengecek atau menginstal Docker:

```bash
# Cek apakah docker sudah ada
docker -v

# Jika tulisan 'command not found', jalankan perintah instalasi otomatis ini:
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose plugin
sudo apt-get install docker-compose-plugin -y
```

---

## TAHAP 2 — Siapkan Kodingan Aplikasi

Pastikan seluruh kodingan VPoint Care Anda (dari Windows) sudah di-copy / ditaruh di dalam server Ubuntu Anda, misalnya di folder `/home/it/GIT_VPOINT/2026-vpoint-care`.

```bash
# Pindah ke dalam direktori aplikasi
cd /home/it/GIT_VPOINT/2026-vpoint-care
```

*(Semua langkah di bawah ini dilakukan di dalam folder `/home/it/GIT_VPOINT/2026-vpoint-care` tersebut)*

---

## TAHAP 3 — Buat File-File Spesial Docker

Kita butuh 3 file baru untuk "mengajari" Docker cara menghidupkan Laravel Anda. Buat file-file ini di dalam folder `/home/it/GIT_VPOINT/2026-vpoint-care`:

### 1. Buat file `Dockerfile`

Ini adalah resep masakan untuk menginstal PHP 8.5 dan Driver ODBC Microsoft. (Buat file bernama `Dockerfile` tanpa ekstensi apa pun).

```dockerfile
FROM php:8.5-fpm

# Install dependensi sistem dasar
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    gnupg2 \
    apt-transport-https

# Tambahkan repository Microsoft (Debian 12 - bawaan php:8.5-fpm)
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list

# Install Driver SQL Server (ODBC) & Ekstensi PHP
RUN apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 unixodbc-dev \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && docker-php-ext-install pdo mbstring exif pcntl bcmath gd zip intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js (untuk keperluan build Vite Laravel)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Set lokasi kerja default
WORKDIR /var/www/html
```

### 2. Buat konfigurasi Nginx (`docker/nginx/default.conf`)

Buat dulu foldernya: `mkdir -p docker/nginx`, lalu buat file `default.conf` di dalamnya:

```nginx
server {
    # Nginx di dalam container mendengar di port 80 (Akan dipetakan ke 82 di luar)
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Proxy untuk Reverb WebSocket
    location /app/ {
        proxy_pass http://reverb:7060;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
}
```

### 3. Buat file `docker-compose.yml`

Ini adalah "Mandor" yang akan menyalakan semua pekerja (App, Nginx, Queue, dan Reverb) secara bersamaan.

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: vpointcare-php85
    restart: unless-stopped
    volumes:
      - ./:/var/www/html
    networks:
      - vpoint-net
    extra_hosts:
      - "host.docker.internal:host-gateway"

  web:
    image: nginx:alpine
    restart: unless-stopped
    ports:
      # PORT LUAR 82 -> PORT DALAM 80
      - "82:80" 
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - vpoint-net
    depends_on:
      - app

  queue:
    image: vpointcare-php85
    restart: unless-stopped
    command: php artisan queue:work --tries=3 --timeout=120 --sleep=3
    volumes:
      - ./:/var/www/html
    networks:
      - vpoint-net
    extra_hosts:
      - "host.docker.internal:host-gateway"
    depends_on:
      - app

  reverb:
    image: vpointcare-php85
    restart: unless-stopped
    command: php artisan reverb:start --host=0.0.0.0 --port=7060
    ports:
      # Ekspos port 7060 keluar untuk dikontak browser
      - "7060:7060"
    volumes:
      - ./:/var/www/html
    networks:
      - vpoint-net
    extra_hosts:
      - "host.docker.internal:host-gateway"
    depends_on:
      - app

networks:
  vpoint-net:
    driver: bridge
```

---

## TAHAP 4 — Sesuaikan File `.env` VPoint Care

Pastikan file `.env` Laravel Anda diubah menjadi seperti ini:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=http://care.vpoint.my.id

# DB SQL Server (Gunakan IP Publik, IP LAN Host, atau host.docker.internal jika DB ada di Ubuntu yang sama)
DB_CONNECTION=sqlsrv
DB_HOST=host.docker.internal
DB_DATABASE=DBVPointCare
DB_USERNAME=sa
DB_PASSWORD=

# REVERB
REVERB_HOST=0.0.0.0
REVERB_PORT=7060
REVERB_SCHEME=http

# VITE
VITE_REVERB_HOST="care.vpoint.my.id"
VITE_REVERB_PORT=7060
VITE_REVERB_SCHEME=http
```

> **CATATAN PENTING VITE:** Karena kita memakai Docker, Browser Anda (Vite) harus menembak langsung ke port `7060` (yang sudah diekspos di `docker-compose.yml`), bukan port `82`.

---

## TAHAP 5 — Eksekusi! 🚀

Jalankan perintah sakti ini (di dalam folder `/home/it/GIT_VPOINT/2026-vpoint-care`):

```bash
# 1. Rakit container (Ini butuh waktu beberapa menit untuk mengunduh PHP & SQL Driver)
sudo docker compose build

# 2. Nyalakan semuanya di latar belakang
sudo docker compose up -d

# 3. Masuk ke dalam container untuk install Vendor (Composer) dan Vite
sudo docker compose exec app composer install --optimize-autoloader --no-dev
sudo docker compose exec app npm install
sudo docker compose exec app npm run build
sudo docker compose exec app php artisan storage:link

# 4. Beri hak akses agar Docker diizinkan membaca file di Ubuntu Anda
sudo chmod +x /home/it
sudo chmod -R 755 /home/it/GIT_VPOINT/2026-vpoint-care
sudo chmod -R 777 /home/it/GIT_VPOINT/2026-vpoint-care/storage /home/it/GIT_VPOINT/2026-vpoint-care/bootstrap/cache

# 5. Clear Cache & Jalankan Migrasi/Seeder (Jika Perlu)
sudo docker compose exec app php artisan optimize:clear
# sudo docker compose exec app php artisan migrate
```

Selesai! Sekarang aplikasi Anda sudah menyala di:
**http://care.vpoint.my.id:82**

Dan Queue Worker serta Reverb sudah berjalan aman di dalam Docker secara otomatis!
