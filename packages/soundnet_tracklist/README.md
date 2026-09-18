# SoundNet Dynamic Tracklist & Audio Player (`soundnet_tracklist`)

**SoundNet Tracklist** extends OpenCart with native audio engineering capabilities: release tracklist management, audio preview uploads, streaming media players, and database persistence.

---

## Features

- **Dedicated Database Schema:** Stores granular release tracks in `oc_product_tracklist` (track numbers, titles, durations, preview URLs/files, sort order, active status).
- **Admin Tab (`tab-tracklist`):** Interactive track table embedded seamlessly into OpenCart's catalog product edit page (`catalog/product_form.twig`), featuring AJAX row additions, reordering, and direct audio file upload.
- **Frontend Streaming Audio Player:** Lightweight HTML5 audio player widget supporting MP3, WAV, OGG, and FLAC snippets with interactive play/pause, scrub bar, duration counters, and active track indicators.
- **Full & Less Collapsible View:** Automatic truncation for long tracklists (albums with > 8 tracks show an ergonomic "Show all N tracks / Show less" toggle) to keep product pages sleek and prevent excessive vertical scrolling.
- **CLI Management:** Full headless administration via `cli/admin_cli.php tracklist:*` and automated scraper ingestion via `cli/catalog_ingest.php`.

---

## Package Structure

```
packages/soundnet_tracklist/
├── install.xml            # OCMOD rules injecting admin tab and product page hooks
├── upload/
│   ├── admin/             # Admin controller, model, language, and Twig view
│   └── catalog/           # Frontend controller, model, tracklist.js, and tracklist.css
└── README.md
```

---

## Database Table Schema

```sql
CREATE TABLE IF NOT EXISTS `oc_product_tracklist` (
  `track_id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `track_num` INT(4) NOT NULL DEFAULT '1',
  `title` VARCHAR(255) NOT NULL,
  `duration` VARCHAR(32) NOT NULL DEFAULT '',
  `preview_file` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT '1',
  `sort_order` INT(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`track_id`),
  KEY `product_id` (`product_id`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Installation

### Method 1: OpenCart Extension Installer (Zip Archive)
1. Build the archive:
   ```bash
   php cli/package_modules.php
   ```
2. Upload `dist/soundnet_tracklist.ocmod.zip` in **Admin -> Extensions -> Installer**.
3. Go to **Extensions -> Modifications** and click **Refresh**.
4. Enable the module in **Extensions -> Extensions -> Modules -> SoundNet Tracklist**.

### Method 2: Headless Ingestion & CLI
```bash
# Retrieve tracklist for a product
php cli/admin_cli.php tracklist:get --product_id=1

# Set tracklist via JSON stream
php cli/admin_cli.php tracklist:set --product_id=1 --payload='[{"track_num":1,"title":"Speak to Me","duration":"1:30","preview_file":"upload/audio/sample.mp3"}]'
```
