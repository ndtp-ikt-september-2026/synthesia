<?php
require_once __DIR__ . '/../config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
$db->set_charset("utf8mb4");

$cat_ids = [5, 6, 7, 8, 9, 10];
$cat_list = implode(',', $cat_ids);

// 1. Check all tables with category_id column
$tablesRes = $db->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND COLUMN_NAME LIKE '%category_id%'");
while ($row = $tablesRes->fetch_assoc()) {
    $t = $row['TABLE_NAME'];
    $c = $row['COLUMN_NAME'];
    $check = $db->query("SELECT COUNT(*) as cnt FROM `$t` WHERE `$c` IN ($cat_list)");
    if ($check) {
        $cnt = $check->fetch_assoc()['cnt'];
        if ($cnt > 0) {
            echo "Table $t.$c has $cnt references to ($cat_list)\n";
        }
    }
}

// 2. Check oc_setting and oc_module
$res = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE value LIKE '%5%' OR value LIKE '%6%'");
while ($r = $res->fetch_assoc()) {
    if (strpos($r['value'], '"5"') !== false || strpos($r['value'], '"6"') !== false) {
        echo "Setting: " . $r['key'] . " => " . $r['value'] . "\n";
    }
}

$res = $db->query("SELECT * FROM " . DB_PREFIX . "module");
while ($r = $res->fetch_assoc()) {
    $set = json_decode($r['setting'], true);
    $encoded = json_encode($set);
    foreach ($cat_ids as $cid) {
        if (strpos($encoded, (string)$cid) !== false) {
            echo "Module " . $r['name'] . " (code=" . $r['code'] . ") contains $cid: " . $r['setting'] . "\n";
            break;
        }
    }
}

// 3. Check SEO URLs
$res = $db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query IN ('category_id=5', 'category_id=6', 'category_id=7', 'category_id=8', 'category_id=9', 'category_id=10')");
while ($r = $res->fetch_assoc()) {
    echo "SEO URL: " . $r['query'] . " => " . $r['keyword'] . "\n";
}
