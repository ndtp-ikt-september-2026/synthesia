import asyncio

from qdrant_client import AsyncQdrantClient
from qdrant_client.models import Distance, PointStruct, VectorParams

from app.core.config import settings


async def seed() -> None:
    client = AsyncQdrantClient(host=settings.qdrant_host, port=settings.qdrant_port)
    collections = await client.get_collections()
    collection_names = [c.name for c in collections.collections]

    if settings.qdrant_collection_name not in collection_names:
        await client.create_collection(
            collection_name=settings.qdrant_collection_name,
            vectors_config=VectorParams(size=4, distance=Distance.COSINE),
        )

    points = [
        PointStruct(
            id=101,
            vector=[0.1, 0.2, 0.3, 0.4],
            payload={
                'entity_type': 'track',
                'title': 'Money',
                'artist': 'Pink Floyd',
                'genre': 'rock',
                'year': 1973,
            },
        ),
        PointStruct(
            id=105,
            vector=[0.11, 0.21, 0.31, 0.41],
            payload={
                'entity_type': 'track',
                'title': 'Time',
                'artist': 'Pink Floyd',
                'genre': 'rock',
                'year': 1973,
            },
        ),
        PointStruct(
            id=203,
            vector=[0.12, 0.22, 0.32, 0.42],
            payload={
                'entity_type': 'track',
                'title': 'Shine On You Crazy Diamond',
                'artist': 'Pink Floyd',
                'genre': 'rock',
                'year': 1975,
            },
        ),
        PointStruct(
            id=45,
            vector=[0.15, 0.25, 0.35, 0.45],
            payload={
                'entity_type': 'instrument',
                'title': 'Fender Stratocaster American Pro II',
                'brand': 'Fender',
                'category': 'guitars',
            },
        ),
        PointStruct(
            id=112,
            vector=[0.16, 0.26, 0.36, 0.46],
            payload={
                'entity_type': 'instrument',
                'title': 'Hiwatt Custom 100 Head',
                'brand': 'Hiwatt',
                'category': 'amplifiers',
            },
        ),
    ]

    await client.upsert(
        collection_name=settings.qdrant_collection_name,
        points=points,
    )
    print('Seeded Qdrant sample data successfully.')
    await client.close()


if __name__ == '__main__':
    asyncio.run(seed())
