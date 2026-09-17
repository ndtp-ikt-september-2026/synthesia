<?php
$im = imagecreatefrompng('C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/.user_uploaded/media_1789632306954.png');
$w = imagesx($im);
$h = imagesy($im);

$bgR = 15; $bgG = 23; $bgB = 42;

$minX = $w; $maxX = 0; $minY = $h; $maxY = 0;
$iconMinX = $w; $iconMaxX = 0; $iconMinY = $h; $iconMaxY = 0;
$textMinX = $w; $textMaxX = 0; $textMinY = $h; $textMaxY = 0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        
        $diff = abs($r - $bgR) + abs($g - $bgG) + abs($b - $bgB);
        if ($diff > 35) {
            $minX = min($minX, $x);
            $maxX = max($maxX, $x);
            $minY = min($minY, $y);
            $maxY = max($maxY, $y);
            
            if ($x < 65) {
                $iconMinX = min($iconMinX, $x);
                $iconMaxX = max($iconMaxX, $x);
                $iconMinY = min($iconMinY, $y);
                $iconMaxY = max($iconMaxY, $y);
            } else {
                $textMinX = min($textMinX, $x);
                $textMaxX = max($textMaxX, $x);
                $textMinY = min($textMinY, $y);
                $textMaxY = max($textMaxY, $y);
            }
        }
    }
}

echo "Overall bounds: X: $minX .. $maxX, Y: $minY .. $maxY\n";
echo "Icon bounds: X: $iconMinX .. $iconMaxX (w=" . ($iconMaxX - $iconMinX + 1) . "), Y: $iconMinY .. $iconMaxY (h=" . ($iconMaxY - $iconMinY + 1) . ")\n";
echo "Text bounds: X: $textMinX .. $textMaxX (w=" . ($textMaxX - $textMinX + 1) . "), Y: $textMinY .. $textMaxY (h=" . ($textMaxY - $textMinY + 1) . ")\n";
echo "Gap between icon and text: " . ($textMinX - $iconMaxX) . " px\n";
