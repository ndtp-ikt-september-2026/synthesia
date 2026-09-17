<?php
$srcPath = 'd:/OSPanel/domains/synthesia/scratch/logo_rect_exact.png';
$im = imagecreatefrompng($srcPath);
$w = imagesx($im);
$h = imagesy($im);

// The top one is roughly y = 0 to 105
// The bottom one is roughly y = 125 to 230
// Let's find the exact bounds of each
$topCard = imagecreatetruecolor(460, 102);
imagecopy($topCard, $im, 0, 0, 0, 0, 460, 102);

$botCard = imagecreatetruecolor(460, 106);
imagealphablending($botCard, false);
imagesavealpha($botCard, true);
$trans = imagecolorallocatealpha($botCard, 0, 0, 0, 127);
imagefill($botCard, 0, 0, $trans);
imagecopy($botCard, $im, 0, 0, 0, 124, 460, 106);

$previewDir = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/';
imagepng($topCard, $previewDir . 'logo_rect_clean.png');
imagepng($botCard, $previewDir . 'logo_badge_clean.png');
imagepng($topCard, 'd:/OSPanel/domains/synthesia/scratch/logo_rect_clean.png');
imagepng($botCard, 'd:/OSPanel/domains/synthesia/scratch/logo_badge_clean.png');

echo "Both clean logos saved!\n";
