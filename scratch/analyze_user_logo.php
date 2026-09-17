<?php
$path = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/.user_uploaded/media_1789632306954.png';
$im = imagecreatefrompng($path);
$w = imagesx($im);
$h = imagesy($im);
echo "Width: $w, Height: $h\n";

// Sample corner background color
$bgRgb = imagecolorat($im, 5, 5);
$bgR = ($bgRgb >> 16) & 0xFF;
$bgG = ($bgRgb >> 8) & 0xFF;
$bgB = $bgRgb & 0xFF;
printf("Background color (5,5): #%02x%02x%02x (RGB: %d, %d, %d)\n", $bgR, $bgG, $bgB, $bgR, $bgG, $bgB);

// Sample the icon color
// The icon is roughly on the left side
$colors = [];
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        // Check for bluish icon colors
        if ($b > 150 && $b > $r * 1.5 && $x < 80) {
            $hex = sprintf("#%02x%02x%02x", $r, $g, $b);
            $colors[$hex] = ($colors[$hex] ?? 0) + 1;
        }
    }
}
arsort($colors);
echo "Top icon bluish colors:\n";
foreach (array_slice($colors, 0, 5) as $hex => $count) {
    echo "  $hex: $count\n";
}

// Sample text colors
$textColors = [];
for ($y = 0; $y < $h; $y++) {
    for ($x = 60; $x < $w; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($r > 180 && $g > 180 && $b > 180) {
            $hex = sprintf("#%02x%02x%02x", $r, $g, $b);
            $textColors[$hex] = ($textColors[$hex] ?? 0) + 1;
        }
    }
}
arsort($textColors);
echo "Top white text colors:\n";
foreach (array_slice($textColors, 0, 5) as $hex => $count) {
    echo "  $hex: $count\n";
}

// Check chromatic aberration colors (red/orange on right, cyan on left of letters)
$chromaColors = [];
for ($y = 0; $y < $h; $y++) {
    for ($x = 60; $x < $w; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($r > 180 && $b < 100) { // warm/orange
            $hex = sprintf("warm #%02x%02x%02x", $r, $g, $b);
            $chromaColors[$hex] = ($chromaColors[$hex] ?? 0) + 1;
        }
        if ($b > 180 && $r < 100) { // cyan
            $hex = sprintf("cyan #%02x%02x%02x", $r, $g, $b);
            $chromaColors[$hex] = ($chromaColors[$hex] ?? 0) + 1;
        }
    }
}
arsort($chromaColors);
echo "Top chromatic colors:\n";
foreach (array_slice($chromaColors, 0, 8) as $hex => $count) {
    echo "  $hex: $count\n";
}
