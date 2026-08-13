#!/usr/bin/env bash
set -e

# ==============================================================================
# VPoint Care (WACS) - Automated Ubuntu VPS Deployment Script (Bash)
# Usage: ./deploy-to-vps.sh [VPS_HOST] [VPS_USER] [VPS_PORT] [SSH_KEY_PATH] [REMOTE_DIR]
# ==============================================================================

VPS_HOST="${1}"
VPS_USER="${2:-root}"
VPS_PORT="${3:-22}"
SSH_KEY="${4:-}"
REMOTE_DIR="${5:-/opt/vpoint-care}"

echo "============================================================"
echo " VPoint Care (WACS) - Automated Ubuntu VPS Deployment"
echo "============================================================"

if [ -z "${VPS_HOST}" ]; then
    read -p "Masukkan IP Address / Domain VPS Ubuntu: " VPS_HOST
    if [ -z "${VPS_HOST}" ]; then
        echo "[ERROR] IP / Domain VPS tidak boleh kosong!"
        exit 1
    fi
fi

if [ -z "${2}" ]; then
    read -p "Masukkan Username SSH VPS [Default: root]: " USER_INPUT
    if [ -n "${USER_INPUT}" ]; then
        VPS_USER="${USER_INPUT}"
    fi
fi

SSH_OPTS=("-p" "${VPS_PORT}")
SCP_OPTS=("-P" "${VPS_PORT}")

if [ -n "${SSH_KEY}" ] && [ -f "${SSH_KEY}" ]; then
    SSH_OPTS+=("-i" "${SSH_KEY}")
    SCP_OPTS+=("-i" "${SSH_KEY}")
    echo "[INFO] Menggunakan SSH Key: ${SSH_KEY}"
else
    echo "[INFO] Menggunakan SSH standard (password diprompt otomatis oleh OpenSSH jika diperlukan)."
fi

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOCAL_SRC="${REPO_ROOT}/src"
TEMP_ARCHIVE="/tmp/vpoint-care-deploy-$$.tar.gz"

if [ ! -f "${LOCAL_SRC}/artisan" ]; then
    echo "[ERROR] Tidak dapat menemukan folder 'src/artisan'. Pastikan script dijalankan dari root repository."
    exit 1
fi

echo ""
echo "[1/4] Membuat arsip deployment bersih (tanpa node_modules & vendor)..."
cd "${REPO_ROOT}"
tar -czf "${TEMP_ARCHIVE}" \
    src/app \
    src/bootstrap \
    src/config \
    src/database \
    src/public \
    src/resources \
    src/routes \
    src/artisan \
    src/composer.json \
    src/composer.lock \
    src/package.json \
    src/package-lock.json \
    src/vite.config.js \
    src/Dockerfile \
    deploy/production

echo "[OK] Arsip berhasil dibuat: ${TEMP_ARCHIVE}"

REMOTE_ARCHIVE="/tmp/vpoint-care-deploy.tar.gz"
echo ""
echo "[2/4] Mengunggah arsip ke ${VPS_USER}@${VPS_HOST}..."

scp "${SCP_OPTS[@]}" "${TEMP_ARCHIVE}" "${VPS_USER}@${VPS_HOST}:${REMOTE_ARCHIVE}"
rm -f "${TEMP_ARCHIVE}"

echo "[OK] Upload berhasil!"

echo ""
echo "[3/4] Menjalankan Docker Setup & Container Build di VPS..."

REMOTE_CMD="set -e; \
mkdir -p /tmp/wacs-deploy && \
tar -xzf ${REMOTE_ARCHIVE} -C /tmp/wacs-deploy deploy/production/vps-setup.sh deploy/production/vps-deploy.sh && \
chmod +x /tmp/wacs-deploy/deploy/production/vps-setup.sh /tmp/wacs-deploy/deploy/production/vps-deploy.sh && \
/tmp/wacs-deploy/deploy/production/vps-setup.sh && \
/tmp/wacs-deploy/deploy/production/vps-deploy.sh '${REMOTE_ARCHIVE}' '${REMOTE_DIR}'"

ssh "${SSH_OPTS[@]}" "${VPS_USER}@${VPS_HOST}" "${REMOTE_CMD}"

echo ""
echo "[4/4] Process Selesai!"
echo "============================================================"
echo " Aplikasi VPoint Care (WACS) telah sukses ter-deploy di Docker VPS!"
echo " - URL Admin Panel  : http://${VPS_HOST}:8080/admin"
echo " - WAHA Dashboard   : http://${VPS_HOST}:3000"
echo " - Reverb Websocket : http://${VPS_HOST}:7060"
echo "============================================================"
