#!/usr/bin/env bash
# ==============================================================================
# Care Desk / WACS - Remote VPS Deployment Orchestrator
# ==============================================================================
set -euo pipefail

ARCHIVE_PATH="${1:-/tmp/care-desk-deploy.tar.gz}"
TARGET_DIR="${2:-/opt/care-desk}"

echo "============================================================"
echo " [VPS Deploy] Memulai Proses Deployment Care Desk (WACS)"
echo " Target Directory : ${TARGET_DIR}"
echo " Archive Source   : ${ARCHIVE_PATH}"
echo "============================================================"

# 1. Validasi Root Access
if [ "$(id -u)" -ne 0 ]; then
    echo "[ERROR] Script vps-deploy.sh harus dijalankan sebagai root!"
    exit 1
fi

if [ ! -f /etc/os-release ]; then
    echo "[ERROR] File /etc/os-release tidak ditemukan. Script ini hanya mendukung Ubuntu VPS!"
    exit 1
fi

# shellcheck disable=SC1091
. /etc/os-release
if [ "${ID:-}" != "ubuntu" ]; then
    echo "[ERROR] Target OS bukan Ubuntu (Terdeteksi: ${NAME:-Unknown OS})."
    exit 1
fi

# 2. Validasi Archive File
if [ ! -f "${ARCHIVE_PATH}" ]; then
    echo "[ERROR] File arsip deployment ${ARCHIVE_PATH} tidak ditemukan!"
    exit 1
fi

# 3. Buat direktori kerja jika belum ada
mkdir -p "${TARGET_DIR}"
mkdir -p "${TARGET_DIR}/backups"
mkdir -p "${TARGET_DIR}/src/storage/framework/cache"
mkdir -p "${TARGET_DIR}/src/storage/framework/sessions"
mkdir -p "${TARGET_DIR}/src/storage/framework/views"
mkdir -p "${TARGET_DIR}/src/storage/logs"
mkdir -p "${TARGET_DIR}/src/bootstrap/cache"

# Simpan .env.production lama jika ada agar tidak terhapus saat update
ENV_FILE="${TARGET_DIR}/deploy/production/.env.production"
ENV_BACKUP="/tmp/.env.production.bak.$$"
HAS_ENV=0
if [ -f "${ENV_FILE}" ]; then
    cp "${ENV_FILE}" "${ENV_BACKUP}"
    HAS_ENV=1
fi

# 4. Ekstrak archive secara aman (termasuk dotfiles seperti .env.production.example)
echo "[1/7] Mengekstrak source code baru..."
TMP_EXTRACT_DIR="/tmp/care-desk-extract-$$"
mkdir -p "${TMP_EXTRACT_DIR}"

if tar -tzf "${ARCHIVE_PATH}" | grep -Eq '(^/|(^|/)\.\.(/|$))'; then
    echo "[ERROR] Arsip deployment mengandung path traversal yang tidak aman!"
    exit 1
fi

tar -xzf "${ARCHIVE_PATH}" --no-same-owner --no-same-permissions -C "${TMP_EXTRACT_DIR}"

# Konfigurasi production selalu dikelola di VPS, bukan dari archive lokal.
rm -f "${TMP_EXTRACT_DIR}/deploy/production/.env.production"

# Copy dotfiles & directories
cp -a "${TMP_EXTRACT_DIR}/." "${TARGET_DIR}/"
rm -rf "${TMP_EXTRACT_DIR}"
rm -f "${ARCHIVE_PATH}"

# Kembalikan file .env.production yang di-backup jika sebelumnya sudah ada
if [ "${HAS_ENV}" -eq 1 ]; then
    cp "${ENV_BACKUP}" "${ENV_FILE}"
    rm -f "${ENV_BACKUP}"
fi

# Pastikan permission folder storage dan bootstrap cache dapat di-write
chmod -R 775 "${TARGET_DIR}/src/storage" "${TARGET_DIR}/src/bootstrap/cache"
chown -R root:www-data "${TARGET_DIR}/src/storage" "${TARGET_DIR}/src/bootstrap/cache" 2>/dev/null || true

cd "${TARGET_DIR}/deploy/production"

# 5. Penyiapan/Pembaruan .env.production
ENV_EXAMPLE="${TARGET_DIR}/deploy/production/.env.production.example"
echo "[2/7] Memvalidasi file konfigurasi .env.production..."

if [ ! -f "${ENV_FILE}" ]; then
    if [ -f "${ENV_EXAMPLE}" ]; then
        cp "${ENV_EXAMPLE}" "${ENV_FILE}"
    else
        echo "[ERROR] File template .env.production.example tidak ditemukan!"
        exit 1
    fi
fi

# Helper untuk membaca key tertentu dari .env.production secara aman
get_env_val() {
    local key="$1"
    local default_val="$2"
    local val
    val=$(grep "^${key}=" "${ENV_FILE}" | head -n1 | cut -d'=' -f2- | tr -d '\r"' || true)
    if [ -z "${val}" ]; then
        echo "${default_val}"
    else
        echo "${val}"
    fi
}

# Helper untuk menyimpan nilai hanya saat variabel belum terisi.
set_env_val() {
    local key="$1"
    local value="$2"

    if grep -q "^${key}=" "${ENV_FILE}"; then
        sed -i "s|^${key}=.*$|${key}=${value}|" "${ENV_FILE}"
    else
        printf '%s=%s\n' "${key}" "${value}" >> "${ENV_FILE}"
    fi
}

has_env_val() {
    local key="$1"
    grep -q "^${key}=[^[:space:]].*$" "${ENV_FILE}"
}

ensure_env_val() {
    local key="$1"
    local value="$2"

    if ! has_env_val "${key}"; then
        set_env_val "${key}" "${value}"
    fi
}

generate_random_secret() {
    openssl rand -hex "$1"
}

ensure_generated_env_val() {
    local key="$1"
    local prefix="$2"
    local length="$3"
    local suffix="$4"

    if ! has_env_val "${key}"; then
        set_env_val "${key}" "${prefix}$(generate_random_secret "${length}")${suffix}"
    fi
}

ensure_generated_env_val "DB_PASSWORD" "Desk_" 6 "!#2026"
ensure_generated_env_val "REDIS_PASSWORD" "Redis_" 8 ""
ensure_generated_env_val "WAHA_API_KEY" "waha_api_" 16 ""
ensure_generated_env_val "WAHA_DASHBOARD_PASSWORD" "Waha_" 6 "!2026"
ensure_generated_env_val "WHATSAPP_SWAGGER_PASSWORD" "Waha_" 6 "!2026"
ensure_generated_env_val "WAHA_WEBHOOK_TOKEN" "wh_token_" 16 ""
ensure_generated_env_val "WAHA_WEBHOOK_HMAC_KEY" "wh_hmac_" 16 ""
ensure_env_val "REVERB_APP_ID" "desk-app"
ensure_generated_env_val "REVERB_APP_KEY" "" 10 ""
ensure_generated_env_val "REVERB_APP_SECRET" "" 16 ""
ensure_env_val "VITE_REVERB_APP_KEY" "$(get_env_val "REVERB_APP_KEY" "")"
ensure_env_val "DB_DATABASE" "DBCareDesk"
ensure_env_val "APP_PORT" "8080"
ensure_env_val "WAHA_PORT" "3000"
ensure_env_val "REVERB_PORT" "7060"

if ! has_env_val "APP_KEY"; then
    set_env_val "APP_KEY" "base64:$(openssl rand -base64 32 | tr -d '\r\n')"
fi

DB_PASSWORD_VAL=$(get_env_val "DB_PASSWORD" "")
DB_DATABASE_VAL=$(get_env_val "DB_DATABASE" "DBCareDesk")
APP_PORT_VAL=$(get_env_val "APP_PORT" "8080")
WAHA_PORT_VAL=$(get_env_val "WAHA_PORT" "3000")
REVERB_PORT_VAL=$(get_env_val "REVERB_PORT" "7060")

# 6. Build Docker Container Stack
echo "[3/7] Mem-build Docker images..."
docker compose --env-file .env.production build

# 7. Jalankan Core Database & Redis, lalu Tunggu Hingga Sehat
echo "[4/7] Menjalankan core database & redis services..."
docker compose --env-file .env.production up -d sqlserver redis

echo "[INFO] Menunggu database (SQL Server) & Redis agar siap menerima koneksi..."
max_retry=30
counter=0
db_ready=0
redis_ready=0

while [ $counter -lt $max_retry ]; do
    if [ $db_ready -eq 0 ]; then
        status_db=$(docker inspect -f '{{.State.Health.Status}}' "$(docker compose --env-file .env.production ps -q sqlserver)" 2>/dev/null || echo "starting")
        if [ "${status_db}" = "healthy" ]; then
            echo "[OK] SQL Server Database Container berstatus HEALTHY."
            db_ready=1
        fi
    fi

    if [ $redis_ready -eq 0 ]; then
        status_redis=$(docker inspect -f '{{.State.Health.Status}}' "$(docker compose --env-file .env.production ps -q redis)" 2>/dev/null || echo "starting")
        if [ "${status_redis}" = "healthy" ]; then
            echo "[OK] Redis Container berstatus HEALTHY."
            redis_ready=1
        fi
    fi

    if [ $db_ready -eq 1 ] && [ $redis_ready -eq 1 ]; then
        break
    fi

    counter=$((counter + 1))
    sleep 2
done

if [ $db_ready -eq 0 ] || [ $redis_ready -eq 0 ]; then
    echo "[ERROR] Core services (SQL Server / Redis) tidak berhasil masuk ke kondisi HEALTHY dalam batas waktu!"
    exit 1
fi

# 8. Manajemen Database SQL Server: Pembuatan DB & Backup Otomatis
echo "[5/7] Memeriksa keberadaan database [${DB_DATABASE_VAL}] di SQL Server..."

# Query SQL Server via sqlcmd langsung tanpa subshell sh -c yang merusak quoting
run_sqlcmd() {
    local query="$1"
    docker compose --env-file .env.production exec -T sqlserver \
        /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "${DB_PASSWORD_VAL}" -C -b -h -1 -W -Q "${query}"
}

if ! [[ "${DB_DATABASE_VAL}" =~ ^[A-Za-z0-9_]+$ ]]; then
    echo "[ERROR] DB_DATABASE hanya boleh berisi huruf, angka, atau underscore untuk keamanan query SQL Server."
    exit 1
fi

BACKUP_DATABASE_NAME=$(printf '%s' "${DB_DATABASE_VAL}" | LC_ALL=C tr -cs 'A-Za-z0-9._-' '_')
DB_EXISTS=$(run_sqlcmd "SET NOCOUNT ON; SELECT CASE WHEN DB_ID(N'${DB_DATABASE_VAL}') IS NULL THEN '0' ELSE '1' END;" | tr -d '\r\n ' || echo "")

if [ "${DB_EXISTS}" = "1" ]; then
    echo "[INFO] Database [${DB_DATABASE_VAL}] sudah terdaftar di SQL Server."

    # Backup otomatis sebelum migrasi
    BACKUP_DIR="/var/opt/mssql/backups"
    BACKUP_FILE="${BACKUP_DIR}/${BACKUP_DATABASE_NAME}_$(date +%Y%m%d_%H%M%S).bak"
    docker compose --env-file .env.production exec -T sqlserver mkdir -p "${BACKUP_DIR}"
    echo "[INFO] Membuat restore-point backup database: ${BACKUP_FILE}..."
    run_sqlcmd "BACKUP DATABASE [${DB_DATABASE_VAL}] TO DISK = '${BACKUP_FILE}' WITH FORMAT, INIT"
    docker compose --env-file .env.production exec -T sqlserver test -s "${BACKUP_FILE}"
    echo "[OK] Backup SQL Server berhasil dibuat."
elif [ "${DB_EXISTS}" = "0" ]; then
    echo "[INFO] Database [${DB_DATABASE_VAL}] belum terdaftar. Membuat database baru..."
    run_sqlcmd "CREATE DATABASE [${DB_DATABASE_VAL}]"
    echo "[OK] Database [${DB_DATABASE_VAL}] berhasil dibuat!"
else
    echo "[ERROR] Tidak dapat memverifikasi keberadaan database [${DB_DATABASE_VAL}]."
    exit 1
fi

# 9. Nyalakan Service App Sebelum Melakukan Exec
echo "[6/7] Menjalankan service app dan menginisialisasi environment..."
docker compose --env-file .env.production up -d app

docker compose --env-file .env.production exec -T app sh -lc "
    composer install --no-dev --optimize-autoloader && \
    php artisan storage:link && \
    npm install --ignore-scripts && \
    npm run build && \
    php artisan optimize:clear && \
    php artisan migrate --force && \
    php artisan optimize
"

# 10. Start Sisa Stack Container & Restart Services
echo "[7/7] Menjalankan seluruh runtime stack service..."
docker compose --env-file .env.production up -d --remove-orphans

echo "[INFO] Merekam ulang seluruh worker dan service untuk memuat source code terbaru..."
docker compose --env-file .env.production restart app web reverb queue-webhooks queue-ai queue-broadcasts scheduler

echo "============================================================"
echo " [SUCCESS] Proses Deployment Care Desk Selesai!"
echo "============================================================"
docker compose --env-file .env.production ps
echo ""

echo "============================================================"
echo " Aplikasi Care Desk (WACS) sukses ter-deploy di Docker VPS!"
echo " - URL Admin Panel  : http://<IP_VPS>:${APP_PORT_VAL}/admin"
echo " - WAHA Dashboard   : http://<IP_VPS>:${WAHA_PORT_VAL}"
echo " - Reverb Websocket : http://<IP_VPS>:${REVERB_PORT_VAL}"
echo "============================================================"
