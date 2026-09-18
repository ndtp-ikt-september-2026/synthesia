# SoundNet Dynamic Tracklist & Audio Player Module Specification

`soundnet_tracklist` is a comprehensive audio playback and tracklist management extension for OpenCart 3.0.3.x. It enables digital audio previews, streaming playback, and relational release tracklists for music albums, vinyl records, and physical CDs.

---

## 1. Problem Statement & Solution

Standard OpenCart treats products as static physical units with simple options and attributes. For music e-commerce, customers expect:
1. Complete release tracklists with track numbers, titles, and exact durations.
2. Direct audio preview streaming (30–90 second snippets) without leaving the product page.
3. Ergonomic presentation that avoids pushing pricing and buy actions off-screen on long tracklists.

`soundnet_tracklist` implements a dedicated relational data model, an AJAX admin editing table, a lightweight HTML5 streaming player, and smart tracklist truncation.

---

## 2. Database Schema

The module introduces the `oc_product_tracklist` table:

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

### Field Definitions:
- `track_id`: Primary auto-incrementing key.
- `product_id`: Foreign key referencing `oc_product.product_id`.
- `track_num`: Logical track position on physical disc / side (e.g. 1, 2, 3).
- `title`: Track title (e.g. *"The Great Gig in the Sky"*).
- `duration`: Formatted duration string (e.g. `"4:44"` or `"04:44"`).
- `preview_file`: Relative file path (`upload/audio/...`) or absolute HTTPS streaming URL (`https://cdn.example.com/...mp3`).
- `status`: Active flag (`1` = visible, `0` = disabled).
- `sort_order`: Display sorting weight.

---

## 3. Administration Management Tab (`tab-tracklist`)

The module injects an interactive tab into OpenCart's catalog product form (`admin/view/template/catalog/product_form.twig`):

```
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│ [General] [Data] [Links] [Attribute] [Option] [Tracklist & Previews 🎵] [Design]            │
├─────────────────────────────────────────────────────────────────────────────────────────────┤
│ ℹ️ SoundNet Audio Engine: Manage tracks, durations, and audio preview snippets.              │
│                                                                                             │
│ ┌───┬─────────────────────────┬──────────┬─────────────────────────────────┬────────┬─────┐ │
│ │ # │ Track Title             │ Duration │ Preview Audio File / URL        │ Status │ Act │ │
│ ├───┼─────────────────────────┼──────────┼─────────────────────────────────┼────────┼─────┤ │
│ │ 1 │ Speak to Me             │ 1:30     │ [upload/audio/sample_01.mp3] ⬆️ │ Active │ ❌  │ │
│ │ 2 │ Breathe (In the Air)    │ 2:43     │ [https://cdn.stream/02.mp3]  ⬆️ │ Active │ ❌  │ │
│ │ 3 │ On the Run              │ 3:30     │ [upload/audio/sample_03.mp3] ⬆️ │ Active │ ❌  │ │
│ └───┴─────────────────────────┴──────────┴─────────────────────────────────┴────────┴─────┘ │
│                                                                        [ + Add Track ]      │
└─────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Features:
- **AJAX Dynamic Rows:** Add or delete tracks dynamically using client-side JavaScript.
- **Audio File Upload:** Direct modal upload for MP3, WAV, OGG, and FLAC files. Automatically populates the file path into the target input field.
- **External CDN Streaming:** Seamlessly accepts HTTPS URLs from remote audio servers and CDNs.

---

## 4. Frontend Audio Player & Collapsible View

### 4.1 HTML5 Streaming Engine (`tracklist.js` & `tracklist.css`)
- **Zero-Dependency Audio Core:** Built on the native Web Audio API / HTML5 `<audio>` element.
- **Interactive Controls:** Play/pause toggle button on each track row, global scrub bar, elapsed/remaining time indicator, and smooth volume slider.
- **Active State:** Highlights currently playing track row with pulsing equalizer indicator.
- **Automatic Next-Track Playback:** Advances to the next available preview track upon playback completion.

### 4.2 Collapsible Full/Less UI
Albums with many tracks (e.g. double vinyls or CD compilations with 12–30 tracks) can push the description and purchase controls too far down the page. 

To solve this:
1. In `catalog/view/theme/default/template/product/product.twig`, the template checks `tracks|length > 8`.
2. Tracks 1 through 8 are displayed normally.
3. Tracks 9+ are placed in an expandable container with class `soundnet-tracklist-collapsed`.
4. A button is rendered below the list:
   ```html
   <button type="button" class="btn-soundnet-tracklist-toggle" id="btn-toggle-tracks">
     <span class="text-expand">Показать все {{ tracks|length }} треков</span>
     <span class="text-collapse">Свернуть</span>
     <i class="fa fa-chevron-down"></i>
   </button>
   ```
5. Clicking toggles smooth CSS max-height transition between 8 tracks and full view.

---

## 5. Headless CLI Administration

### 5.1 Admin CLI (`cli/admin_cli.php`)

```bash
# Retrieve tracklist for a product in JSON format
php cli/admin_cli.php tracklist:get --product_id=1

# Replace tracklist via JSON payload file
php cli/admin_cli.php tracklist:set --product_id=1 --payload-file=tracks.json

# Replace tracklist via inline JSON
php cli/admin_cli.php tracklist:set --product_id=1 --payload='[{"track_num":1,"title":"Intro","duration":"1:20","preview_file":"upload/audio/intro.mp3"}]'

# Dry-run validation of tracklist payload
php cli/admin_cli.php tracklist:set --product_id=1 --payload-file=tracks.json --dry-run

# Clear all tracks for a product
php cli/admin_cli.php tracklist:clear --product_id=1
```

### 5.2 Description Cleaner (`cli/clean_description_tracklists.php`)
Migrates legacy tracklists embedded as raw text in product descriptions into clean relational `oc_product_tracklist` records, eliminating duplication between descriptions and the audio player.

```bash
php cli/clean_description_tracklists.php
```

---

## 6. Distribution & Packaging

- **Package Source:** [`packages/soundnet_tracklist/`](../../packages/soundnet_tracklist/)
- **Compiled Archive:** `dist/soundnet_tracklist.ocmod.zip`
- **Compiler Command:** `php cli/package_modules.php soundnet_tracklist`
