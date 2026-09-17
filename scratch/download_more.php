<?php
$targets = [
    'cd_audio_png.png' => 'https://upload.wikimedia.org/wikipedia/commons/f/ff/Audio_Compact_Disc.png',
    'walden_body.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/e/e3/00_Walden_Acoustic_Guitar_D310e_body.jpg',
    'walden_d640.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/e/e6/00_Walden_D640T_acoustic_guitar.jpg',
    'somogyi.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/b/b3/Ervin-Somogyi-Modified-Dreadnought.jpg'
];

$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: SynthesiaStorefrontBot/1.0 (contact@synthesia.local)\r\n"
    ]
]);

foreach ($targets as $filename => $url) {
    echo "Downloading $filename...\n";
    $content = @file_get_contents($url, false, $ctx);
    if ($content) {
        file_put_contents('d:/OSPanel/domains/synthesia/scratch/candidates/' . $filename, $content);
        echo "Saved $filename (" . strlen($content) . " bytes)\n";
    } else {
        echo "Failed $filename\n";
    }
}
