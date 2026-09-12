<?php
$db = new mysqli('localhost', 'root', 'syn-sept', 'syn-db');
if ($db->connect_error) {
    die("DB connection failed: " . $db->connect_error . "\n");
}

$title = 'SYNTHESIA — Магазин винила, CD и музыкальных инструментов';
$store_name = 'SYNTHESIA';

$db->query("UPDATE oc_setting SET `value` = '" . $db->real_escape_string($title) . "' WHERE `key` = 'config_meta_title'");
$db->query("UPDATE oc_setting SET `value` = '" . $db->real_escape_string($store_name) . "' WHERE `key` = 'config_name'");

echo "Updated config_meta_title to: " . $title . "\n";
echo "Updated config_name to: " . $store_name . "\n";

$res = $db->query("SELECT `key`, `value` FROM oc_setting WHERE `key` IN ('config_name', 'config_meta_title')");
while ($r = $res->fetch_assoc()) {
    echo "  " . $r['key'] . " = " . $r['value'] . "\n";
}
