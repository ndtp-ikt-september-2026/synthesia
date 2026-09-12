<?php
$html = file_get_contents('scratch/rendered_product.html');

$checks = [
  'nav-tabs (old bootstrap tabs)' => strpos($html, 'nav-tabs') !== false,
  'thumbnails' => strpos($html, 'thumbnails') !== false,
  'breadcrumb (old bootstrap breadcrumb)' => strpos($html, 'breadcrumb') !== false,
  'btn-primary' => strpos($html, 'btn-primary') !== false,
  'btn-default' => strpos($html, 'btn-default') !== false,
  'table-bordered (old opencart table)' => strpos($html, 'table-bordered') !== false,
  'form-horizontal' => strpos($html, 'form-horizontal') !== false,
  'product-thumb (old related products)' => strpos($html, 'product-thumb') !== false,
  'rating' => strpos($html, 'class="rating"') !== false,
  'tab-description' => strpos($html, 'tab-description') !== false,
  'tab-specification' => strpos($html, 'tab-specification') !== false,
  'tab-review' => strpos($html, 'tab-review') !== false,
  'display:none hack' => strpos($html, 'display:none') !== false,
  'tags' => strpos($html, 'tags') !== false,
];

foreach ($checks as $key => $val) {
    echo "$key: " . ($val ? "YES (PRESENT)" : "NO") . "\n";
}

// Print sections of the rendered page
preg_match_all('/<div class="product-detail-layout">.*?<\/div>\s*<\/div>\s*<\/div>/s', $html, $detailMatch);
echo "\n--- Detail layout length: " . (isset($detailMatch[0][0]) ? strlen($detailMatch[0][0]) : 0) . "\n";

// What comes after the product-detail-layout?
$pos = strpos($html, 'product-detail-layout');
if ($pos !== false) {
    $after = substr($html, $pos + 500);
    // Find what follows after product-commercial-box
    $posComm = strpos($after, 'product-commercial-box');
    if ($posComm !== false) {
        echo "\n--- Content after commercial box (first 2000 chars) ---\n";
        echo substr($after, $posComm, 2500);
    }
}
