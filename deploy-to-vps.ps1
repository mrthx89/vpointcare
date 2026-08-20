[CmdletBinding()]
param (
    [Parameter(Mandatory = $false, HelpMessage = "IP atau Domain Server VPS Ubuntu")]
    [string]$VpsHost,

    [Parameter(Mandatory = $false, HelpMessage = "Username SSH VPS (default: root)")]
    [string]$VpsUser = "root",

    [Parameter(Mandatory = $false, HelpMessage = "Port SSH (default: 22)")]
    [int]$VpsPort = 22,

    [Parameter(Mandatory = $false, HelpMessage = "Path ke file SSH Private Key")]
    [string]$SshKey = "",

    [Parameter(Mandatory = $false, HelpMessage = "Direktori target deployment di VPS")]
    [string]$RemoteDir = "/opt/care-desk"
)

$ErrorActionPreference = "Stop"

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " Care Desk (WACS) - Automated Ubuntu VPS Deployment" -ForegroundColor Green
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

# 3. Penanganan SSH Key Bootstrap (ed25519)
$HomeSshDir = Join-Path $env:USERPROFILE ".ssh"
if (-not $SshKey) {
    $SshKey = Join-Path $HomeSshDir "id_ed25519"
}

if (-not (Test-Path $SshKey)) {
    Write-Host "[INFO] SSH Key tidak ditemukan di $SshKey. Meng-generate SSH Key ed25519 lokal..." -ForegroundColor Yellow
    if (-not (Test-Path $HomeSshDir)) {
        New-Item -ItemType Directory -Path $HomeSshDir -Force | Out-Null
    }
    $KeyGenCmd = Get-Command "ssh-keygen" -ErrorAction SilentlyContinue
    if ($KeyGenCmd) {
        & ssh-keygen -t ed25519 -N "" -f $SshKey
    } else {
        Write-Error "ssh-keygen tidak ditemukan. Tidak dapat membuat SSH Key secara otomatis."
        exit 1
    }
}

$SshPubKey = "${SshKey}.pub"
if (-not (Test-Path $SshPubKey)) {
    Write-Error "File Public SSH Key ($SshPubKey) tidak ditemukan."
    exit 1
}

# Tes koneksi SSH berbasis Key
Write-Host "[INFO] Memeriksa autentikasi SSH Key ke $VpsUser@${VpsHost}:${VpsPort}..." -ForegroundColor Cyan
$TestKeyArgs = @("-i", $SshKey, "-p", $VpsPort, "-o", "BatchMode=yes", "-o", "StrictHostKeyChecking=accept-new", "-o", "ConnectTimeout=7", "${VpsUser}@${VpsHost}", "echo OK")
$TestResult = & ssh @TestKeyArgs 2>&1

if ($LASTEXITCODE -ne 0) {
    Write-Host "[INFO] SSH Key belum terdaftar di VPS. Memulai bootstrap SSH key otomatis..." -ForegroundColor Yellow
    Write-Host "[NOTE] Silakan masukkan password VPS ($VpsUser) ketika diminta oleh OpenSSH." -ForegroundColor Yellow
    Write-Host "[NOTE] Password TIDAK disimpan dan hanya digunakan sekali oleh OpenSSH untuk memasang public key." -ForegroundColor Gray
    
    $PubKeyContent = (Get-Content -LiteralPath $SshPubKey -Raw).Trim()
    $RemoteAppendCmd = "mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo '$PubKeyContent' >> ~/.ssh/authorized_keys && sort -u ~/.ssh/authorized_keys -o ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"
    
    $BootstrapArgs = @("-p", $VpsPort, "-o", "StrictHostKeyChecking=accept-new", "${VpsUser}@${VpsHost}", $RemoteAppendCmd)
    & ssh @BootstrapArgs

    if ($LASTEXITCODE -ne 0) {
        Write-Error "Gagal memasang Public SSH Key ke VPS target."
        exit 1
    }

    # Re-test koneksi dengan key
    Write-Host "[INFO] Verifikasi koneksi SSH key setelah bootstrap..." -ForegroundColor Cyan
    $TestResult = & ssh @TestKeyArgs 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Autentikasi SSH key masih gagal setelah bootstrap!"
        exit 1
    }
}

Write-Host "[OK] Autentikasi SSH Key berhasil!" -ForegroundColor Green

$SshOpts = @("-i", $SshKey, "-p", $VpsPort, "-o", "StrictHostKeyChecking=accept-new")
$ScpOpts = @("-i", $SshKey, "-P", $VpsPort, "-o", "StrictHostKeyChecking=accept-new")

$RepoRoot = $PSScriptRoot
$LocalSrc = Join-Path $RepoRoot "src"
$LocalDeploy = Join-Path $RepoRoot "deploy\production"
$TempArchive = Join-Path $env:TEMP "care-desk-deploy-$([guid]::NewGuid().ToString('N').Substring(0,8)).tar.gz"

if (-not (Test-Path (Join-Path $LocalSrc "artisan"))) {
    Write-Error "Tidak dapat menemukan folder 'src/artisan'. Pastikan script dijalankan dari root repository."
    exit 1
}

# 4. Membuat Arsip Bersih
Write-Host "`n[1/4] Membuat arsip deployment bersih (termasuk src & deploy/production)..." -ForegroundColor Cyan

Push-Location $RepoRoot
try {
    tar -czf $TempArchive `
        --exclude="src/vendor" `
        --exclude="src/node_modules" `
        --exclude="src/.env" `
        --exclude="src/.env.*" `
        --exclude="deploy/production/.env.production" `
        --exclude="src/storage/logs/*" `
        --exclude="src/storage/framework/cache/*" `
        --exclude="src/storage/framework/sessions/*" `
        --exclude="src/storage/framework/views/*" `
        --exclude="src/tests" `
        --exclude="src/.phpunit.result.cache" `
        src `
        deploy/production
    
    Write-Host "[OK] Arsip berhasil dibuat: $TempArchive" -ForegroundColor Green
} finally {
    Pop-Location
}

# 5. Transfer File ke VPS
$RemoteArchive = "/tmp/care-desk-deploy.tar.gz"
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

# Escaping single quotes in RemoteDir path to prevent injection in ssh context
$EscapedRemoteDir = $RemoteDir -replace "'", "'\''"
$RemoteCmd = "set -e; " +
             "mkdir -p /tmp/wacs-deploy && " +
             "tar -xzf $RemoteArchive -C /tmp/wacs-deploy deploy/production/vps-setup.sh deploy/production/vps-deploy.sh && " +
             "chmod +x /tmp/wacs-deploy/deploy/production/vps-setup.sh /tmp/wacs-deploy/deploy/production/vps-deploy.sh && " +
             "/tmp/wacs-deploy/deploy/production/vps-setup.sh && " +
             "/tmp/wacs-deploy/deploy/production/vps-deploy.sh '$RemoteArchive' '$EscapedRemoteDir'"

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
Write-Host " Aplikasi Care Desk (WACS) telah sukses ter-deploy di Docker VPS!" -ForegroundColor Green
Write-Host " - URL Admin Panel  : http://${VpsHost}:8080/admin" -ForegroundColor Yellow
Write-Host " - WAHA Dashboard   : http://${VpsHost}:3000" -ForegroundColor Yellow
Write-Host " - Reverb Websocket : http://${VpsHost}:7060" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
