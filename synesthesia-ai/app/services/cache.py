import hashlib
import json
from typing import Any

from redis.asyncio import Redis

from app.core.config import settings


def generate_recommendation_cache_key(track_id: int, params: dict[str, Any]) -> str:
    sorted_params = json.dumps(params, sort_keys=True)
    params_hash = hashlib.md5(sorted_params.encode('utf-8')).hexdigest()
    return f'sim:track:{track_id}:{params_hash}'


async def get_cached_recommendation(
    redis_client: Redis | None,
    cache_key: str,
) -> list[int] | None:
    if redis_client is None:
        return None
    try:
        data = await redis_client.get(cache_key)
        if data is not None:
            return json.loads(data)
    except Exception:
        pass
    return None


async def set_cached_recommendation(
    redis_client: Redis | None,
    cache_key: str,
    product_ids: list[int],
    ttl: int = settings.cache_ttl_seconds,
) -> None:
    if redis_client is None:
        return
    try:
        await redis_client.set(cache_key, json.dumps(product_ids), ex=ttl)
    except Exception:
        pass


async def invalidate_product_caches(
    redis_client: Redis | None,
    product_id: int,
) -> None:
    if redis_client is None:
        return
    try:
        pattern = f'sim:track:{product_id}:*'
        keys = await redis_client.keys(pattern)
        if keys:
            await redis_client.delete(*keys)
    except Exception:
        pass
