<?php
/**
 * Utility to clean duplicated tracklists from product descriptions in OpenCart.
 * 
 * 1. Removes <h4>Треклист:</h4><ol>...</ol> blocks from product descriptions.
 * 2. For legacy products (IDs 164, 165, 172-176) with plain-text tracklists:
 *    - Parses and inserts tracks into oc_product_tracklist
 *    - Cleans tracklists out of their descriptions, preserving release notes
 * 3. Clears OpenCart system and template cache
 */

require_once __DIR__ . '/../config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
if ($db->connect_error) {
    fwrite(STDERR, "[ERROR] DB connect failed: " . $db->connect_error . PHP_EOL);
    exit(1);
}
$db->set_charset('utf8mb4');

echo "========================================================\n";
echo "  SYNTHESIA - DEDUPLICATE TRACKLISTS IN DESCRIPTIONS\n";
echo "========================================================\n\n";

// --- 1. Clean HTML <h4>Треклист:</h4><ol>...</ol> descriptions ---
$h4Pattern = '/\s*<h4>Треклист:<\/h4>\s*<ol>.*?<\/ol>\s*/is';

$res = $db->query("SELECT pd.product_id, pd.language_id, pd.name, pd.description 
                   FROM " . DB_PREFIX . "product_description pd 
                   WHERE pd.description LIKE '%<h4>Треклист:</h4>%'");

$h4Count = 0;
echo "1. Processing HTML tracklist blocks (<h4>Треклист:</h4>)...\n";
while ($row = $res->fetch_assoc()) {
    $pid = (int)$row['product_id'];
    $langId = (int)$row['language_id'];
    $original = $row['description'];

    $cleaned = preg_replace($h4Pattern, "\n", $original);
    $cleaned = trim($cleaned);

    if ($cleaned !== $original) {
        $stmt = $db->prepare("UPDATE " . DB_PREFIX . "product_description SET description = ? WHERE product_id = ? AND language_id = ?");
        $stmt->bind_param('sii', $cleaned, $pid, $langId);
        $stmt->execute();
        $stmt->close();
        $h4Count++;
    }
}
echo "   Cleaned $h4Count product description(s).\n\n";

// --- 2. Process legacy plain-text tracklist products (164, 165, 172-176) ---
$legacyIds = [164, 165, 172, 173, 174, 175, 176];
echo "2. Processing legacy text tracklists for IDs: " . implode(', ', $legacyIds) . "...\n";

foreach ($legacyIds as $pid) {
    $res = $db->query("SELECT pd.language_id, pd.name, pd.description, p.model 
                       FROM " . DB_PREFIX . "product_description pd 
                       JOIN " . DB_PREFIX . "product p ON pd.product_id = p.product_id 
                       WHERE pd.product_id = $pid");

    while ($row = $res->fetch_assoc()) {
        $langId = (int)$row['language_id'];
        $desc = $row['description'];

        if (stripos($desc, 'Треклист:') === false) {
            continue;
        }

        // Split tracklist and release info
        $tracklistPart = '';
        $infoPart = '';

        if (preg_match('/Треклист:(.*?)(?=Информация о релизе:|$)/is', $desc, $m)) {
            $tracklistPart = trim($m[1]);
        }
        if (preg_match('/Информация о релизе:(.*)$/is', $desc, $m)) {
            $infoPart = trim($m[1]);
        }

        // Parse track lines
        $trackLines = array_filter(array_map('trim', explode("\n", $tracklistPart)));
        $parsedTracks = [];
        foreach ($trackLines as $line) {
            if (preg_match('/^([A-Za-z0-9\.\-]+)\s+(.*?)(?:\s+\((\d+:\d{2})\))?$/', $line, $tm)) {
                $parsedTracks[] = [
                    'pos'      => rtrim($tm[1], '.'),
                    'title'    => trim($tm[2]),
                    'duration' => $tm[3] ?? '0:00'
                ];
            }
        }

        // Insert into oc_product_tracklist if not already present
        $checkTracks = $db->query("SELECT COUNT(*) as cnt FROM " . DB_PREFIX . "product_tracklist WHERE product_id = $pid");
        $existingTracksCount = (int)$checkTracks->fetch_assoc()['cnt'];

        if ($existingTracksCount === 0 && !empty($parsedTracks)) {
            $tIdx = 1;
            foreach ($parsedTracks as $t) {
                $pos = (int)preg_replace('/[^0-9]/', '', $t['pos']);
                if ($pos <= 0) $pos = $tIdx;
                $titleEsc = $db->real_escape_string($t['title']);
                $durEsc = $db->real_escape_string($t['duration']);


                $db->query("INSERT INTO " . DB_PREFIX . "product_tracklist 
                            (product_id, track_num, title, duration, preview_file, status, sort_order) 
                            VALUES ($pid, $pos, '$titleEsc', '$durEsc', '', 1, " . ($tIdx - 1) . ")");
                $tIdx++;
            }
            echo "   - Product $pid ({$row['name']}): inserted " . count($parsedTracks) . " track(s) into " . DB_PREFIX . "product_tracklist\n";
        }

        // Clean description: format cleanly with artist/album header and release notes if available
        $cleanDesc = '';
        // Extract artist, title, label from attributes if possible
        $attrRes = $db->query("SELECT ad.name as attr_name, pa.text 
                               FROM " . DB_PREFIX . "product_attribute pa 
                               JOIN " . DB_PREFIX . "attribute_description ad ON (pa.attribute_id = ad.attribute_id AND ad.language_id = $langId) 
                               WHERE pa.product_id = $pid AND pa.language_id = $langId");
        $attrs = [];
        while ($ar = $attrRes->fetch_assoc()) {
            $attrs[$ar['attr_name']] = $ar['text'];
        }

        $artist = $attrs['Исполнитель'] ?? '';
        $year = $attrs['Год выпуска'] ?? '';
        $label = $attrs['Лейбл'] ?? '';
        $yearSuffix = $year ? " ($year)" : '';

        $descParts = [];
        if ($artist || $label) {
            $descParts[] = "<p><strong>Исполнитель:</strong> " . htmlspecialchars($artist) . "<br><strong>Альбом:</strong> " . htmlspecialchars($row['name']) . $yearSuffix . "<br><strong>Лейбл:</strong> " . htmlspecialchars($label) . "</p>";
        }

        if ($infoPart) {
            $descParts[] = "<p><strong>Информация о релизе:</strong><br>" . nl2br(htmlspecialchars($infoPart)) . "</p>";
        }

        $newDesc = implode("\n", $descParts);

        $stmt = $db->prepare("UPDATE " . DB_PREFIX . "product_description SET description = ? WHERE product_id = ? AND language_id = ?");
        $stmt->bind_param('sii', $newDesc, $pid, $langId);
        $stmt->execute();
        $stmt->close();
        echo "   - Product $pid: description cleaned.\n";
    }
}
echo "\n";

// --- 3. Purge OpenCart Cache ---
echo "3. Purging OpenCart system and template cache...\n";
$cacheDirs = [
    DIR_CACHE,
    DIR_STORAGE . 'cache/',
    DIR_IMAGE . 'cache/'
];
$cacheDirs = array_unique(array_filter($cacheDirs, 'is_dir'));
$clearedFiles = 0;

foreach ($cacheDirs as $cDir) {
    if (is_dir($cDir)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            if ($f->isFile()) {
                @unlink($f->getPathname());
                $clearedFiles++;
            }
        }
    }
}
echo "   Cleared $clearedFiles cache file(s).\n\n";

// --- 4. Final verification ---
$remH4 = $db->query("SELECT COUNT(*) as cnt FROM " . DB_PREFIX . "product_description WHERE description LIKE '%<h4>Треклист:</h4>%'")->fetch_assoc()['cnt'];
$remText = $db->query("SELECT COUNT(*) as cnt FROM " . DB_PREFIX . "product_description WHERE description LIKE '%Треклист:%'")->fetch_assoc()['cnt'];

echo "========================================================\n";
echo "  SUMMARY:\n";
echo "  - Products with <h4>Треклист:</h4> remaining: $remH4\n";
echo "  - Products with 'Треклист:' text remaining: $remText\n";
echo "========================================================\n";
echo "[SUCCESS] Duplicate tracklists successfully purged from descriptions.\n";
