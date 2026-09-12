<?php
$html = file_get_contents('http://synthesia/index.php?route=product/product&product_id=42');
$pos = strpos($html, 'Инструменты для этой песни');
echo "Pos: " . var_export($pos, true) . "\n";
if ($pos !== false) {
    echo substr($html, max(0, $pos - 100), 250) . "\n";
}
