<?php
// Exact OpenCart 3 OCMOD Compiler CLI Utility
require_once __DIR__ . '/../admin/config.php';
require_once DIR_SYSTEM . 'startup.php';

$registry = new Registry();
$config = new Config();
$config->load('default');
$config->load('admin');
$registry->set('config', $config);

$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);

// Clean DIR_MODIFICATION first
$files = [];
$path = [DIR_MODIFICATION . '*'];
while (count($path) != 0) {
    $next = array_shift($path);
    foreach (glob($next) as $file) {
        if (is_dir($file)) {
            $path[] = $file . '/*';
        }
        $files[] = $file;
    }
}
rsort($files);
foreach ($files as $file) {
    if ($file != DIR_MODIFICATION . 'index.html') {
        if (is_file($file)) {
            unlink($file);
        } elseif (is_dir($file)) {
            rmdir($file);
        }
    }
}

// Auto-sync install.xml into DB if exists
if (is_file(__DIR__ . '/../install.xml')) {
    $xml_content = file_get_contents(__DIR__ . '/../install.xml');
    $check_mod = $db->query("SELECT modification_id FROM " . DB_PREFIX . "modification WHERE code = 'soundnet_storefront'");
    if ($check_mod->num_rows) {
        $db->query("UPDATE " . DB_PREFIX . "modification SET xml = '" . $db->escape($xml_content) . "' WHERE code = 'soundnet_storefront'");
        echo "Auto-synced install.xml to oc_modification in database.\n";
    }
}

$query = $db->query("SELECT * FROM " . DB_PREFIX . "modification WHERE status = '1'");
echo "Found " . $query->num_rows . " active modifications in DB.\n";

$modification = [];

foreach ($query->rows as $result) {
    $xml = $result['xml'];
    if (empty($xml)) continue;

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->loadXml($xml);

    $name = $dom->getElementsByTagName('name')->item(0)->textContent;
    echo "Processing MOD: " . $name . "\n";

    $files = $dom->getElementsByTagName('modification')->item(0)->getElementsByTagName('file');

    foreach ($files as $file) {
        $operations = $file->getElementsByTagName('operation');
        $files_path = explode('|', str_replace('\\', '/', $file->getAttribute('path')));

        foreach ($files_path as $f_path) {
            $path = '';

            if (substr($f_path, 0, 7) == 'catalog') {
                $path = DIR_CATALOG . substr($f_path, 8);
            } elseif (substr($f_path, 0, 5) == 'admin') {
                $path = DIR_APPLICATION . substr($f_path, 6);
            } elseif (substr($f_path, 0, 6) == 'system') {
                $path = DIR_SYSTEM . substr($f_path, 7);
            }

            if ($path && is_file($path)) {
                $key = '';
                if (substr($path, 0, strlen(DIR_CATALOG)) == DIR_CATALOG) {
                    $key = 'catalog/' . substr($path, strlen(DIR_CATALOG));
                } elseif (substr($path, 0, strlen(DIR_APPLICATION)) == DIR_APPLICATION) {
                    $key = 'admin/' . substr($path, strlen(DIR_APPLICATION));
                } elseif (substr($path, 0, strlen(DIR_SYSTEM)) == DIR_SYSTEM) {
                    $key = 'system/' . substr($path, strlen(DIR_SYSTEM));
                }

                if (!isset($modification[$key])) {
                    $modification[$key] = file_get_contents($path);
                }

                foreach ($operations as $operation) {
                    $search = $operation->getElementsByTagName('search')->item(0)->textContent;
                    $trim = $operation->getElementsByTagName('search')->item(0)->getAttribute('trim');
                    $index = $operation->getElementsByTagName('search')->item(0)->getAttribute('index');

                    if (!$trim || $trim == 'true') {
                        $search = trim($search);
                    }

                    $add = $operation->getElementsByTagName('add')->item(0)->textContent;
                    $trim = $operation->getElementsByTagName('add')->item(0)->getAttribute('trim');
                    $position = $operation->getElementsByTagName('add')->item(0)->getAttribute('position');
                    $offset = (int)$operation->getElementsByTagName('add')->item(0)->getAttribute('offset');

                    if ($trim == 'true') {
                        $add = trim($add);
                    }

                    $indexes = ($index !== '') ? explode(',', $index) : [];
                    $lines = explode("\n", $modification[$key]);
                    $i = 0;

                    for ($line_id = 0; $line_id < count($lines); $line_id++) {
                        $line = $lines[$line_id];
                        $match = false;

                        if (stripos($line, $search) !== false) {
                            if (!$indexes || in_array($i, $indexes)) {
                                $match = true;
                            }
                            $i++;
                        }

                        if ($match) {
                            switch ($position) {
                                default:
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
                            echo "  -> Applied '$position' to $key for: " . substr($search, 0, 40) . "...\n";
                            break;
                        }
                    }
                    $modification[$key] = implode("\n", $lines);
                }
            }
        }
    }
}

// Write to DIR_MODIFICATION
foreach ($modification as $key => $content) {
    $file = DIR_MODIFICATION . $key;
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($file, $content);
    echo "Compiled: $key (" . strlen($content) . " bytes)\n";
}

echo "All modifications compiled successfully into DIR_MODIFICATION!\n";
