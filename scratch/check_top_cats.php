<?php
require_once __DIR__ . '/../config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
$db->set_charset("utf8mb4");

$res = $db->query("
    SELECT c.category_id, c.sort_order, cd.name 
    FROM " . DB_PREFIX . "category c 
    JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id AND cd.language_id = 1)
    WHERE c.parent_id = 0
    ORDER BY c.sort_order ASC, c.category_id ASC
");
while ($r = $res->fetch_assoc()) {
    echo $r['category_id'] . " | sort: " . $r['sort_order'] . " | name: " . $r['name'] . "\n";
}
