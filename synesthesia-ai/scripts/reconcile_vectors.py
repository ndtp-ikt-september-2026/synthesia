import asyncio
import hashlib
from typing import Any

from qdrant_client import AsyncQdrantClient

from app.core.qdrant import get_qdrant_client
from app.services.vector_search import delete_product_vector, upsert_product_vector


def compute_content_hash(title: str, description: str | None, attributes: dict[str, Any]) -> str:
    desc = description or ''
    raw = f'{title}:{desc}:{attributes}'
    return hashlib.sha256(raw.encode('utf-8')).hexdigest()


async def reconcile_vectors(
    db_rows: list[dict[str, Any]] | None = None,
    qdrant_client: AsyncQdrantClient | None = None,
) -> dict[str, int]:
    client = qdrant_client or await get_qdrant_client()
    stats = {'upserted': 0, 'deleted': 0, 'skipped': 0}

    if db_rows is None:
        db_rows = []

    for row in db_rows:
        product_id = int(row['product_id'])
        title = row.get('title', '')
        description = row.get('description')
        category_id = row.get('category_id')
        status = row.get('status', 1)
        attributes = row.get('attributes', {})
        current_hash = row.get('content_hash') or compute_content_hash(title, description, attributes)
        qdrant_hash = row.get('qdrant_hash')

        if status == 0 or row.get('vector_status') == 'deleted':
            await delete_product_vector(client, product_id)
            stats['deleted'] += 1
            continue

        if current_hash != qdrant_hash or row.get('vector_status') == 'pending':
            await upsert_product_vector(
                client,
                product_id=product_id,
                title=title,
                description=description,
                category_id=category_id,
                status=status,
                attributes=attributes,
            )
            stats['upserted'] += 1
        else:
            stats['skipped'] += 1

    return stats


if __name__ == '__main__':
    asyncio.run(reconcile_vectors())
