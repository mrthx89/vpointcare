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

# 1. Interactive Prompts jika parameter belum diisi
if [ -z "${VPS_HOST}" ]; then
    read -p "Masukkan IP Address / Domain VPS Ubuntu: " VPS_HOST
    if [ -z "${VPS_HOST}" ]; then
        echo "[ERROR] IP / Domain VPS tidak boleh kosong!"
        exit 1
    fi
fi

# 2. Verifikasi Tooling Lokal (tar, ssh, scp)
for cmd in tar ssh scp; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
        echo "[ERROR] Perintah '$cmd' tidak ditemukan. Pastikan OpenSSH Client dan tar terpasang."
        exit 1
    fi
done

# 3. Penanganan SSH Key Bootstrap (ed25519)
if [ -z "${SSH_KEY}" ]; then
    SSH_KEY="${HOME}/.ssh/id_ed25519"
fi

if [ ! -f "${SSH_KEY}" ]; then
    echo "[INFO] SSH Key tidak ditemukan di ${SSH_KEY}. Meng-generate SSH Key ed25519 lokal..."
    mkdir -p "${HOME}/.ssh"
    chmod 700 "${HOME}/.ssh"
    if command -v ssh-keygen >/dev/null 2>&1; then
        ssh-keygen -t ed25519 -N "" -f "${SSH_KEY}"
    else
        echo "[ERROR] ssh-keygen tidak ditemukan. Gagal membuat SSH Key otomatis."
        exit 1
    fi
fi

if [ ! -f "${SSH_KEY}.pub" ]; then
    echo "[ERROR] File Public SSH Key (${SSH_KEY}.pub) tidak ditemukan."
    exit 1
fi

# Tes koneksi SSH berbasis Key
echo "[INFO] Memeriksa autentikasi SSH Key ke ${VPS_USER}@${VPS_HOST}:${VPS_PORT}..."
if ssh -i "${SSH_KEY}" -p "${VPS_PORT}" -o BatchMode=yes -o StrictHostKeyChecking=accept-new -o ConnectTimeout=7 "${VPS_USER}@${VPS_HOST}" "echo OK" >/dev/null 2>&1; then
    echo "[OK] Autentikasi SSH Key berhasil!"
else
    echo "[INFO] SSH Key belum terdaftar di VPS. Memulai bootstrap SSH key otomatis..."
    echo "[NOTE] Silakan masukkan password VPS (${VPS_USER}) ketika diminta oleh OpenSSH."
    echo "[NOTE] Password TIDAK disimpan dan hanya digunakan sekali oleh OpenSSH untuk memasang public key."
    
    if command -v ssh-copy-id >/dev/null 2>&1; then
        ssh-copy-id -i "${SSH_KEY}.pub" -p "${VPS_PORT}" -o StrictHostKeyChecking=accept-new "${VPS_USER}@${VPS_HOST}"
    else
        # Fallback manual append
        cat "${SSH_KEY}.pub" | ssh -p "${VPS_PORT}" -o StrictHostKeyChecking=accept-new "${VPS_USER}@${VPS_HOST}" \
            "mkdir -p ~/.ssh && chmod 700 ~/.ssh && cat >> ~/.ssh/authorized_keys && sort -u ~/.ssh/authorized_keys -o ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"
    fi

    # Re-test koneksi dengan key
    echo "[INFO] Verifikasi koneksi SSH key setelah bootstrap..."
    if ! ssh -i "${SSH_KEY}" -p "${VPS_PORT}" -o BatchMode=yes -o StrictHostKeyChecking=accept-new -o ConnectTimeout=7 "${VPS_USER}@${VPS_HOST}" "echo OK" >/dev/null 2>&1; then
        echo "[ERROR] Autentikasi SSH key masih gagal setelah bootstrap!"
        exit 1
    fi
    echo "[OK] Bootstrap SSH Key berhasil!"
fi

SSH_OPTS=("-i" "${SSH_KEY}" "-p" "${VPS_PORT}" "-o" "StrictHostKeyChecking=accept-new")
SCP_OPTS=("-i" "${SSH_KEY}" "-P" "${VPS_PORT}" "-o" "StrictHostKeyChecking=accept-new")

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOCAL_SRC="${REPO_ROOT}/src"
TEMP_ARCHIVE="/tmp/vpoint-care-deploy-$$.tar.gz"

if [ ! -f "${LOCAL_SRC}/artisan" ]; then
    echo "[ERROR] Tidak dapat menemukan folder 'src/artisan'. Pastikan script dijalankan dari root repository."
    exit 1
fi

# Cleanup arsip lokal saat script keluar
trap 'rm -f "${TEMP_ARCHIVE}"' EXIT

echo ""
echo "[1/4] Membuat arsip deployment bersih (termasuk src & deploy/production)..."
cd "${REPO_ROOT}"
tar -czf "${TEMP_ARCHIVE}" \
    --exclude="src/vendor" \
    --exclude="src/node_modules" \
    --exclude="src/.env" \
    --exclude="src/.env.*" \
    --exclude="deploy/production/.env.production" \
    --exclude="src/storage/logs/*" \
    --exclude="src/storage/framework/cache/*" \
    --exclude="src/storage/framework/sessions/*" \
    --exclude="src/storage/framework/views/*" \
    --exclude="src/tests" \
    --exclude="src/.phpunit.result.cache" \
    src \
    deploy/production

echo "[OK] Arsip berhasil dibuat: ${TEMP_ARCHIVE}"

REMOTE_ARCHIVE="/tmp/vpoint-care-deploy.tar.gz"
echo ""
echo "[2/4] Mengunggah arsip ke ${VPS_USER}@${VPS_HOST}..."

scp "${SCP_OPTS[@]}" "${TEMP_ARCHIVE}" "${VPS_USER}@${VPS_HOST}:${REMOTE_ARCHIVE}"
echo "[OK] Upload berhasil!"

echo ""
echo "[3/4] Menjalankan Docker Setup & Container Build di VPS..."

# Safe escaping single quotes in RemoteDir path to prevent injection in ssh context
ESCAPED_REMOTE_DIR=$(echo "${REMOTE_DIR}" | sed "s/'/'\\\\''/g")
REMOTE_CMD="set -e; \
mkdir -p /tmp/wacs-deploy && \
tar -xzf ${REMOTE_ARCHIVE} -C /tmp/wacs-deploy deploy/production/vps-setup.sh deploy/production/vps-deploy.sh && \
chmod +x /tmp/wacs-deploy/deploy/production/vps-setup.sh /tmp/wacs-deploy/deploy/production/vps-deploy.sh && \
/tmp/wacs-deploy/deploy/production/vps-setup.sh && \
/tmp/wacs-deploy/deploy/production/vps-deploy.sh '${REMOTE_ARCHIVE}' '${ESCAPED_REMOTE_DIR}'"

ssh "${SSH_OPTS[@]}" "${VPS_USER}@${VPS_HOST}" "${REMOTE_CMD}"

echo ""
echo "[4/4] Process Selesai!"
echo "============================================================"
echo " Aplikasi VPoint Care (WACS) telah sukses ter-deploy di Docker VPS!"
echo " - URL Admin Panel  : http://${VPS_HOST}:8080/admin"
echo " - WAHA Dashboard   : http://${VPS_HOST}:3000"
echo " - Reverb Websocket : http://${VPS_HOST}:7060"
echo "============================================================"
