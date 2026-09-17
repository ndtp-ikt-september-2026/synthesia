<?php
// 1. Verify Stop-Kran absence on frontend
$html = file_get_contents('http://synthesia/');
$hasStopkran = (stripos($html, 'stopkran') !== false);
echo "Frontend Stop-Kran present: " . ($hasStopkran ? "YES (ERROR)" : "NO (VERIFIED GONE)") . "\n";

// 2. Verify Logo files exist and dimensions
$logoFiles = [
    'Storefront' => 'd:/OSPanel/domains/synthesia/image/catalog/logo.png',
    'Admin' => 'd:/OSPanel/domains/synthesia/admin/view/image/logo.png'
];
foreach ($logoFiles as $label => $path) {
    if (file_exists($path)) {
        $sz = getimagesize($path);
        echo "$label logo: {$sz[0]}x{$sz[1]} ({$sz['mime']})\n";
    } else {
        echo "$label logo: MISSING\n";
    }
}

// 3. Verify Category images
$catFiles = [
    'Vinyl' => 'd:/OSPanel/domains/synthesia/image/catalog/categories/vinyl_records.jpg',
    'Compact Discs' => 'd:/OSPanel/domains/synthesia/image/catalog/categories/compact_discs.jpg',
    'Electric Guitars' => 'd:/OSPanel/domains/synthesia/image/catalog/categories/electric_guitars.jpg',
    'Acoustic Guitars' => 'd:/OSPanel/domains/synthesia/image/catalog/categories/acoustic_guitars.jpg',
    'Synthesizers' => 'd:/OSPanel/domains/synthesia/image/catalog/categories/synthesizers.jpg'
];
foreach ($catFiles as $label => $path) {
    if (file_exists($path)) {
        $sz = getimagesize($path);
        echo "$label category: {$sz[0]}x{$sz[1]} ({$sz['mime']})\n";
    } else {
        echo "$label category: MISSING\n";
    }
}
