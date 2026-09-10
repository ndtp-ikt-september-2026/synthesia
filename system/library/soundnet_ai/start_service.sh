#!/usr/bin/env bash
# Launch helper script for SoundNet AI Python microservice daemon
cd "$(dirname "$0")"
uv run uvicorn app.main:app --host 127.0.0.1 --port 8000 --workers 1
