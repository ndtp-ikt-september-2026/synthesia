<?php
$srcFile = 'd:/OSPanel/domains/synthesia/scratch/candidates/cd_audio_png.png';
if (file_exists($srcFile)) {
    $src = imagecreatefrompng($srcFile);
    $w = imagesx($src);
    $h = imagesy($src);
    echo "CD PNG size: {$w}x{$h}\n";
    $tw = 600;
    $th = 600;
    $thumb = imagecreatetruecolor($tw, $th);
    imagealphablending($thumb, false);
    imagesavealpha($thumb, true);
    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
    imagepng($thumb, 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/preview_cd_audio.png');
    echo "Preview saved.\n";
}
