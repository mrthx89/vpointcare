@echo off
setlocal EnableExtensions EnableDelayedExpansion

REM ==========================================================
REM VPoint Care Docker Update Script
REM Jalankan dari Windows. Butuh OpenSSH client: ssh, scp, tar.
REM Script ini tidak mengupload .env, vendor, node_modules, storage.
REM ==========================================================

set "REMOTE_USER=it"
set "REMOTE_HOST=172.22.46.111"
set "REMOTE_APP_DIR=/home/it/GIT_VPOINT/2026-vpoint-care"
set "LOCAL_SRC=%~dp0src"
set "ARCHIVE=%TEMP%\vpoint-care-update.tar.gz"
set "REMOTE_ARCHIVE=/tmp/vpoint-care-update.tar.gz"

if not exist "%LOCAL_SRC%\artisan" (
    echo [ERROR] Tidak menemukan folder Laravel: "%LOCAL_SRC%"
    echo Pastikan script ini ada di root repo yang berisi folder src.
    pause
    exit /b 1
)

where ssh >nul 2>nul || (
    echo [ERROR] ssh tidak ditemukan. Install/OpenSSH Client dulu.
    pause
    exit /b 1
)

where scp >nul 2>nul || (
    echo [ERROR] scp tidak ditemukan. Install/OpenSSH Client dulu.
    pause
    exit /b 1
)

where tar >nul 2>nul || (
    echo [ERROR] tar tidak ditemukan. Windows 10/11 biasanya sudah punya tar.
    pause
    exit /b 1
)

echo.
echo [INFO] Remote: %REMOTE_USER%@%REMOTE_HOST%:%REMOTE_APP_DIR%
echo [INFO] Source: %LOCAL_SRC%
echo.
choice /C YN /M "Lanjut deploy update ke server?"
if errorlevel 2 exit /b 0

set /P "SUDO_PASS=Masukkan password sudo server untuk user %REMOTE_USER%: "

if exist "%ARCHIVE%" del /f /q "%ARCHIVE%"

echo.
echo [1/7] Membuat archive update...
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
    vite.config.js
if errorlevel 1 (
    popd
    echo [ERROR] Gagal membuat archive.
    pause
    exit /b 1
)
popd

echo.
echo [2/7] Upload archive ke server...
scp "%ARCHIVE%" %REMOTE_USER%@%REMOTE_HOST%:%REMOTE_ARCHIVE%
if errorlevel 1 (
    echo [ERROR] Upload gagal.
    pause
    exit /b 1
)

echo.
echo [3/7] Extract file update di server...
ssh %REMOTE_USER%@%REMOTE_HOST% "cd %REMOTE_APP_DIR% && mkdir -p /tmp/vpoint-care-update && rm -rf /tmp/vpoint-care-update/* && tar -xzf %REMOTE_ARCHIVE% -C /tmp/vpoint-care-update && cp -R /tmp/vpoint-care-update/app /tmp/vpoint-care-update/config /tmp/vpoint-care-update/database /tmp/vpoint-care-update/public /tmp/vpoint-care-update/resources /tmp/vpoint-care-update/routes . && cp /tmp/vpoint-care-update/composer.json /tmp/vpoint-care-update/composer.lock /tmp/vpoint-care-update/package.json /tmp/vpoint-care-update/package-lock.json /tmp/vpoint-care-update/vite.config.js ."
if errorlevel 1 (
    echo [ERROR] Extract/copy gagal.
    pause
    exit /b 1
)

echo.
echo [4/7] Fix permission source dan writable folder...
ssh %REMOTE_USER%@%REMOTE_HOST% "cd %REMOTE_APP_DIR% && printf '%SUDO_PASS%\n' | sudo -S find app config routes resources database public/images -type d -exec chmod 755 {} \; && printf '%SUDO_PASS%\n' | sudo -S find app config routes resources database public/images -type f -exec chmod 644 {} \; && printf '%SUDO_PASS%\n' | sudo -S chown -R www-data:www-data storage bootstrap/cache && printf '%SUDO_PASS%\n' | sudo -S chmod -R ug+rwX storage bootstrap/cache"
if errorlevel 1 (
    echo [ERROR] Permission fix gagal.
    pause
    exit /b 1
)

echo.
echo [5/7] Clear cache, install dependency, migrate, build asset...
ssh %REMOTE_USER%@%REMOTE_HOST% "cd %REMOTE_APP_DIR% && printf '%SUDO_PASS%\n' | sudo -S docker compose up -d && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app sh -lc 'rm -f bootstrap/cache/*.php bootstrap/cache/*.tmp bootstrap/cache/pac* storage/framework/views/*.php' && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app composer install --no-dev --optimize-autoloader && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app php artisan optimize:clear && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app php artisan migrate --force && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app npm run build"
if errorlevel 1 (
    echo [ERROR] Docker/app update command gagal.
    pause
    exit /b 1
)

echo.
echo [6/7] Restart services...
ssh %REMOTE_USER%@%REMOTE_HOST% "cd %REMOTE_APP_DIR% && printf '%SUDO_PASS%\n' | sudo -S docker compose restart app web queue reverb"
if errorlevel 1 (
    echo [ERROR] Restart gagal.
    pause
    exit /b 1
)

echo.
echo [7/7] Health check...
ssh %REMOTE_USER%@%REMOTE_HOST% "cd %REMOTE_APP_DIR% && printf '%SUDO_PASS%\n' | sudo -S docker compose ps && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app php artisan about && printf '%SUDO_PASS%\n' | sudo -S docker compose exec -T app php artisan route:list | grep external-auth || true"
if errorlevel 1 (
    echo [WARN] Health check ada error. Cek log manual.
)

echo.
echo [DONE] Deploy update selesai.
echo Buka: https://care.vpoint.my.id/admin/login
echo Jika SVG masih cache lama, purge Cloudflare atau ganti nama file SVG.
echo.
pause
endlocal


