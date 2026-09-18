# Synesthesia AI Vector Engine Module Specification

`synesthesia-ai` is an asynchronous microservice powering cross-domain product recommendations, semantic catalog search, and acoustic similarity discovery for the Synthesia platform.

---

## 1. Architectural Overview

```
┌──────────────────────────────────────┐
│       OpenCart Core (PHP)            │
│   • addProduct/after                 │
│   • editProduct/after                │
└──────────────────┬───────────────────┘
                   │ Asynchronous Webhook Dispatch
                   ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      synesthesia-ai Microservice                        │
│                                                                         │
│   ┌────────────────────────┐         ┌──────────────────────────────┐   │
│   │   FastAPI Endpoints    │         │    Embedding Generator       │   │
│   │ • /recommend/gear      │────────▶│ • Text / Spec Vectorizer     │   │
│   │ • /recommend/similar   │         │ • Sentence-Transformers HNSW │   │
│   │ • /search/semantic     │         └──────────────┬───────────────┘   │
│   └───────────┬────────────┘                        │                   │
│               │                                     ▼                   │
│               │ Query Cache            ┌────────────────────────────┐   │
│               ▼                        │    Qdrant Vector DB        │   │
│   ┌────────────────────────┐           │ • 1536-dim Cosine Metric   │   │
│   │      Redis L2 Cache    │           │ • Cross-Domain Collection  │   │
│   │ • 60-second TTL        │           └────────────────────────────┘   │
│   └────────────────────────┘                                            │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Cross-Domain Latent Embedding Space

Traditional e-commerce recommendation systems only recommend items within the same category (e.g. albums recommend other albums). Synthesia bridges the gap between music consumers and music creators:

### 1. Music Release Representation:
- Ingests: Artist, Album Title, Genre, Release Year, Record Label, Tracklist titles, and the `Вайб / Характер звучания` attribute (e.g., *"ambient, spacey synth, tape delay, psych rock"*).
- Converts text into high-dimensional dense vectors using a pre-trained sentence transformer.

### 2. Musical Equipment Representation:
- Ingests: Instrument Category, Brand, Sound Style (`Стиль звучания`), Pickup specifications, and Tonewoods (e.g., *"Vintage Stratocaster, single-coil glass tone, blues/funk, psych leads"*).
- Maps instruments into the identical semantic space.

### 3. Cross-Domain Vector Proximity:
- An album with a psychedelic space-rock vibe mathematically clusters near analog synthesizers, delay pedals, and vintage electric guitars.
- When an album page is viewed, the system executes a cosine similarity k-NN search filtered by `type = 'instrument'` to find the matching gear that produced that specific acoustic aesthetic.

---

## 3. OpenCart Event Hook Integration

The integration is registered in OpenCart's `oc_event` table via `cli/setup_soundnet_ai.php`:

```sql
INSERT INTO `oc_event` (`code`, `trigger`, `action`, `status`, `sort_order`) VALUES
('soundnet_ai_product_add', 'admin/model/catalog/product/addProduct/after', 'extension/module/soundnet_storefront/onProductChange', 1, 0),
('soundnet_ai_product_edit', 'admin/model/catalog/product/editProduct/after', 'extension/module/soundnet_storefront/onProductChange', 1, 0);
```

### Relational Vector Status Tracking:
The database maintains synchronization state in `oc_product_vector_status`:

```sql
CREATE TABLE IF NOT EXISTS `oc_product_vector_status` (
  `product_id` INT(11) NOT NULL,
  `is_indexed` TINYINT(1) DEFAULT 0,
  `has_audio` TINYINT(1) DEFAULT 0,
  `audio_path` VARCHAR(255) NULL,
  `content_hash` VARCHAR(32) NOT NULL DEFAULT '',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. API Specification

### 4.1 Cross-Domain Gear Matching
- **Endpoint:** `GET /api/v1/recommend/gear`
- **Parameters:** `product_id` (int), `limit` (int, default: 6)
- **Response:**
  ```json
  {
    "status": "success",
    "product_id": 42,
    "album_name": "The Dark Side of the Moon",
    "recommendations": [
      {
        "product_id": 105,
        "name": "Moog Grandmother Semi-Modular Analog Synthesizer",
        "category": "Клавишные и синтезаторы",
        "similarity_score": 0.942,
        "price": 999.00
      },
      {
        "product_id": 88,
        "name": "Fender American Professional II Stratocaster",
        "category": "Гитары",
        "similarity_score": 0.891,
        "price": 1799.00
      }
    ]
  }
  ```

### 4.2 Acoustic Similar Albums
- **Endpoint:** `GET /api/v1/recommend/similar`
- **Parameters:** `product_id` (int), `limit` (int, default: 8)
- **Response:** Array of musically similar vinyl and CD releases based on vibe, genre, and instrumentation.

### 4.3 Semantic Catalog Search
- **Endpoint:** `GET /api/v1/search/semantic`
- **Parameters:** `q` (string, e.g. *"warm 70s analog synth with heavy bass"*)
- **Response:** Hybrid search results returning both relevant albums and instruments that match the natural language description.

---

## 5. Docker Deployment & Orchestration

The service is fully containerized in `synesthesia-ai/docker-compose.yml`:

```yaml
version: '3.8'
services:
  qdrant:
    image: qdrant/qdrant:latest
    ports:
      - "6333:6333"
    volumes:
      - qdrant_storage:/qdrant/storage

  redis:
    image: redis:6.2-alpine
    ports:
      - "6379:6379"

  api:
    build: .
    ports:
      - "8000:8000"
    environment:
      - QDRANT_HOST=qdrant
      - QDRANT_PORT=6333
      - REDIS_URL=redis://redis:6379/0
    depends_on:
      - qdrant
      - redis

volumes:
  qdrant_storage:
```

### Running the Microservice:
```bash
cd synesthesia-ai
docker compose up -d
```
