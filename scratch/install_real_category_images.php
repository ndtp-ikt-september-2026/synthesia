<?php
$sourceMap = [
    'vinyl_records.jpg' => 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/test_vinyl_records.jpg',
    'compact_discs.jpg' => 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/test_compact_discs.jpg',
    'electric_guitars.jpg' => 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/test_electric_guitars.jpg',
    'acoustic_guitars.jpg' => 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/test_acoustic_guitars.jpg',
    'synthesizers.jpg' => 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/synth_crop_minimoog_right.jpg'
];

$destDirs = [
    'd:/OSPanel/domains/synthesia/image/catalog/categories/',
    'd:/OSPanel/domains/synthesia/upload/image/catalog/categories/'
];

foreach ($destDirs as $d) {
    if (!is_dir($d)) mkdir($d, 0777, true);
    foreach ($sourceMap as $name => $src) {
        if (!file_exists($src)) {
            echo "ERROR: Source $src does not exist!\n";
            continue;
        }
        copy($src, $d . $name);
        echo "Copied $name to $d\n";
    }
}

// Clear OpenCart cache for categories
$cacheDir = 'd:/OSPanel/domains/synthesia/image/cache/catalog/categories/';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '*');
    foreach ($files as $f) {
        if (is_file($f)) unlink($f);
    }
    echo "Cleared cache in $cacheDir\n";
}

echo "All 5 category images installed successfully!\n";
