<?php
require 'config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);

$q = $db->query("
    SELECT pa.product_id, pa.attribute_id, pa.text 
    FROM " . DB_PREFIX . "product_attribute pa
    WHERE pa.text LIKE '%Pink Floyd%'
");
while ($r = $q->fetch_assoc()) {
    echo "Product {$r['product_id']}, attr_id {$r['attribute_id']}: '{$r['text']}'\n";
}
