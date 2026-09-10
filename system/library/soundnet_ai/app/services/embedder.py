import hashlib
import math


def generate_embedding(text: str, dimension: int = 384) -> list[float]:
    if not text:
        return [0.0] * dimension

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
