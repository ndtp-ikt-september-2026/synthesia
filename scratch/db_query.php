<?php
require_once('config.php');
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query('SELECT * FROM oc_layout_module WHERE layout_id = 2');
echo "Layout 2 modules:\n";
while ($row = $res->fetch_assoc()) {
    echo "  - code: " . $row['code'] . ", position: " . $row['position'] . ", sort_order: " . $row['sort_order'] . "\n";
}

$tracks = $db->query("SHOW TABLES LIKE '%track%'");
echo "\nTrack tables:\n";
while ($r = $tracks->fetch_row()) {
    echo "  - " . $r[0] . "\n";
}
