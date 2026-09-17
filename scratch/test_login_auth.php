<?php
require 'admin/config.php';
require_once(DIR_SYSTEM . 'startup.php');

$registry = new Registry();
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);
$config = new Config();
$registry->set('config', $config);
$request = new Request();
$registry->set('request', $request);
$session = new Session('native');
$registry->set('session', $session);

$user = new Cart\User($registry);

foreach (['admin', 'root', 'synthesia', 'password', '123456', 'syn-sept'] as $pass) {
    if ($user->login('admin', $pass)) {
        echo "SUCCESS! Password is '$pass'\n";
        break;
    }
}
if (!$user->isLogged()) {
    echo "None of the standard passwords matched.\n";
    // Check salt and password hash in DB
    $res = $db->query("SELECT user_id, username, salt, password FROM " . DB_PREFIX . "user WHERE username = 'admin'");
    if ($res->num_rows) {
        $row = $res->row;
        echo "Salt: {$row['salt']}, Pass hash: {$row['password']}\n";
    }
}
