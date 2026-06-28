@echo off
setlocal EnableExtensions

REM ==========================================================
REM VPoint Care Docker Update Script
REM Jalankan dari Windows. Untuk tanpa prompt password, install PuTTY CLI: plink.exe dan pscp.exe
REM Jika plink/pscp tidak ada, script fallback ke OpenSSH ssh/scp dan password login tetap diprompt oleh OpenSSH.
REM Script ini tidak mengupload .env, vendor, node_modules, storage.
REM Script ini sudah mendukung stack baru: Redis + queue-webhooks + queue-ai + queue-broadcasts
REM Usage PuTTY tanpa prompt password: deploy-update-server.bat "PASSWORD_SUDO_DAN_SSH"
REM Usage OpenSSH key: deploy-update-server.bat "PASSWORD_SUDO" "C:\Users\nama\.ssh\vpoint_it"
REM ==========================================================

set "REMOTE_USER=it"
set "REMOTE_HOST=172.22.46.111"
set "REMOTE_APP_DIR=/home/it/GIT_VPOINT/2026-vpoint-care"
set "LOCAL_SRC=%~dp0src"
set "ARCHIVE=%TEMP%\vpoint-care-update.tar.gz"
set "REMOTE_ARCHIVE=/tmp/vpoint-care-update.tar.gz"
set "REMOTE_HOSTKEY=SHA256:BdptTOPZFEA9BPzVyJH3ybZUYnbvvpSB51WBce7P9fc"
set "SSH_KEY=%~2"
set "SSH_OPTS="
set "SCP_OPTS="
if not "%SSH_KEY%"=="" (
    set SSH_OPTS=-i "%SSH_KEY%"
    set SCP_OPTS=-i "%SSH_KEY%"
)

if not exist "%LOCAL_SRC%\artisan" (
    echo [ERROR] Tidak menemukan folder Laravel: "%LOCAL_SRC%"
    echo Pastikan script ini ada di root repo yang berisi folder src.
    pause
    exit /b 1
)

where tar >nul 2>nul || (
    echo [ERROR] tar tidak ditemukan. Windows 10/11 biasanya sudah punya tar.
    pause
    exit /b 1
)

set "USE_PUTTY=0"
where plink >nul 2>nul && where pscp >nul 2>nul && set "USE_PUTTY=1"

if "%USE_PUTTY%"=="0" (
    where ssh >nul 2>nul || (
        echo [ERROR] ssh tidak ditemukan. Install OpenSSH Client atau PuTTY plink/pscp dulu.
        pause
        exit /b 1
    )

    where scp >nul 2>nul || (
        echo [ERROR] scp tidak ditemukan. Install OpenSSH Client atau PuTTY plink/pscp dulu.
        pause
        exit /b 1
    )
)
echo.
echo [INFO] Remote: %REMOTE_USER%@%REMOTE_HOST%:%REMOTE_APP_DIR%
echo [INFO] Source: %LOCAL_SRC%
echo [INFO] Stack: app, web, redis, reverb, queue-webhooks, queue-ai, queue-broadcasts
if not "%SSH_KEY%"=="" echo [INFO] SSH key: %SSH_KEY%
echo.
choice /C YN /M "Lanjut deploy update ke server?"
if errorlevel 2 exit /b 0

if not "%~1"=="" (
    set "SUDO_PASS=%~1"
    echo [INFO] Password sudo dibaca dari parameter pertama.
) else (
    set /P "SUDO_PASS=Masukkan password sudo server untuk user %REMOTE_USER%: "
)

if "%USE_PUTTY%"=="1" (
    set SSH_CMD=plink -batch -hostkey "%REMOTE_HOSTKEY%" -pw "%SUDO_PASS%" %REMOTE_USER%@%REMOTE_HOST%
    set SCP_CMD=pscp -batch -hostkey "%REMOTE_HOSTKEY%" -pw "%SUDO_PASS%"
    echo [INFO] Mode SSH: PuTTY plink/pscp tanpa prompt password login.
) else (
    set SSH_CMD=ssh %SSH_OPTS% %REMOTE_USER%@%REMOTE_HOST%
    set SCP_CMD=scp %SCP_OPTS%
    echo [INFO] Mode SSH: OpenSSH. Password login masih diprompt oleh ssh/scp jika SSH key belum dikonfigurasi.
)
if exist "%ARCHIVE%" del /f /q "%ARCHIVE%"

echo.
echo [1/7] Membuat archive update termasuk Docker config...
pushd "%LOCAL_SRC%" || exit /b 1
tar -czf "%ARCHIVE%" ^
    app ^
    config ^
    database ^
    public/images ^
    resources ^
    routes ^
    composer.json ^
    composer.lock ^
    package.json ^
    package-lock.json ^
    vite.config.js ^
    Dockerfile ^
    docker-compose.yml
if errorlevel 1 (
    popd
    echo [ERROR] Gagal membuat archive.
    pause
    exit /b 1
)
popd

echo.
echo [2/7] Upload archive ke server...
%SCP_CMD% "%ARCHIVE%" %REMOTE_USER%@%REMOTE_HOST%:%REMOTE_ARCHIVE%
if errorlevel 1 (
    echo [ERROR] Upload gagal.
    pause
    exit /b 1
)

echo.
echo [3/7] Extract file update di server...
%SSH_CMD% "cd %REMOTE_APP_DIR% && mkdir -p /tmp/vpoint-care-update && rm -rf /tmp/vpoint-care-update/* && tar -xzf %REMOTE_ARCHIVE% -C /tmp/vpoint-care-update && cp -R /tmp/vpoint-care-update/app /tmp/vpoint-care-update/config /tmp/vpoint-care-update/database /tmp/vpoint-care-update/public /tmp/vpoint-care-update/resources /tmp/vpoint-care-update/routes . && cp /tmp/vpoint-care-update/composer.json /tmp/vpoint-care-update/composer.lock /tmp/vpoint-care-update/package.json /tmp/vpoint-care-update/package-lock.json /tmp/vpoint-care-update/vite.config.js . && printf '%SUDO_PASS%\n' | sudo -S cp /tmp/vpoint-care-update/Dockerfile /tmp/vpoint-care-update/docker-compose.yml ."
if errorlevel 1 (
    echo [ERROR] Extract/copy gagal.
    pause
    exit /b 1
)

echo.
echo [4/7] Fix permission source dan writable folder...
%SSH_CMD% "cd %REMOTE_APP_DIR% && printf '%SUDO_PASS%\n' | sudo -S find app config routes resources database public/images -type d -exec chmod 755 {} \; && printf '%SUDO_PASS%\n' | sudo -S find app config routes resources database public/images -type f -exec chmod 644 {} \; && printf '%SUDO_PASS%\n' | sudo -S chmod 644 Dockerfile docker-compose.yml && printf '%SUDO_PASS%\n' | sudo -S chown -R www-data:www-data storage bootstrap/cache && printf '%SUDO_PASS%\n' | sudo -S chmod -R ug+rwX storage bootstrap/cache"
if errorlevel 1 (
    echo [ERROR] Permission fix gagal.
    pause
    exit /b 1
)

echo.
echo [5/7] Rebuild image, start Redis/workers, install dependency, migrate, build asset...
%SSH_CMD% "cd %REMOTE_APP_DIR% && printf '%SUDO_PASS%\n' | sudo -S docker compose build app && printf '%SUDO_PASS%\n' | sudo -S docker compose up -d --remove-orphans redis app web reverb queue-webhooks queue-ai queue-broadcasts && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app sh -lc 'rm -f bootstrap/cache/*.php bootstrap/cache/*.tmp bootstrap/cache/pac* storage/framework/views/*.php' && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app composer install --no-dev --optimize-autoloader && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app php artisan optimize:clear && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app php artisan migrate --force && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app npm run build && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app php artisan optimize"
if errorlevel 1 (
    echo [ERROR] Docker/app update command gagal.
    pause
    exit /b 1
)

echo.
echo [6/7] Restart app services dan dedicated workers...
%SSH_CMD% "cd %REMOTE_APP_DIR% && printf '%SUDO_PASS%\n' | sudo -S docker compose restart app web reverb queue-webhooks queue-ai queue-broadcasts"
if errorlevel 1 (
    echo [ERROR] Restart gagal.
    pause
    exit /b 1
)

echo.
echo [7/7] Health check...
%SSH_CMD% "cd %REMOTE_APP_DIR% && printf '%SUDO_PASS%\n' | sudo -S docker compose ps && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app php artisan about && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app php artisan route:list | grep -E 'external-auth|v-point-assistant|webhooks.waha' || true && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app php -m | grep -i redis || true"
if errorlevel 1 (
    echo [WARN] Health check ada error. Cek log manual.
)

echo.
echo [DONE] Deploy update selesai.
echo Buka: https://care.vpoint.my.id/admin/login
echo Cek worker: docker compose ps queue-webhooks queue-ai queue-broadcasts redis
echo Cek log: docker compose logs -f queue-webhooks queue-ai queue-broadcasts reverb
echo Jika SVG masih cache lama, purge Cloudflare atau ganti nama file SVG.
echo.
pause
endlocal
