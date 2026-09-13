<?php
require_once __DIR__ . '/../config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
$res = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` LIKE '%image_cart%'");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

// Also check product TERRIS TC-3801A SB
$res2 = $db->query("SELECT product_id, image FROM " . DB_PREFIX . "product WHERE model LIKE '%TERRIS%' OR product_id IN (SELECT product_id FROM " . DB_PREFIX . "product_description WHERE name LIKE '%TERRIS%')");
while ($r = $res2->fetch_assoc()) {
    print_r($r);
}
