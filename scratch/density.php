<?php
$im = imagecreatefrompng('d:/OSPanel/domains/synthesia/scratch/synthesia_logo_transparent.png');
$w = imagesx($im);
$h = imagesy($im);

// Let's trace column densities across X
for ($x = 350; $x < 500; $x += 10) {
    $count = 0;
    for ($y = 0; $y < $h; $y++) {
        $a = (imagecolorat($im, $x, $y) >> 24) & 0x7F;
        if ($a < 120) $count++;
    }
    echo "X=$x: count=$count\n";
}
