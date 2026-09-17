<?php
require 'config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);

$sql = "
    SELECT p.product_id, pd.name 
    FROM " . DB_PREFIX . "product p 
    LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
    WHERE pd.language_id = 1
      AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 1 AND pa.text LIKE '%Pink Floyd%')
    ORDER BY pd.name ASC
    LIMIT 20
";
$res = $db->query($sql);
while ($r = $res->fetch_assoc()) {
    echo "PID {$r['product_id']}: {$r['name']}\n";
}
