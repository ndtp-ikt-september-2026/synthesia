<?php
$artifact_dir = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/7a592832-44df-4fca-b2c6-1db03dfba9e9/';
$dest_dir = __DIR__ . '/../image/catalog/categories/';

if (!is_dir($dest_dir)) {
    mkdir($dest_dir, 0777, true);
}

$mapping = [
    2  => ['src' => 'cat_vinyl_records_1789194610319.jpg',   'dest' => 'vinyl_records.jpg'],
    3  => ['src' => 'cat_compact_discs_1789194774015.jpg',   'dest' => 'compact_discs.jpg'],
    15 => ['src' => 'cat_electric_guitar_1789194791206.jpg', 'dest' => 'electric_guitars.jpg'],
    12 => ['src' => 'cat_acoustic_guitar_1789194808288.jpg', 'dest' => 'acoustic_guitars.jpg'],
    24 => ['src' => 'cat_synthesizer_1789194828153.jpg',     'dest' => 'synthesizers.jpg']
];

$db = new mysqli('localhost', 'root', 'syn-sept', 'syn-db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

$upload_dest_dir = __DIR__ . '/../upload/image/catalog/categories/';
if (!is_dir($upload_dest_dir)) {
    mkdir($upload_dest_dir, 0777, true);
}

foreach ($mapping as $cat_id => $info) {
    $src = $artifact_dir . $info['src'];
    $dst = $dest_dir . $info['dest'];
    $up_dst = $upload_dest_dir . $info['dest'];
    if (is_file($src)) {
        copy($src, $dst);
        copy($src, $up_dst);
        echo "Copied {$info['src']} to {$info['dest']} (both image/ and upload/)\n";
    } else {
        echo "Warning: {$src} not found\n";
    }

    $db_img = 'catalog/categories/' . $info['dest'];
    $db->query("UPDATE oc_category SET image = '" . $db->real_escape_string($db_img) . "' WHERE category_id = " . (int)$cat_id);
    echo "Updated oc_category #{$cat_id} image => {$db_img}\n";
}

// Update parent categories
$parent_mapping = [
    1  => 'catalog/categories/vinyl_records.jpg',
    11 => 'catalog/categories/electric_guitars.jpg',
    16 => 'catalog/categories/electric_guitars.jpg',
    22 => 'catalog/categories/synthesizers.jpg',
    27 => 'catalog/categories/acoustic_guitars.jpg'
];

foreach ($parent_mapping as $pid => $pimg) {
    $db->query("UPDATE oc_category SET image = '" . $db->real_escape_string($pimg) . "' WHERE category_id = " . (int)$pid);
    echo "Updated parent oc_category #{$pid} image => {$pimg}\n";
}

// Clear any category cache in image/cache
$cache_cat_dir = __DIR__ . '/../image/cache/catalog/categories/';
if (is_dir($cache_cat_dir)) {
    $files = glob($cache_cat_dir . '*');
    foreach ($files as $f) {
        if (is_file($f)) unlink($f);
    }
    echo "Cleared cache in image/cache/catalog/categories/\n";
}
