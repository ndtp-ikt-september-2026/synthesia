<?php
require 'config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
if ($db->connect_error) die("DB error: " . $db->connect_error);

echo "=== OC_PRODUCT COLUMNS ===\n";
$res = $db->query("SHOW COLUMNS FROM " . DB_PREFIX . "product");
while ($r = $res->fetch_assoc()) {
    echo "{$r['Field']} ({$r['Type']})\n";
}

echo "\n=== ATTRIBUTE GROUPS & ATTRIBUTES ===\n";
$res = $db->query("
    SELECT a.attribute_id, ad.name as attr_name, agd.name as group_name 
    FROM " . DB_PREFIX . "attribute a
    LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id AND ad.language_id = 1)
    LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (a.attribute_group_id = agd.attribute_group_id AND agd.language_id = 1)
");
while ($r = $res->fetch_assoc()) {
    echo "Attr #{$r['attribute_id']}: {$r['attr_name']} [Group: {$r['group_name']}]\n";
}

echo "\n=== SAMPLE PRODUCT ATTRIBUTES (first 10) ===\n";
$res = $db->query("
    SELECT pa.product_id, ad.name as attr_name, pa.text 
    FROM " . DB_PREFIX . "product_attribute pa
    LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (pa.attribute_id = ad.attribute_id AND ad.language_id = 1)
    LIMIT 15
");
while ($r = $res->fetch_assoc()) {
    echo "PID {$r['product_id']}: {$r['attr_name']} => '{$r['text']}'\n";
}

echo "\n=== MANUFACTURERS ===\n";
$res = $db->query("SELECT manufacturer_id, name FROM " . DB_PREFIX . "manufacturer LIMIT 10");
while ($r = $res->fetch_assoc()) {
    echo "Mfr #{$r['manufacturer_id']}: {$r['name']}\n";
}

echo "\n=== SAMPLE PRODUCTS (first 3) ===\n";
$res = $db->query("
    SELECT p.product_id, pd.name, p.model, p.manufacturer_id, m.name as manufacturer_name
    FROM " . DB_PREFIX . "product p
    LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
    LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
    LIMIT 5
");
while ($r = $res->fetch_assoc()) {
    echo "PID {$r['product_id']}: '{$r['name']}', Mfr: '{$r['manufacturer_name']}', Model: '{$r['model']}'\n";
}
