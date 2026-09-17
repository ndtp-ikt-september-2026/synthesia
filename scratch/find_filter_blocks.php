<?php
$lines = file('d:/OSPanel/domains/synthesia/admin/controller/catalog/product.php');
foreach ($lines as $idx => $line) {
    if (strpos($line, 'filter_model') !== false) {
        echo "Line " . ($idx + 1) . ": " . trim($line) . "\n";
    }
}
