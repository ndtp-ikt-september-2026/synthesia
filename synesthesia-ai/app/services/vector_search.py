import math
from typing import Any

from qdrant_client import AsyncQdrantClient
from qdrant_client.models import FieldCondition, Filter, MatchValue, PointStruct, Range

from app.core.config import settings
from app.services.embedder import generate_embedding


def compute_centroid_vector(vectors: list[list[float]]) -> list[float] | None:
    if not vectors:
        return None

    dim = len(vectors[0])
    sum_vec = [0.0] * dim

    for vec in vectors:
        if len(vec) != dim:
            continue
        for i in range(dim):
            sum_vec[i] += vec[i]

    norm = math.sqrt(sum(v * v for v in sum_vec))
    if norm == 0:
        return None

    return [v / norm for v in sum_vec]


async def _get_track_vector(
    qdrant_client: AsyncQdrantClient,
    track_id: int,
) -> list[float] | None:
    try:
        points = await qdrant_client.retrieve(
            collection_name=settings.qdrant_collection_name,
            ids=[track_id],
            with_vectors=True,
        )
    except Exception:
        return None

    if not points:
        return None

    vector = points[0].vector
    if isinstance(vector, dict):
        for v in vector.values():
            if isinstance(v, list):
                return v
        return None
    elif isinstance(vector, list):
        return vector
    return None


async def _get_multiple_track_vectors(
    qdrant_client: AsyncQdrantClient,
    track_ids: list[int],
) -> list[list[float]]:
    try:
        points = await qdrant_client.retrieve(
            collection_name=settings.qdrant_collection_name,
            ids=track_ids,
            with_vectors=True,
        )
    except Exception:
        return []

    vectors: list[list[float]] = []
    for point in points:
        vector = point.vector
        if isinstance(vector, dict):
            for v in vector.values():
                if isinstance(v, list):
                    vectors.append(v)
                    break
        elif isinstance(vector, list):
            vectors.append(vector)
    return vectors


async def search_similar_tracks(
    qdrant_client: AsyncQdrantClient,
    track_id: int,
    limit: int = 10,
    genre: str | None = None,
    year_from: int | None = None,
    year_to: int | None = None,
) -> list[int] | None:
    vector = await _get_track_vector(qdrant_client, track_id)
    if vector is None:
        return None

    must_conditions: list[FieldCondition] = [
        FieldCondition(
            key='entity_type',
            match=MatchValue(value='track'),
        )
    ]

    if genre:
        must_conditions.append(
            FieldCondition(
                key='genre',
                match=MatchValue(value=genre),
            )
        )
    if year_from is not None or year_to is not None:
        must_conditions.append(
            FieldCondition(
                key='year',
                range=Range(
                    gte=year_from,
                    lte=year_to,
                ),
            )
        )

    search_filter = Filter(must=must_conditions)

    hits = await qdrant_client.search(
        collection_name=settings.qdrant_collection_name,
        query_vector=vector,
        query_filter=search_filter,
        limit=limit + 5,
    )

    results: list[int] = []
    for hit in hits:
        hit_id = int(hit.id)
        if hit_id == track_id:
            continue
        results.append(hit_id)
        if len(results) >= limit:
            break

    return results


async def search_matching_instruments(
    qdrant_client: AsyncQdrantClient,
    track_id: int,
    limit: int = 5,
    category_id: int | None = None,
) -> list[int] | None:
    vector = await _get_track_vector(qdrant_client, track_id)
    if vector is None:
        return None

    must_conditions: list[FieldCondition] = [
        FieldCondition(
            key='entity_type',
            match=MatchValue(value='instrument'),
        )
    ]

    if category_id is not None:
        must_conditions.append(
            FieldCondition(
                key='category_id',
                match=MatchValue(value=category_id),
            )
        )

    search_filter = Filter(must=must_conditions)

    hits = await qdrant_client.search(
        collection_name=settings.qdrant_collection_name,
        query_vector=vector,
        query_filter=search_filter,
        limit=limit,
    )

    results: list[int] = [int(hit.id) for hit in hits]
    return results


async def search_similar_albums_from_tracks(
    qdrant_client: AsyncQdrantClient,
    track_ids: list[int],
    limit: int = 10,
    genre: str | None = None,
) -> list[int] | None:
    vectors = await _get_multiple_track_vectors(qdrant_client, track_ids)
    if not vectors:
        return None

    centroid = compute_centroid_vector(vectors)
    if centroid is None:
        return None

    must_conditions: list[FieldCondition] = [
        FieldCondition(
            key='entity_type',
            match=MatchValue(value='album'),
        )
    ]
    if genre:
        must_conditions.append(
            FieldCondition(
                key='genre',
                match=MatchValue(value=genre),
            )
        )

    search_filter = Filter(must=must_conditions)

    hits = await qdrant_client.search(
        collection_name=settings.qdrant_collection_name,
        query_vector=centroid,
        query_filter=search_filter,
        limit=limit,
    )

    return [int(hit.id) for hit in hits]


async def search_similar_albums_from_album(
    qdrant_client: AsyncQdrantClient,
    album_id: int,
    limit: int = 10,
    genre: str | None = None,
) -> list[int] | None:
    vector = await _get_track_vector(qdrant_client, album_id)
    if vector is None:
        return None

    must_conditions: list[FieldCondition] = [
        FieldCondition(
            key='entity_type',
            match=MatchValue(value='album'),
        )
    ]
    if genre:
        must_conditions.append(
            FieldCondition(
                key='genre',
                match=MatchValue(value=genre),
            )
        )

    search_filter = Filter(must=must_conditions)

    hits = await qdrant_client.search(
        collection_name=settings.qdrant_collection_name,
        query_vector=vector,
        query_filter=search_filter,
        limit=limit + 1,
    )

    results: list[int] = []
    for hit in hits:
        hit_id = int(hit.id)
        if hit_id == album_id:
            continue
        results.append(hit_id)
        if len(results) >= limit:
            break

    return results


async def upsert_product_vector(
    qdrant_client: AsyncQdrantClient,
    product_id: int,
    title: str,
    description: str | None = None,
    category_id: int | None = None,
    status: int = 1,
    entity_type: str | None = None,
    track_ids: list[int] | None = None,
    attributes: dict[str, Any] | None = None,
) -> None:
    attrs = attributes or {}
    desc = description or ''
    cat = category_id or ''

    resolved_entity_type = entity_type or attrs.get('entity_type')
    if not resolved_entity_type:
        resolved_entity_type = 'track' if category_id != 12 else 'instrument'

    vector: list[float] | None = None

    if resolved_entity_type == 'album' and track_ids:
        track_vectors = await _get_multiple_track_vectors(qdrant_client, track_ids)
        if track_vectors:
            vector = compute_centroid_vector(track_vectors)

    if vector is None:
        text_content = f'{title} {desc} {cat} {attrs}'
        vector = generate_embedding(text_content)

    payload: dict[str, Any] = {
        'product_id': product_id,
        'title': title,
        'description': description,
        'category_id': category_id,
        'status': status,
        'entity_type': resolved_entity_type,
        'attributes': attrs,
    }
    if track_ids:
        payload['track_ids'] = track_ids
    if 'genre' in attrs:
        payload['genre'] = attrs['genre']
    if 'year' in attrs:
        payload['year'] = attrs['year']

    point = PointStruct(
        id=product_id,
        vector=vector,
        payload=payload,
    )

    await qdrant_client.upsert(
        collection_name=settings.qdrant_collection_name,
        points=[point],
    )


async def delete_product_vector(
    qdrant_client: AsyncQdrantClient,
    product_id: int,
) -> None:
    await qdrant_client.delete(
        collection_name=settings.qdrant_collection_name,
        points_selector=[product_id],
    )

