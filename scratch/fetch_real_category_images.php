<?php
function findCommonsImages($query, $limit = 5) {
    $url = 'https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6&gsrsearch=' . urlencode($query) . '&gsrlimit=' . $limit . '&prop=imageinfo&iiprop=url|size&format=json';
    $opts = ['http' => ['header' => "User-Agent: SoundNetDev/1.0 (soundnet-test@example.org)\r\n"]];
    $ctx = stream_context_create($opts);
    $json = file_get_contents($url, false, $ctx);
    $data = json_decode($json, true);
    $res = [];
    if (!empty($data['query']['pages'])) {
        foreach ($data['query']['pages'] as $p) {
            if (!empty($p['imageinfo'][0]['url'])) {
                $info = $p['imageinfo'][0];
                if (preg_match('/\.(jpe?g)(\?.*)?$/i', $info['url'])) {
                    $res[] = [
                        'title' => $p['title'],
                        'url' => $info['url'],
                        'w' => $info['width'],
                        'h' => $info['height']
                    ];
                }
            }
        }
    }
    return $res;
}

$queries = [
    'vinyl' => 'turntable vinyl',
    'cd' => 'compact disc audio',
    'electric_guitar' => 'Fender Stratocaster guitar',
    'acoustic_guitar' => 'acoustic guitar',
    'synthesizer' => 'analog synthesizer'
];

foreach ($queries as $cat => $q) {
    echo "=== $cat ($q) ===\n";
    $items = findCommonsImages($q);
    foreach ($items as $it) {
        echo "  - {$it['title']} ({$it['w']}x{$it['h']}): {$it['url']}\n";
    }
}
