<?php
require 'config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$passwords = ['admin', 'root', 'synthesia', 'password', '123456', 'syn-sept', 'openserver', '12345'];
foreach ($passwords as $p) {
    $sql = "SELECT username FROM " . DB_PREFIX . "user WHERE username = 'admin' AND (password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $db->real_escape_string($p) . "'))))) OR password = '" . md5($p) . "')";
    $res = $db->query($sql);
    if ($res && $res->num_rows > 0) {
        echo "FOUND PASSWORD: '$p'\n";
        exit;
    }
}
echo "No standard password matched.\n";
