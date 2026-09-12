<?php
require_once __DIR__ . '/../config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

$db->set_charset("utf8mb4");

$query = "
    SELECT c.category_id, c.parent_id, c.top, c.column, c.sort_order, c.status, cd.language_id, cd.name,
           (SELECT COUNT(*) FROM " . DB_PREFIX . "product_to_category p2c WHERE p2c.category_id = c.category_id) AS prod_count,
           (SELECT COUNT(*) FROM " . DB_PREFIX . "category sub WHERE sub.parent_id = c.category_id) AS sub_count
    FROM " . DB_PREFIX . "category c
    LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
    ORDER BY c.parent_id ASC, c.sort_order ASC, c.category_id ASC
";

$res = $db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = 2");
while ($row = $res->fetch_assoc()) {
    echo "Product 2 category: " . json_encode($row) . "\n";
}

$res = $db->query("SELECT p2c.*, pd.name FROM " . DB_PREFIX . "product_to_category p2c LEFT JOIN " . DB_PREFIX . "product_description pd ON p2c.product_id = pd.product_id WHERE p2c.category_id IN (5,6,7,8,9,10)");
while ($row = $res->fetch_assoc()) {
    echo "P2C under 5-10: " . json_encode($row) . "\n";
}


$res = $db->query("SELECT * FROM " . DB_PREFIX . "category WHERE category_id = 5");
echo "Cat 5 row: " . json_encode($res->fetch_assoc()) . "\n";

$res = $db->query("SELECT * FROM " . DB_PREFIX . "category_path WHERE category_id = 5 OR path_id = 5");
while ($row = $res->fetch_assoc()) {
    echo "Cat path: " . json_encode($row) . "\n";
}

$res = $db->query("SELECT c.category_id, cd.name FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id AND cd.language_id = 1) WHERE c.parent_id = 5");
while ($row = $res->fetch_assoc()) {
    echo "Child of 5: " . json_encode($row) . "\n";
}


