<?php
require 'config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$res = $db->query("
    SELECT a.attribute_id, ad.name 
    FROM " . DB_PREFIX . "attribute a 
    JOIN " . DB_PREFIX . "attribute_description ad ON a.attribute_id = ad.attribute_id 
    WHERE ad.name IN ('Исполнитель', 'Год выпуска', 'Жанр', 'Бренд', 'Лейбл', 'Формат издания')
");
while ($r = $res->fetch_assoc()) {
    echo "{$r['name']} => ID {$r['attribute_id']}\n";
}

// Check sample distinct values for each
$keys = ['Исполнитель', 'Год выпуска', 'Жанр', 'Бренд'];
foreach ($keys as $k) {
    echo "\nSample values for '$k':\n";
    $q = $db->query("
        SELECT DISTINCT pa.text 
        FROM " . DB_PREFIX . "product_attribute pa
        JOIN " . DB_PREFIX . "attribute_description ad ON pa.attribute_id = ad.attribute_id
        WHERE ad.name = '$k'
        ORDER BY pa.text ASC
        LIMIT 10
    ");
    while ($row = $q->fetch_assoc()) {
        echo "  - " . trim($row['text']) . "\n";
    }
}
