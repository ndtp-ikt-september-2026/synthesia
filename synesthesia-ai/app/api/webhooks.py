from fastapi import APIRouter, BackgroundTasks, Depends
from qdrant_client import AsyncQdrantClient
from redis.asyncio import Redis

from app.core.qdrant import get_qdrant_client
from app.core.redis import get_redis_client
from app.schemas.webhook import ProductSyncRequest, ProductSyncResponse
from app.services.cache import invalidate_product_caches
from app.services.vector_search import delete_product_vector, upsert_product_vector

router = APIRouter(prefix='/internal/webhooks', tags=['webhooks'])


async def process_product_sync_background(
    request: ProductSyncRequest,
    qdrant_client: AsyncQdrantClient,
    redis_client: Redis,
) -> None:
    if request.event == 'product.deleted' or (request.data and request.data.status == 0):
        await delete_product_vector(qdrant_client, request.product_id)
        await invalidate_product_caches(redis_client, request.product_id)
    else:
        if request.data:
            await upsert_product_vector(
                qdrant_client,
                product_id=request.product_id,
                title=request.data.title,
                description=request.data.description,
                category_id=request.data.category_id,
                status=request.data.status,
                entity_type=request.data.entity_type,
                track_ids=request.data.track_ids,
                attributes=request.data.attributes,
            )
            await invalidate_product_caches(redis_client, request.product_id)


@router.post('/product-sync', response_model=ProductSyncResponse, status_code=202)
async def handle_product_sync(
    request: ProductSyncRequest,
    background_tasks: BackgroundTasks,
    qdrant_client: AsyncQdrantClient = Depends(get_qdrant_client),
    redis_client: Redis = Depends(get_redis_client),
) -> ProductSyncResponse:
    background_tasks.add_task(
        process_product_sync_background,
        request=request,
        qdrant_client=qdrant_client,
        redis_client=redis_client,
    )
    return ProductSyncResponse(
        status='accepted',
        message='Webhook processing started',
    )
