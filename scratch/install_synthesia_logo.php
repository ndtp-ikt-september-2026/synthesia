<?php
// Production logo builder and installer for Synthesia

$sourceTrans = 'd:/OSPanel/domains/synthesia/scratch/synthesia_logo_transparent.png';
$sourceWhite = 'd:/OSPanel/domains/synthesia/scratch/synthesia_logo_white_bg.png';

$imTrans = imagecreatefrompng($sourceTrans);
imagealphablending($imTrans, false);
imagesavealpha($imTrans, true);

// Crop tight bounds around artwork:
// Artwork bounds in sourceTrans:
// X: 12 to 1002, Y: 20 to 435
$minX = 12; $maxX = 1002;
$minY = 20; $maxY = 435;
$pad = 12;

$cropX = max(0, $minX - $pad);
$cropY = max(0, $minY - $pad);
$cropW = min(imagesx($imTrans) - $cropX, ($maxX - $minX) + ($pad * 2));
$cropH = min(imagesy($imTrans) - $cropY, ($maxY - $minY) + ($pad * 2));

$tightTrans = imagecreatetruecolor($cropW, $cropH);
imagealphablending($tightTrans, false);
imagesavealpha($tightTrans, true);
$transColor = imagecolorallocatealpha($tightTrans, 255, 255, 255, 127);
imagefill($tightTrans, 0, 0, $transColor);
imagecopy($tightTrans, $imTrans, 0, 0, $cropX, $cropY, $cropW, $cropH);

// 1. Target 1: Storefront Main Logo (image/catalog/logo.png)
// High-res retina 2x master: 600px width, proportional height (~255px)
$catalogW = 600;
$catalogH = (int)round($cropH * ($catalogW / $cropW));
$catalogLogo = imagecreatetruecolor($catalogW, $catalogH);
imagealphablending($catalogLogo, false);
imagesavealpha($catalogLogo, true);
$transBg = imagecolorallocatealpha($catalogLogo, 255, 255, 255, 127);
imagefill($catalogLogo, 0, 0, $transBg);
imagecopyresampled($catalogLogo, $tightTrans, 0, 0, 0, 0, $catalogW, $catalogH, $cropW, $cropH);

// 2. Target 2: Admin Panel Logo (admin/view/image/logo.png)
// Admin header brand is 235px wide, height ~40px.
// High-res 2x version: width ~300px, height ~128px
$adminW = 320;
$adminH = (int)round($cropH * ($adminW / $cropW));
$adminLogo = imagecreatetruecolor($adminW, $adminH);
imagealphablending($adminLogo, false);
imagesavealpha($adminLogo, true);
$transBgAdmin = imagecolorallocatealpha($adminLogo, 255, 255, 255, 127);
imagefill($adminLogo, 0, 0, $transBgAdmin);
imagecopyresampled($adminLogo, $tightTrans, 0, 0, 0, 0, $adminW, $adminH, $cropW, $cropH);

// 3. Target 3: Square Brand Icon / Mark (image/catalog/synthesia_mark.png)
// The circular vinyl/synth wave emblem (bounds: X: 12..375, Y: 20..435)
$emblemPad = 8;
$emblemX = max(0, 12 - $emblemPad);
$emblemY = max(0, 20 - $emblemPad);
$emblemW = 375 - 12 + ($emblemPad * 2);
$emblemH = 435 - 20 + ($emblemPad * 2);
$size = max($emblemW, $emblemH);

$squareEmblem = imagecreatetruecolor($size, $size);
imagealphablending($squareEmblem, false);
imagesavealpha($squareEmblem, true);
$transBgSq = imagecolorallocatealpha($squareEmblem, 255, 255, 255, 127);
imagefill($squareEmblem, 0, 0, $transBgSq);
$destX = (int)round(($size - $emblemW) / 2);
$destY = (int)round(($size - $emblemH) / 2);
imagecopy($squareEmblem, $imTrans, $destX, $destY, $emblemX, $emblemY, $emblemW, $emblemH);

// Resample emblem to 512x512
$emblem512 = imagecreatetruecolor(512, 512);
imagealphablending($emblem512, false);
imagesavealpha($emblem512, true);
imagefill($emblem512, 0, 0, $transBgSq);
imagecopyresampled($emblem512, $squareEmblem, 0, 0, 0, 0, 512, 512, $size, $size);

// Resample emblem to 32x32 for icon
$emblem32 = imagecreatetruecolor(32, 32);
imagealphablending($emblem32, false);
imagesavealpha($emblem32, true);
imagefill($emblem32, 0, 0, $transBgSq);
imagecopyresampled($emblem32, $squareEmblem, 0, 0, 0, 0, 32, 32, $size, $size);

// Backup originals if not already backed up
$catLogoPath = 'd:/OSPanel/domains/synthesia/image/catalog/logo.png';
$catBak = 'd:/OSPanel/domains/synthesia/image/catalog/logo.png.livestore.bak';
if (file_exists($catLogoPath) && !file_exists($catBak)) {
    copy($catLogoPath, $catBak);
    echo "Backed up catalog logo to $catBak\n";
}

$admLogoPath = 'd:/OSPanel/domains/synthesia/admin/view/image/logo.png';
$admBak = 'd:/OSPanel/domains/synthesia/admin/view/image/logo.png.livestore.bak';
if (file_exists($admLogoPath) && !file_exists($admBak)) {
    copy($admLogoPath, $admBak);
    echo "Backed up admin logo to $admBak\n";
}

// Write the new logos
imagepng($catalogLogo, $catLogoPath, 9);
echo "Installed new catalog logo ($catalogW x $catalogH) -> $catLogoPath\n";

imagepng($adminLogo, $admLogoPath, 9);
echo "Installed new admin logo ($adminW x $adminH) -> $admLogoPath\n";

// Also save extra assets in image/catalog/
imagepng($emblem512, 'd:/OSPanel/domains/synthesia/image/catalog/synthesia_icon_512.png', 9);
imagepng($emblem32, 'd:/OSPanel/domains/synthesia/image/catalog/synthesia_icon_32.png', 9);
imagepng($catalogLogo, 'd:/OSPanel/domains/synthesia/image/catalog/synthesia_logo.png', 9);

// Clear image cache
$cacheFile = 'd:/OSPanel/domains/synthesia/image/cache/catalog/logo-100x100.png';
if (file_exists($cacheFile)) {
    unlink($cacheFile);
    echo "Cleared cache: $cacheFile\n";
}

echo "Logo installation completed successfully!\n";
