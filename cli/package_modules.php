<?php
/**
 * Synthesia / SoundNet OCMOD Package Compiler
 *
 * Compiles all modular extensions from packages/ into production-ready .ocmod.zip archives in dist/
 *
 * Usage:
 *   php cli/package_modules.php [--all]
 *   php cli/package_modules.php soundnet_storefront
 *   php cli/package_modules.php soundnet_tracklist
 *   php cli/package_modules.php soundnet_stopkran
 */

$rootDir = dirname(__DIR__);
$distDir = $rootDir . '/dist';
$packagesDir = $rootDir . '/packages';

if (!is_dir($distDir)) {
    mkdir($distDir, 0777, true);
}

$modules = [
    'soundnet_storefront' => [
        'name' => 'SoundNet Storefront & Product Enhancements',
        'dir' => $packagesDir . '/soundnet_storefront',
        'zip' => $distDir . '/soundnet_storefront.ocmod.zip'
    ],
    'soundnet_tracklist' => [
        'name' => 'SoundNet Dynamic Tracklist & Audio Player',
        'dir' => $packagesDir . '/soundnet_tracklist',
        'zip' => $distDir . '/soundnet_tracklist.ocmod.zip'
    ],
    'soundnet_stopkran' => [
        'name' => 'SoundNet Stop-Kran Circuit Breaker & Watchdog',
        'dir' => $packagesDir . '/soundnet_stopkran',
        'zip' => $distDir . '/soundnet_stopkran.ocmod.zip'
    ]
];

$target = $argv[1] ?? '--all';

echo "=== Synthesia OCMOD Package Compiler ===" . PHP_EOL . PHP_EOL;

function addFolderToZip($folder, ZipArchive $zip, $exclusiveLength) {
    $handle = opendir($folder);
    while ($file = readdir($handle)) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $filePath = $folder . '/' . $file;
        $localPath = substr($filePath, $exclusiveLength);
        $localPath = str_replace('\\', '/', $localPath);

        if (is_file($filePath)) {
            $zip->addFile($filePath, $localPath);
        } elseif (is_dir($filePath)) {
            $zip->addEmptyDir($localPath);
            addFolderToZip($filePath, $zip, $exclusiveLength);
        }
    }
    closedir($handle);
}

$built = 0;
foreach ($modules as $code => $info) {
    if ($target !== '--all' && $target !== $code) {
        continue;
    }

    echo "Building package [{$code}] ({$info['name']})..." . PHP_EOL;
    $srcDir = $info['dir'];
    if (!is_dir($srcDir)) {
        fwrite(STDERR, "  [ERROR] Source directory not found: {$srcDir}" . PHP_EOL);
        continue;
    }

    $zipPath = $info['zip'];
    if (file_exists($zipPath)) {
        unlink($zipPath);
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        fwrite(STDERR, "  [ERROR] Cannot open zip archive for writing: {$zipPath}" . PHP_EOL);
        continue;
    }

    // 1. Add install.xml if present
    $installXml = $srcDir . '/install.xml';
    if (file_exists($installXml)) {
        $zip->addFile($installXml, 'install.xml');
    }

    // 2. Add upload/ directory recursively
    $uploadDir = $srcDir . '/upload';
    if (is_dir($uploadDir)) {
        addFolderToZip($uploadDir, $zip, strlen($srcDir) + 1);
    }

    // 3. Add cli/ directory if present (e.g. StopKran emergency CLI)
    $cliDir = $srcDir . '/cli';
    if (is_dir($cliDir)) {
        addFolderToZip($cliDir, $zip, strlen($srcDir) + 1);
    }

    $fileCount = $zip->numFiles;
    $zip->close();

    $sizeBytes = filesize($zipPath);
    $sizeKb = round($sizeBytes / 1024, 2);
    echo "  [SUCCESS] Created {$zipPath}" . PHP_EOL;
    echo "  Files: {$fileCount}, Size: {$sizeKb} KB ({$sizeBytes} bytes)" . PHP_EOL . PHP_EOL;
    $built++;
}

echo "Done. Built {$built} package(s) into dist/" . PHP_EOL;
