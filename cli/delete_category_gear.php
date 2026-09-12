<?php
/**
 * CLI Migration: Delete Category 5 ("Музыкальные инструменты и оборудование") and redundant subcategories (6-10)
 */

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}
$db->set_charset("utf8mb4");

$catIds = [5, 6, 7, 8, 9, 10];
$catList = implode(',', $catIds);

echo "Starting deletion of categories: $catList\n";

// 1. Remap Product 2 (Fender American Professional II Stratocaster)
echo "Remapping product 2 to category 15 (Электрогитары) and 11 (Гитары)...\n";
$db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = 2");
$db->query("INSERT INTO " . DB_PREFIX . "product_to_category (product_id, category_id, main_category) VALUES (2, 15, 1)");
$db->query("INSERT INTO " . DB_PREFIX . "product_to_category (product_id, category_id, main_category) VALUES (2, 11, 0)");

// Remap test guitars 3 and 4 to 15 & 11
echo "Remapping test guitars 3 and 4 from category 1 to 15 and 11...\n";
foreach ([3, 4] as $pid) {
    $db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = $pid AND category_id = 1");
    // Ensure they are in 15 and 11
    $db->query("INSERT IGNORE INTO " . DB_PREFIX . "product_to_category (product_id, category_id, main_category) VALUES ($pid, 15, 1)");
    $db->query("INSERT IGNORE INTO " . DB_PREFIX . "product_to_category (product_id, category_id, main_category) VALUES ($pid, 11, 0)");
}

// 2. Delete any remaining product associations with 5, 6, 7, 8, 9, 10
$db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE category_id IN ($catList)");
echo "Removed product associations in oc_product_to_category for ($catList)\n";

// 3. Delete from oc_category_path
$db->query("DELETE FROM " . DB_PREFIX . "category_path WHERE category_id IN ($catList) OR path_id IN ($catList)");
echo "Deleted from oc_category_path\n";

// 4. Delete from oc_category_description
$db->query("DELETE FROM " . DB_PREFIX . "category_description WHERE category_id IN ($catList)");
echo "Deleted from oc_category_description\n";

// 5. Delete from oc_category_to_store
$db->query("DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id IN ($catList)");
echo "Deleted from oc_category_to_store\n";

// 6. Delete from oc_category_to_layout
$db->query("DELETE FROM " . DB_PREFIX . "category_to_layout WHERE category_id IN ($catList)");
echo "Deleted from oc_category_to_layout\n";

// 7. Delete from oc_category_filter
$db->query("DELETE FROM " . DB_PREFIX . "category_filter WHERE category_id IN ($catList)");
echo "Deleted from oc_category_filter\n";

// 8. Delete from oc_seo_url for categories 5, 6, 7, 8, 9, 10
$db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query IN ('category_id=5', 'category_id=6', 'category_id=7', 'category_id=8', 'category_id=9', 'category_id=10')");
echo "Deleted SEO URLs for ($catList)\n";

// 9. Delete from oc_category
$db->query("DELETE FROM " . DB_PREFIX . "category WHERE category_id IN ($catList)");
echo "Deleted categories from oc_category\n";

// 10. Update Category 11 SEO URL to 'gitary' for ru-ru
$checkSeo = $db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = 'gitary'");
if ($checkSeo->num_rows == 0) {
    $db->query("UPDATE " . DB_PREFIX . "seo_url SET keyword = 'gitary' WHERE query = 'category_id=11' AND (language_id = 1 OR language_id = (SELECT language_id FROM " . DB_PREFIX . "language WHERE code LIKE 'ru%' LIMIT 1))");
    echo "Updated Category 11 SEO keyword to 'gitary'\n";
} else {
    echo "Notice: 'gitary' keyword still occupied by: " . json_encode($checkSeo->fetch_assoc()) . "\n";
}

// 11. Clear OpenCart Caches
$cacheDirs = [
    DIR_CACHE,
    DIR_STORAGE . 'cache/',
    DIR_STORAGE . 'cache/template/'
];
$cleared = 0;
foreach ($cacheDirs as $dir) {
    if (is_dir($dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            if ($fileinfo->isFile()) {
                @unlink($fileinfo->getRealPath());
                $cleared++;
            }
        }
    }
}
echo "Cleared $cleared cache files.\n";
echo "MIGRATION COMPLETED SUCCESSFULLY.\n";
