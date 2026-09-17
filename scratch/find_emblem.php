<?php
$srcPath = 'd:/OSPanel/domains/synthesia/scratch/synthesia_logo_transparent.png';
$im = imagecreatefrompng($srcPath);
$w = imagesx($im);
$h = imagesy($im);

// The emblem is on the left half of the image
// Let's find non-transparent pixels in the left half (say x from 0 to 450)
$minX = $w; $minY = $h; $maxX = 0; $maxY = 0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < 450; $x++) {
        $rgba = imagecolorat($im, $x, $y);
        $a = ($rgba >> 24) & 0x7F;
        if ($a < 120) {
            if ($x < $minX) $minX = $x;
            if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y;
            if ($y > $maxY) $maxY = $y;
        }
    }
}

echo "Emblem bounds: X: $minX .. $maxX (width: " . ($maxX - $minX + 1) . "), Y: $minY .. $maxY (height: " . ($maxY - $minY + 1) . ")\n";
