<?php
function searchCommons($query) {
    $url = "https://commons.wikimedia.org/w/api.php?action=query&format=json&generator=search&gsrnamespace=6&gsrlimit=10&gsrsearch=" . urlencode($query) . "&prop=imageinfo&iiprop=url|size|extmetadata";
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: SynthesiaStorefrontBot/1.0 (contact@synthesia.local)\r\n"
        ]
    ];
    $ctx = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $ctx);
    if (!$raw) return [];
    $data = json_decode($raw, true);
    $results = [];
    if (!empty($data['query']['pages'])) {
        foreach ($data['query']['pages'] as $page) {
            if (!empty($page['imageinfo'][0])) {
                $info = $page['imageinfo'][0];
                $title = $page['title'];
                if (preg_match('/\.(jpg|jpeg|png)$/i', $title)) {
                    $results[] = [
                        'title' => $title,
                        'url' => $info['url'],
                        'width' => $info['width'],
                        'height' => $info['height']
                    ];
                }
            }
        }
    }
    return $results;
}

echo "=== Compact Disc ===\n";
$cdResults = searchCommons("Compact Disc filetype:bitmap");
foreach (array_slice($cdResults, 0, 5) as $r) {
    echo "{$r['title']} ({$r['width']}x{$r['height']}): {$r['url']}\n";
}

echo "\n=== Acoustic Guitar ===\n";
$guitarResults = searchCommons("acoustic guitar studio white background");
foreach (array_slice($guitarResults, 0, 5) as $r) {
    echo "{$r['title']} ({$r['width']}x{$r['height']}): {$r['url']}\n";
}

if (empty($guitarResults)) {
    $guitarResults = searchCommons("acoustic guitar dreadnought");
    foreach (array_slice($guitarResults, 0, 5) as $r) {
        echo "{$r['title']} ({$r['width']}x{$r['height']}): {$r['url']}\n";
    }
}
