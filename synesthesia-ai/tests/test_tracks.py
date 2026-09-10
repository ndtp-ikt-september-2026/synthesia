import os
from unittest.mock import AsyncMock, patch

from fastapi.testclient import TestClient

from app.main import app

client = TestClient(app)


def test_similar_tracks_success() -> None:
    mock_search_ids = [102, 105, 208, 310]

    with patch('app.api.internal_v1.get_cached_recommendation', new_callable=AsyncMock, return_value=None):
        with patch('app.api.internal_v1.search_similar_tracks', new_callable=AsyncMock, return_value=mock_search_ids):
            with patch('app.api.internal_v1.set_cached_recommendation', new_callable=AsyncMock):
                response = client.get(
                    '/internal/v1/tracks/101/similar',
                    params={
                        'limit': 10,
                        'genre': 'rock',
                        'year_from': 1970,
                        'year_to': 1980,
                    },
                )
                assert response.status_code == 200
                data = response.json()
                assert data['source_track_id'] == 101
                assert data['similar_track_ids'] == [102, 105, 208, 310]
                assert data['total'] == 4
                assert data['cached'] is False


def test_similar_tracks_cached() -> None:
    mock_cached_ids = [201, 202]

    with patch('app.api.internal_v1.get_cached_recommendation', new_callable=AsyncMock, return_value=mock_cached_ids):
        response = client.get('/internal/v1/tracks/101/similar')
        assert response.status_code == 200
        data = response.json()
        assert data['source_track_id'] == 101
        assert data['similar_track_ids'] == [201, 202]
        assert data['cached'] is True


def test_similar_tracks_not_found() -> None:
    with patch('app.api.internal_v1.get_cached_recommendation', new_callable=AsyncMock, return_value=None):
        with patch('app.api.internal_v1.search_similar_tracks', new_callable=AsyncMock, return_value=None):
            response = client.get('/internal/v1/tracks/999/similar')
            assert response.status_code == 404
            assert response.json()['detail'] == 'Track not found'


def test_matching_instruments_success() -> None:
    mock_instrument_ids = [402, 501, 788]

    with patch(
        'app.api.internal_v1.search_matching_instruments', new_callable=AsyncMock, return_value=mock_instrument_ids
    ):
        response = client.get(
            '/internal/v1/tracks/101/instruments',
            params={
                'limit': 5,
                'category_id': 12,
            },
        )
        assert response.status_code == 200
        data = response.json()
        assert data['source_track_id'] == 101
        assert data['matching_instrument_ids'] == [402, 501, 788]
        assert data['total'] == 3


def test_matching_instruments_not_found() -> None:
    with patch('app.api.internal_v1.search_matching_instruments', new_callable=AsyncMock, return_value=None):
        response = client.get('/internal/v1/tracks/999/instruments')
        assert response.status_code == 404
        assert response.json()['detail'] == 'Track not found'


def test_product_sync_webhook() -> None:
    payload = {
        'event': 'product.updated',
        'product_id': 402,
        'data': {
            'title': 'Fender Stratocaster Classic',
            'description': 'Electric guitar with bright sound',
            'category_id': 12,
            'status': 1,
            'attributes': {'genre': 'funk, rock'},
        },
    }
    with patch('app.api.webhooks.upsert_product_vector', new_callable=AsyncMock):
        with patch('app.api.webhooks.invalidate_product_caches', new_callable=AsyncMock):
            response = client.post('/internal/webhooks/product-sync', json=payload)
            assert response.status_code == 202
            data = response.json()
            assert data['status'] == 'accepted'


def test_health_check_healthy() -> None:
    mock_qdrant = AsyncMock()
    mock_redis = AsyncMock()

    with patch('app.main.get_qdrant_client', new_callable=AsyncMock, return_value=mock_qdrant):
        with patch('app.main.get_redis_client', new_callable=AsyncMock, return_value=mock_redis):
            response = client.get('/health')
            assert response.status_code == 200
            data = response.json()
            assert data['status'] == 'ok'
            assert data['qdrant'] == 'healthy'
            assert data['redis'] == 'healthy'


def test_no_double_quotes_in_python_code() -> None:
    root_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
    py_files = []
    for dirpath, _, filenames in os.walk(root_dir):
        if '.venv' in dirpath or '.git' in dirpath:
            continue
        for filename in filenames:
            if filename.endswith('.py'):
                py_files.append(os.path.join(dirpath, filename))

    dq = chr(34)
    violations = []

    for filepath in py_files:
        with open(filepath, encoding='utf-8') as f:
            lines = f.readlines()
            for line_idx, line in enumerate(lines, 1):
                stripped = line.strip()
                if stripped.startswith('#'):
                    continue
                if dq in line:
                    violations.append(f'{filepath}:{line_idx}: {line.strip()}')

    assert not violations, 'Double quote violations found in Python files:\n' + '\n'.join(violations)
