@echo off
setlocal enabledelayedexpansion

cd /d "%~dp0"

echo [SoundNet AI] Starting Docker containers (Qdrant, Redis, AI Daemon)...
docker compose up -d

REM Wait for MySQL and run database migration and module verification in background
start /b "" cmd /c "timeout /t 5 /nobreak >nul && if exist D:\OSPanel\modules\php\PHP_7.4\php.exe D:\OSPanel\modules\php\PHP_7.4\php.exe cli\setup_soundnet_ai.php > setup.log 2>&1"

if not defined progdir (
    if not "%1"=="/silent" (
        echo.
        echo ===================================================
        echo                 ALL SERVICES STARTED!              
        echo ===================================================
        echo - OpenCart Store:   http://synthesia/
        echo - OpenCart Admin:   http://synthesia/admin/
        echo - FastAPI Daemon:   http://127.0.0.1:8000/docs
        echo - Qdrant Dashboard: http://127.0.0.1:6333/dashboard
        echo ===================================================
        echo.
        timeout /t 5 >nul
    )
)
