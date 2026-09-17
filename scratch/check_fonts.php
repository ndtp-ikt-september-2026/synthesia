<?php
$fonts = [
    'segoeuib.ttf',
    'segoeui.ttf',
    'arialbd.ttf',
    'arial.ttf',
    'ariblk.ttf',
    'tahomabd.ttf',
    'verdanab.ttf'
];
foreach ($fonts as $f) {
    $p = 'C:/Windows/Fonts/' . $f;
    echo "$f: " . (file_exists($p) ? "EXISTS (" . filesize($p) . " bytes)" : "NOT FOUND") . "\n";
}
