<?php
$html = file_get_contents('http://synthesia/');
$matches = [];
preg_match('/<nav id="menu".*?<\/nav>/s', $html, $matches);
if (!empty($matches[0])) {
    echo "Found menu HTML:\n";
    preg_match_all('/<li class="dropdown"><a href="(.*?)".*?>(.*?)<\/a>/', $matches[0], $topLinks);
    for ($i = 0; $i < count($topLinks[0]); $i++) {
        echo "- Top: " . trim(strip_tags($topLinks[2][$i])) . " => " . $topLinks[1][$i] . "\n";
    }
} else {
    echo "Menu not found in HTML\n";
}
