<?php
$previewDir = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/';

// 1. Vinyl Records (Audio-Technica on teal vinyl)
$vinylSrc = imagecreatefromjpeg('d:/OSPanel/domains/synthesia/scratch/candidates/vinyl.jpg');
$vw = imagesx($vinylSrc);
$vh = imagesy($vinylSrc);
// Crop square: focus on turntable tonearm and vinyl center
// vinyl.jpg is 4896 x 3264. Square side = 3264. Let's take x = (4896 - 3264) / 2 = 816, or slightly right to center tonearm
$vinylSquare = imagecreatetruecolor(1024, 1024);
imagecopyresampled($vinylSquare, $vinylSrc, 0, 0, 700, 0, 1024, 1024, 3264, 3264);
imagejpeg($vinylSquare, $previewDir . 'test_vinyl_records.jpg', 92);
imagedestroy($vinylSrc);

// 2. Compact Discs (Real optical CD on sleek studio dark slate background)
$cdPng = imagecreatefrompng('d:/OSPanel/domains/synthesia/scratch/candidates/cd_audio_png.png');
$cw = imagesx($cdPng);
$ch = imagesy($cdPng);
$cdSquare = imagecreatetruecolor(1024, 1024);
// Sleek studio slate/dark gradient background matching store theme
$bgSlate = imagecolorallocate($cdSquare, 15, 23, 42); // #0f172a
imagefill($cdSquare, 0, 0, $bgSlate);
// Add subtle soft radial glow behind the disc
for ($r = 380; $r >= 0; $r -= 10) {
    $alpha = (int)(127 - (127 - 105) * (1 - $r / 380));
    $glowCol = imagecolorallocatealpha($cdSquare, 30, 45, 75, $alpha);
    imagefilledellipse($cdSquare, 512, 512, $r * 2, $r * 2, $glowCol);
}
// Paste CD disc at size 880x880 centered
imagecopyresampled($cdSquare, $cdPng, 72, 72, 0, 0, 880, 880, $cw, $ch);
imagejpeg($cdSquare, $previewDir . 'test_compact_discs.jpg', 92);
imagedestroy($cdPng);

// 3. Electric Guitars (Gibson Les Paul Classic Cherry Sunburst)
$elecSrc = imagecreatefromjpeg('d:/OSPanel/domains/synthesia/scratch/candidates/electric.jpg');
$ew = imagesx($elecSrc);
$eh = imagesy($elecSrc);
// electric.jpg is 3516 x 5273. The guitar body is at the bottom.
// We want body + pickups + bridge + knobs centered nicely
$elecSquare = imagecreatetruecolor(1024, 1024);
// Crop square: side = 3516, y = 1400 (guitar body)
imagecopyresampled($elecSquare, $elecSrc, 0, 0, 0, 1450, 1024, 1024, 3516, 3516);
imagejpeg($elecSquare, $previewDir . 'test_electric_guitars.jpg', 92);
imagedestroy($elecSrc);

// 4. Acoustic Guitars (Ervin Somogyi Handcrafted Master Dreadnought)
$acSrc = imagecreatefromjpeg('d:/OSPanel/domains/synthesia/scratch/candidates/somogyi.jpg');
$aw = imagesx($acSrc);
$ah = imagesy($acSrc);
// somogyi.jpg is 1725 x 2531. Guitar body centered vertically
$acSquare = imagecreatetruecolor(1024, 1024);
imagecopyresampled($acSquare, $acSrc, 0, 0, 0, 500, 1024, 1024, 1725, 1725);
imagejpeg($acSquare, $previewDir . 'test_acoustic_guitars.jpg', 92);
imagedestroy($acSrc);

// 5. Synthesizers (Moog Minimoog Model D)
$synthSrc = imagecreatefromjpeg('d:/OSPanel/domains/synthesia/scratch/candidates/synth_alt.jpg');
$sw = imagesx($synthSrc);
$sh = imagesy($synthSrc);
// synth_alt.jpg is 4349 x 1339 (two Minimoogs side-by-side).
// Let's crop the left Minimoog with its keyboard, knobs, oscillator bank, and wood cabinet:
// x: 80 to 2160 (w ~ 2080), y: 0 to 1339.
// To make it square: we can place the Minimoog on a clean dark studio background or crop 1339x1339:
$synthSquare = imagecreatetruecolor(1024, 1024);
$synthBg = imagecolorallocate($synthSquare, 20, 24, 33);
imagefill($synthSquare, 0, 0, $synthBg);
// Or crop square from x: 100, y: 0, w: 1339, h: 1339
imagecopyresampled($synthSquare, $synthSrc, 0, 100, 100, 0, 1024, 824, 1900, 1339);
imagejpeg($synthSquare, $previewDir . 'test_synthesizers.jpg', 92);
imagedestroy($synthSrc);

echo "Category test images generated!\n";
