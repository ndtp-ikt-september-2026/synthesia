from contextlib import asynccontextmanager

from fastapi import FastAPI, HTTPException, status

from app.api.internal_v1 import router as internal_v1_router
from app.api.webhooks import router as webhooks_router
from app.core.qdrant import close_qdrant_client, get_qdrant_client, init_qdrant_collection
from app.core.redis import close_redis_client, get_redis_client


@asynccontextmanager
async def lifespan(app: FastAPI):
    try:
        client = await get_qdrant_client()
        await init_qdrant_collection(client)
    except Exception:
        pass
    yield
    await close_qdrant_client()
    await close_redis_client()


app = FastAPI(
    title='soundnet-ai',
    version='0.1.0',
    description='SoundNet AI vector similarity and acoustic feature microservice',
    lifespan=lifespan,
)

app.include_router(internal_v1_router)
app.include_router(webhooks_router)


@app.get('/health')
async def health_check() -> dict[str, str]:
    qdrant_status = 'unhealthy'
    redis_status = 'unhealthy'

    try:
        qdrant_client = await get_qdrant_client()
        await qdrant_client.get_collections()
        qdrant_status = 'healthy'
    except Exception:
        pass

    try:
        redis_client = await get_redis_client()
        await redis_client.ping()
        redis_status = 'healthy'
    except Exception:
        pass

    if qdrant_status != 'healthy' or redis_status != 'healthy':
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail={
                'status': 'unhealthy',
                'qdrant': qdrant_status,
                'redis': redis_status,
            },
        )

    return {
        'status': 'ok',
        'qdrant': qdrant_status,
        'redis': redis_status,
    }
