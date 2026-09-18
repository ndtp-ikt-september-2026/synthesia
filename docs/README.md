# 📚 Synthesia Architecture & Modules Technical Documentation

Welcome to the technical engineering documentation for the **Synthesia (SoundNet)** e-commerce ecosystem. This documentation provides in-depth architectural specifications, data flow diagrams, database schemas, and integration guides for each subsystem.

---

## Subsystem Modules

The platform is structured into modular subsystems:

```
docs/
├── README.md                      # Documentation Directory Overview (You are here)
└── modules/
    ├── storefront.md              # SoundNet Storefront & Dark Velvet UI Engine
    ├── tracklist.md               # SoundNet Dynamic Tracklist & Audio Streaming Player
    ├── stopkran.md                # SoundNet Stop-Kran Early Watchdog & Circuit Breaker
    └── ai_engine.md               # Synesthesia AI Vector Matching Microservice
```

---

## 📑 Module Index

### 1. [SoundNet Storefront (`soundnet_storefront`)](modules/storefront.md)
Comprehensive guide to the storefront architecture:
- Dark Velvet UI design system and CSS variables.
- Dual recommendation hero carousels.
- Cross-domain "Gear for this Vibe" matching algorithms.
- OCMOD modification points in core OpenCart controllers and Twig templates.
- Sticky search header and custom category cards grid.

### 2. [SoundNet Dynamic Tracklist (`soundnet_tracklist`)](modules/tracklist.md)
Technical specifications for music release tracklists and audio playback:
- Relational schema (`oc_product_tracklist`).
- Admin management tab (`tab-tracklist` in `product_form.twig`).
- HTML5 streaming audio widget with play/pause, seek scrubbers, and duration.
- Collapsible tracklist UI with an 8-track display threshold for albums with many tracks.
- Scraper and CLI ingestion interfaces.

### 3. [SoundNet Stop-Kran Watchdog (`soundnet_stopkran`)](modules/stopkran.md)
Kernel-level resilience and fault tolerance for OpenCart 3:
- Analysis of OpenCart's modification vulnerability (white-screen outages).
- Early bootstrap watchdog hook in `system/startup.php`.
- Crash detection, call-stack AST inspection, and modification fault isolation.
- Automatic circuit breaker, modification deactivation, and cache purge.
- Emergency CLI (`cli/stopkran.php`) and administrator control panel.

### 4. [Synesthesia AI Engine (`synesthesia-ai`)](modules/ai_engine.md)
FastAPI and Qdrant vector similarity service:
- Cross-domain latent space embedding architecture.
- Real-time catalog mutation webhooks (`addProduct/after`, `editProduct/after`).
- Vector search API endpoints (`/recommend/instruments`, `/search/semantic`).
- Redis L2 query caching and Docker Compose deployment.

---

## 🛠️ CLI Subsystem Reference

For detailed documentation on headless command-line utilities, consult the **[CLI Systems Reference Manual](../cli/README.md)**.
