<?php
$rawPng = __DIR__ . '/raw_retina_rounded.png';
$im = imagecreatefrompng($rawPng);
$w = imagesx($im);
$h = imagesy($im);

// Let's find distinct non-transparent connected regions along Y
$rowHasContent = [];
for ($y = 0; $y < $h; $y++) {
    $has = false;
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $a = ($rgb >> 24) & 0x7F;
        if ($a < 120) {
            $has = true;
            break;
        }
    }
    $rowHasContent[$y] = $has;
}

// Group contiguous blocks of Y
$blocks = [];
$inBlock = false;
$start = 0;
for ($y = 0; $y < $h; $y++) {
    if ($rowHasContent[$y] && !$inBlock) {
        $inBlock = true;
        $start = $y;
    } elseif (!$rowHasContent[$y] && $inBlock) {
        $inBlock = false;
        $blocks[] = [$start, $y - 1];
    }
}
if ($inBlock) {
    $blocks[] = [$start, $h - 1];
}

echo "Found " . count($blocks) . " blocks of content along Y.\n";

$names = ['retina_rounded_badge_16', 'retina_rounded_badge_24', 'retina_rounded_pill'];
$artDir = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/';

foreach ($blocks as $idx => $block) {
    if ($idx >= count($names)) break;
    list($y1, $y2) = $block;
    
    // Find minX and maxX for this block
    $minX = $w - 1; $maxX = 0;
    for ($y = $y1; $y <= $y2; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgb = imagecolorat($im, $x, $y);
            $a = ($rgb >> 24) & 0x7F;
            if ($a < 120) {
                $minX = min($minX, $x);
                $maxX = max($maxX, $x);
            }
        }
    }
    
    $cw = $maxX - $minX + 1;
    $ch = $y2 - $y1 + 1;
    
    $cropped = imagecreatetruecolor($cw, $ch);
    imagealphablending($cropped, false);
    imagesavealpha($cropped, true);
    $trans = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
    imagefill($cropped, 0, 0, $trans);
    
    imagecopy($cropped, $im, 0, 0, $minX, $y1, $cw, $ch);
    
    $target1 = __DIR__ . '/' . $names[$idx] . '.png';
    $target2 = $artDir . $names[$idx] . '.png';
    
    imagepng($cropped, $target1, 9);
    imagepng($cropped, $target2, 9);
    
    echo "Saved {$names[$idx]}: {$cw}x{$ch} to $target1 and $target2\n";
}
