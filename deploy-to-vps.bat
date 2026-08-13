@echo off
setlocal
echo ============================================================
echo  VPoint Care (WACS) - VPS Deployment Launcher
echo ============================================================
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0deploy-to-vps.ps1" %*

if errorlevel 1 (
    echo.
    echo [ERROR] Deployment gagal! Cek pesan kesalahan di atas.
    pause
    exit /b 1
)

echo.
echo [DONE] Deployment selesai.
pause
endlocal
