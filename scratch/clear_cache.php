<?php
require_once __DIR__ . '/../config.php';

function clearDir($dir) {
    if (!is_dir($dir)) return 0;
    $count = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        if ($file->isFile()) {
            @unlink($file->getRealPath());
            $count++;
        }
    }
    return $count;
}

$c1 = clearDir(DIR_STORAGE . 'cache');
$c2 = clearDir(DIR_CACHE);
echo "Cleared cache: $c1 in storage/cache, $c2 in system/cache\n";
