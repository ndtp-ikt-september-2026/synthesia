<?php
require_once __DIR__ . '/../config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
$db->set_charset("utf8mb4");

$res = $db->query("
    SELECT c.category_id, cd.name, COUNT(p2c.product_id) as prods
    FROM " . DB_PREFIX . "category c
    JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id AND cd.language_id = 1)
    LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (c.category_id = p2c.category_id)
    WHERE c.category_id IN (16, 17, 18, 19, 20, 21, 27, 28, 29, 30, 31)
    GROUP BY c.category_id
");
while ($r = $res->fetch_assoc()) {
    echo $r['category_id'] . " | " . $r['name'] . " | " . $r['prods'] . " products\n";
}
