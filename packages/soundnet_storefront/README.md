# SoundNet Storefront Extension (`soundnet_storefront`)

**SoundNet Storefront** is a high-performance, dark-aesthetic OpenCart 3 theme and catalog enhancement module designed specifically for vinyl record shops and musical instrument retail ecosystems.

---

## Features

- **Dark Velvet Aesthetics:** Bespoke responsive design system with deep dark backgrounds (`#0f172a`, `#1e293b`), crisp typography (Inter), and neon accent highlights (`#38bdf8`, `#818cf8`).
- **Dual Dynamic Hero Carousels:** Cross-domain carousels highlighting latest/featured vinyl releases and matching musical gear.
- **Cross-Domain Gear Matching:** Automatic "Matching Instruments & Gear" section on album product cards, pairing musical records with the equipment that created their sound (e.g. Pink Floyd LP -> Vintage Stratocaster & Analog Synthesizer).
- **Collapsible Tracklist Player:** Integrated tracklist previewer with responsive full/compact accordion view (auto-collapses tracklists with > 8 tracks).
- **AI Similar Modal:** Quick-view modal to discover acoustically similar records and matching equipment.
- **Enhanced Header & Search:** Responsive sticky navigation with quick search bar and category drop-downs.

---

## Package Structure

```
packages/soundnet_storefront/
├── install.xml            # OCMOD modification instructions
├── upload/
│   ├── admin/             # Module admin controller & views
│   ├── catalog/           # Storefront controllers, models, Twig templates, CSS, JS
│   └── image/             # Brand logos and currency icons
└── README.md
```

---

## Installation

### Method 1: OpenCart Extension Installer (Zip Archive)
1. Build the archive using the repository packaging tool:
   ```bash
   php cli/package_modules.php
   ```
2. Navigate to **Admin Panel -> Extensions -> Installer**.
3. Upload `dist/soundnet_storefront.ocmod.zip`.
4. Navigate to **Extensions -> Modifications** and click **Refresh** (blue refresh icon).
5. Navigate to **Extensions -> Extensions -> Modules**, find **SoundNet Storefront**, and click **Install** then **Edit** to configure settings.

### Method 2: Direct File Copy (Development / OSPanel)
1. Copy all contents of `upload/` directly into your OpenCart web root.
2. In the OpenCart admin panel, install the modification XML from `install.xml`.
3. Refresh the modification cache via **Extensions -> Modifications -> Refresh** or run:
   ```bash
   php cli/admin_cli.php cache:clear
   ```

---

## Key Template & Asset Locations

- **Stylesheet:** `catalog/view/theme/default/stylesheet/soundnet_storefront.css`
- **JavaScript:** `catalog/view/javascript/soundnet_storefront.js`
- **Product Card Template:** `catalog/view/theme/default/template/product/product.twig`
- **Home Layout:** `catalog/view/theme/default/template/common/home.twig`
- **Recommendations Component:** `catalog/view/theme/default/template/extension/module/soundnet_recommendations.twig`
