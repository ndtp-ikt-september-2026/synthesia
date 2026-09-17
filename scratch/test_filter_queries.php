<?php
require 'config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);

$sql = "
    SELECT p.product_id, pd.name 
    FROM " . DB_PREFIX . "product p 
    LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
    WHERE pd.language_id = 1
      AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 1 AND pa.text LIKE '%Pink Floyd%')
      AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 3 AND pa.text LIKE '%1973%')
";
$res = $db->query($sql);
echo "Pink Floyd 1973 results:\n";
while ($r = $res->fetch_assoc()) {
    echo "  PID {$r['product_id']}: {$r['name']}\n";
}

$sql2 = "
    SELECT p.product_id, pd.name 
    FROM " . DB_PREFIX . "product p 
    LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
    WHERE pd.language_id = 1
      AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 7 AND pa.text LIKE '%Fender%')
";
$res2 = $db->query($sql2);
echo "\nFender results (first 5):\n";
$i = 0;
while ($r = $res2->fetch_assoc()) {
    if (++$i > 5) break;
    echo "  PID {$r['product_id']}: {$r['name']}\n";
}
