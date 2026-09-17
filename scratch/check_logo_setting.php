<?php
require 'config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$res = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` = 'config_logo'");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
