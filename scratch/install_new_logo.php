<?php
$logoSrc = 'd:/OSPanel/domains/synthesia/scratch/logo_rect_clean.png';

$targets = [
    'd:/OSPanel/domains/synthesia/image/catalog/logo.png',
    'd:/OSPanel/domains/synthesia/admin/view/image/logo.png',
    'd:/OSPanel/domains/synthesia/upload/image/catalog/logo.png',
    'd:/OSPanel/domains/synthesia/upload/admin/view/image/logo.png'
];

foreach ($targets as $t) {
    $dir = dirname($t);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    copy($logoSrc, $t);
    echo "Copied to $t\n";
}

// Clear all logo cached thumbnails
$cacheFiles = glob('d:/OSPanel/domains/synthesia/image/cache/catalog/logo*');
foreach ($cacheFiles as $f) {
    if (is_file($f)) {
        unlink($f);
        echo "Deleted cache: $f\n";
    }
}

echo "Logo installed and cache cleared successfully!\n";
