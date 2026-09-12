<?php
require_once __DIR__ . '/../config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
$db->set_charset("utf8mb4");

$res = $db->query("
    SELECT p.product_id, p.model, pd.name, p2c.category_id, cd.name as cat_name
    FROM " . DB_PREFIX . "product p
    LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
    JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
    JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id AND cd.language_id = 1)
    WHERE pd.name LIKE '%Stratocaster%' OR pd.name LIKE '%Fender%' OR p.product_id < 10
    LIMIT 20
");
$res = $db->query("
    SELECT p.product_id, p.model, pd.name, p2c.category_id, p2c.main_category, cd.name as cat_name 
    FROM " . DB_PREFIX . "product p 
    JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
    JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
    JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id AND cd.language_id = 1)
    WHERE p.product_id IN (2, 3, 4)
");
while ($r = $res->fetch_assoc()) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

while ($r = $res->fetch_assoc()) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}


