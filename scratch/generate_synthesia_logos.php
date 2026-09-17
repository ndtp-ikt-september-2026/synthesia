<?php
$fontPath = 'd:/OSPanel/domains/synthesia/scratch/Inter.ttf';
if (!file_exists($fontPath)) {
    $fontPath = 'C:/Windows/Fonts/segoeuib.ttf';
}

$previewDir = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/';

// Base design: 250 x 54 -> 4x scale: 1000 x 216
$scale = 4;
$w = 260 * $scale; // 1040
$h = 56 * $scale;  // 224

function drawLogo(&$im, $scale, $theme = 'dark') {
    global $fontPath;
    $w = imagesx($im);
    $h = imagesy($im);
    
    // Icon geometry
    // Center of knob
    $cx = (int)(38 * $scale);
    $cy = (int)(28 * $scale);
    $outerR = (int)(13 * $scale);
    $innerR = (int)(4.2 * $scale);
    $strokeW = (int)(2.2 * $scale);
    
    // Colors
    $cyanR = 56; $cyanG = 189; $cyanB = 248; // #38bdf8
    $amberR = 251; $amberG = 146; $amberB = 60; // #fb923c
    
    if ($theme === 'dark') {
        $iconCol = imagecolorallocate($im, $cyanR, $cyanG, $cyanB);
        $textCoreCol = imagecolorallocate($im, 248, 250, 252); // #f8fafc
        $fringeLeftCol = imagecolorallocatealpha($im, $cyanR, $cyanG, $cyanB, 40);
        $fringeRightCol = imagecolorallocatealpha($im, $amberR, $amberG, $amberB, 45);
    } else {
        // Light theme (transparent bg, dark text)
        $iconCol = imagecolorallocate($im, 2, 132, 199); // #0284c7
        $textCoreCol = imagecolorallocate($im, 15, 23, 42); // #0f172a
        $fringeLeftCol = imagecolorallocatealpha($im, 14, 165, 233, 50);
        $fringeRightCol = imagecolorallocatealpha($im, 245, 158, 11, 55);
    }
    
    // Draw outer circle with thickness
    for ($i = -$strokeW/2; $i <= $strokeW/2; $i += 0.5) {
        imageellipse($im, $cx, $cy, ($outerR + $i) * 2, ($outerR + $i) * 2, $iconCol);
    }
    // Draw inner circle with thickness
    for ($i = -$strokeW/2; $i <= $strokeW/2; $i += 0.5) {
        imageellipse($im, $cx, $cy, ($innerR + $i) * 2, ($innerR + $i) * 2, $iconCol);
    }
    // Draw 12 o'clock notch (from outer circle pointing down or outer top)
    $notchTop = $cy - $outerR - (int)(1 * $scale);
    $notchBottom = $cy - $outerR + (int)(4 * $scale);
    imagesetthickness($im, $strokeW);
    imageline($im, $cx, $notchTop, $cx, $notchBottom, $iconCol);
    
    // Draw typography SYNTHESIA
    $fontSize = 20 * $scale;
    $textX = (int)(62 * $scale);
    $textY = (int)(36 * $scale);
    $text = "SYNTHESIA";
    $tracking = (int)(3.5 * $scale);
    
    // Render letters with custom tracking and chromatic aberration
    $curX = $textX;
    $len = strlen($text);
    for ($i = 0; $i < $len; $i++) {
        $char = $text[$i];
        $box = imagettfbbox($fontSize, 0, $fontPath, $char);
        $charW = $box[2] - $box[0];
        
        // 1. Left cyan fringe (shifted left)
        $shiftL = (int)(1.8 * $scale);
        imagettftext($im, $fontSize, 0, $curX - $shiftL, $textY, $fringeLeftCol, $fontPath, $char);
        
        // 2. Right warm amber fringe (shifted right)
        $shiftR = (int)(1.8 * $scale);
        imagettftext($im, $fontSize, 0, $curX + $shiftR, $textY, $fringeRightCol, $fontPath, $char);
        
        // 3. Center crisp core
        imagettftext($im, $fontSize, 0, $curX, $textY, $textCoreCol, $fontPath, $char);
        
        $curX += $charW + $tracking;
    }
}

// 1. Sleek Dark Obsidian Capsule Badge (Pill with rounded corners)
$logoCapsule = imagecreatetruecolor($w, $h);
imagealphablending($logoCapsule, false);
imagesavealpha($logoCapsule, true);
$trans = imagecolorallocatealpha($logoCapsule, 0, 0, 0, 127);
imagefill($logoCapsule, 0, 0, $trans);

imagealphablending($logoCapsule, true);
// Draw rounded rectangle with color #0f172a and subtle border
$bgObsidian = imagecolorallocate($logoCapsule, 15, 23, 42); // #0f172a
$radius = (int)(14 * $scale);

// Fill rounded rectangle
imagefilledellipse($logoCapsule, $radius, $radius, $radius * 2, $radius * 2, $bgObsidian);
imagefilledellipse($logoCapsule, $w - $radius, $radius, $radius * 2, $radius * 2, $bgObsidian);
imagefilledellipse($logoCapsule, $radius, $h - $radius, $radius * 2, $radius * 2, $bgObsidian);
imagefilledellipse($logoCapsule, $w - $radius, $h - $radius, $radius * 2, $radius * 2, $bgObsidian);
imagefilledrectangle($logoCapsule, $radius, 0, $w - $radius, $h, $bgObsidian);
imagefilledrectangle($logoCapsule, 0, $radius, $w, $h - $radius, $bgObsidian);

// Subtle tech border
$borderCol = imagecolorallocatealpha($logoCapsule, 56, 189, 248, 85); // 30% cyan border
imagesetthickness($logoCapsule, (int)(1 * $scale));
imagerectangle($logoCapsule, $radius/2, $radius/2, $w - $radius/2, $h - $radius/2, $borderCol);

drawLogo($logoCapsule, $scale, 'dark');
imagepng($logoCapsule, $previewDir . 'logo_variant_capsule.png', 9);

// 2. Exact Rectangle matching screenshot (#0f172a solid background)
$logoRect = imagecreatetruecolor($w, $h);
$bgObsidian2 = imagecolorallocate($logoRect, 15, 23, 42);
imagefill($logoRect, 0, 0, $bgObsidian2);
imagealphablending($logoRect, true);
drawLogo($logoRect, $scale, 'dark');
imagepng($logoRect, $previewDir . 'logo_variant_rect.png', 9);

// 3. Clean Transparent version (cyan icon + dark text + stereo fringes)
$logoTrans = imagecreatetruecolor($w, $h);
imagealphablending($logoTrans, false);
imagesavealpha($logoTrans, true);
$trans2 = imagecolorallocatealpha($logoTrans, 0, 0, 0, 127);
imagefill($logoTrans, 0, 0, $trans2);
imagealphablending($logoTrans, true);
drawLogo($logoTrans, $scale, 'light');
imagepng($logoTrans, $previewDir . 'logo_variant_transparent.png', 9);

// 4. Compact Capsule Badge (Content bounded, tighter padding)
// Content width is about 210px base -> 840px scale
$cw = 220 * $scale; // 880
$ch = 48 * $scale;  // 192
$logoCompact = imagecreatetruecolor($cw, $ch);
imagealphablending($logoCompact, false);
imagesavealpha($logoCompact, true);
imagefill($logoCompact, 0, 0, $trans);
imagealphablending($logoCompact, true);

$cRadius = (int)(24 * $scale); // Full pill
imagefilledellipse($logoCompact, $cRadius, $ch/2, $ch, $ch, $bgObsidian);
imagefilledellipse($logoCompact, $cw - $cRadius, $ch/2, $ch, $ch, $bgObsidian);
imagefilledrectangle($logoCompact, $cRadius, 0, $cw - $cRadius, $ch, $bgObsidian);

// Draw subtle border around pill
$pillBorder = imagecolorallocatealpha($logoCompact, 56, 189, 248, 90);
imagesetthickness($logoCompact, (int)(1.5 * $scale));
// draw border outline
imagearc($logoCompact, $cRadius, $ch/2, $ch - 2, $ch - 2, 90, 270, $pillBorder);
imagearc($logoCompact, $cw - $cRadius, $ch/2, $ch - 2, $ch - 2, 270, 90, $pillBorder);
imageline($logoCompact, $cRadius, 1, $cw - $cRadius, 1, $pillBorder);
imageline($logoCompact, $cRadius, $ch - 2, $cw - $cRadius, $ch - 2, $pillBorder);

// Scale logo slightly to fit inside pill
// Shift x by -10px
$imPill = imagecreatetruecolor($cw, $ch);
imagealphablending($imPill, false);
imagesavealpha($imPill, true);
imagefill($imPill, 0, 0, $trans);
imagealphablending($imPill, true);
imagecopy($imPill, $logoCompact, 0, 0, 0, 0, $cw, $ch);

// Draw logo elements centered inside compact pill
$scalePill = $scale * 0.92;
$cxP = (int)(32 * $scale);
$cyP = (int)($ch / 2);
$outerRP = (int)(12 * $scalePill);
$innerRP = (int)(3.8 * $scalePill);
$strokeWP = (int)(2.2 * $scalePill);
$iconColP = imagecolorallocate($imPill, $cyanR, $cyanG, $cyanB);

for ($i = -$strokeWP/2; $i <= $strokeWP/2; $i += 0.5) {
    imageellipse($imPill, $cxP, $cyP, ($outerRP + $i) * 2, ($outerRP + $i) * 2, $iconColP);
    imageellipse($imPill, $cxP, $cyP, ($innerRP + $i) * 2, ($innerRP + $i) * 2, $iconColP);
}
$notchTopP = $cyP - $outerRP - (int)(1 * $scalePill);
$notchBottomP = $cyP - $outerRP + (int)(3.8 * $scalePill);
imagesetthickness($imPill, $strokeWP);
imageline($imPill, $cxP, $notchTopP, $cxP, $notchBottomP, $iconColP);

// Text inside pill
$fontSizeP = 18 * $scale;
$textXP = (int)(54 * $scale);
$textYP = (int)($cyP + (7 * $scale));
$textP = "SYNTHESIA";
$trackingP = (int)(3.2 * $scale);

$textCoreColP = imagecolorallocate($imPill, 248, 250, 252);
$fringeLP = imagecolorallocatealpha($imPill, $cyanR, $cyanG, $cyanB, 40);
$fringeRP = imagecolorallocatealpha($imPill, $amberR, $amberG, $amberB, 45);

$curXP = $textXP;
for ($i = 0; $i < strlen($textP); $i++) {
    $char = $textP[$i];
    $box = imagettfbbox($fontSizeP, 0, $fontPath, $char);
    $charW = $box[2] - $box[0];
    
    $shiftL = (int)(1.8 * $scale);
    $shiftR = (int)(1.8 * $scale);
    imagettftext($imPill, $fontSizeP, 0, $curXP - $shiftL, $textYP, $fringeLP, $fontPath, $char);
    imagettftext($imPill, $fontSizeP, 0, $curXP + $shiftR, $textYP, $fringeRP, $fontPath, $char);
    imagettftext($imPill, $fontSizeP, 0, $curXP, $textYP, $textCoreColP, $fontPath, $char);
    
    $curXP += $charW + $trackingP;
}

imagepng($imPill, $previewDir . 'logo_variant_pill.png', 9);

echo "All 4 logo variants generated in $previewDir\n";
