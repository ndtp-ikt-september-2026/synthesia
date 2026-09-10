# synesthesia-ai

Vector similarity search and cross-domain product matching microservice powered by FastAPI, Qdrant, and Redis.

## Database Footprint (Docker Only)

Databases (`Qdrant` and `Redis`) are isolated and run exclusively inside Docker containers. Python connects as a network client to the containerized database services.

### Running Databases & Services with Docker Compose

To start Qdrant, Redis, and the FastAPI application in Docker:

```bash
docker compose up -d
```

### Running Databases in Docker for Local Python Development

To start only Qdrant and Redis in Docker while running Python locally:

```bash
docker compose up -d qdrant redis
```

Seed initial vector store data:

```bash
uv run python scripts/seed_data.py
```

Run the local development server:

```bash
uv run main.py
```

### Verification & Testing

- **Linting**: `uv run ruff check .`
- **Unit Tests**: `uv run pytest`
