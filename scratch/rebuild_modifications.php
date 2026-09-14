<?php
// Script to rebuild OpenCart modification cache from oc_modification and system XML
require_once __DIR__ . '/../admin/config.php';

echo "DIR_MODIFICATION: " . DIR_MODIFICATION . "\n";
echo "DIR_CATALOG: " . DIR_CATALOG . "\n";
echo "DIR_APPLICATION: " . DIR_APPLICATION . "\n";
echo "DIR_SYSTEM: " . DIR_SYSTEM . "\n";

$pdo = new PDO('mysql:host=' . DB_HOSTNAME . ';port=' . DB_PORT . ';dbname=' . DB_DATABASE, DB_USERNAME, DB_PASSWORD);

// 1. Enable modifications
$pdo->query("UPDATE " . DB_PREFIX . "modification SET status = 1 WHERE code = 'soundnet_storefront'");
echo "Enabled soundnet_storefront modification in DB.\n";

// 2. Clear DIR_MODIFICATION
function clearModDir($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        if ($file->getFilename() === 'index.html') continue;
        if ($file->isDir()) {
            @rmdir($file->getRealPath());
        } else {
            @unlink($file->getRealPath());
        }
    }
}
clearModDir(DIR_MODIFICATION);
echo "Cleared DIR_MODIFICATION.\n";

// 3. Load all XML
$xmlList = [];
if (is_file(DIR_SYSTEM . 'modification.xml')) {
    $xmlList[] = file_get_contents(DIR_SYSTEM . 'modification.xml');
}
foreach (glob(DIR_SYSTEM . '*.ocmod.xml') as $f) {
    $xmlList[] = file_get_contents($f);
}
$stmt = $pdo->query("SELECT xml FROM " . DB_PREFIX . "modification WHERE status = '1'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $xmlList[] = $row['xml'];
}

echo "Loaded " . count($xmlList) . " XML modification files.\n";

$modification = [];
$original = [];
$log = [];

foreach ($xmlList as $xml) {
    if (empty($xml)) continue;

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    if (!@$dom->loadXml($xml)) {
        echo "Warning: Could not parse XML\n";
        continue;
    }

    $modName = $dom->getElementsByTagName('name')->length ? $dom->getElementsByTagName('name')->item(0)->textContent : 'Unnamed';
    $log[] = 'MOD: ' . $modName;
    echo "Processing MOD: " . $modName . "\n";

    $files = $dom->getElementsByTagName('modification')->item(0)->getElementsByTagName('file');

    foreach ($files as $fileNode) {
        $operations = $fileNode->getElementsByTagName('operation');
        $filePaths = explode('|', str_replace('\\', '/', $fileNode->getAttribute('path')));

        foreach ($filePaths as $relPath) {
            $fullPath = '';
            if (substr($relPath, 0, 7) == 'catalog') {
                $fullPath = DIR_CATALOG . substr($relPath, 8);
            } elseif (substr($relPath, 0, 5) == 'admin') {
                $fullPath = DIR_APPLICATION . substr($relPath, 6);
            } elseif (substr($relPath, 0, 6) == 'system') {
                $fullPath = DIR_SYSTEM . substr($relPath, 7);
            }

            if (!$fullPath) continue;

            $matchedFiles = glob($fullPath, GLOB_BRACE);
            if (!$matchedFiles) {
                echo "  No matched files for path: $fullPath\n";
                continue;
            }

            foreach ($matchedFiles as $mFile) {
                $key = '';
                if (substr($mFile, 0, strlen(DIR_CATALOG)) == DIR_CATALOG) {
                    $key = 'catalog/' . substr($mFile, strlen(DIR_CATALOG));
                } elseif (substr($mFile, 0, strlen(DIR_APPLICATION)) == DIR_APPLICATION) {
                    $key = 'admin/' . substr($mFile, strlen(DIR_APPLICATION));
                } elseif (substr($mFile, 0, strlen(DIR_SYSTEM)) == DIR_SYSTEM) {
                    $key = 'system/' . substr($mFile, strlen(DIR_SYSTEM));
                }

                if (!isset($modification[$key])) {
                    $content = file_get_contents($mFile);
                    $modification[$key] = preg_replace('~\r?\n~', "\n", $content);
                    $original[$key] = preg_replace('~\r?\n~', "\n", $content);
                    $log[] = PHP_EOL . 'FILE: ' . $key;
                }

                foreach ($operations as $operation) {
                    $searchNode = $operation->getElementsByTagName('search')->item(0);
                    $addNode = $operation->getElementsByTagName('add')->item(0);
                    if (!$searchNode || !$addNode) continue;

                    $search = $searchNode->textContent;
                    $trim = $searchNode->getAttribute('trim');
                    $index = $searchNode->getAttribute('index');
                    if (!$trim || $trim == 'true') {
                        $search = trim($search);
                    }

                    $add = $addNode->textContent;
                    $trimAdd = $addNode->getAttribute('trim');
                    $position = $addNode->getAttribute('position') ?: 'replace';
                    $offset = (int)($addNode->getAttribute('offset') ?: 0);
                    if ($trimAdd == 'true') {
                        $add = trim($add);
                    }

                    $indexes = ($index !== '') ? explode(',', $index) : [];

                    $lines = explode("\n", $modification[$key]);
                    $matchCount = 0;
                    $applied = false;

                    for ($line_id = 0; $line_id < count($lines); $line_id++) {
                        $line = $lines[$line_id];
                        if (stripos($line, $search) !== false) {
                            $isTarget = false;
                            if (!$indexes || in_array($matchCount, $indexes)) {
                                $isTarget = true;
                            }
                            $matchCount++;

                            if ($isTarget) {
                                switch ($position) {
                                    case 'replace':
                                        $new_lines = explode("\n", $add);
                                        if ($offset < 0) {
                                            array_splice($lines, $line_id + $offset, abs($offset) + 1, [str_replace($search, $add, $line)]);
                                            $line_id -= $offset;
                                        } else {
                                            array_splice($lines, $line_id, $offset + 1, [str_replace($search, $add, $line)]);
                                        }
                                        break;
                                    case 'before':
                                        $new_lines = explode("\n", $add);
                                        array_splice($lines, $line_id - $offset, 0, $new_lines);
                                        $line_id += count($new_lines);
                                        break;
                                    case 'after':
                                        $new_lines = explode("\n", $add);
                                        array_splice($lines, ($line_id + 1) + $offset, 0, $new_lines);
                                        $line_id += count($new_lines);
                                        break;
                                }
                                $applied = true;
                            }
                        }
                    }

                    if ($applied) {
                        echo "  [OK] Applied op: $position '$search' in $key\n";
                    } else {
                        echo "  [FAIL] Op not matched: '$search' in $key\n";
                    }

                    $modification[$key] = implode("\n", $lines);
                }
            }
        }
    }
}

// 4. Write modified files to DIR_MODIFICATION
$written = 0;
foreach ($modification as $key => $value) {
    if ($original[$key] != $value) {
        $destFile = DIR_MODIFICATION . $key;
        $destDir = dirname($destFile);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0777, true);
        }
        file_put_contents($destFile, $value);
        echo "Written modified file: $destFile (" . strlen($value) . " bytes)\n";
        $written++;
    }
}
echo "Total modified files written: $written\n";

// 5. Clear system cache
function clearCache($dir) {
    if (!is_dir($dir)) return 0;
    $cnt = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        if ($f->isFile()) {
            @unlink($f->getRealPath());
            $cnt++;
        }
    }
    return $cnt;
}
$purged = clearCache(DIR_STORAGE . 'cache/');
echo "Purged $purged cached files in DIR_CACHE.\n";
