<?php
$dir = 'd:/OSPanel/domains/synthesia/scratch/candidates/';
$files = scandir($dir);
$previewDir = 'C:/Users/zabazaba/.gemini/antigravity-ide/brain/097bb6c3-3902-46ed-ac44-d43d8da2b227/scratch/';
if (!is_dir($previewDir)) {
    mkdir($previewDir, 0777, true);
}

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $filePath = $dir . $file;
    $info = getimagesize($filePath);
    if (!$info) continue;
    
    echo "$file: {$info[0]}x{$info[1]} ({$info['mime']})\n";
    
    // Create small 600px preview for quick inspection
    $src = imagecreatefromjpeg($filePath);
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
    imagejpeg($thumb, $previewDir . 'preview_' . $file, 85);
    imagedestroy($thumb);
    imagedestroy($src);
}
echo "Previews created in artifact scratch directory.\n";
