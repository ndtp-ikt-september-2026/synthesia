import os
from unittest.mock import AsyncMock, patch

from fastapi.testclient import TestClient

from app.core.config import settings
from app.main import app
from app.services.audio_service import extract_acoustic_features
from app.services.vector_service import (
    fuse_text_and_audio_vectors,
    generate_text_embedding,
    normalize_audio_features,
)

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


def test_product_sync_webhook_json() -> None:
    payload = {
        'product_id': 402,
        'action': 'upsert',
        'entity_type': 'track',
        'text_for_embedding': 'Pink Floyd - Time Progressive Rock 1973',
    }
    with patch('app.api.webhooks.upsert_soundnet_vector', new_callable=AsyncMock):
        with patch('app.api.webhooks.invalidate_product_caches', new_callable=AsyncMock):
            response = client.post(
                '/internal/webhooks/product-sync',
                json=payload,
                headers={'X-Internal-Secret': settings.internal_secret},
            )
            assert response.status_code == 202
            data = response.json()
            assert data['status'] == 'accepted'


def test_instrument_sync_webhook_with_tags() -> None:
    payload = {
        'product_id': 701,
        'action': 'upsert',
        'entity_type': 'instrument',
        'text_for_embedding': 'Instrument: Fender Stratocaster | Brand: Fender | Type: Electric Guitar | Tags: fender, stratocaster, alder body, maple neck, single coil, vintage vibe | Characteristics: Neck: Maple, Body: Alder, Pickups: Single Coil',
        'tags': 'fender, stratocaster, alder body, maple neck, single coil, vintage vibe',
        'category_id': 15,
    }
    with patch('app.api.webhooks.upsert_soundnet_vector', new_callable=AsyncMock) as mock_upsert:
        with patch('app.api.webhooks.invalidate_product_caches', new_callable=AsyncMock):
            response = client.post(
                '/internal/webhooks/product-sync',
                json=payload,
                headers={'X-Internal-Secret': settings.internal_secret},
            )
            assert response.status_code == 202
            data = response.json()
            assert data['status'] == 'accepted'
            mock_upsert.assert_called_once()
            call_kwargs = mock_upsert.call_args.kwargs
            assert call_kwargs['product_id'] == 701
            assert call_kwargs['entity_type'] == 'instrument'
            assert call_kwargs['tags'] == 'fender, stratocaster, alder body, maple neck, single coil, vintage vibe'
            assert call_kwargs['category_id'] == 15
            assert 'Instrument: Fender Stratocaster' in call_kwargs['text_for_embedding']


def test_product_sync_webhook_unauthorized() -> None:
    payload = {
        'product_id': 402,
        'action': 'upsert',
        'text_for_embedding': 'Unauthorized track',
    }
    response = client.post(
        '/internal/webhooks/product-sync',
        json=payload,
        headers={'X-Internal-Secret': 'wrong_secret'},
    )
    assert response.status_code == 403


def test_product_sync_webhook_multipart() -> None:
    with patch('app.api.webhooks.upsert_soundnet_vector', new_callable=AsyncMock):
        with patch('app.api.webhooks.invalidate_product_caches', new_callable=AsyncMock):
            response = client.post(
                '/internal/webhooks/product-sync',
                data={
                    'product_id': 505,
                    'action': 'upsert',
                    'entity_type': 'track',
                    'text_for_embedding': 'Test Track with Audio',
                },
                files={
                    'audio_file': ('sample.wav', b'RIFFdemoWAVEfmt data', 'audio/wav'),
                },
                headers={'X-Internal-Secret': settings.internal_secret},
            )
            assert response.status_code == 202
            data = response.json()
            assert data['status'] == 'accepted'


def test_extract_acoustic_features_corrupt_file() -> None:
    # Corrupt or non-existent audio file should return None and not raise exception
    res = extract_acoustic_features('non_existent_file.wav')
    assert res is None


def test_vector_fusion_text_only() -> None:
    text = 'Solaris Ambient Drone Soundscape'
    vec = generate_text_embedding(text, dimension=384)
    assert len(vec) == 384
    fused = fuse_text_and_audio_vectors(vec, None, dimension=384)
    assert len(fused) == 384
    assert fused == vec


def test_vector_fusion_with_audio_stats() -> None:
    text = 'Dark Techno Club Mix'
    text_vec = generate_text_embedding(text, dimension=384)
    fake_audio_stats = {
        'bpm': 135.0,
        'spectral_centroid_mean': 2800.0,
        'zero_crossing_rate_mean': 0.12,
        'mfcc_means': [10.0, -5.0, 3.0, 2.0, -1.0, 0.5, -0.2, 0.1, -0.1, 0.05, -0.02, 0.01, 0.0],
    }
    normalized = normalize_audio_features(fake_audio_stats)
    assert len(normalized) == 16

    fused = fuse_text_and_audio_vectors(text_vec, fake_audio_stats, dimension=384)
    assert len(fused) == 384
    # Ensure fused vector differs from pure text vector due to acoustic blending
    assert fused != text_vec


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
        if '.venv' in dirpath or '.git' in dirpath or '__pycache__' in dirpath:
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
