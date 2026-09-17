<?php
$candidates = [
    'vinyl' => 'https://upload.wikimedia.org/wikipedia/commons/e/ec/Audio-Technica_turntable_playing_coloured_vinyl.jpg',
    'vinyl_alt' => 'https://upload.wikimedia.org/wikipedia/commons/b/b6/12in-Vinyl-LP-Record-Angle.jpg',
    'cd' => 'https://upload.wikimedia.org/wikipedia/commons/f/f4/Audio_CD_with_Copy_Protection_%28Open_Jewel_Case_%E2%80%93_With_Compact_Disc%29.jpg',
    'electric' => 'https://upload.wikimedia.org/wikipedia/commons/a/ab/Gibson_Les_Paul_Classic_--_2024_--_0429.jpg',
    'acoustic' => 'https://upload.wikimedia.org/wikipedia/commons/0/0c/Martin_D-28_Acoustic_Guitar.jpg',
    'synth' => 'https://upload.wikimedia.org/wikipedia/commons/f/f1/Moog_und_ARP_Synthesizer.jpg',
    'synth_alt' => 'https://upload.wikimedia.org/wikipedia/commons/5/5a/Minimoog_1979_left_2017_right.jpg'
];

$dest = 'd:/OSPanel/domains/synthesia/scratch/candidates/';
if (!is_dir($dest)) mkdir($dest, 0777, true);

$opts = ['http' => ['header' => "User-Agent: SoundNetDev/1.0 (soundnet-test@example.org)\r\n"]];
$ctx = stream_context_create($opts);

foreach ($candidates as $k => $u) {
    $file = $dest . $k . '.jpg';
    echo "Downloading $k from $u...\n";
    $data = file_get_contents($u, false, $ctx);
    if ($data !== false) {
        file_put_contents($file, $data);
        $s = getimagesize($file);
        echo "  Saved $file (" . $s[0] . "x" . $s[1] . ", " . strlen($data) . " bytes)\n";
    } else {
        echo "  Failed to download $k\n";
    }
}
