<?php
$candidates = [
    'walden_body.jpg',
    'walden_d640.jpg',
    'somogyi.jpg'
];
$previewDir = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/';

foreach ($candidates as $c) {
    $path = 'd:/OSPanel/domains/synthesia/scratch/candidates/' . $c;
    if (!file_exists($path)) continue;
    $src = imagecreatefromjpeg($path);
    $w = imagesx($src);
    $h = imagesy($src);
    $maxDim = 600;
    if ($w > $h) {
        $nw = $maxDim;
        $nh = (int)round($h * ($maxDim / $w));
    } else {
        $nh = $maxDim;
        $nw = (int)round($w * ($maxDim / $h));
    }
    $thumb = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagejpeg($thumb, $previewDir . 'preview_' . $c, 85);
    imagedestroy($thumb);
    imagedestroy($src);
    echo "Saved preview_$c\n";
}
