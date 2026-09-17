<?php
$artDir = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/';
$files = [
    'd:/OSPanel/domains/synthesia/scratch/logo_rect_clean.png' => $artDir . 'synthesia_exact_logo.png',
    $artDir . 'scratch/live_storefront_screenshot.png' => $artDir . 'live_storefront.png',
    $artDir . 'scratch/category_electric_live.png' => $artDir . 'live_category_electric.png',
    $artDir . 'scratch/test_vinyl_records.jpg' => $artDir . 'real_cat_vinyl.jpg',
    $artDir . 'scratch/test_compact_discs.jpg' => $artDir . 'real_cat_cd.jpg',
    $artDir . 'scratch/test_electric_guitars.jpg' => $artDir . 'real_cat_electric.jpg',
    $artDir . 'scratch/test_acoustic_guitars.jpg' => $artDir . 'real_cat_acoustic.jpg',
    $artDir . 'scratch/synth_crop_minimoog_right.jpg' => $artDir . 'real_cat_synth.jpg'
];

foreach ($files as $src => $dst) {
    if (file_exists($src)) {
        copy($src, $dst);
        echo "Copied to " . basename($dst) . "\n";
    } else {
        echo "Missing source: $src\n";
    }
}
