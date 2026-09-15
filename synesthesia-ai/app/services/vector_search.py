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


async def _execute_vector_query(
    qdrant_client: AsyncQdrantClient,
    collection_name: str,
    vector: list[float],
    query_filter: Filter | None = None,
    limit: int = 10,
) -> list[Any]:
    '''Executes vector search supporting both query_points (qdrant-client >=1.10) and legacy search.'''
    if hasattr(qdrant_client, 'query_points'):
        res = await qdrant_client.query_points(
            collection_name=collection_name,
            query=vector,
            query_filter=query_filter,
            limit=limit,
        )
        return list(res.points)
    elif hasattr(qdrant_client, 'search'):
        res = await qdrant_client.search(
            collection_name=collection_name,
            query_vector=vector,
            query_filter=query_filter,
            limit=limit,
        )
        return list(res)
    return []


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

    hits = await _execute_vector_query(
        qdrant_client=qdrant_client,
        collection_name=settings.qdrant_collection_name,
        vector=vector,
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


try:
    from app.services.vector_service import generate_text_embedding
except ImportError:
    from app.services.embedder import generate_embedding as generate_text_embedding


def _compute_sound_affinity(inst_payload: dict[str, Any], genre: str | None, vibe: str | None) -> float:
    '''Calculates acoustic and genre affinity score adjustments for instrument ranking.'''
    text = str(inst_payload.get('text_for_embedding', '')).lower()
    cat_id = inst_payload.get('category_id')
    try:
        cat_id = int(cat_id) if cat_id is not None else 0
    except (ValueError, TypeError):
        cat_id = 0

    g = (genre or '').lower()
    v = (vibe or '').lower()

    bonus = 0.0

    # Classify track sound profile
    is_heavy_or_rock = any(w in g or w in v for w in ['rock', 'metal', 'thrash', 'grunge', 'punk', 'heavy', 'hard rock', 'alternative'])
    is_acoustic_track = any(w in g or w in v for w in ['acoustic', 'folk', 'country', 'fingerstyle', 'unplugged'])
    is_electronic_track = any(w in g or w in v for w in ['electronic', 'techno', 'house', 'synth', 'electro', 'ambient', 'trance', 'dance', 'edm', 'club'])

    # Classify instrument hardware type
    is_electric_guitar = (cat_id == 15 or 'электрогитар' in text or 'h+h' in text or 'superstrat' in text or 'hss' in text)
    is_acoustic_guitar = (cat_id in (12, 14) or 'акустическ' in text or 'классическ' in text or 'дредноут' in text)
    is_synth = (cat_id in (22, 24) or 'синтезатор' in text or 'midi' in text or 'volca' in text)
    is_pedal_or_amp = (cat_id in (16, 19, 20) or 'педаль' in text or 'кабинет' in text or 'усилител' in text)
    is_bass = (cat_id == 13 or 'бас-гитар' in text)

    if is_electronic_track:
        if is_synth:
            bonus += 0.40
        elif is_pedal_or_amp and any(w in text for w in ['ambient', 'delay', 'reverb', 'modulation']):
            bonus += 0.20
        elif is_acoustic_guitar:
            bonus -= 0.40
        elif is_electric_guitar:
            bonus -= 0.25
    elif is_heavy_or_rock and not is_acoustic_track:
        if is_electric_guitar:
            bonus += 0.35
        if is_pedal_or_amp:
            bonus += 0.25
        if is_bass:
            bonus += 0.25
        if ('metal' in v or 'thrash' in v) and any(w in text for w in ['metal', 'h+h', 'hard rock', 'heavy']):
            bonus += 0.20
        if 'grunge' in v and any(w in text for w in ['alternative', 'grunge', 'rock', 'overdrive', 'distortion', 'ses-55', 'raptor']):
            bonus += 0.18
        if is_acoustic_guitar:
            bonus -= 0.45
        if is_synth:
            bonus -= 0.30
    elif is_acoustic_track:
        if is_acoustic_guitar:
            bonus += 0.40
        if is_electric_guitar:
            bonus -= 0.30
        if is_synth:
            bonus -= 0.30

    # Direct keyword matches between track vibe and instrument style text
    for word in ['metal', 'grunge', 'alternative', 'blues', 'funk', 'hard rock', 'ambient', 'synthwave', 'rock & roll', 'punk', 'heavy metal', 'thrash', 'jazz']:
        if word in v and word in text:
            bonus += 0.12

    return bonus


async def search_matching_instruments(
    qdrant_client: AsyncQdrantClient,
    track_id: int,
    limit: int = 5,
    category_id: int | None = None,
    genre: str | None = None,
    vibe: str | None = None,
    artist: str | None = None,
) -> list[int] | None:
    try:
        points = await qdrant_client.retrieve(
            collection_name=settings.qdrant_collection_name,
            ids=[track_id],
            with_payload=True,
            with_vectors=True,
        )
    except Exception:
        points = []

    track_vector = None
    if points:
        track_point = points[0]
        raw_v = track_point.vector
        if isinstance(raw_v, dict):
            for v in raw_v.values():
                if isinstance(v, list):
                    track_vector = v
                    break
        elif isinstance(raw_v, list):
            track_vector = raw_v

        payload = track_point.payload or {}
        text_for_emb = payload.get('text_for_embedding', '')
        if not genre or not vibe or not artist:
            for part in text_for_emb.split('|'):
                p = part.strip()
                if not artist and p.startswith('Artist:'):
                    artist = p.replace('Artist:', '').strip()
                elif not genre and p.startswith('Genre:'):
                    genre = p.replace('Genre:', '').strip()
                elif not vibe and p.startswith('Vibe:'):
                    vibe = p.replace('Vibe:', '').strip()

    # Build music equipment search query based on genre & vibe
    g_lower = (genre or '').lower()
    v_lower = (vibe or '').lower()

    gear_intent = []
    if any(w in g_lower or w in v_lower for w in ['rock', 'metal', 'thrash', 'grunge', 'punk', 'heavy']):
        gear_intent.append('electric guitars, rock amplifiers, overdrive pedals, bass guitars')
    elif any(w in g_lower or w in v_lower for w in ['electronic', 'techno', 'house', 'synth', 'electro', 'ambient', 'trance', 'dance']):
        gear_intent.append('synthesizers, drum machines, midi controllers, analog synths, ambient pedals')
    elif any(w in g_lower or w in v_lower for w in ['acoustic', 'folk', 'country', 'fingerstyle', 'unplugged']):
        gear_intent.append('acoustic guitars, classical guitars, folk instruments')
    elif any(w in g_lower or w in v_lower for w in ['jazz', 'blues', 'soul']):
        gear_intent.append('jazz guitars, blues electric guitars, tube amps, pianos')
    else:
        gear_intent.append('musical instruments, guitars, keyboards')

    query_parts = []
    if genre:
        query_parts.append(f'Genre: {genre}')
    if vibe:
        query_parts.append(f'Style: {vibe}')
    if artist:
        query_parts.append(f'Artist: {artist}')
    if gear_intent:
        query_parts.append(f'Instruments: {", ".join(gear_intent)}')

    if query_parts:
        sound_query_text = ' | '.join(query_parts)
        query_vector = generate_text_embedding(sound_query_text)
    elif track_vector:
        query_vector = track_vector
    else:
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

    candidate_limit = max(limit * 8, 50)
    hits = await _execute_vector_query(
        qdrant_client=qdrant_client,
        collection_name=settings.qdrant_collection_name,
        vector=query_vector,
        query_filter=search_filter,
        limit=candidate_limit,
    )

    if not hits:
        return []

    scored_hits = []
    for hit in hits:
        hit_id = int(hit.id)
        hit_payload = getattr(hit, 'payload', None) or {}
        hit_score = float(getattr(hit, 'score', 0.0) or 0.0)
        affinity = _compute_sound_affinity(hit_payload, genre, vibe)
        scored_hits.append((hit_score + affinity, hit_id))

    scored_hits.sort(key=lambda x: x[0], reverse=True)
    results: list[int] = [h[1] for h in scored_hits[:limit]]
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

    hits = await _execute_vector_query(
        qdrant_client=qdrant_client,
        collection_name=settings.qdrant_collection_name,
        vector=centroid,
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

    hits = await _execute_vector_query(
        qdrant_client=qdrant_client,
        collection_name=settings.qdrant_collection_name,
        vector=vector,
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

