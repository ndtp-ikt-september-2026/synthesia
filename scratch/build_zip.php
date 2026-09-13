<?php
$src = realpath(__DIR__ . '/stopkran_pkg');
$zipFile = realpath(__DIR__ . '/..') . '/soundnet_stopkran.ocmod.zip';

if (file_exists($zipFile)) {
    unlink($zipFile);
}

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Failed to open zip archive: {$zipFile}\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    $subPath = substr($item->getPathname(), strlen($src) + 1);
    $entry = str_replace('\\', '/', $subPath);

    if ($item->isDir()) {
        $zip->addEmptyDir($entry);
    } else {
        $zip->addFile($item->getPathname(), $entry);
    }
}

$zip->close();
echo "Canonical ZIP created successfully: {$zipFile}\n";
