@echo off
rem Launch helper script for SoundNet AI Python microservice daemon
cd /d "%~dp0"
uv run uvicorn app.main:app --host 127.0.0.1 --port 8000 --workers 1
