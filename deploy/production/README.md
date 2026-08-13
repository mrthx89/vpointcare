# Deployment Production VPoint Care (WACS) dengan Docker Compose

Dokumen ini menjelaskan tata cara pengoperasian stack deployment production VPoint Care secara terisolasi menggunakan Docker Compose. Stack ini mengintegrasikan seluruh dependency utama dalam satu lingkungan terkontrol:
- **WACS Web & App (Laravel 13 & PHP-FPM 8.5)**
- **Nginx Web Server**
- **Microsoft SQL Server Express 2022**
- **WAHA WhatsApp Gateway (Publik)**
- **Redis (Cache, Session, Queue, Broadcast)**
- **Queue Workers (Webhooks, AI, Broadcasts)**
- **Scheduler (Cron Job / Schedule Work)**
- **Laravel Reverb (Realtime WebSocket)**

---

## 0. Quick Start: Deployment Otomatis Satu Tombol Ke VPS Ubuntu

Gunakan script deployment otomatis dari mesin lokal (Windows atau Linux/macOS) untuk langsung menyiapkan VPS Ubuntu baru, mentransfer source code bersih, mengkonfigurasi environment, mem-build Docker, dan menjalankan seluruh stack.

### Cara Deploy dari Windows (PowerShell/CMD):
1. Jalankan file `deploy-to-vps.bat` atau panggil script PowerShell:
   ```powershell
   .\deploy-to-vps.ps1 -VpsHost "IP_VPS_ANDA" -VpsUser "root"
   ```
   *Script mendukung opsi `-VpsPort` dan `-SshKey` untuk SSH Key file.*
2. Ikuti prompt pengisian jika ada parameter yang belum ditentukan.
3. Script akan membungkus source, mengunggah ke VPS, menginstall Docker jika belum ada, mem-build image, memigrasi database, dan mengaktifkan aplikasi secara otomatis.

### Cara Deploy dari Linux/macOS/Git Bash:
1. Pastikan script executable:
   ```bash
   chmod +x deploy-to-vps.sh
   ```
2. Jalankan script Bash:
   ```bash
   ./deploy-to-vps.sh "IP_VPS_ANDA" "root"
   ```

*Catatan Keamanan: Script tidak pernah menyimpan password root/SSH di local storage atau git. Jika file `.env.production` di VPS belum ada, remote orchestrator akan menggenerate credentials dan password acak yang kuat untuk SQL Server, Redis, WAHA, dan Reverb secara aman.*

---

## 1. Prerequisites & Persiapan Environment

1. Pastikan **Docker Engine** (versi 24.0+) dan **Docker Compose** (v2.20+) sudah terinstal di server production.
2. Salin file contoh konfigurasi environment:
   ```bash
   cd deploy/production
   cp .env.production.example .env.production
   ```
3. Edit `.env.production` dan sesuaikan nilai rahasia (secrets):
   - `APP_KEY`: Generate dengan `docker compose --env-file .env.production run --rm app php artisan key:generate --show` sebelum startup pertama.
   - `DB_PASSWORD`: Password SA untuk SQL Server (minimal 8 karakter, kombinasi huruf besar, kecil, angka, simbol).
   - `REDIS_PASSWORD`: Password keamanan untuk Redis.
   - `WAHA_PUBLIC_URL`: URL publik WAHA untuk dashboard/API dan konfigurasi WAHA.
   - `WACS_WAHA_BASE_URL`: URL internal yang dipakai WACS untuk memanggil WAHA, biasanya `http://waha:3000`.
   - `WAHA_API_KEY`: Key autentikasi API WAHA.
   - `WAHA_DASHBOARD_USERNAME` dan `WAHA_DASHBOARD_PASSWORD`: Credential dashboard WAHA.
   - `WHATSAPP_SWAGGER_USERNAME` dan `WHATSAPP_SWAGGER_PASSWORD`: Credential Swagger WAHA.
   - `WAHA_WEBHOOK_TOKEN`: Token unik URL webhook WACS.
   - `WAHA_WEBHOOK_HMAC_KEY`: Secret key verifikasi HMAC webhook WAHA.
   - `WHATSAPP_HOOK_URL`: Base URL callback Nginx/WACS untuk menerima webhook WAHA (contoh: `http://web/webhooks/waha` atau `https://care.domain.com/webhooks/waha`).
   - Open AI / Provider Key (`OPENAI_API_KEY`, dll).

---

## 2. Langkah Deployment Pertama (Initial Setup)

### Step A: Build & Install Dependensi (Setup Profile)
Jalankan setup container untuk memasang dependensi PHP (`composer install --no-dev`) dan melakukan kompilasi frontend asset Vite (`npm run build`):
```bash
docker compose --env-file .env.production run --build --rm setup
```

### Step B: Jalankan Core Services (Database, Redis, WAHA)
Nyalakan container database, Redis, dan WAHA terlebih dahulu sampai statusnya sehat (`healthy`):
```bash
docker compose --env-file .env.production up -d sqlserver redis waha
```
Periksa health status:
```bash
docker compose --env-file .env.production ps
```

### Step C: Jalankan Database Migration (Operasi Manual Operator)
> **PENTING:** Pastikan SQL Server sudah *healthy* dan lakukan backup database jika memperbarui deployment existing.
Jalankan migrasi database ke SQL Server secara eksplisit:
```bash
docker compose --env-file .env.production run --rm app php artisan migrate --force
```

### Step D: Nyalakan Seluruh Service Production
Jalankan seluruh service aplikasi, web server, queue workers, scheduler, dan Reverb:
```bash
docker compose --env-file .env.production up -d
```

---

## 3. Verifikasi Operasional & Webhook

1. **Akses Dashboard Admin WACS:** Buka browser ke `http://<IP-SERVER>:<APP_PORT>/admin` (atau domain reverse proxy publik).
2. **Akses Dashboard WAHA:** Buka browser ke `https://<WAHA_PUBLIC_URL>` atau `http://<IP-SERVER>:<WAHA_PORT>` menggunakan credential dashboard yang dikonfigurasi.
3. **Uji Webhook & Realtime:**
   - Kirim pesan uji coba ke WhatsApp WAHA.
   - Periksa log webhook worker:
     ```bash
     docker compose --env-file .env.production logs -f queue-webhooks
     ```
   - Pastikan pesan masuk tampil secara realtime pada Inbox Admin WACS.

---

## 4. Perintah Operasional Harian

- **Melihat Status Service:**
  ```bash
  docker compose --env-file .env.production ps
  ```
- **Melihat Log Aplikasi & Worker:**
  ```bash
  docker compose --env-file .env.production logs -f app queue-webhooks queue-ai scheduler
  ```
- **Restart Queue Workers Setelah Update Code:**
  ```bash
  docker compose --env-file .env.production exec app php artisan queue:restart
  ```
- **Menghentikan Stack Tanpa Menghapus Data:**
  ```bash
  docker compose --env-file .env.production down
  ```
  *(DILARANG menggunakan flag `-v` karena akan menghapus volume database SQL Server dan sesi WAHA!)*

---

## 5. Prosedur Rollback

Jika terjadi hambatan setelah update:
1. Hentikan container production: `docker compose --env-file .env.production down`.
2. Kembalikan kode aplikasi/bind-mount ke commit/release yang stabil.
3. Jika migrasi database sempat dijalankan dan bermasalah, lakukan restore backup SQL Server dari restore point sebelum migrasi.
4. Jalankan kembali container: `docker compose --env-file .env.production up -d`.
