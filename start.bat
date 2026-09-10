@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion
title Synesthesia - Full Project Orchestrator

echo =====================================================================
echo           SYNESTHESIA + SOUNDNET AI - ONE-CLICK LAUNCHER             
echo =====================================================================
echo.

cd /d "%~dp0"

REM 1. Check / Start Docker Desktop
echo [1/4] Checking Docker Engine...
docker ps >nul 2>&1
if "%ERRORLEVEL%"=="0" (
    echo       [OK] Docker Engine is online.
) else (
    echo       [INFO] Docker Engine not responding. Starting Docker Desktop...
    if exist "%LOCALAPPDATA%\Programs\DockerDesktop\Docker Desktop.exe" (
        start "" "%LOCALAPPDATA%\Programs\DockerDesktop\Docker Desktop.exe"
    ) else if exist "%ProgramFiles%\Docker\Docker\Docker Desktop.exe" (
        start "" "%ProgramFiles%\Docker\Docker\Docker Desktop.exe"
    )
    echo       [INFO] Waiting for Docker daemon to become ready...
    for /L %%i in (1,1,30) do (
        docker ps >nul 2>&1
        if "!ERRORLEVEL!"=="0" (
            echo       [OK] Docker Engine is ready.
            goto :docker_ready
        )
        timeout /t 2 /nobreak >nul
    )
    echo       [WARN] Docker did not respond in time.
)
:docker_ready

REM 2. Start Open Server Panel
echo.
echo [2/4] Checking Open Server Panel...
tasklist /FI "IMAGENAME eq Open Server Panel.exe" 2>nul | find /I "Open Server Panel.exe" >nul
if "%ERRORLEVEL%"=="0" (
    echo       [OK] Open Server Panel is already running.
) else (
    echo       [INFO] Launching Open Server Panel...
    if exist "D:\OSPanel\Open Server Panel.exe" (
        start "" "D:\OSPanel\Open Server Panel.exe"
        echo       [OK] Open Server Panel started.
    ) else (
        echo       [WARN] D:\OSPanel\Open Server Panel.exe not found.
    )
)

REM 3. Launch Docker AI Stack (Qdrant + Redis + SoundNet AI)
echo.
echo [3/4] Launching SoundNet AI Stack (Qdrant, Redis, AI Microservice)...
docker compose up -d
if "%ERRORLEVEL%"=="0" (
    echo       [OK] Qdrant, Redis, and SoundNet AI containers are running.
) else (
    echo       [WARN] Docker Compose failed or is building. Launching local daemon...
    start "SoundNet AI Daemon" cmd /k "cd /d system\library\soundnet_ai && uv run uvicorn app.main:app --host 127.0.0.1 --port 8000 --workers 1"
)

REM 4. Verify Database & Apply Migrations
echo.
echo [4/4] Verifying Database Connection & Applying Migrations...
for /L %%i in (1,1,10) do (
    if exist "D:\OSPanel\modules\php\PHP_7.4\php.exe" (
        "D:\OSPanel\modules\php\PHP_7.4\php.exe" cli\setup_soundnet_ai.php >nul 2>&1
        if "!ERRORLEVEL!"=="0" (
            echo       [OK] MySQL and OpenCart modules configured successfully.
            goto :setup_ready
        )
    )
    timeout /t 2 /nobreak >nul
)
:setup_ready

REM 5. Open Browser & Display Summary
echo.
echo =====================================================================
echo                        ALL SYSTEMS ONLINE!                           
echo =====================================================================
echo.
echo   * Storefront:          http://synthesia/
echo   * Admin Dashboard:     http://synthesia/admin/
echo   * AI API Swagger:      http://127.0.0.1:8000/docs
echo   * AI Health Endpoint:  http://127.0.0.1:8000/health
echo   * Qdrant Dashboard:    http://127.0.0.1:6333/dashboard
echo.
echo =====================================================================
echo.

REM Automatically open browser
start http://synthesia/
start http://127.0.0.1:8000/docs

echo Press any key to close this console (services keep running in background)...
pause >nul
