<?php
$previewDir = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/';

// Option A: Minimoog closer crop from synth_alt.jpg
$src = imagecreatefromjpeg('d:/OSPanel/domains/synthesia/scratch/candidates/synth_alt.jpg');
// Right Minimoog: x: 2150 to 4300, y: 50 to 1339
$cropA = imagecreatetruecolor(1024, 1024);
// Let's crop centered around knobs and keys
$bg = imagecolorallocate($cropA, 25, 28, 36);
imagefill($cropA, 0, 0, $bg);
imagecopyresampled($cropA, $src, 0, 80, 2200, 0, 1024, 860, 2100, 1339);
imagejpeg($cropA, $previewDir . 'synth_crop_minimoog_right.jpg', 92);

// Option B: Moog & ARP Modular studio crop from synth.jpg
$srcModular = imagecreatefromjpeg('d:/OSPanel/domains/synthesia/scratch/candidates/synth.jpg');
// synth.jpg is 2968x2968. Let's crop the main synthesizer rack:
// x: 300 to 2600, y: 400 to 2700
$cropB = imagecreatetruecolor(1024, 1024);
imagecopyresampled($cropB, $srcModular, 0, 0, 400, 600, 1024, 1024, 2100, 2100);
imagejpeg($cropB, $previewDir . 'synth_crop_modular.jpg', 92);

echo "Crops generated\n";
