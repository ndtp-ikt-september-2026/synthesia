<?php
/**
 * Utility to purge mock products and products without images.
 *
 * Deletes:
 * 1. Mock / test items (e.g. models starting with TEST-, PF-DSOTM, FEN-AM-PRO2, PF-WYWH)
 * 2. Products with no main image or empty image field
 *
 * Cascades cleanly across all OpenCart and custom database tables,
 * removes empty/orphaned image folders, and clears system cache.
 */

require_once __DIR__ . '/../config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
if ($db->connect_error) {
    fwrite(STDERR, "[ERROR] DB connection failed: " . $db->connect_error . PHP_EOL);
    exit(1);
}
$db->set_charset('utf8mb4');

echo "========================================================\n";
echo "  SYNTHESIA - PURGE MOCK & NO-IMAGE PRODUCTS\n";
echo "========================================================\n\n";

// 1. Identify products to delete
$res = $db->query("SELECT p.product_id, p.model, p.image, pd.name 
                   FROM " . DB_PREFIX . "product p 
                   LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1) 
                   ORDER BY p.product_id ASC");

$targetIds = [];
$targetDetails = [];

while ($row = $res->fetch_assoc()) {
    $pid = (int)$row['product_id'];
    $model = trim($row['model']);
    $image = trim($row['image'] ?? '');
    $name = $row['name'] ?? '';

    $isMock = false;
    $reason = '';

    // Check mock model patterns
    if (stripos($model, 'TEST-') === 0 || 
        $model === 'PF-DSOTM-1973-LP' || 
        $model === 'FEN-AM-PRO2-STRAT-OW' || 
        $model === 'PF-WYWH-1975-LP') {
        $isMock = true;
        $reason = 'Mock/Test Product';
    }

    // Check missing image
    $hasImageFile = (!empty($image) && file_exists(DIR_IMAGE . $image));
    if (!$hasImageFile) {
        if ($isMock) {
            $reason .= ' & No Image';
        } else {
            $reason = 'No Image';
        }
        $targetIds[] = $pid;
        $targetDetails[$pid] = [
            'model' => $model,
            'name' => $name,
            'image' => $image,
            'reason' => $reason
        ];
    } elseif ($isMock) {
        $targetIds[] = $pid;
        $targetDetails[$pid] = [
            'model' => $model,
            'name' => $name,
            'image' => $image,
            'reason' => $reason
        ];
    }
}

$targetIds = array_unique($targetIds);
sort($targetIds);

echo "Found " . count($targetIds) . " products targeted for deletion:\n";
foreach ($targetDetails as $pid => $info) {
    echo sprintf(" - [ID: %3d] [Model: %-25s] Reason: %-25s | Name: %s\n",
        $pid, $info['model'], $info['reason'], $info['name']
    );
}
echo "\n";

if (empty($targetIds)) {
    echo "No products to delete. Catalog is already clean.\n";
    exit(0);
}

$idList = implode(',', $targetIds);

// 2. Cascade delete from all database tables
$tablesWithProductId = [
    DB_PREFIX . 'product' => 'product_id',
    DB_PREFIX . 'product_description' => 'product_id',
    DB_PREFIX . 'product_to_category' => 'product_id',
    DB_PREFIX . 'product_to_store' => 'product_id',
    DB_PREFIX . 'product_to_layout' => 'product_id',
    DB_PREFIX . 'product_attribute' => 'product_id',
    DB_PREFIX . 'product_image' => 'product_id',
    DB_PREFIX . 'product_option' => 'product_id',
    DB_PREFIX . 'product_option_value' => 'product_id',
    DB_PREFIX . 'product_discount' => 'product_id',
    DB_PREFIX . 'product_special' => 'product_id',
    DB_PREFIX . 'product_filter' => 'product_id',
    DB_PREFIX . 'product_reward' => 'product_id',
    DB_PREFIX . 'product_related' => 'product_id',
    DB_PREFIX . 'product_related_article' => 'product_id',
    DB_PREFIX . 'product_related_mn' => 'product_id',
    DB_PREFIX . 'product_related_wb' => 'product_id',
    DB_PREFIX . 'article_related_product' => 'product_id',
    DB_PREFIX . 'product_recurring' => 'product_id',
    DB_PREFIX . 'coupon_product' => 'product_id',
    DB_PREFIX . 'review' => 'product_id',
    DB_PREFIX . 'product_tracklist' => 'product_id',
    DB_PREFIX . 'product_vector_status' => 'product_id',
    DB_PREFIX . 'cart' => 'product_id',
    DB_PREFIX . 'customer_wishlist' => 'product_id',
    DB_PREFIX . 'googleshopping_product' => 'product_id',
    DB_PREFIX . 'googleshopping_product_status' => 'product_id',
    DB_PREFIX . 'googleshopping_product_target' => 'product_id',
];

$db->query("SET FOREIGN_KEY_CHECKS = 0");

echo "Deleting database records...\n";
foreach ($tablesWithProductId as $table => $col) {
    $check = $db->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        $db->query("DELETE FROM `$table` WHERE `$col` IN ($idList)");
        $affected = $db->affected_rows;
        if ($affected > 0) {
            echo "  - `$table`: deleted $affected row(s)\n";
        }
    }
}

// Clean reverse related products (where deleted product was the related_id)
$check = $db->query("SHOW TABLES LIKE '" . DB_PREFIX . "product_related'");
if ($check && $check->num_rows > 0) {
    $db->query("DELETE FROM `" . DB_PREFIX . "product_related` WHERE `related_id` IN ($idList)");
    if ($db->affected_rows > 0) {
        echo "  - `" . DB_PREFIX . "product_related` (reverse related_id): deleted " . $db->affected_rows . " row(s)\n";
    }
}

// Clean SEO URLs
$check = $db->query("SHOW TABLES LIKE '" . DB_PREFIX . "seo_url'");
if ($check && $check->num_rows > 0) {
    $seoQueries = array_map(function($id) { return "'product_id=$id'"; }, $targetIds);
    $db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `query` IN (" . implode(',', $seoQueries) . ")");
    if ($db->affected_rows > 0) {
        echo "  - `" . DB_PREFIX . "seo_url`: deleted " . $db->affected_rows . " row(s)\n";
    }
}

$db->query("SET FOREIGN_KEY_CHECKS = 1");

// 3. Clean filesystem image directories for deleted products
echo "\nCleaning filesystem directories...\n";
$deletedDirs = 0;
foreach ($targetDetails as $pid => $info) {
    $modelClean = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $info['model']);
    $dir = DIR_IMAGE . 'catalog/products/' . $modelClean;
    if (is_dir($dir)) {
        // Recursively remove directory
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        @rmdir($dir);
        $deletedDirs++;
        echo "  - Removed directory: image/catalog/products/$modelClean\n";
    }
}
echo "Total directories cleaned: $deletedDirs\n";

// 4. Cache Purge
echo "\nPurging OpenCart cache...\n";
$cacheDirs = [
    DIR_CACHE,
    DIR_STORAGE . 'cache/',
    DIR_IMAGE . 'cache/'
];
$cacheDirs = array_unique(array_filter($cacheDirs, 'is_dir'));
$clearedFiles = 0;

foreach ($cacheDirs as $cDir) {
    if (is_dir($cDir)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            if ($f->isFile()) {
                @unlink($f->getPathname());
                $clearedFiles++;
            }
        }
    }
}
echo "Cleared $clearedFiles cache file(s).\n";

// 5. Final verification count
$countRes = $db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product");
$remainingCount = $countRes->fetch_assoc()['total'];

$noImgRes = $db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product WHERE image = '' OR image IS NULL");
$remainingNoImg = $noImgRes->fetch_assoc()['total'];

echo "\n========================================================\n";
echo "  SUMMARY:\n";
echo "  - Products deleted: " . count($targetIds) . "\n";
echo "  - Products remaining in catalog: $remainingCount\n";
echo "  - Products with empty image remaining: $remainingNoImg\n";
echo "========================================================\n";
echo "[SUCCESS] Catalog cleanup completed successfully.\n";
