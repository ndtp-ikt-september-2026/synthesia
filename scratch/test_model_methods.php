<?php
require 'config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);

// Test getDistinctAttributeValues for Years (DESC)
$q = $db->query("SELECT DISTINCT TRIM(text) AS val FROM " . DB_PREFIX . "product_attribute WHERE attribute_id = 3 AND TRIM(text) != '' ORDER BY val DESC");
$years = [];
while ($r = $q->fetch_assoc()) $years[] = $r['val'];
echo "Years (count " . count($years) . "): " . implode(', ', array_slice($years, 0, 10)) . "...\n";

// Test getDistinctAttributeValues for Genres
$q = $db->query("SELECT DISTINCT TRIM(text) AS val FROM " . DB_PREFIX . "product_attribute WHERE attribute_id = 2 AND TRIM(text) != '' ORDER BY val ASC");
$genres = [];
while ($r = $q->fetch_assoc()) $genres[] = $r['val'];
echo "Genres (count " . count($genres) . "): " . implode(', ', $genres) . "\n";

// Test getDistinctAttributeValues for Formats
$q = $db->query("SELECT DISTINCT TRIM(text) AS val FROM " . DB_PREFIX . "product_attribute WHERE attribute_id = 5 AND TRIM(text) != '' ORDER BY val ASC");
$formats = [];
while ($r = $q->fetch_assoc()) $formats[] = $r['val'];
echo "Formats (count " . count($formats) . "): " . implode(', ', $formats) . "\n";

// Test getProductsMusicAttributes for first 5 products
$q = $db->query("SELECT product_id FROM " . DB_PREFIX . "product LIMIT 5");
$pids = [];
while ($r = $q->fetch_assoc()) $pids[] = $r['product_id'];

$q2 = $db->query("SELECT product_id, attribute_id, TRIM(text) as text FROM " . DB_PREFIX . "product_attribute WHERE product_id IN (" . implode(',', $pids) . ") AND attribute_id IN (1, 2, 3, 5, 7)");
$prodAttrs = [];
while ($r = $q2->fetch_assoc()) {
    $prodAttrs[$r['product_id']][$r['attribute_id']] = $r['text'];
}
echo "\nMusic attributes sample:\n";
foreach ($pids as $pid) {
    $artist = $prodAttrs[$pid][1] ?? '-';
    $year   = $prodAttrs[$pid][3] ?? '-';
    $genre  = $prodAttrs[$pid][2] ?? '-';
    $brand  = $prodAttrs[$pid][7] ?? '-';
    echo "  PID $pid: Artist='$artist', Year='$year', Genre='$genre', Brand='$brand'\n";
}
