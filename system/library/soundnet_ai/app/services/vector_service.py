import hashlib
import logging
import math
from typing import Any

from qdrant_client import AsyncQdrantClient
from qdrant_client.models import PointStruct

from app.core.config import settings

logger = logging.getLogger('soundnet_ai.vector_service')

_st_model = None
_st_model_loaded = False


def _get_sentence_transformer():
    global _st_model, _st_model_loaded
    if not _st_model_loaded:
        try:
            from sentence_transformers import SentenceTransformer

            _st_model = SentenceTransformer(settings.embedding_model_name)
            logger.info(f'Loaded SentenceTransformer model: {settings.embedding_model_name}')
        except Exception as exc:
            logger.warning(f'Could not load SentenceTransformer ({exc}). Using deterministic fallback embedder.')
            _st_model = None
        _st_model_loaded = True
    return _st_model


def generate_text_embedding(text: str, dimension: int = 384) -> list[float]:
    '''Generates 384-d text embedding using sentence-transformers or deterministic fallback.'''
    if not text:
        return [0.0] * dimension

    model = _get_sentence_transformer()
    if model is not None:
        try:
            embedding = model.encode(text, convert_to_numpy=True, normalize_embeddings=True)
            return [float(val) for val in embedding]
        except Exception as exc:
            logger.warning(f'SentenceTransformer encoding failed ({exc}). Using fallback.')

    # Deterministic fallback embedder
    seed_bytes = hashlib.sha256(text.encode('utf-8')).digest()
    vec: list[float] = []
    for i in range(dimension):
        chunk = hashlib.sha256(seed_bytes + i.to_bytes(4, 'big')).digest()
        val = (int.from_bytes(chunk[:4], 'big') / (2**32 - 1)) * 2.0 - 1.0
        vec.append(val)

    norm = math.sqrt(sum(v * v for v in vec))
    if norm > 0:
        vec = [v / norm for v in vec]
    return vec


def normalize_audio_features(features: dict[str, Any]) -> list[float]:
    '''Normalizes 16 acoustic features (1 BPM + 1 spectral centroid + 1 ZCR + 13 MFCCs) into [-1, 1].'''
    bpm = float(features.get('bpm', 120.0))
    norm_bpm = max(min((bpm - 40.0) / 160.0, 1.0), 0.0) * 2.0 - 1.0

    sc = float(features.get('spectral_centroid_mean', 2000.0))
    norm_sc = max(min((sc - 200.0) / 4800.0, 1.0), 0.0) * 2.0 - 1.0

    zcr = float(features.get('zero_crossing_rate_mean', 0.1))
    norm_zcr = max(min(zcr / 0.5, 1.0), 0.0) * 2.0 - 1.0

    mfccs = features.get('mfcc_means', [])
    norm_mfccs: list[float] = []
    for i in range(13):
        val = float(mfccs[i]) if i < len(mfccs) else 0.0
        # tanh compression scaled for typical MFCC ranges
        norm_val = math.tanh(val / 50.0)
        norm_mfccs.append(norm_val)

    return [norm_bpm, norm_sc, norm_zcr] + norm_mfccs


def fuse_text_and_audio_vectors(
    text_vector: list[float],
    audio_features: dict[str, Any] | None,
    dimension: int = 384,
) -> list[float]:
    '''Fuses 384-d text embedding with normalized audio features preserving 384-d unit sphere.'''
    if not audio_features:
        return text_vector

    raw_audio_stats = normalize_audio_features(audio_features)
    num_features = len(raw_audio_stats)  # 16 features

    # Project 16 features across 384 dimensions (16 * 24 = 384)
    repeat_factor = dimension // num_features
    audio_vec: list[float] = []
    for i in range(dimension):
        feat_idx = (i // repeat_factor) % num_features
        # subtle phase shift across repeated blocks for distinctiveness
        block_idx = i % repeat_factor
        phase_mod = math.cos(block_idx * math.pi / repeat_factor) * 0.1
        audio_vec.append(raw_audio_stats[feat_idx] + phase_mod)

    # Normalize audio vector
    audio_norm = math.sqrt(sum(v * v for v in audio_vec))
    if audio_norm > 0:
        audio_vec = [v / audio_norm for v in audio_vec]
    else:
        audio_vec = [0.0] * dimension

    # Blend: 80% text semantics, 20% acoustic feature profile
    fused: list[float] = []
    for i in range(dimension):
        val = 0.8 * text_vector[i] + 0.2 * audio_vec[i]
        fused.append(val)

    fused_norm = math.sqrt(sum(v * v for v in fused))
    if fused_norm > 0:
        fused = [v / fused_norm for v in fused]

    return fused


async def upsert_soundnet_vector(
    qdrant_client: AsyncQdrantClient,
    product_id: int,
    entity_type: str,
    text_for_embedding: str,
    audio_features: dict[str, Any] | None = None,
    category_id: int | None = None,
    tags: str | None = None,
) -> None:
    '''Calculates embedding and upserts point into Qdrant collection soundnet_catalog.'''
    text_vector = generate_text_embedding(text_for_embedding, dimension=384)
    final_vector = fuse_text_and_audio_vectors(text_vector, audio_features, dimension=384)

    payload: dict[str, Any] = {
        'product_id': product_id,
        'entity_type': entity_type,
        'text_for_embedding': text_for_embedding,
        'has_audio': audio_features is not None,
    }
    if category_id is not None and category_id > 0:
        payload['category_id'] = category_id
    if tags:
        payload['tags'] = tags
    if audio_features is not None:
        payload['bpm'] = audio_features.get('bpm')
        payload['spectral_centroid_mean'] = audio_features.get('spectral_centroid_mean')
        payload['zero_crossing_rate_mean'] = audio_features.get('zero_crossing_rate_mean')
        payload['mfcc_means'] = audio_features.get('mfcc_means')

    point = PointStruct(
        id=product_id,
        vector=final_vector,
        payload=payload,
    )

    await qdrant_client.upsert(
        collection_name=settings.qdrant_collection_name,
        points=[point],
    )
    has_audio_flag = payload['has_audio']
    logger.info(f'Upserted product {product_id} into {settings.qdrant_collection_name} (has_audio={has_audio_flag})')


async def delete_soundnet_vector(
    qdrant_client: AsyncQdrantClient,
    product_id: int,
) -> None:
    '''Deletes point from Qdrant collection soundnet_catalog.'''
    await qdrant_client.delete(
        collection_name=settings.qdrant_collection_name,
        points_selector=[product_id],
    )
    logger.info(f'Deleted product {product_id} from {settings.qdrant_collection_name}')
