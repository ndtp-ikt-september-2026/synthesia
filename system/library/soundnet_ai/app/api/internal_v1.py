from fastapi import APIRouter, Depends, HTTPException, Query
from qdrant_client import AsyncQdrantClient
from redis.asyncio import Redis

from app.core.qdrant import get_qdrant_client
from app.core.redis import get_redis_client
from app.schemas.recommendation import (
    MatchingInstrumentsResponse,
    SimilarAlbumsFromAlbumResponse,
    SimilarAlbumsFromTracksRequest,
    SimilarAlbumsResponse,
    SimilarTracksResponse,
)
from app.services.cache import (
    generate_recommendation_cache_key,
    get_cached_recommendation,
    set_cached_recommendation,
)
from app.services.vector_search import (
    search_matching_instruments,
    search_similar_albums_from_album,
    search_similar_albums_from_tracks,
    search_similar_tracks,
)

router = APIRouter(prefix='/internal/v1', tags=['internal_v1'])


@router.get('/tracks/{track_id}/similar', response_model=SimilarTracksResponse)
async def get_similar_tracks(
    track_id: int,
    limit: int = Query(10, ge=1, le=50),
    genre: str | None = Query(None),
    year_from: int | None = Query(None),
    year_to: int | None = Query(None),
    qdrant_client: AsyncQdrantClient = Depends(get_qdrant_client),
    redis_client: Redis = Depends(get_redis_client),
) -> SimilarTracksResponse:
    params = {
        'limit': limit,
        'genre': genre,
        'year_from': year_from,
        'year_to': year_to,
    }
    cache_key = generate_recommendation_cache_key(track_id, params)
    cached_ids = await get_cached_recommendation(redis_client, cache_key)
    if cached_ids is not None:
        return SimilarTracksResponse(
            source_track_id=track_id,
            similar_track_ids=cached_ids,
            total=len(cached_ids),
            cached=True,
        )

    similar_ids = await search_similar_tracks(
        qdrant_client=qdrant_client,
        track_id=track_id,
        limit=limit,
        genre=genre,
        year_from=year_from,
        year_to=year_to,
    )

    if similar_ids is None:
        raise HTTPException(
            status_code=404,
            detail='Track not found',
        )

    await set_cached_recommendation(
        redis_client=redis_client,
        cache_key=cache_key,
        product_ids=similar_ids,
    )

    return SimilarTracksResponse(
        source_track_id=track_id,
        similar_track_ids=similar_ids,
        total=len(similar_ids),
        cached=False,
    )


@router.get('/tracks/{track_id}/instruments', response_model=MatchingInstrumentsResponse)
async def get_matching_instruments(
    track_id: int,
    limit: int = Query(5, ge=1, le=20),
    category_id: int | None = Query(None),
    genre: str | None = Query(None),
    vibe: str | None = Query(None),
    artist: str | None = Query(None),
    qdrant_client: AsyncQdrantClient = Depends(get_qdrant_client),
) -> MatchingInstrumentsResponse:
    instrument_ids = await search_matching_instruments(
        qdrant_client=qdrant_client,
        track_id=track_id,
        limit=limit,
        category_id=category_id,
        genre=genre,
        vibe=vibe,
        artist=artist,
    )

    if instrument_ids is None:
        raise HTTPException(
            status_code=404,
            detail='Track not found',
        )

    return MatchingInstrumentsResponse(
        source_track_id=track_id,
        matching_instrument_ids=instrument_ids,
        total=len(instrument_ids),
    )


@router.post('/albums/similar-from-tracks', response_model=SimilarAlbumsResponse)
async def get_similar_albums_from_tracks(
    body: SimilarAlbumsFromTracksRequest,
    qdrant_client: AsyncQdrantClient = Depends(get_qdrant_client),
) -> SimilarAlbumsResponse:
    album_ids = await search_similar_albums_from_tracks(
        qdrant_client=qdrant_client,
        track_ids=body.track_ids,
        limit=body.limit,
        genre=body.genre,
    )

    if album_ids is None:
        raise HTTPException(
            status_code=404,
            detail='No vectors found for specified tracks',
        )

    return SimilarAlbumsResponse(
        source_track_ids=body.track_ids,
        similar_album_ids=album_ids,
        total=len(album_ids),
    )


@router.get('/albums/{album_id}/similar', response_model=SimilarAlbumsFromAlbumResponse)
async def get_similar_albums_from_album(
    album_id: int,
    limit: int = Query(10, ge=1, le=50),
    genre: str | None = Query(None),
    qdrant_client: AsyncQdrantClient = Depends(get_qdrant_client),
) -> SimilarAlbumsFromAlbumResponse:
    album_ids = await search_similar_albums_from_album(
        qdrant_client=qdrant_client,
        album_id=album_id,
        limit=limit,
        genre=genre,
    )

    if album_ids is None:
        raise HTTPException(
            status_code=404,
            detail='Album not found',
        )

    return SimilarAlbumsFromAlbumResponse(
        source_album_id=album_id,
        similar_album_ids=album_ids,
        total=len(album_ids),
    )

