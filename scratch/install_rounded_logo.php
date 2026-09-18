<?php
$sourcePill = __DIR__ . '/retina_rounded_pill.png';
$sourceBadge24 = __DIR__ . '/retina_rounded_badge_24.png';
$sourceBadge16 = __DIR__ . '/retina_rounded_badge_16.png';

$targets = [
    'd:/OSPanel/domains/synthesia/image/catalog/logo.png',
    'd:/OSPanel/domains/synthesia/admin/view/image/logo.png',
    'd:/OSPanel/domains/synthesia/upload/image/catalog/logo.png',
    'd:/OSPanel/domains/synthesia/upload/admin/view/image/logo.png'
];

foreach ($targets as $t) {
    $dir = dirname($t);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    copy($sourcePill, $t);
    echo "Copied pill logo to $t\n";
}

// Keep backups of variants
copy($sourcePill, 'd:/OSPanel/domains/synthesia/image/catalog/logo_pill.png');
copy($sourceBadge24, 'd:/OSPanel/domains/synthesia/image/catalog/logo_badge_rounded_24.png');
copy($sourceBadge16, 'd:/OSPanel/domains/synthesia/image/catalog/logo_badge_rounded_16.png');

// Clear image cache
$cacheFiles = glob('d:/OSPanel/domains/synthesia/image/cache/catalog/logo*');
foreach ($cacheFiles as $f) {
    if (is_file($f)) {
        unlink($f);
        echo "Deleted cache: $f\n";
    }
}

echo "Rounded logo installed and cache cleared!\n";
