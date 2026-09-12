<?php
require_once __DIR__ . '/../config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
$db->set_charset("utf8mb4");

$res = $db->query("SELECT p.*, pd.name, pd.description FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id WHERE p.product_id = 2 AND pd.language_id = 1");
print_r($res->fetch_assoc());

$res2 = $db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = 2");
while ($r = $res2->fetch_assoc()) {
    print_r($r);
}
