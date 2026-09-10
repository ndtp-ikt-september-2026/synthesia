from unittest.mock import AsyncMock, patch

from fastapi.testclient import TestClient

from app.main import app
from app.services.vector_search import compute_centroid_vector

client = TestClient(app)


def test_compute_centroid_vector_valid() -> None:
    vec1 = [1.0, 0.0, 0.0]
    vec2 = [0.0, 1.0, 0.0]
    centroid = compute_centroid_vector([vec1, vec2])
    assert centroid is not None
    assert len(centroid) == 3
    assert round(centroid[0], 4) == 0.7071
    assert round(centroid[1], 4) == 0.7071
    assert round(centroid[2], 4) == 0.0


def test_compute_centroid_vector_empty() -> None:
    assert compute_centroid_vector([]) is None


def test_similar_albums_from_tracks_success() -> None:
    mock_album_ids = [501, 502, 503]

    with patch(
        'app.api.internal_v1.search_similar_albums_from_tracks',
        new_callable=AsyncMock,
        return_value=mock_album_ids,
    ):
        response = client.post(
            '/internal/v1/albums/similar-from-tracks',
            json={
                'track_ids': [101, 102, 103],
                'limit': 5,
                'genre': 'jazz',
            },
        )
        assert response.status_code == 200
        data = response.json()
        assert data['source_track_ids'] == [101, 102, 103]
        assert data['similar_album_ids'] == [501, 502, 503]
        assert data['total'] == 3


def test_similar_albums_from_tracks_not_found() -> None:
    with patch(
        'app.api.internal_v1.search_similar_albums_from_tracks',
        new_callable=AsyncMock,
        return_value=None,
    ):
        response = client.post(
            '/internal/v1/albums/similar-from-tracks',
            json={
                'track_ids': [999, 998],
                'limit': 5,
            },
        )
        assert response.status_code == 404
        assert response.json()['detail'] == 'No vectors found for specified tracks'


def test_similar_albums_from_album_success() -> None:
    mock_album_ids = [502, 505]

    with patch(
        'app.api.internal_v1.search_similar_albums_from_album',
        new_callable=AsyncMock,
        return_value=mock_album_ids,
    ):
        response = client.get('/internal/v1/albums/501/similar?limit=5')
        assert response.status_code == 200
        data = response.json()
        assert data['source_album_id'] == 501
        assert data['similar_album_ids'] == [502, 505]
        assert data['total'] == 2


def test_similar_albums_from_album_not_found() -> None:
    with patch(
        'app.api.internal_v1.search_similar_albums_from_album',
        new_callable=AsyncMock,
        return_value=None,
    ):
        response = client.get('/internal/v1/albums/999/similar')
        assert response.status_code == 404
        assert response.json()['detail'] == 'Album not found'
