<?php
$cookieFile = __DIR__ . '/admin_cookies.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

// 1. GET login page
$ch = curl_init('http://synthesia/admin/index.php?route=common/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$html = curl_exec($ch);

// 2. POST login credentials
$postFields = [
    'username' => 'admin',
    'password' => 'syn-sept'
];
curl_setopt($ch, CURLOPT_URL, 'http://synthesia/admin/index.php?route=common/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
$loginResp = curl_exec($ch);
$effUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

echo "Effective URL after login: $effUrl\n";
parse_str(parse_url($effUrl, PHP_URL_QUERY), $queryParams);
$token = $queryParams['user_token'] ?? '';
echo "Extracted user_token: $token\n";

if (!$token) {
    die("ERROR: Failed to log in to admin panel!\n");
}

// 3. Test autocompleteArtist
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_URL, "http://synthesia/admin/index.php?route=catalog/product/autocompleteArtist&user_token=$token&filter_name=P");
$autoArtist = curl_exec($ch);
echo "\nAutocomplete Artist for 'P':\n" . $autoArtist . "\n";

// 4. Test autocompleteBrand
curl_setopt($ch, CURLOPT_URL, "http://synthesia/admin/index.php?route=catalog/product/autocompleteBrand&user_token=$token&filter_name=F");
$autoBrand = curl_exec($ch);
echo "\nAutocomplete Brand for 'F':\n" . $autoBrand . "\n";

// 5. Test Filter by Artist: Pink Floyd
curl_setopt($ch, CURLOPT_URL, "http://synthesia/admin/index.php?route=catalog/product&user_token=$token&filter_artist=" . urlencode('Pink Floyd'));
$filterArtistHtml = curl_exec($ch);
echo "\nFilter Artist 'Pink Floyd':\n";
if (strpos($filterArtistHtml, 'Pink Floyd') !== false) {
    echo "  SUCCESS! Found Pink Floyd products (e.g. 'A Foot In The Door')\n";
} else {
    echo "  FAILED to find Pink Floyd product\n";
}

// 6. Test Filter by Year: 1973
curl_setopt($ch, CURLOPT_URL, "http://synthesia/admin/index.php?route=catalog/product&user_token=$token&filter_year=1973");
$filterYearHtml = curl_exec($ch);
echo "\nFilter Year '1973':\n";
if (strpos($filterYearHtml, 'The Dark Side of the Moon') !== false) {
    echo "  SUCCESS! Found product for year 1973\n";
} else {
    echo "  FAILED to find 1973 product\n";
}

// 7. Test Filter by Brand: Fender
curl_setopt($ch, CURLOPT_URL, "http://synthesia/admin/index.php?route=catalog/product&user_token=$token&filter_brand=Fender");
$filterBrandHtml = curl_exec($ch);
echo "\nFilter Brand 'Fender':\n";
if (strpos($filterBrandHtml, 'Stratocaster') !== false) {
    echo "  SUCCESS! Found Fender Stratocaster\n";
} else {
    echo "  FAILED to find Fender product\n";
}

// 8. Test Filter by Genre: Electronic
curl_setopt($ch, CURLOPT_URL, "http://synthesia/admin/index.php?route=catalog/product&user_token=$token&filter_genre=Electronic");
$filterGenreHtml = curl_exec($ch);
echo "\nFilter Genre 'Electronic':\n";
if (strpos($filterGenreHtml, 'Daft Punk') !== false || strpos($filterGenreHtml, 'Electronic') !== false) {
    echo "  SUCCESS! Found Electronic products\n";
} else {
    echo "  FAILED to find Electronic products\n";
}

curl_close($ch);
echo "\nAll filter API tests completed!\n";
