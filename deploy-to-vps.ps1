[CmdletBinding()]
param (
    [Parameter(Mandatory = $false, HelpMessage = "IP atau Domain Server VPS Ubuntu")]
    [string]$VpsHost,

    [Parameter(Mandatory = $false, HelpMessage = "Username SSH VPS (contoh: root, ubuntu, it)")]
    [string]$VpsUser = "root",

    [Parameter(Mandatory = $false, HelpMessage = "Port SSH (default: 22)")]
    [int]$VpsPort = 22,

    [Parameter(Mandatory = $false, HelpMessage = "Path ke file SSH Private Key (opsional)")]
    [string]$SshKey = "",

    [Parameter(Mandatory = $false, HelpMessage = "Direktori target deployment di VPS")]
    [string]$RemoteDir = "/opt/vpoint-care"
)

$ErrorActionPreference = "Stop"

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " VPoint Care (WACS) - Automated Ubuntu VPS Deployment" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Cyan

# 1. Interactive Prompts jika parameter belum diisi
if (-not $VpsHost) {
    $VpsHost = Read-Host "Masukkan IP Address / Domain VPS Ubuntu"
    if (-not $VpsHost) {
        Write-Error "IP / Domain VPS tidak boleh kosong!"
        exit 1
    }
}

if (-not $VpsUser) {
    $UserInput = Read-Host "Masukkan Username SSH VPS [Default: root]"
    if ($UserInput) { $VpsUser = $UserInput }
}

# 2. Verifikasi Tooling Lokal (tar, ssh, scp)
foreach ($cmd in @("tar", "ssh", "scp")) {
    if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) {
        Write-Error "Perintah '$cmd' tidak ditemukan. Pastikan OpenSSH Client dan tar terinstall di Windows."
        exit 1
    }
}

# 3. Opsi SSH & SCP
$SshOpts = @("-p", $VpsPort)
$ScpOpts = @("-P", $VpsPort)

if ($SshKey -and (Test-Path $SshKey)) {
    $SshOpts += @("-i", $SshKey)
    $ScpOpts += @("-i", $SshKey)
    Write-Host "[INFO] Menggunakan SSH Key: $SshKey" -ForegroundColor Yellow
} else {
    Write-Host "[INFO] Menggunakan SSH standard (password diprompt otomatis oleh OpenSSH jika diperlukan)." -ForegroundColor Yellow
}

$RepoRoot = $PSScriptRoot
$LocalSrc = Join-Path $RepoRoot "src"
$LocalDeploy = Join-Path $RepoRoot "deploy\production"
$TempArchive = Join-Path $env:TEMP "vpoint-care-deploy-$([guid]::NewGuid().ToString('N').Substring(0,8)).tar.gz"

if (-not (Test-Path (Join-Path $LocalSrc "artisan"))) {
    Write-Error "Tidak dapat menemukan folder 'src/artisan'. Pastikan script dijalankan dari root repository."
    exit 1
}

# 4. Membuat Arsip Bersih
Write-Host "`n[1/4] Membuat arsip deployment bersih (tanpa node_modules & vendor)..." -ForegroundColor Cyan

Push-Location $RepoRoot
try {
    tar -czf $TempArchive `
        src/app `
        src/bootstrap `
        src/config `
        src/database `
        src/public `
        src/resources `
        src/routes `
        src/artisan `
        src/composer.json `
        src/composer.lock `
        src/package.json `
        src/package-lock.json `
        src/vite.config.js `
        src/Dockerfile `
        deploy/production
    
    Write-Host "[OK] Arsip berhasil dibuat: $TempArchive" -ForegroundColor Green
} finally {
    Pop-Location
}

# 5. Transfer File ke VPS
$RemoteArchive = "/tmp/vpoint-care-deploy.tar.gz"
Write-Host "`n[2/4] Mengunggah arsip ke $VpsUser@${VpsHost}..." -ForegroundColor Cyan

$ScpTarget = "${VpsUser}@${VpsHost}:${RemoteArchive}"
$ScpArgs = $ScpOpts + @($TempArchive, $ScpTarget)
& scp @ScpArgs

if ($LASTEXITCODE -ne 0) {
    Remove-Item -Force $TempArchive -ErrorAction SilentlyContinue
    Write-Error "Upload ke VPS gagal!"
    exit 1
}

Remove-Item -Force $TempArchive -ErrorAction SilentlyContinue
Write-Host "[OK] Upload berhasil!" -ForegroundColor Green

# 6. Eksekusi Remote Setup & Deploy di VPS
Write-Host "`n[3/4] Menjalankan Docker Setup & Container Build di VPS..." -ForegroundColor Cyan

$RemoteCmd = "set -e; " +
             "mkdir -p /tmp/wacs-deploy && " +
             "tar -xzf $RemoteArchive -C /tmp/wacs-deploy deploy/production/vps-setup.sh deploy/production/vps-deploy.sh && " +
             "chmod +x /tmp/wacs-deploy/deploy/production/vps-setup.sh /tmp/wacs-deploy/deploy/production/vps-deploy.sh && " +
             "/tmp/wacs-deploy/deploy/production/vps-setup.sh && " +
             "/tmp/wacs-deploy/deploy/production/vps-deploy.sh '$RemoteArchive' '$RemoteDir'"

$SshTarget = "${VpsUser}@${VpsHost}"
$SshArgs = $SshOpts + @($SshTarget, $RemoteCmd)
& ssh @SshArgs

if ($LASTEXITCODE -ne 0) {
    Write-Error "Proses deployment remote gagal!"
    exit 1
}

# 7. Selesai
Write-Host "`n[4/4] Process Selesai!" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " Aplikasi VPoint Care (WACS) telah sukses ter-deploy di Docker VPS!" -ForegroundColor Green
Write-Host " - URL Admin Panel  : http://${VpsHost}:8080/admin" -ForegroundColor Yellow
Write-Host " - WAHA Dashboard   : http://${VpsHost}:3000" -ForegroundColor Yellow
Write-Host " - Reverb Websocket : http://${VpsHost}:7060" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
