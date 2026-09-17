<?php
$srcPath = 'd:/OSPanel/domains/synthesia/scratch/edge_logo.png';
$im = imagecreatefrompng($srcPath);
$w = imagesx($im);
$h = imagesy($im);
echo "Raw edge output: {$w}x{$h}\n";

// Target is the #0f172a card
$targetR = 15; $targetG = 23; $targetB = 42;
$minX = $w; $maxX = 0; $minY = $h; $maxY = 0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        
        // Match anything inside or on the logo card (#0f172a or non-white)
        if (!($r > 245 && $g > 245 && $b > 245)) {
            $minX = min($minX, $x);
            $maxX = max($maxX, $x);
            $minY = min($minY, $y);
            $maxY = max($maxY, $y);
        }
    }
}

echo "Bounds: X: $minX..$maxX, Y: $minY..$maxY\n";
if ($maxX > $minX && $maxY > $minY) {
    $cropW = $maxX - $minX + 1;
    $cropH = $maxY - $minY + 1;
    $cropped = imagecreatetruecolor($cropW, $cropH);
    imagecopy($cropped, $im, 0, 0, $minX, $minY, $cropW, $cropH);
    imagepng($cropped, 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/edge_rendered_logo.png');
    echo "Saved edge_rendered_logo.png ({$cropW}x{$cropH})\n";
}
