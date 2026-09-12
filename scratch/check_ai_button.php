<?php
$html = file_get_contents('http://synthesia/index.php?route=product/product&product_id=182');
preg_match('/<div class="studio-product-details">.*?<div class="studio-commercial-card"/s', $html, $m);
if (!empty($m[0])) {
    echo "Product Detail Header Area:\n" . $m[0] . "\n";
}

$html2 = file_get_contents('http://synthesia/index.php?route=product/category&path=2');
preg_match('/<div class="vinyl-card".*?<\/div>\s*<\/div>\s*<\/div>/s', $html2, $m2);
if (!empty($m2[0])) {
    echo "Category Vinyl Card Area:\n" . $m2[0] . "\n";
}
