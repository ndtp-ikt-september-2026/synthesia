from qdrant_client import AsyncQdrantClient
from qdrant_client.models import Distance, HnswConfigDiff, VectorParams

from app.core.config import settings

qdrant_client: AsyncQdrantClient | None = None


async def get_qdrant_client() -> AsyncQdrantClient:
    global qdrant_client
    if qdrant_client is None:
        qdrant_client = AsyncQdrantClient(
            host=settings.qdrant_host,
            port=settings.qdrant_port,
        )
    return qdrant_client


async def close_qdrant_client() -> None:
    global qdrant_client
    if qdrant_client is not None:
        await qdrant_client.close()
        qdrant_client = None


async def init_qdrant_collection(client: AsyncQdrantClient | None = None) -> None:
    if client is None:
        client = await get_qdrant_client()

    collections_res = await client.get_collections()
    collection_names = [col.name for col in collections_res.collections]

    if settings.qdrant_collection_name not in collection_names:
        await client.create_collection(
            collection_name=settings.qdrant_collection_name,
            vectors_config=VectorParams(
                size=384,
                distance=Distance.COSINE,
                on_disk=True,
            ),
            hnsw_config=HnswConfigDiff(
                on_disk=True,
            ),
        )
