from pydantic import BaseModel, Field


class SimilarTracksResponse(BaseModel):
    source_track_id: int
    similar_track_ids: list[int]
    total: int
    cached: bool = False


class MatchingInstrumentsResponse(BaseModel):
    source_track_id: int
    matching_instrument_ids: list[int]
    total: int


class SimilarAlbumsFromTracksRequest(BaseModel):
    track_ids: list[int] = Field(..., min_length=1, max_length=50)
    limit: int = Field(10, ge=1, le=50)
    genre: str | None = None


class SimilarAlbumsResponse(BaseModel):
    source_track_ids: list[int]
    similar_album_ids: list[int]
    total: int
    cached: bool = False


class SimilarAlbumsFromAlbumResponse(BaseModel):
    source_album_id: int
    similar_album_ids: list[int]
    total: int
    cached: bool = False
