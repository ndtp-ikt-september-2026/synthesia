<?php
$url = 'https://raw.githubusercontent.com/rsms/inter/master/docs/font-files/Inter-Bold.otf';
$content = @file_get_contents($url, false, stream_context_create([
    'http' => ['header' => "User-Agent: SynthesiaFontFetcher/1.0\r\n"]
]));
if ($content && strlen($content) > 10000) {
    file_put_contents('d:/OSPanel/domains/synthesia/scratch/Inter-Bold.otf', $content);
    echo "Inter-Bold.otf downloaded successfully! (" . strlen($content) . " bytes)\n";
} else {
    // Try ttf
    $url2 = 'https://github.com/google/fonts/raw/main/ofl/inter/Inter%5Bopsz%2Cwght%5D.ttf';
    $content2 = @file_get_contents($url2, false, stream_context_create([
        'http' => ['header' => "User-Agent: SynthesiaFontFetcher/1.0\r\n"]
    ]));
    if ($content2 && strlen($content2) > 10000) {
        file_put_contents('d:/OSPanel/domains/synthesia/scratch/Inter.ttf', $content2);
        echo "Inter.ttf downloaded successfully! (" . strlen($content2) . " bytes)\n";
    } else {
        echo "Could not download Inter, will use Segoe UI Bold\n";
    }
}
