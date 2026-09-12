<?php
require_once('config.php');
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
$res = $db->query("SELECT * FROM oc_product_tracklist WHERE product_id = 169 ORDER BY track_num ASC");
echo "Tracks for product 169:\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
