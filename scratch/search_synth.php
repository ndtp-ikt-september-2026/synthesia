<?php
function searchCommons($query) {
    $url = "https://commons.wikimedia.org/w/api.php?action=query&format=json&generator=search&gsrnamespace=6&gsrlimit=12&gsrsearch=" . urlencode($query) . "&prop=imageinfo&iiprop=url|size";
    $opts = ['http' => ['method' => 'GET', 'header' => "User-Agent: SynthesiaStorefrontBot/1.0\r\n"]];
    $raw = @file_get_contents($url, false, stream_context_create($opts));
    if (!$raw) return [];
    $data = json_decode($raw, true);
    $results = [];
    if (!empty($data['query']['pages'])) {
        foreach ($data['query']['pages'] as $p) {
            if (!empty($p['imageinfo'][0])) {
                $info = $p['imageinfo'][0];
                if (preg_match('/\.(jpg|jpeg)$/i', $p['title'])) {
                    $results[] = [
                        'title' => $p['title'],
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

$synths = searchCommons("analog synthesizer keyboard studio");
foreach ($synths as $s) {
    echo "{$s['title']} ({$s['width']}x{$s['height']}): {$s['url']}\n";
}
