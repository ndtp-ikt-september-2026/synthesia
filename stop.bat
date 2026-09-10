@echo off
setlocal enabledelayedexpansion
title Synesthesia - Graceful Shutdown

echo =====================================================================
echo                SYNESTHESIA - GRACEFUL SHUTDOWN                       
echo =====================================================================
echo.

cd /d "%~dp0"

echo [1/2] Stopping Docker containers (Qdrant, Redis, SoundNet AI)...
docker compose down
echo       [OK] Docker containers stopped.

echo.
echo [2/2] Services stopped.
echo Note: Open Server Panel remains running in the system tray if you need it.
echo.
pause
