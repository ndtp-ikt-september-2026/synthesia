<?php
$srcPath = 'd:/OSPanel/domains/synthesia/scratch/retina_two_logos.png';
$im = imagecreatefrompng($srcPath);
$w = imagesx($im);
$h = imagesy($im);

// Split into top half (logoRect) and bottom half (logoBadge)
// Let's find vertical dividing point where alpha = 127
$midY = (int)($h / 2);

function getBox($im, $x1, $y1, $x2, $y2) {
    $minX = $x2; $maxX = $x1; $minY = $y2; $maxY = $y1;
    for ($y = $y1; $y <= $y2; $y++) {
        for ($x = $x1; $x <= $x2; $x++) {
            $rgb = imagecolorat($im, $x, $y);
            $a = ($rgb >> 24) & 0x7F;
            if ($a < 120) {
                $minX = min($minX, $x);
                $maxX = max($maxX, $x);
                $minY = min($minY, $y);
                $maxY = max($maxY, $y);
            }
        }
    }
    return [$minX, $maxX, $minY, $maxY];
}

$box1 = getBox($im, 0, 0, $w - 1, $midY - 1);
$box2 = getBox($im, 0, $midY, $w - 1, $h - 1);

function cropAndSave($im, $box, $dest) {
    list($x1, $x2, $y1, $y2) = $box;
    $cw = $x2 - $x1 + 1;
    $ch = $y2 - $y1 + 1;
    $cropped = imagecreatetruecolor($cw, $ch);
    imagealphablending($cropped, false);
    imagesavealpha($cropped, true);
    $trans = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
    imagefill($cropped, 0, 0, $trans);
    imagecopy($cropped, $im, 0, 0, $x1, $y1, $cw, $ch);
    imagepng($cropped, $dest, 9);
    echo "Saved $dest ({$cw}x{$ch})\n";
}

$previewDir = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/';
cropAndSave($im, $box1, $previewDir . 'logo_rect_exact.png');
cropAndSave($im, $box2, $previewDir . 'logo_badge_rounded.png');
cropAndSave($im, $box1, 'd:/OSPanel/domains/synthesia/scratch/logo_rect_exact.png');
cropAndSave($im, $box2, 'd:/OSPanel/domains/synthesia/scratch/logo_badge_rounded.png');
