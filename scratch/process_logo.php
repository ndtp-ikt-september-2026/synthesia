<?php
$srcPath = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/synthesia_brand_logo_1789630621927.jpg';
$src = imagecreatefromjpeg($srcPath);

$minX = 141;
$maxX = 1122;
$minY = 216;
$maxY = 631;

$padding = 20;
$cropX = max(0, $minX - $padding);
$cropY = max(0, $minY - $padding);
$cropW = min(imagesx($src) - $cropX, ($maxX - $minX) + ($padding * 2));
$cropH = min(imagesy($src) - $cropY, ($maxY - $minY) + ($padding * 2));

// 1. Cropped clean white
$croppedWhite = imagecreatetruecolor($cropW, $cropH);
$white = imagecolorallocate($croppedWhite, 255, 255, 255);
imagefill($croppedWhite, 0, 0, $white);
imagecopy($croppedWhite, $src, 0, 0, $cropX, $cropY, $cropW, $cropH);
imagepng($croppedWhite, 'd:/OSPanel/domains/synthesia/scratch/synthesia_logo_white_bg.png', 9);

// 2. Cropped transparent PNG
// Standard color-key with smooth alpha blending for white background removal
$croppedTrans = imagecreatetruecolor($cropW, $cropH);
imagealphablending($croppedTrans, false);
imagesavealpha($croppedTrans, true);
$transparent = imagecolorallocatealpha($croppedTrans, 255, 255, 255, 127);
imagefill($croppedTrans, 0, 0, $transparent);

for ($y = 0; $y < $cropH; $y++) {
    for ($x = 0; $x < $cropW; $x++) {
        $rgb = imagecolorat($croppedWhite, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        
        // Calculate lightness
        // If pure or near white:
        $minC = min($r, $g, $b);
        $maxC = max($r, $g, $b);
        
        if ($minC >= 252) {
            // fully transparent
            imagesetpixel($croppedTrans, $x, $y, $transparent);
        } elseif ($minC >= 210 && ($maxC - $minC) < 25) {
            // Anti-aliasing edge against white:
            // Calculate alpha: 0 (opaque) to 127 (transparent)
            // 210 -> alpha ~ 0, 252 -> alpha ~ 127
            $ratio = ($minC - 210) / (252 - 210); // 0.0 to 1.0
            $alpha = (int)round($ratio * 127);
            
            // Recover original foreground color (un-multiply white)
            $invA = 1.0 - ($alpha / 127.0);
            if ($invA > 0.01) {
                $newR = max(0, min(255, (int)round(($r - 255 * ($alpha / 127.0)) / $invA)));
                $newG = max(0, min(255, (int)round(($g - 255 * ($alpha / 127.0)) / $invA)));
                $newB = max(0, min(255, (int)round(($b - 255 * ($alpha / 127.0)) / $invA)));
            } else {
                $newR = $r; $newG = $g; $newB = $b;
            }
            $col = imagecolorallocatealpha($croppedTrans, $newR, $newG, $newB, $alpha);
            imagesetpixel($croppedTrans, $x, $y, $col);
        } else {
            // opaque pixel
            $col = imagecolorallocatealpha($croppedTrans, $r, $g, $b, 0);
            imagesetpixel($croppedTrans, $x, $y, $col);
        }
    }
}
imagepng($croppedTrans, 'd:/OSPanel/domains/synthesia/scratch/synthesia_logo_transparent.png', 9);

echo "Created white and transparent cropped logos successfully.\n";
