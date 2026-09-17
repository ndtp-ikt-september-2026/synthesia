<?php
require 'config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$res = $db->query("SELECT user_id, username, status FROM " . DB_PREFIX . "user");
while ($r = $res->fetch_assoc()) {
    echo "User: {$r['username']}, ID: {$r['user_id']}, Status: {$r['status']}\n";
}
