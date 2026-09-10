# SoundNet AI + OpenCart Unified PowerShell Launcher
$ErrorActionPreference = 'Continue'

Write-Host '===================================================' -ForegroundColor Cyan
Write-Host '      SoundNet AI + OpenCart Unified Launcher       ' -ForegroundColor Cyan
Write-Host '===================================================' -ForegroundColor Cyan

$rootDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $rootDir

# 1. Start Open Server Panel if present and not running
$ospProcess = Get-Process -Name 'Open Server Panel' -ErrorAction SilentlyContinue
if ($ospProcess) {
    Write-Host '[OK] Open Server Panel is running.' -ForegroundColor Green
} else {
    $ospExe = 'D:\OSPanel\Open Server Panel.exe'
    if (Test-Path $ospExe) {
        Write-Host '[INFO] Starting Open Server Panel...' -ForegroundColor Yellow
        Start-Process $ospExe
    } else {
        Write-Host '[NOTICE] Open Server Panel executable not found at D:\OSPanel\' -ForegroundColor DarkYellow
    }
}

# 2. Launch Docker Services (Qdrant, Redis, SoundNet AI)
Write-Host '[INFO] Starting Docker containers (Qdrant, Redis, SoundNet AI)...' -ForegroundColor Cyan
docker compose up --build -d

if ($LASTEXITCODE -eq 0) {
    Write-Host '[OK] Docker containers launched successfully.' -ForegroundColor Green
} else {
    Write-Host '[WARN] Docker compose failed or Docker is offline. Falling back to local daemon...' -ForegroundColor Yellow
    Start-Process cmd.exe -ArgumentList '/k "cd /d system\library\soundnet_ai && uv run uvicorn app.main:app --host 127.0.0.1 --port 8000 --workers 1"'
}

# 3. Wait for services
Write-Host '[INFO] Waiting 5 seconds for services to initialize...' -ForegroundColor Gray
Start-Sleep -Seconds 5

# 4. Run Migration and Module Auto-Configuration
$phpExe = 'D:\OSPanel\modules\php\PHP_7.4\php.exe'
if (Test-Path $phpExe) {
    Write-Host '[INFO] Running database migrations & OpenCart module auto-registration...' -ForegroundColor Cyan
    & $phpExe cli\setup_soundnet_ai.php
}

Write-Host ''
Write-Host '===================================================' -ForegroundColor Green
Write-Host '                 STACK IS READY!                    ' -ForegroundColor Green
Write-Host '===================================================' -ForegroundColor Green
Write-Host '- Storefront:       http://synthesia/' -ForegroundColor White
Write-Host '- Admin Panel:      http://synthesia/admin/' -ForegroundColor White
Write-Host '- FastAPI Docs:     http://127.0.0.1:8000/docs' -ForegroundColor White
Write-Host '- FastAPI Health:   http://127.0.0.1:8000/health' -ForegroundColor White
Write-Host '- Qdrant Dashboard: http://127.0.0.1:6333/dashboard' -ForegroundColor White
Write-Host '===================================================' -ForegroundColor Green
