<?php
$urls = [
    'electric' => 'https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6&gsrsearch=Fender+Stratocaster+guitar&gsrlimit=10&prop=imageinfo&iiprop=url|size&format=json',
    'acoustic' => 'https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6&gsrsearch=acoustic+guitar+instrument&gsrlimit=10&prop=imageinfo&iiprop=url|size&format=json'
];

$opts = ['http' => ['header' => "User-Agent: SynthesiaStore/1.0\r\n"]];
$ctx = stream_context_create($opts);

foreach ($urls as $k => $u) {
    echo "=== $k ===\n";
    $json = file_get_contents($u, false, $ctx);
    $data = json_decode($json, true);
    if (!empty($data['query']['pages'])) {
        foreach ($data['query']['pages'] as $p) {
            if (!empty($p['imageinfo'][0]['url']) && preg_match('/\.(jpe?g)$/i', $p['imageinfo'][0]['url'])) {
                echo "  " . $p['title'] . " (" . $p['imageinfo'][0]['width'] . "x" . $p['imageinfo'][0]['height'] . "): " . $p['imageinfo'][0]['url'] . "\n";
            }
        }
    }
}
