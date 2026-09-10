import logging
from typing import Any

logger = logging.getLogger('soundnet_ai.audio_service')


def extract_acoustic_features(file_path: str) -> dict[str, Any] | None:
    '''Safely extracts acoustic features from audio using librosa.

    Returns a dict with bpm, spectral_centroid_mean, zero_crossing_rate_mean,
    and 13 mfcc_means, or None on failure/corruption.
    '''
    if not file_path:
        return None

    try:
        import librosa
        import numpy as np

        # Load first 30 seconds at 22050 Hz mono
        y, sr = librosa.load(file_path, sr=22050, mono=True, duration=30.0)
        if y is None or len(y) == 0:
            logger.warning(f'Audio file {file_path} contains empty audio signal')
            return None

        # 1. BPM / Tempo
        tempo, _ = librosa.beat.beat_track(y=y, sr=sr)
        if isinstance(tempo, (np.ndarray, list)):
            bpm = float(tempo[0]) if len(tempo) > 0 else 120.0
        else:
            bpm = float(tempo)

        # 2. Spectral Centroid Mean
        spec_cent = librosa.feature.spectral_centroid(y=y, sr=sr)
        spectral_centroid_mean = float(np.mean(spec_cent))

        # 3. Zero-Crossing Rate Mean
        zcr = librosa.feature.zero_crossing_rate(y=y)
        zero_crossing_rate_mean = float(np.mean(zcr))

        # 4. 13 MFCC Means
        mfccs = librosa.feature.mfcc(y=y, sr=sr, n_mfcc=13)
        mfcc_means = [float(val) for val in np.mean(mfccs, axis=1)]

        return {
            'bpm': bpm,
            'spectral_centroid_mean': spectral_centroid_mean,
            'zero_crossing_rate_mean': zero_crossing_rate_mean,
            'mfcc_means': mfcc_means,
        }
    except Exception as exc:
        logger.warning(f'Failed to extract acoustic features from {file_path}: {exc}. Falling back to text-only.')
        return None
