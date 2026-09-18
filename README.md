<div align="center">

# 🎛️ Synthesia // SoundNet

**Next-Generation Hybrid E-Commerce Ecosystem for Vinyl Records & Musical Instruments**

*Powered by OpenCart 3.0.3.x, FastAPI AI Microservices, Qdrant Vector Engine & Stop-Kran Fault Tolerance*

---

[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.0+-777bb4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![OpenCart Core](https://img.shields.io/badge/OpenCart-3.0.3.x%20Fork-2396f3?style=flat-square&logo=opencart&logoColor=white)](https://www.opencart.com/)
[![Python Microservice](https://img.shields.io/badge/Python-3.11+-3776ab?style=flat-square&logo=python&logoColor=white)](https://www.python.org/)
[![FastAPI](https://img.shields.io/badge/FastAPI-Async%20Core-009688?style=flat-square&logo=fastapi&logoColor=white)](https://fastapi.tiangolo.com/)
[![Qdrant Vector DB](https://img.shields.io/badge/Qdrant-Vector%20Similarity-dc2626?style=flat-square)](https://qdrant.tech/)
[![Redis Cache](https://img.shields.io/badge/Redis-6.2%20Alpine-dc382d?style=flat-square&logo=redis&logoColor=white)](https://redis.io/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ed?style=flat-square&logo=docker&logoColor=white)](https://www.docker.com/)
[![License](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](LICENSE)

</div>

---

## 📖 Overview

**Synthesia (SoundNet)** is an enterprise e-commerce platform bridging the worlds of audiophile music collecting and sound creation gear. Built upon a heavily optimized **OpenCart 3.0.3.x** core and paired with an asynchronous **Python FastAPI + Qdrant** vector microservice, Synthesia offers intelligent cross-domain product discovery: matching vinyl records with the exact musical instruments, synthesizers, and amplifiers that shaped their acoustic signature.

Engineered with an **agent-first philosophy**, Synthesia includes a headless command-line interface (CLI) suite for automated scraper ingestion, headless catalog administration, and **Stop-Kran** — an early-bootstrap kernel watchdog that prevents white-screen outages by automatically isolating crashing OCMOD modifications.

---

## 🏛️ System Architecture

```
                                  ┌────────────────────────┐
                                  │   Web Browser Client   │
                                  └───────────┬────────────┘
                                              │ HTTP / HTTPS
                                              ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│                                   OpenCart 3.0.3.x Monolith                                 │
│                                                                                             │
│   ┌───────────────────────────────┐     ┌───────────────────────────────────────────────┐   │
│   │   SoundNet Dark Storefront    │     │             Administration Suite              │   │
│   │   • Dark Velvet Theme         │     │   • Catalog & Attribute Management            │   │
│   │   • Dual Recommendation Hero  │     │   • Tracklist & Audio Preview Tab             │   │
│   │   • Collapsible Tracklist     │     │   • Stop-Kran Watchdog Control Panel          │   │
│   │   • HTML5 Streaming Player    │     │   • Modernized Rounded Admin Theme            │   │
│   └───────────────┬───────────────┘     └───────────────────────┬───────────────────────┘   │
│                   │                                             │                           │
│                   └──────────────────────┬──────────────────────┘                           │
│                                          ▼                                                  │
│                         ┌─────────────────────────────────┐                                 │
│                         │    Stop-Kran Watchdog Kernel    │                                 │
│                         │    (system/startup.php Hook)    │                                 │
│                         │    • Early Bootstrap Guard      │                                 │
│                         │    • Fatal Error Interceptor    │                                 │
│                         │    • OCMOD Circuit Breaker      │                                 │
│                         └────────────────┬────────────────┘                                 │
│                                          ▼                                                  │
│                         ┌─────────────────────────────────┐                                 │
│                         │     OpenCart MVC-L Framework    │                                 │
│                         │  Controllers | Models | Views   │                                 │
│                         └────────┬───────────────┬────────┘                                 │
│                                  │               │                                          │
└──────────────────────────────────┼───────────────┼──────────────────────────────────────────┘
                                   │               │
            ┌──────────────────────┘               └──────────────────────┐
            ▼                                                             ▼
┌───────────────────────┐                                     ┌───────────────────────┐
│   MySQL 5.7+ / 8.0    │                                     │  Headless CLI Suite   │
│                       │                                     │                       │
│ • oc_product          │                                     │ • catalog_ingest.php  │
│ • oc_product_tracklist│                                     │ • admin_cli.php       │
│ • oc_product_vector_* │                                     │ • stopkran.php        │
│ • oc_modification     │                                     │ • package_modules.php │
└───────────▲───────────┘                                     └───────────▲───────────┘
            │                                                             │
            │ Event Triggers (addProduct/after, editProduct/after)        │
            └──────────────────────────────┬──────────────────────────────┘
                                           │ Webhook Dispatch
                                           ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│                             Synesthesia AI Microservice (Docker)                            │
│                                                                                             │
│      ┌─────────────────────────┐     ┌─────────────────┐     ┌───────────────────────┐      │
│      │     FastAPI Gateway     │────▶│  Qdrant Engine  │────▶│   Redis Cache L2      │      │
│      │  • Cross-Domain Match   │     │  Vector Store   │     │   • Fast Query Cache  │      │
│      │  • Semantic Search      │     │  Embeddings     │     │   • Token Store       │      │
│      │  • Audio Vibe Vectors   │     │  1536-dim HNSW  │     │                       │      │
│      └─────────────────────────┘     └─────────────────┘     └───────────────────────┘      │
└─────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## ✨ Key Features

### 1. 🎵 Dark Velvet SoundNet Storefront
- **Immersive Palette:** Tailored deep obsidian background (`#0b0f19`), slate secondary cards (`#1e293b`), and vibrant soundwave cyan (`#38bdf8`) / electric purple (`#818cf8`) accents.
- **Dual Dynamic Hero Carousels:** Cross-domain showcases presenting new vinyl drops side-by-side with studio equipment.
- **Quick-Access Sticky Header:** Responsive search with real-time category filtering and brand emblem badge.
- **"Gear for this Vibe" Recommendations:** Automated cross-category pairing (e.g. Pink Floyd LP ➡️ Fender Stratocaster & Moog Synthesizer).

### 2. 🎼 Dynamic Tracklist & Audio Streaming Player
- **Dedicated Relational Schema:** Stored in `oc_product_tracklist` (track numbers, titles, durations, preview URLs, sort orders).
- **Embedded Admin Tab (`tab-tracklist`):** Dynamic AJAX-driven management grid within `product_form.twig` with drag-and-drop ordering and audio file upload.
- **HTML5 Player Widget:** Integrated player supporting MP3, WAV, OGG, and FLAC snippets with interactive scrub bars and active track state.
- **Smart Collapsible View:** Albums with > 8 tracks automatically render a sleek toggle button (*"Show all 16 tracks / Show less"*), preserving layout balance.

### 3. 🛡️ Stop-Kran Self-Healing Watchdog
- **Early Kernel Bootstrap:** Injected into `system/startup.php` prior to autoloader initialization.
- **Outage Prevention:** Intercepts fatal PHP errors and identifies the responsible OCMOD extension.
- **Automated Circuit Breaker:** Automatically deactivates the faulty modification, purges `system/storage/modification/`, and restores the store within milliseconds.
- **Emergency CLI (`cli/stopkran.php`):** Headless safe-mode toggle, modification isolation, and cache flushing directly from the terminal without HTTP gateway dependencies.

### 4. ⚡ Headless CLI Automation Suite
- **Scraper Ingestion (`cli/catalog_ingest.php`):** High-throughput idempotent upsert pipeline supporting JSON payloads via file or STDIN pipe with automatic attribute creation and asset deduplication.
- **Store Administration (`cli/admin_cli.php`):** Comprehensive commands for products, categories, tracklists, system configuration, and cache invalidation.
- **One-Command Module Packager (`cli/package_modules.php`):** Compiles OCMOD extensions into distributable `.ocmod.zip` archives with a single command.

### 5. 🧠 Synesthesia AI Vector Engine
- **FastAPI Microservice:** Asynchronous Python service containerized with Docker Compose.
- **Cross-Domain Embeddings:** Maps vinyl releases and music hardware into a unified latent vector space.
- **Event-Driven Synchronization:** OpenCart core event triggers push real-time updates to the AI pipeline whenever products are created or updated.

---

## 📦 Extension Modules (OCMOD Packages)

Synthesia includes 3 production-grade, self-contained OCMOD extensions located in the [`packages/`](packages/) directory:

| Extension Code | Package Directory | Description | Documentation |
| :--- | :--- | :--- | :--- |
| `soundnet_storefront` | [`packages/soundnet_storefront/`](packages/soundnet_storefront/) | Dark Velvet theme, hero carousels, category cards, AI modal, and gear matching. | [Docs](docs/modules/storefront.md) |
| `soundnet_tracklist` | [`packages/soundnet_tracklist/`](packages/soundnet_tracklist/) | Admin tracklist management tab, HTML5 audio player, and collapsible view. | [Docs](docs/modules/tracklist.md) |
| `soundnet_stopkran` | [`packages/soundnet_stopkran/`](packages/soundnet_stopkran/) | Early bootstrap watchdog, crash circuit breaker, admin panel, and emergency CLI. | [Docs](docs/modules/stopkran.md) |

### Building OCMOD Packages

Compile all modules into production-ready `.ocmod.zip` archives in `dist/`:

```bash
php cli/package_modules.php
```

Or build a specific module:

```bash
php cli/package_modules.php soundnet_storefront
php cli/package_modules.php soundnet_tracklist
php cli/package_modules.php soundnet_stopkran
```

---

## 🚀 Quickstart & Installation

### Prerequisites

- **PHP:** 7.4 or 8.0+ with extensions: `mysqli`, `gd`, `curl`, `mbstring`, `zip`, `xml`, `openssl`
- **Web Server:** Nginx or Apache (OSPanel, Docker, or native Linux LEMP)
- **Database:** MySQL 5.7+ or MariaDB 10.3+
- **Python (Optional for AI service):** 3.11+ with [uv](https://github.com/astral-sh/uv) or Docker Compose

---

### Step 1: Clone Repository & Configure Environment

```bash
git clone https://github.com/your-org/synthesia.git
cd synthesia
```

Create configuration files from distribution templates:

```bash
cp config-dist.php config.php
cp admin/config-dist.php admin/config.php
```

Edit `config.php` and `admin/config.php` to set your local HTTP URLs, directory paths, and MySQL database credentials.

---

### Step 2: Database Initialization & Seeding

Run the automated database migrator and seed clean taxonomies:

```bash
# Seed taxonomies, categories, and attribute groups
php cli/reset_and_seed_catalog.php

# Register Belarusian Ruble (BYN) currency
php cli/add_currency_byr.php

# Initialize AI vector tracking tables
php cli/setup_soundnet_ai.php
```

---

### Step 3: Compile & Install OCMOD Modifications

```bash
# Compile packages
php cli/package_modules.php

# Refresh modifications cache
php cli/admin_cli.php cache:clear
```

In your OpenCart Admin (**Extensions -> Installer**), upload the compiled `.ocmod.zip` packages from `dist/` and click **Refresh** in **Extensions -> Modifications**.

---

### Step 4: Run the Synesthesia AI Microservice (Docker)

```bash
cd synesthesia-ai
docker compose up -d
```

The AI microservice will initialize:
- **FastAPI API:** `http://localhost:8000/docs` (Swagger UI)
- **Qdrant Vector DB:** `http://localhost:6333/dashboard`
- **Redis Cache:** `localhost:6379`

Seed the vector store:

```bash
uv run python scripts/seed_data.py
```

---

## 💻 Headless CLI Reference

All CLI scripts support standard execution with deterministic output. See the [CLI Reference Manual](cli/README.md) for extensive documentation.

### Catalog Ingestion (`cli/catalog_ingest.php`)

```bash
# Ingest catalog from JSON file
php cli/catalog_ingest.php --file=catalog.json

# Pipe payload from external scraper stream
curl -s "https://api.scraper.internal/vinyl" | php cli/catalog_ingest.php --stdin

# Dry run validation without database writes
php cli/catalog_ingest.php --file=catalog.json --dry-run
```

### Store Administration (`cli/admin_cli.php`)

```bash
# List active products
php cli/admin_cli.php product:list --limit=20 --status=1

# Manage release tracklists
php cli/admin_cli.php tracklist:get --product_id=42
php cli/admin_cli.php tracklist:set --product_id=42 --payload-file=tracks.json

# Clear OpenCart file-system cache
php cli/admin_cli.php cache:clear

# System health and database verification
php cli/admin_cli.php status
```

### Emergency Watchdog (`cli/stopkran.php`)

```bash
# Check watchdog and modification health
php cli/stopkran.php status

# Emergency cache clear & rebuild
php cli/stopkran.php clear-cache

# Toggle Safe Mode (bypasses all modifications)
php cli/stopkran.php safe-mode on
php cli/stopkran.php safe-mode off

# Isolate / disable a broken modification
php cli/stopkran.php disable --code=buggy_extension
```

---

## 📂 Repository Structure

```
synthesia/
├── admin/                     # OpenCart Administration panel
│   ├── config-dist.php        # Admin configuration template
│   ├── controller/            # Admin controllers (Product, Extension, StopKran)
│   ├── language/              # Translations (en-gb, ru-ru)
│   ├── model/                 # Admin data models
│   └── view/                  # Admin Twig templates and assets
├── audio/                     # Local audio storage for previews
├── catalog/                   # OpenCart Storefront application
│   ├── controller/            # Storefront controllers (Product, Module, Home)
│   ├── language/              # Storefront translations
│   ├── model/                 # Storefront catalog models
│   └── view/                  # Theme templates, stylesheets, JS widgets
├── cli/                       # Headless Command-Line Interface suite
│   ├── admin_cli.php          # Store management CLI
│   ├── catalog_ingest.php     # High-throughput ingestion pipeline
│   ├── clean_description_tracklists.php # Tracklist cleaner & normalizer
│   ├── package_modules.php    # Automated OCMOD zip compiler
│   ├── reset_and_seed_catalog.php # Database resets & clean taxonomy seeding
│   ├── setup_soundnet_ai.php  # AI schema migrator & webhook installer
│   ├── stopkran.php           # Emergency circuit breaker CLI
│   └── README.md              # Extensive CLI systems reference
├── config-dist.php            # Root configuration template
├── docs/                      # Technical module documentation
│   ├── README.md              # Documentation index
│   └── modules/
│       ├── ai_engine.md       # FastAPI & Qdrant vector architecture
│       ├── stopkran.md        # Stop-Kran watchdog & circuit breaker
│       ├── storefront.md      # Dark Velvet UI & gear matching
│       └── tracklist.md       # Audio engine & collapsible player
├── image/                     # Storefront images, category icons, logos
├── migrations/                # Database SQL migration files
├── packages/                  # Standalone OCMOD extension source trees
│   ├── soundnet_stopkran/     # Stop-Kran watchdog package
│   ├── soundnet_storefront/   # Storefront theme package
│   └── soundnet_tracklist/    # Audio tracklist package
├── synesthesia-ai/            # Python FastAPI vector microservice
│   ├── app/                   # FastAPI routing, models, vector matching
│   ├── docker-compose.yml     # Qdrant, Redis & FastAPI orchestration
│   ├── Dockerfile             # Container definition
│   └── pyproject.toml         # Python dependencies & configuration
├── system/                    # OpenCart engine, startup hooks & libraries
│   ├── library/               # Core libraries & StopKran watchdog hook
│   └── startup.php            # Kernel bootstrapper
└── upload/                    # Production deployment overlay
```

---

## 📚 Technical Documentation

Explore deep-dive technical manuals in the [`docs/`](docs/) directory:

- 🎨 **[SoundNet Storefront Documentation](docs/modules/storefront.md)**: Design system, layout hierarchy, Twig templates, and gear matching.
- 🎼 **[SoundNet Tracklist Documentation](docs/modules/tracklist.md)**: Relational tracklist schema, audio preview player, and collapsible UI.
- 🛡️ **[SoundNet Stop-Kran Documentation](docs/modules/stopkran.md)**: Kernel watchdog architecture, circuit breaker mechanics, and emergency CLI.
- 🧠 **[Synesthesia AI Engine Documentation](docs/modules/ai_engine.md)**: FastAPI endpoints, vector similarity algorithms, and Docker orchestration.
- ⌨️ **[Headless CLI Systems Reference](cli/README.md)**: Input/output contracts, error envelopes, and automated scraper integration.

---

## 📄 License

This project is open-source software licensed under the **[MIT License](LICENSE)**.

---

<div align="center">
Built with passion for music, analog sound, and resilient software architecture.
</div>
