# SoundNet Storefront Module Specification

`soundnet_storefront` is the custom presentation layer powering the Synthesia e-commerce platform. It transforms the standard OpenCart 3 theme into a modern, dark-velvet audiophile retail destination tailored for vinyl records, physical audio formats, and musical instruments.

---

## 1. Architectural Overview

```
                                [ HTTP Client Request ]
                                           │
                                           ▼
                           [ OpenCart Catalog Routing ]
                                           │
               ┌───────────────────────────┴───────────────────────────┐
               ▼                                                       ▼
      [ Common Header / Search ]                                [ Product Controller ]
      • Loads soundnet_storefront.css                           • Enriches Release Metadata
      • Loads soundnet_storefront.js                            • Resolves "Matching Gear"
      • Injects localized search                                • Injects AI Similar Modal
               │                                                       │
               └───────────────────────────┬───────────────────────────┘
                                           ▼
                          [ Twig Rendering Engine ]
                 • catalog/view/theme/default/template/product/product.twig
                 • catalog/view/theme/default/template/common/home.twig
                 • catalog/view/theme/default/template/extension/module/*.twig
                                           │
                                           ▼
                              [ Browser DOM Output ]
```

---

## 2. Design System & CSS Tokens

All visual styles are centralized in `catalog/view/theme/default/stylesheet/soundnet_storefront.css`. The design system uses custom CSS variables for consistency:

```css
:root {
  /* Deep Space Dark Palette */
  --sn-bg-base: #0b0f19;
  --sn-bg-surface: #131b2e;
  --sn-bg-elevated: #1e293b;
  --sn-bg-glass: rgba(19, 27, 46, 0.85);

  /* Borders & Dividers */
  --sn-border-subtle: rgba(255, 255, 255, 0.08);
  --sn-border-accent: rgba(56, 189, 248, 0.3);

  /* Typography Colors */
  --sn-text-primary: #f8fafc;
  --sn-text-secondary: #94a3b8;
  --sn-text-muted: #64748b;

  /* Acoustic Accents */
  --sn-accent-cyan: #38bdf8;
  --sn-accent-purple: #818cf8;
  --sn-accent-emerald: #34d399;
  --sn-accent-gold: #fbbf24;

  /* Radii & Shadows */
  --sn-radius-sm: 8px;
  --sn-radius-md: 14px;
  --sn-radius-lg: 20px;
  --sn-shadow-card: 0 10px 30px -5px rgba(0, 0, 0, 0.5);
  --sn-shadow-glow: 0 0 25px rgba(56, 189, 248, 0.15);
}
```

---

## 3. Key Storefront Components

### 3.1 Dual Recommendation Carousels (`soundnet_recommendations.twig`)
Instead of a generic single product slider, the homepage and category layouts feature dual synchronized recommendation tracks:
- **Track 1: Curated Vinyl & Audiophile Releases:** Showcases featured records with artist, format badge (e.g. `180g Vinyl`, `Deluxe 2LP`), and release year.
- **Track 2: Sound Creation & Studio Gear:** Showcases electric guitars, analog synthesizers, studio headphones, and boutique effects.

### 3.2 "Gear for this Vibe" Cross-Domain Matching
When viewing any music release product card, the controller executes `getMatchingInstruments($product_id)`:
1. Determines the acoustic vibe / genre of the current album (e.g. *Progressive Rock / Psychedelic*).
2. Queries the catalog for instruments mapped with corresponding sound characteristics (e.g. *Stratocaster single-coil guitars, Moog analog synthesizers*).
3. Renders a dedicated horizontal gear showcase directly beneath the album details, inviting musicians and fans to recreate the album's iconic sound.

### 3.3 Collapsible Full/Less Tracklist
For albums with extensive tracklists (> 8 tracks), the UI prevents page clutter by displaying the first 8 tracks and rendering an ergonomic collapsible toggle:
- Smooth CSS max-height transition.
- Dynamically counts total tracks: *"Показать все 16 треков / Свернуть"*.
- State is preserved during audio playback.

### 3.4 AI Similar Release Modal (`soundnet_similar_modal.twig`)
Embedded directly into the product layout before the footer:
- Triggered by the *"Найти похожие по звучанию"* action button.
- Queries the `synesthesia-ai` microservice for acoustic vector neighbors.
- Displays nearest matching albums and gear with similarity scores.

---

## 4. OCMOD Modification Rules (`install.xml`)

The storefront modification is injected non-destructively via `install.xml`:

### 1. `catalog/controller/product/product.php`
- Injects module languages and models.
- Enqueues `soundnet_storefront.css` and `soundnet_storefront.js`.
- Queries `getReleaseMetadata()` and passes `is_music`, `artist`, `year`, `label`, `format_badge`, `matching_gear`, and `tracks` into the Twig template context.

### 2. `catalog/view/theme/default/template/product/product.twig`
- Injects the AI Similar Modal before `{{ footer }}`.
- Replaces legacy tab layout with a sleek two-column layout: media cover + audio player on the left, specifications + buy box + gear carousel on the right.

### 3. `catalog/controller/common/header.php`
- Injects localized navigation labels: catalog dropdown, quick search placeholder, and branding emblem.

### 4. `catalog/controller/common/home.php`
- Replaces standard OpenCart home layout top and bottom columns with `soundnet_storefront` dual carousels and categories grid.

---

## 5. File Structure & Distribution

The source files for this module are located in:
- Source tree: [`packages/soundnet_storefront/`](../../packages/soundnet_storefront/)
- Dist archive: `dist/soundnet_storefront.ocmod.zip`
- CSS stylesheet: `catalog/view/theme/default/stylesheet/soundnet_storefront.css`
- JavaScript widget: `catalog/view/javascript/soundnet_storefront.js`
