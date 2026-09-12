<?php
require_once __DIR__ . '/../config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$db->query("DELETE FROM oc_layout_module WHERE layout_id = 1 AND code = 'featured.28'");
echo "Deleted featured.28 from layout 1. Affected rows: " . $db->affected_rows . "\n";

// Verify layout 1 modules
$res = $db->query("SELECT * FROM oc_layout_module WHERE layout_id = 1");
echo "Remaining modules in layout 1: " . $res->num_rows . "\n";
while ($row = $res->fetch_assoc()) {
    echo "- " . $row['code'] . " at " . $row['position'] . "\n";
}
