<?php
require 'config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);

$attrIds = [
    'Artist' => 1,
    'Genre' => 2,
    'Year' => 3,
    'Label' => 4,
    'Format' => 5,
    'Brand' => 7
];

foreach ($attrIds as $name => $id) {
    $q = $db->query("
        SELECT DISTINCT TRIM(pa.text) as val 
        FROM " . DB_PREFIX . "product_attribute pa 
        WHERE pa.attribute_id = $id AND TRIM(pa.text) != ''
        ORDER BY val ASC
    ");
    $vals = [];
    while ($r = $q->fetch_assoc()) $vals[] = $r['val'];
    echo "$name (ID $id): " . count($vals) . " distinct values\n";
    echo "  Samples: " . implode(', ', array_slice($vals, 0, 8)) . "\n\n";
}
