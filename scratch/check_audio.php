<?php
require_once('config.php');
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
$res = $db->query("SELECT product_id, track_num, title, duration, preview_file FROM oc_product_tracklist WHERE preview_file != '' LIMIT 10");
echo "Sample tracks with preview files:\n";
while ($row = $res->fetch_assoc()) {
    $exists = file_exists($row['preview_file']) ? 'YES' : 'NO';
    echo "Product {$row['product_id']} #{$row['track_num']} - {$row['title']} (Exists: $exists): {$row['preview_file']}\n";
}
