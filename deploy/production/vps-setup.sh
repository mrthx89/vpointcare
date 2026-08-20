#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Care Desk / WACS - Remote VPS Docker Setup Script (Ubuntu)
# ==============================================================================

echo "============================================================"
echo " [VPS Setup] Memeriksa & Menyiapkan Docker di Ubuntu VPS"
echo "============================================================"

# 1. Validasi Root Access
if [ "$(id -u)" -ne 0 ]; then
    echo "[ERROR] Script vps-setup.sh harus dijalankan sebagai root!"
    exit 1
fi

# 2. Validasi Sistem Operasi (Ubuntu Only)
if [ -f /etc/os-release ]; then
    # shellcheck disable=SC1091
    . /etc/os-release
    if [ "${ID:-}" != "ubuntu" ]; then
        echo "[ERROR] Target OS bukan Ubuntu (Terdeteksi: ${NAME:-Unknown OS}). Script ini khusus untuk Ubuntu VPS!"
        exit 1
    fi
    echo "[INFO] Sistem Operasi terverifikasi: Ubuntu ${VERSION_ID:-}"
else
    echo "[ERROR] File /etc/os-release tidak ditemukan. Hanya Ubuntu VPS yang didukung!"
    exit 1
fi

# Function check command
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# 3. Periksa & Install Docker Engine
if command_exists docker && docker --version >/dev/null 2>&1; then
    echo "[OK] Docker Engine sudah terinstall: $(docker --version)"
else
    echo "[INFO] Docker Engine belum terinstall. Menginstall dari repo resmi Docker..."
    apt-get update -y
    apt-get install -y ca-certificates curl gnupg lsb-release

    install -m 0755 -d /etc/apt/keyrings
    if [ -f /etc/apt/keyrings/docker.gpg ]; then
        rm -f /etc/apt/keyrings/docker.gpg
    fi
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    chmod a+r /etc/apt/keyrings/docker.gpg

    UBUNTU_CODENAME=$(lsb_release -cs 2>/dev/null || echo "noble")
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu ${UBUNTU_CODENAME} stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null

    apt-get update -y
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

    systemctl enable docker
    systemctl start docker
    echo "[OK] Docker Engine berhasil terinstall!"
fi

# 4. Periksa Docker Compose
if docker compose version >/dev/null 2>&1; then
    echo "[OK] Docker Compose v2 sudah terinstall: $(docker compose version)"
else
    echo "[INFO] Memasang docker-compose-plugin..."
    apt-get update -y
    apt-get install -y docker-compose-plugin
    if docker compose version >/dev/null 2>&1; then
        echo "[OK] docker-compose-plugin berhasil terinstall!"
    else
        echo "[ERROR] Gagal memasang docker-compose-plugin!"
        exit 1
    fi
fi

if ! systemctl is-active --quiet docker; then
    systemctl start docker
fi
systemctl enable docker >/dev/null 2>&1 || true
if ! docker info >/dev/null 2>&1; then
    echo "[ERROR] Daemon Docker Engine tidak merespons setelah setup!"
    exit 1
fi

# 5. Pastikan user pelaksana masuk group docker jika ada
if [ -n "${SUDO_USER:-}" ]; then
    usermod -aG docker "${SUDO_USER}" || true
fi

echo "[SUCCESS] Konfigurasi Docker di VPS selesai dengan sukses!"
