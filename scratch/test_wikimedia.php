<?php
function searchWikimedia($query) {
    $url = 'https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6&gsrsearch=' . urlencode($query) . '&gsrlimit=10&prop=imageinfo&iiprop=url|size&format=json';
    $opts = ['http' => ['header' => "User-Agent: SynthesiaStore/1.0 (synthesia-local)\r\n"]];
    $ctx = stream_context_create($opts);
    $json = file_get_contents($url, false, $ctx);
    $data = json_decode($json, true);
    $results = [];
    if (!empty($data['query']['pages'])) {
        foreach ($data['query']['pages'] as $p) {
            if (!empty($p['imageinfo'][0]['url']) && preg_match('/\.(jpe?g|png)$/i', $p['imageinfo'][0]['url'])) {
                $results[] = [
                    'title' => $p['title'],
                    'url' => $p['imageinfo'][0]['url'],
                    'width' => $p['imageinfo'][0]['width'],
                    'height' => $p['imageinfo'][0]['height']
                ];
            }
        }
    }
    return $results;
}

$queries = [
    'electric' => '"Les Paul" guitar',
    'acoustic' => '"acoustic guitar"',
    'vinyl' => '"turntable" vinyl',
    'cd' => '"compact disc" audio',
    'synth' => 'synthesizer analog'
];

foreach ($queries as $k => $q) {
    echo "=== $k ===\n";
    $res = searchWikimedia($q);
    foreach ($res as $r) {
        echo "  - " . $r['title'] . " (" . $r['width'] . "x" . $r['height'] . "): " . $r['url'] . "\n";
    }
}
