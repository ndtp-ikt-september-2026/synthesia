<?php
require_once __DIR__ . '/../config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== LAYOUT MODULES FOR LAYOUT 1 (Home) ===\n";
$res = $db->query("SELECT lm.*, l.name as layout_name FROM oc_layout_module lm JOIN oc_layout l ON lm.layout_id = l.layout_id WHERE lm.layout_id = 1 ORDER BY lm.position, lm.sort_order");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['layout_module_id']} | code: {$row['code']} | position: {$row['position']} | sort: {$row['sort_order']}\n";
}

echo "\n=== ALL MODULES IN oc_module ===\n";
$res2 = $db->query("SELECT * FROM oc_module");
while ($row = $res2->fetch_assoc()) {
    echo "Module ID: {$row['module_id']} | name: {$row['name']} | code: {$row['code']}\n";
}

echo "\n=== CATEGORIES ===\n";
$res3 = $db->query("SELECT c.category_id, cd.name, c.parent_id FROM oc_category c JOIN oc_category_description cd ON c.category_id = cd.category_id WHERE cd.language_id = 1");
while ($row = $res3->fetch_assoc()) {
    echo "Cat: {$row['category_id']} | {$row['name']} | parent: {$row['parent_id']}\n";
}

echo "\n=== CONFIG TELEPHONE, EMAIL, ADDRESS ===\n";
$res4 = $db->query("SELECT `key`, `value` FROM oc_setting WHERE `key` IN ('config_telephone', 'config_email', 'config_address', 'config_open', 'config_comment')");
while ($row = $res4->fetch_assoc()) {
    echo "Setting: {$row['key']} => {$row['value']}\n";
}

echo "\n=== CATEGORY IMAGES AND TOP PRODUCT IMAGES ===\n";
$cat_ids = array(2, 3, 15, 12, 24);
foreach ($cat_ids as $cid) {
    $q_c = $db->query("SELECT c.category_id, c.image, cd.name FROM oc_category c JOIN oc_category_description cd ON c.category_id=cd.category_id WHERE c.category_id=$cid AND cd.language_id=1");
    $crow = $q_c->fetch_assoc();
    echo "Cat {$cid} ({$crow['name']}): image = '{$crow['image']}'\n";
}
echo "\n=== CATEGORY 3 PRODUCTS ===\n";
$q_c3 = $db->query("SELECT p.product_id, p.image, pd.name FROM oc_product p JOIN oc_product_to_category p2c ON p.product_id=p2c.product_id JOIN oc_product_description pd ON p.product_id=pd.product_id WHERE p2c.category_id=3");
while ($r3 = $q_c3->fetch_assoc()) {
    $ex = is_file(DIR_IMAGE . $r3['image']) ? 'EXISTS' : 'NOT FOUND';
    echo "Product {$r3['product_id']}: {$r3['name']} | image: {$r3['image']} ({$ex})\n";
}
$q = $db->query("SELECT p2c.category_id, cd.name FROM oc_product_to_category p2c JOIN oc_category_description cd ON p2c.category_id = cd.category_id WHERE p2c.product_id = 42 AND cd.language_id=1");
while ($r = $q->fetch_assoc()) {
    echo "Cat: " . $r['category_id'] . " " . $r['name'] . "\n";
}
$q2 = $db->query("SELECT pa.attribute_id, ad.name, pa.text FROM oc_product_attribute pa JOIN oc_attribute_description ad ON pa.attribute_id = ad.attribute_id WHERE pa.product_id = 42 AND ad.language_id=1");
while ($r2 = $q2->fetch_assoc()) {
    echo "Attr: " . $r2['name'] . " = " . $r2['text'] . "\n";
}
