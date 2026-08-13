#!/usr/bin/env bash
set -e

# ==============================================================================
# VPoint Care / WACS - Remote VPS Deployment Orchestrator
# Script ini dieksekusi di server VPS Ubuntu untuk mengekstrak source code,
# mengelola .env.production, mem-build Docker container, serta menjalankan
# migrasi dan optimasi Laravel.
# ==============================================================================

ARCHIVE_PATH="${1:-/tmp/vpoint-care-deploy.tar.gz}"
TARGET_DIR="${2:-/opt/vpoint-care}"

echo "============================================================"
echo " [VPS Deploy] Memulai Proses Deployment VPoint Care (WACS)"
echo " Target Directory : ${TARGET_DIR}"
echo " Archive Source   : ${ARCHIVE_PATH}"
echo "============================================================"

if [ "$(id -u)" -ne 0 ]; then
    SUDO="sudo"
else
    SUDO=""
fi

if [ ! -f "${ARCHIVE_PATH}" ]; then
    echo "[ERROR] Archive file ${ARCHIVE_PATH} tidak ditemukan!"
    exit 1
fi

# 1. Buat direktori target jika belum ada
$SUDO mkdir -p "${TARGET_DIR}"
$SUDO mkdir -p "${TARGET_DIR}/deploy/production"
$SUDO mkdir -p "${TARGET_DIR}/src/storage/framework/cache"
$SUDO mkdir -p "${TARGET_DIR}/src/storage/framework/sessions"
$SUDO mkdir -p "${TARGET_DIR}/src/storage/framework/views"
$SUDO mkdir -p "${TARGET_DIR}/src/storage/logs"
$SUDO mkdir -p "${TARGET_DIR}/src/bootstrap/cache"

# 2. Ekstrak archive
echo "[1/6] Mengekstrak source code ke ${TARGET_DIR}..."
TMP_EXTRACT_DIR="/tmp/vpoint-care-extract-$$"
mkdir -p "${TMP_EXTRACT_DIR}"
tar -xzf "${ARCHIVE_PATH}" -C "${TMP_EXTRACT_DIR}"

# Salin source files
$SUDO cp -R "${TMP_EXTRACT_DIR}"/* "${TARGET_DIR}/"
rm -rf "${TMP_EXTRACT_DIR}"
rm -f "${ARCHIVE_PATH}"

# Fix permission dasar
$SUDO chmod -R 755 "${TARGET_DIR}"

cd "${TARGET_DIR}/deploy/production"

# 3. Penyiapan .env.production (jika belum ada)
ENV_FILE="${TARGET_DIR}/deploy/production/.env.production"
ENV_EXAMPLE="${TARGET_DIR}/deploy/production/.env.production.example"

if [ ! -f "${ENV_FILE}" ]; then
    echo "[2/6] Mempersiapkan file .env.production baru dari template..."
    if [ -f "${ENV_EXAMPLE}" ]; then
        cp "${ENV_EXAMPLE}" "${ENV_FILE}"
        
        # Generate password acak yang aman
        GEN_DB_PASS="VPoint_$(openssl rand -hex 6)!#2026"
        GEN_REDIS_PASS="Redis_$(openssl rand -hex 8)"
        GEN_WAHA_KEY="waha_api_$(openssl rand -hex 16)"
        GEN_WAHA_PASS="Waha_$(openssl rand -hex 6)!2026"
        GEN_WEBHOOK_TOKEN="wh_token_$(openssl rand -hex 16)"
        GEN_WEBHOOK_HMAC="wh_hmac_$(openssl rand -hex 16)"
        GEN_REVERB_KEY="$(openssl rand -hex 10)"
        GEN_REVERB_SECRET="$(openssl rand -hex 16)"

        # Replace placeholders di .env.production
        sed -i "s|DB_PASSWORD=|DB_PASSWORD=${GEN_DB_PASS}|g" "${ENV_FILE}"
        sed -i "s|REDIS_PASSWORD=|REDIS_PASSWORD=${GEN_REDIS_PASS}|g" "${ENV_FILE}"
        sed -i "s|WAHA_API_KEY=|WAHA_API_KEY=${GEN_WAHA_KEY}|g" "${ENV_FILE}"
        sed -i "s|WAHA_DASHBOARD_PASSWORD=|WAHA_DASHBOARD_PASSWORD=${GEN_WAHA_PASS}|g" "${ENV_FILE}"
        sed -i "s|WHATSAPP_SWAGGER_PASSWORD=|WHATSAPP_SWAGGER_PASSWORD=${GEN_WAHA_PASS}|g" "${ENV_FILE}"
        sed -i "s|WAHA_WEBHOOK_TOKEN=|WAHA_WEBHOOK_TOKEN=${GEN_WEBHOOK_TOKEN}|g" "${ENV_FILE}"
        sed -i "s|WAHA_WEBHOOK_HMAC_KEY=|WAHA_WEBHOOK_HMAC_KEY=${GEN_WEBHOOK_HMAC}|g" "${ENV_FILE}"
        sed -i "s|REVERB_APP_ID=|REVERB_APP_ID=vpoint-app|g" "${ENV_FILE}"
        sed -i "s|REVERB_APP_KEY=|REVERB_APP_KEY=${GEN_REVERB_KEY}|g" "${ENV_FILE}"
        sed -i "s|REVERB_APP_SECRET=|REVERB_APP_SECRET=${GEN_REVERB_SECRET}|g" "${ENV_FILE}"
        sed -i "s|VITE_REVERB_APP_KEY=|VITE_REVERB_APP_KEY=${GEN_REVERB_KEY}|g" "${ENV_FILE}"
        
        echo "[INFO] Generated default secrets for SQL Server, Redis, WAHA, and Reverb."
    else
        echo "[WARN] .env.production.example tidak ditemukan. Pastikan .env.production ada."
    fi
else
    echo "[2/6] Menggunakan file .env.production yang sudah ada."
fi

# 4. Build Docker Images
echo "[3/6] Mem-build Docker images..."
$SUDO docker compose --env-file .env.production build

# 5. Jalankan Stack Service Core
echo "[4/6] Menjalankan container Docker Stack..."
$SUDO docker compose --env-file .env.production up -d --remove-orphans

# Menunggu database SQL Server & Redis siap
echo "[INFO] Menunggu database & redis siap (10 detik)..."
sleep 10

# 6. Install Dependensi & Asset di Container App
echo "[5/6] Memasang Composer packages, NPM build, dan optimasi Laravel..."
$SUDO docker compose --env-file .env.production exec -T app sh -lc "
    composer install --no-dev --optimize-autoloader && \
    php artisan key:generate --force && \
    npm run build && \
    php artisan optimize:clear && \
    php artisan migrate --force && \
    php artisan optimize
"

# 7. Restart Services & Workers
echo "[6/6] Merekam ulang seluruh worker dan service..."
$SUDO docker compose --env-file .env.production restart app web reverb queue-webhooks queue-ai queue-broadcasts scheduler

echo "============================================================"
echo " [SUCCESS] Deployment VPoint Care (WACS) Berhasil!"
echo "============================================================"
$SUDO docker compose --env-file .env.production ps
echo ""
echo "Detail Akses:"
echo " - Admin Panel WACS  : http://<IP_VPS>:8080/admin"
echo " - WAHA Dashboard    : http://<IP_VPS>:3000"
echo " - Reverb Websocket  : http://<IP_VPS>:7060"
echo ""
echo "Lokasi Konfigurasi VPS: ${ENV_FILE}"
echo "Untuk mengubah Domain/SSL/API Key AI, edit file .env.production tersebut."
echo "============================================================"
