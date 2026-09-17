<?php
require 'config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);

echo "=== DISTINCT ATTRIBUTE NAMES RELATED TO ARTIST / YEAR / GENRE / BRAND ===\n";
$res = $db->query("
    SELECT ad.name, COUNT(DISTINCT pa.product_id) as prod_count 
    FROM " . DB_PREFIX . "product_attribute pa
    JOIN " . DB_PREFIX . "attribute_description ad ON (pa.attribute_id = ad.attribute_id AND ad.language_id = 1)
    WHERE ad.name LIKE '%исполнител%' 
       OR ad.name LIKE '%artist%' 
       OR ad.name LIKE '%бренд%' 
       OR ad.name LIKE '%brand%' 
       OR ad.name LIKE '%год%' 
       OR ad.name LIKE '%year%' 
       OR ad.name LIKE '%жанр%' 
       OR ad.name LIKE '%genre%'
       OR ad.name LIKE '%лейбл%'
       OR ad.name LIKE '%формат%'
       OR ad.name LIKE '%тип%'
    GROUP BY ad.name
    ORDER BY prod_count DESC
");
while ($r = $res->fetch_assoc()) {
    echo "{$r['name']}: {$r['prod_count']} products\n";
}

echo "\n=== CHECK MANUFACTURER USAGE ===\n";
$res = $db->query("SELECT COUNT(*) as c FROM " . DB_PREFIX . "product WHERE manufacturer_id > 0");
$r = $res->fetch_assoc();
echo "Products with manufacturer_id > 0: {$r['c']}\n";

$res = $db->query("SELECT COUNT(*) as c FROM " . DB_PREFIX . "manufacturer");
$r = $res->fetch_assoc();
echo "Total manufacturers in oc_manufacturer: {$r['c']}\n";

echo "\n=== CATEGORIES IN OC_CATEGORY ===\n";
$res = $db->query("
    SELECT c.category_id, cd.name, c.parent_id 
    FROM " . DB_PREFIX . "category c
    JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id AND cd.language_id = 1)
    ORDER BY c.parent_id, cd.name
");
while ($r = $res->fetch_assoc()) {
    echo "Cat #{$r['category_id']}: {$r['name']} (parent: {$r['parent_id']})\n";
}
