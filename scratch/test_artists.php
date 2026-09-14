<?php
$mysqli = new mysqli('127.0.0.1', 'root', 'syn-sept', 'syn-db');
$res = $mysqli->query('SELECT p.product_id, pd.name, pa.text as artist FROM oc_product p JOIN oc_product_description pd ON p.product_id=pd.product_id LEFT JOIN oc_product_attribute pa ON (p.product_id=pa.product_id AND pa.attribute_id=1) WHERE pa.text IN ("Hole", "Meat Puppets", "The Jesus Lizard", "Daft Punk", "Eric Prydz")');
while ($row = $res->fetch_assoc()) {
    echo $row['product_id'] . ': [' . $row['artist'] . '] - ' . $row['name'] . "\n";
}
