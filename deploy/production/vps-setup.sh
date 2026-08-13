#!/usr/bin/env bash
set -e

# ==============================================================================
# VPoint Care / WACS - Remote VPS Docker Setup Script (Ubuntu)
# Script ini meng-install Docker Engine & Docker Compose plugin di Ubuntu VPS
# jika belum tersedia.
# ==============================================================================

echo "============================================================"
echo " [VPS Setup] Memeriksa Lingkungan Docker di Ubuntu VPS"
echo "============================================================"

# Pastikan dijalankan sebagai root atau dengan sudo
if [ "$(id -u)" -ne 0 ]; then
    SUDO="sudo"
else
    SUDO=""
fi

# Function check command
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# 1. Periksa Docker Engine
if command_exists docker && docker --version >/dev/null 2>&1; then
    echo "[OK] Docker Engine sudah terinstall: $(docker --version)"
else
    echo "[INFO] Docker Engine belum terinstall. Menginstall Docker Engine..."
    $SUDO apt-get update -y
    $SUDO apt-get install -y ca-certificates curl gnupg lsb-release

    $SUDO install -m 0755 -d /etc/apt/keyrings
    if [ ! -f /etc/apt/keyrings/docker.gpg ]; then
        curl -fsSL https://download.docker.com/linux/ubuntu/gpg | $SUDO gpg --dearmor -o /etc/apt/keyrings/docker.gpg
        $SUDO chmod a+r /etc/apt/keyrings/docker.gpg
    fi

    UBUNTU_CODENAME=$(lsb_release -cs 2>/dev/null || echo "noble")
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu ${UBUNTU_CODENAME} stable" | $SUDO tee /etc/apt/sources.list.d/docker.list > /dev/null

    $SUDO apt-get update -y
    $SUDO apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

    $SUDO systemctl enable docker
    $SUDO systemctl start docker
    echo "[OK] Docker Engine berhasil terinstall!"
fi

# 2. Periksa Docker Compose
if docker compose version >/dev/null 2>&1; then
    echo "[OK] Docker Compose v2 sudah terinstall: $(docker compose version)"
else
    echo "[INFO] Memasang docker-compose-plugin..."
    $SUDO apt-get update -y
    $SUDO apt-get install -y docker-compose-plugin
fi

# 3. Pastikan user lokal masuk group docker
if [ -n "$SUDO_USER" ]; then
    $SUDO usermod -aG docker "$SUDO_USER" || true
fi

echo "[OK] Lingkungan VPS siap menjalankan Docker Compose!"
