import logging
import os
import tempfile
from typing import Any

from fastapi import APIRouter, BackgroundTasks, Depends, Header, HTTPException, Request, status
from qdrant_client import AsyncQdrantClient
from redis.asyncio import Redis

from app.core.config import settings
from app.core.qdrant import get_qdrant_client
from app.core.redis import get_redis_client
from app.services.audio_service import extract_acoustic_features
from app.services.cache import invalidate_product_caches
from app.services.vector_service import delete_soundnet_vector, upsert_soundnet_vector

logger = logging.getLogger('soundnet_ai.webhooks')

router = APIRouter(prefix='/internal/webhooks', tags=['webhooks'])


async def process_product_sync_background(
    product_id: int,
    action: str,
    entity_type: str,
    text_for_embedding: str,
    temp_audio_path: str | None,
    qdrant_client: AsyncQdrantClient,
    redis_client: Redis,
    category_id: int | None = None,
    tags: str | None = None,
) -> None:
    '''Background worker to process audio, calculate embeddings, upsert to Qdrant and invalidate cache.'''
    try:
        if action == 'delete':
            await delete_soundnet_vector(qdrant_client, product_id)
            await invalidate_product_caches(redis_client, product_id)
        else:
            audio_features: dict[str, Any] | None = None
            if temp_audio_path and os.path.exists(temp_audio_path):
                audio_features = extract_acoustic_features(temp_audio_path)

            await upsert_soundnet_vector(
                qdrant_client=qdrant_client,
                product_id=product_id,
                entity_type=entity_type,
                text_for_embedding=text_for_embedding,
                audio_features=audio_features,
                category_id=category_id,
                tags=tags,
            )
            await invalidate_product_caches(redis_client, product_id)
    except Exception as exc:
        logger.error(f'Background sync failed for product {product_id}: {exc}')
    finally:
        # Purge temporary uploaded file
        if temp_audio_path and os.path.exists(temp_audio_path):
            try:
                os.remove(temp_audio_path)
                logger.debug(f'Purged temporary audio upload: {temp_audio_path}')
            except Exception as e:
                logger.warning(f'Could not remove temp file {temp_audio_path}: {e}')


@router.post('/product-sync', status_code=status.HTTP_202_ACCEPTED)
async def handle_product_sync(
    request: Request,
    background_tasks: BackgroundTasks,
    x_internal_secret: str | None = Header(None, alias='X-Internal-Secret'),
    qdrant_client: AsyncQdrantClient = Depends(get_qdrant_client),
    redis_client: Redis = Depends(get_redis_client),
) -> dict[str, str]:
    '''Webhook endpoint accepting multipart/form-data or JSON with X-Internal-Secret validation.'''
    if settings.internal_secret and x_internal_secret != settings.internal_secret:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail='Invalid or missing X-Internal-Secret header',
        )

    content_type = request.headers.get('content-type', '')
    product_id: int = 0
    action: str = 'upsert'
    entity_type: str = 'track'
    text_for_embedding: str = ''
    temp_audio_path: str | None = None

    if 'multipart/form-data' in content_type or 'application/x-www-form-urlencoded' in content_type:
        form = await request.form()
        product_id = int(form.get('product_id', 0))
        action = str(form.get('action', 'upsert'))
        entity_type = str(form.get('entity_type', 'track'))
        text_for_embedding = str(form.get('text_for_embedding', ''))

        tags_val = form.get('tags')
        tags = str(tags_val).strip() if tags_val is not None else None
        cat_val = form.get('category_id')
        category_id = int(cat_val) if cat_val is not None and str(cat_val).isdigit() else None

        audio_file = form.get('audio_file')
        if audio_file is not None and hasattr(audio_file, 'read') and hasattr(audio_file, 'filename'):
            filename = str(audio_file.filename)
            if filename:
                ext = os.path.splitext(filename)[1] or '.bin'
                fd, temp_path = tempfile.mkstemp(suffix=ext, prefix='soundnet_upload_')
                content = await audio_file.read()
                with os.fdopen(fd, 'wb') as f:
                    f.write(content)
                temp_audio_path = temp_path
    else:
        json_body = await request.json()
        product_id = int(json_body.get('product_id', 0))
        action = str(json_body.get('action', 'upsert'))
        entity_type = str(json_body.get('entity_type', 'track'))
        text_for_embedding = str(json_body.get('text_for_embedding', ''))
        tags_val = json_body.get('tags')
        tags = str(tags_val).strip() if tags_val is not None else None
        cat_val = json_body.get('category_id')
        category_id = int(cat_val) if cat_val is not None and str(cat_val).isdigit() else None

        # Support legacy payload format if present
        if not text_for_embedding and 'data' in json_body and isinstance(json_body['data'], dict):
            d = json_body['data']
            title = d.get('title', '')
            desc = d.get('description', '')
            text_for_embedding = f'{title} {desc}'.strip()
            entity_type = d.get('entity_type', 'track')
            if not tags and 'tags' in d:
                tags = str(d.get('tags', '')).strip() or None
            if category_id is None and 'category_id' in d and str(d.get('category_id')).isdigit():
                category_id = int(d.get('category_id'))
            if json_body.get('event') == 'product.deleted':
                action = 'delete'

    if not text_for_embedding and tags:
        text_for_embedding = f'Instrument Tags: {tags}'

    if product_id <= 0:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail='Invalid product_id provided',
        )

    background_tasks.add_task(
        process_product_sync_background,
        product_id=product_id,
        action=action,
        entity_type=entity_type,
        text_for_embedding=text_for_embedding,
        temp_audio_path=temp_audio_path,
        qdrant_client=qdrant_client,
        redis_client=redis_client,
        category_id=category_id,
        tags=tags,
    )

    return {
        'status': 'accepted',
        'message': 'Product sync background task scheduled',
    }
