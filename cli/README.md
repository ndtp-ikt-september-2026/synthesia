# Synesthesia CLI Systems Reference Manual

Comprehensive technical documentation for the headless Command-Line Interface (CLI) tooling suite powering the **Synesthesia (LiveStore / OpenCart 3.0.3.x Fork)** e-commerce core.

---

## 1. Architectural Foundations & Design Principles

All utilities in the `cli/` subsystem follow strict enterprise design principles:

- **Headless Runtime Execution:** Operates strictly outside the HTTP / Nginx gateway context to eliminate HTTP timeout limits (e.g. `504 Gateway Time-out`) and memory starvation.
- **Resource Constraints:** Scripts enforce `set_time_limit(0)`, `ignore_user_abort(true)`, and `@ini_set('memory_limit', '512M')`.
- **Agent-First Determinism:** All CLI tools support a global `--format=json` flag (enabled by default in non-interactive environments) providing structured JSON output envelopes compatible with automated orchestrators, CI/CD pipelines, and AI agent workers.
- **Strict Code Standards:** All PHP code strictly adheres to single quotes (`'`) for string literals, array keys, configuration items, and SQL queries to guarantee consistent quoting and avoid string interpolation overhead.
- **Native Event Preservation:** Catalog mutations strictly execute via OpenCart Model Proxies (`ModelCatalogProduct::addProduct`, `ModelCatalogProduct::editProduct`) to ensure native OpenCart event triggers (`admin/model/catalog/product/addProduct/after`) fire, maintaining real-time downstream AI vector webhooks to FastAPI services.

---

## 2. Ingestion Utility: `cli/catalog_ingest.php`

High-throughput, fault-tolerant ingestion pipeline specifically architected to interface with external web scrapers and data extractors written in Python, Node.js, or Bash.

### 2.1 Invocation Syntax & Flags

```bash
php cli/catalog_ingest.php [options]
```

| Flag | Description | Default |
| :--- | :--- | :--- |
| `--file=<path>`, `-f=<path>` | Path to a local JSON file containing an array of scraped product entities. | *None* |
| `--stdin` | Read the JSON payload stream directly from standard input (STDIN pipe). | `false` |
| `--format=json\|text` | Envelope response format. | `json` |
| `--dry-run` | Validates payload, maps attributes, and simulates processing without DB writes. | `false` |
| `--skip-images` | Skips downloading remote image assets (useful for fast schema-only sync). | `false` |
| `--help`, `-h` | Prints usage manual and payload schema specifications. | - |

---

### 2.2 Scraper Data Contract (Input Payload Schema)

The utility accepts either a JSON array of product objects or a single product object:

```json
[
  {
    "type": "track",
    "name": "The Dark Side of the Moon",
    "model": "PF-DSOTM-1973-LP",
    "price": 38.50,
    "quantity": 10,
    "category_ids": [1, 3],
    "image_url": "https://images.example.com/covers/dsotm.jpg",
    "additional_images": [
      "https://images.example.com/covers/dsotm_back.jpg",
      "https://images.example.com/covers/dsotm_booklet.jpg"
    ],
    "description": "Remastered 180g gatefold vinyl release with original posters and stickers.",
    "attributes": {
      "Исполнитель": "Pink Floyd",
      "Жанр": "Progressive Rock",
      "Год выпуска": "1973",
      "Лейбл": "Harvest",
      "Формат издания": "LP 180g",
      "Вайб / Характер звучания": "dark, immersive, psychedelic, lush synth"
    },
    "tracklist": [
      {"track_num": 1, "title": "Speak to Me", "duration": "1:30", "preview_file": "https://cdn.freesound.org/previews/560/560446_11861866-lq.mp3"},
      {"track_num": 2, "title": "Breathe (In the Air)", "duration": "2:43", "preview_file": "https://cdn.freesound.org/previews/612/612089_11861866-lq.mp3"},
      {"track_num": 3, "title": "On the Run", "duration": "3:30", "preview_file": "upload/audio/sample_on_the_run.mp3"}
    ]
  },
  {
    "type": "instrument",
    "name": "Fender American Professional II Stratocaster",
    "model": "FEN-AM-PRO2-STRAT-OW",
    "price": 1799.00,
    "quantity": 3,
    "category_ids": [2, 6],
    "image_url": "https://images.example.com/gear/strat_front.jpg",
    "additional_images": [],
    "description": "Solid body electric guitar featuring V-Mod II single-coil pickups and sculpted neck heel.",
    "attributes": {
      "Бренд": "Fender",
      "Тип инструмента": "Электрогитара",
      "Стиль звучания": "Blues, Funk, Rock",
      "Звукосниматели": "V-Mod II Single-Coil",
      "Материал корпуса": "Ольха"
    }
  }
]
```

#### Field Specifications:
- `model` *(string, required)*: Unique catalog identifier / SKU used as the idempotent upsert key.
- `name` *(string, required)*: Human-readable product title. Populates all installed store languages.
- `price` *(float, optional)*: Unit price in default store currency. Default `0.00`.
- `quantity` *(int, optional)*: Stock level. Default `0`.
- `category_ids` *(array of int, optional)*: OpenCart category IDs. First element is assigned as `main_category_id`.
- `image_url` *(string, optional)*: HTTP/HTTPS URL of primary product cover.
- `additional_images` *(array of string, optional)*: HTTP/HTTPS URLs of gallery/secondary images.
- `description` *(string, optional)*: HTML/Markdown product description.
- `attributes` *(object / map, optional)*: Key-value map of specifications. Keys are dynamically resolved against `oc_attribute_description`.
- `tracklist` *(array of objects or strings, optional)*: Release tracklist. Accepts either objects (`{"track_num": 1, "title": "...", "duration": "...", "preview_file": "..."}`) or strings (`"01. Title (3:45)"`). Automatically populates `oc_product_tracklist`.

---

### 2.3 Upsert & Fault-Tolerance Architecture

```
                       [ Scraper Input ]
                     (File or STDIN Stream)
                               │
                               ▼
                   [ CLI Ingest Bootstrapper ]
                               │
            ┌──────────────────┴──────────────────┐
            ▼                                     ▼
   [ Dynamic Attribute Resolver ]       [ Remote Asset Downloader ]
   • Case-insensitive cache check       • Deduplication via MD5 hash
   • Auto-creates missing attributes    • Timeout guard (3s connect / 6s rx)
            │                                     │
            └──────────────────┬──────────────────┘
                               │
                               ▼
                    [ Idempotent Upsert ]
               Query oc_product by 'model' (SKU)
                               │
               ┌───────────────┴───────────────┐
      [ Exists ]                               [ Does NOT Exist ]
           │                                            │
           ▼                                            ▼
 ModelCatalogProduct::editProduct            ModelCatalogProduct::addProduct
           │                                            │
           └──────────────────┬─────────────────────────┘
                              ▼
               [ OpenCart Core Events Trigger ]
             `addProduct/after` | `editProduct/after`
                              │
                              ▼
           [ FastAPI Async Vector Embedding Webhook ]
```

1. **Idempotent Matching:** Products are deduplicated by `model`. If the model exists, non-destructive updates merge new pricing, inventory, descriptions, and attributes. If not, a new product is inserted.
2. **Automatic Attribute Resolution:** Keys in `attributes` (e.g., `'Исполнитель'`, `'Бренд'`) are matched case-insensitively against `oc_attribute_description`. If an attribute does not exist, the resolver registers it on the fly under the default attribute group, ensuring scraper payloads never abort due to novel specifications.
3. **Resilient Asset Pipeline:**
   - Images are downloaded to `image/catalog/products/<sanitized_model>/img_<hash>.<ext>`.
   - Local caching prevents re-downloading identical images.
   - Network failures or 404s log `[IMAGE NOTICE]` to `STDERR` without aborting product insertion.
4. **Batch Isolation:** Each item in a batch is isolated in a `try...catch` block. If an individual record fails, it is appended to `errors` while subsequent products continue uninterrupted.

---

### 2.4 Deterministic Output Envelope

#### Success Envelope (`HTTP/CLI 0`):
```json
{
  "status": "success",
  "dry_run": false,
  "processed": 2,
  "inserted": 1,
  "updated": 1,
  "failed": 0,
  "items": [
    {
      "model": "PF-DSOTM-1973-LP",
      "action": "updated",
      "product_id": 1,
      "name": "The Dark Side of the Moon",
      "price": 38.5,
      "quantity": 10
    },
    {
      "model": "FEN-AM-PRO2-STRAT-OW",
      "action": "inserted",
      "product_id": 2,
      "name": "Fender American Professional II Stratocaster",
      "price": 1799,
      "quantity": 3
    }
  ],
  "errors": [],
  "message": "Catalog ingestion completed: 1 inserted, 1 updated, 0 failed."
}
```

#### Exit Codes:
- `0`: Ingestion completed successfully or partially (with isolated item errors).
- `1`: Fatal system exception or database connection failure.
- `2`: Invalid command invocation or malformed JSON payload.

---

### 2.5 Scraper Integration Examples

#### Bash / cURL
```bash
# Pipe raw scraped output directly to the ingest engine
curl -s "https://api.scraper.internal/v1/vinyl" | php cli/catalog_ingest.php --stdin
```

#### Python 3 (`subprocess`)
```python
import subprocess
import json

payload = [
    {
        "model": "SONY-WH1000XM5-BLK",
        "name": "Sony WH-1000XM5 Wireless Headphones",
        "price": 399.00,
        "quantity": 5,
        "category_ids": [5],
        "attributes": {"Тип": "Беспроводные наушники", "Цвет": "Черный"}
    }
]

process = subprocess.Popen(
    ["php", "cli/catalog_ingest.php", "--stdin", "--format=json"],
    stdin=subprocess.PIPE,
    stdout=subprocess.PIPE,
    stderr=subprocess.PIPE,
    text=True
)

stdout, stderr = process.communicate(input=json.dumps(payload))
result = json.loads(stdout)

print(f"Processed: {result['processed']}, Inserted: {result['inserted']}, Updated: {result['updated']}")
```

#### Node.js (`child_process`)
```javascript
const { spawn } = require('child_process');

const payload = [{
  model: 'KORG-MINILOGUE-XD',
  name: 'Korg Minilogue XD Polyphonic Synthesizer',
  price: 649.00,
  quantity: 2,
  category_ids: [5]
}];

const child = spawn('php', ['cli/catalog_ingest.php', '--stdin', '--format=json']);

let stdoutData = '';
child.stdout.on('data', (data) => { stdoutData += data; });
child.on('close', (code) => {
  const response = JSON.parse(stdoutData);
  console.log(`Ingest finished with code ${code}:`, response.message);
});

child.stdin.write(JSON.stringify(payload));
child.stdin.end();
```

---

## 3. Administration Utility: `cli/admin_cli.php`

Comprehensive headless OpenCart store management suite for AI agents and administrators.

### 3.1 Product Operations (`product:*`)

```bash
# List products with pagination and filters
php cli/admin_cli.php product:list --limit=20 --page=1 --status=1

# Retrieve full product details (including descriptions, categories, attributes, and vector sync state)
php cli/admin_cli.php product:get --id=1

# Create product via JSON payload file
php cli/admin_cli.php product:create --file=payload.json

# Update specific fields
php cli/admin_cli.php product:update --id=1 --payload='{"price": 42.00, "quantity": 15}'

# Delete a product
php cli/admin_cli.php product:delete --id=1
```

### 3.2 Tracklist Operations (`tracklist:*`)

```bash
# Retrieve tracklist and preview streams for a release
php cli/admin_cli.php tracklist:get --product_id=1

# Replace / set tracklist via JSON payload file
php cli/admin_cli.php tracklist:set --product_id=1 --payload-file=tracks.json

# Replace / set tracklist via inline JSON
php cli/admin_cli.php tracklist:set --product_id=1 --payload='[{"track_num":1,"title":"Intro","duration":"1:20","preview_file":"upload/audio/intro.mp3"}]'

# Dry-run validation of tracklist without writing to database
php cli/admin_cli.php tracklist:set --product_id=1 --payload-file=tracks.json --dry-run

# Clear all audio tracks for a product
php cli/admin_cli.php tracklist:clear --product_id=1
```

### 3.3 Category Operations (`category:*`)

```bash
# List all categories hierarchically
php cli/admin_cli.php category:list

# Retrieve category metadata
php cli/admin_cli.php category:get --id=2

# Create category
php cli/admin_cli.php category:create --payload='{"name": "Синтезаторы", "parent_id": 5}'

# Update category
php cli/admin_cli.php category:update --id=5 --payload='{"sort_order": 10}'

# Delete category
php cli/admin_cli.php category:delete --id=5
```

### 3.4 Configuration Settings (`setting:*`)

```bash
# Read a configuration key
php cli/admin_cli.php setting:get --key=config_name

# Update or insert a configuration key
php cli/admin_cli.php setting:set --group=config --key=config_maintenance --value=0

# List all settings for a group
php cli/admin_cli.php setting:list --group=config
```

### 3.5 Maintenance & Diagnostics

```bash
# Clear all OpenCart file-system cache files
php cli/admin_cli.php cache:clear

# Check system health, database connection, and language settings
php cli/admin_cli.php status
```

---

## 4. Initialization & Maintenance Utilities

### 4.1 Catalog Purge & Seed: `cli/reset_and_seed_catalog.php`
Idempotently clears legacy products, options, categories, and attributes, then seeds clean Synesthesia store taxonomies:
```bash
php cli/reset_and_seed_catalog.php --format=json
```
- **Taxonomies Seeded:**
  - `Музыка / Винил / CD` (`Виниловые пластинки`, `Компакт-диски`, `Цифровые релизы`)
  - `Музыкальные инструменты и оборудование` (`Гитары`, `Клавишные и синтезаторы`, `Усилители и кабинеты`, `Педали эффектов`, `DJ-оборудование`)
- **Attribute Groups Seeded:**
  - `Характеристики винила / аудио` (Исполнитель, Лейбл, Год выпуска, Формат издания, Жанр, Вайб / Характер звучания)
  - `Спецификации инструментов` (Бренд, Тип инструмента, Стиль звучания, Звукосниматели, Материал корпуса)

### 4.2 Belarusian Ruble (BYN) Registration: `cli/add_currency_byr.php`
Registers the official Belarusian Ruble currency into `oc_currency` and generates standardized web-ready icons using PHP GD:
```bash
php cli/add_currency_byr.php --source-image=upload/image/catalog/source_currency.png
```
- **Generated Assets:**
  - `image/catalog/currency/byn.png` (64x64 master icon)
  - `image/catalog/currency/byn_32.png` (32x32 UI icon)
  - `image/catalog/currency/byn.webp` (Optimized modern format)

### 4.3 Template Garbage Cleanup: `cli/purge_template_junk.php`
Purges legacy OpenCart / theme demo content while strictly preserving production system configurations, languages, currencies, and order statuses:
```bash
php cli/purge_template_junk.php --format=json
```
- **Purge Targets:** Demo banners, manufacturers, demo blogs/news, coupons, vouchers, affiliates, and unused theme layouts.
