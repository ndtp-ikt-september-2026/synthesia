<?php
$cookieFile = __DIR__ . '/admin_cookies.txt';

function fetchPage($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

// Read token
require 'config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
// Login again fresh
$ch = curl_init('http://synthesia/admin/index.php?route=common/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['username' => 'admin', 'password' => 'syn-sept']));
$resp = curl_exec($ch);
$effUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
parse_str(parse_url($effUrl, PHP_URL_QUERY), $qp);
$token = $qp['user_token'] ?? '';
echo "Token: $token\n";

if ($token) {
    // 1. Unfiltered product list
    $html = fetchPage("http://synthesia/admin/index.php?route=catalog/product&user_token=$token", $cookieFile);
    $html = str_replace('href="view/', 'href="http://synthesia/admin/view/', $html);
    $html = str_replace('src="view/', 'src="http://synthesia/admin/view/', $html);
    $html = str_replace('href="catalog/', 'href="http://synthesia/catalog/', $html);
    $html = str_replace('src="catalog/', 'src="http://synthesia/catalog/', $html);
    file_put_contents('d:/OSPanel/domains/synthesia/scratch/admin_preview.html', $html);
    echo "Saved admin_preview.html (" . strlen($html) . " bytes)\n";

    // 2. Filtered by Artist
    $htmlPF = fetchPage("http://synthesia/admin/index.php?route=catalog/product&user_token=$token&filter_artist=" . urlencode('Pink Floyd'), $cookieFile);
    $htmlPF = str_replace('href="view/', 'href="http://synthesia/admin/view/', $htmlPF);
    $htmlPF = str_replace('src="view/', 'src="http://synthesia/admin/view/', $htmlPF);
    file_put_contents('d:/OSPanel/domains/synthesia/scratch/admin_preview_filtered.html', $htmlPF);
    echo "Saved admin_preview_filtered.html (" . strlen($htmlPF) . " bytes)\n";
}
