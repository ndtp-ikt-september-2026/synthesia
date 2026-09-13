<?php
$cookieFile = __DIR__ . '/cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

// First request to get session cookie
$ch0 = curl_init('http://synthesia/');
curl_setopt($ch0, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch0, CURLOPT_COOKIEJAR, $cookieFile);
curl_exec($ch0);

// Add product to cart
$ch = curl_init('http://synthesia/index.php?route=checkout/cart/add');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['product_id' => 16, 'quantity' => 1]));
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$res = curl_exec($ch);
curl_close($ch);
echo "Add response: " . $res . "\n";

// Get cart info HTML
$ch2 = curl_init('http://synthesia/index.php?route=common/cart/info');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_COOKIEFILE, $cookieFile);
$html = curl_exec($ch2);
curl_close($ch2);
echo "Cart info HTML:\n" . $html . "\n";

