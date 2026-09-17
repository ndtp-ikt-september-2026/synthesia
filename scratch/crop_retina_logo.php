<?php
$srcPath = 'd:/OSPanel/domains/synthesia/scratch/retina_logo_raw.png';
$im = imagecreatefrompng($srcPath);
$w = imagesx($im);
$h = imagesy($im);
echo "Retina raw: {$w}x{$h}\n";

$minX = $w; $maxX = 0; $minY = $h; $maxY = 0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $a = ($rgb >> 24) & 0x7F;
        if ($a < 125) { // not transparent
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
    imagealphablending($cropped, false);
    imagesavealpha($cropped, true);
    $trans = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
    imagefill($cropped, 0, 0, $trans);
    imagecopy($cropped, $im, 0, 0, $minX, $minY, $cropW, $cropH);
    
    $destArtifact = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/final_logo.png';
    imagepng($cropped, $destArtifact, 9);
    imagepng($cropped, 'd:/OSPanel/domains/synthesia/scratch/final_logo.png', 9);
    echo "Saved final_logo.png ({$cropW}x{$cropH})\n";
}
