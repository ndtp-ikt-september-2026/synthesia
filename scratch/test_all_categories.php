<?php
$baseUrl = 'http://synthesia';

$categories = [
    1 => 'Музыка / Винил / CD',
    2 => 'Виниловые пластинки',
    3 => 'Компакт-диски',
    4 => 'Цифровые релизы',
    5 => 'Музыкальные инструменты (DELETED)',
    11 => 'Гитары',
    12 => 'Акустические гитары',
    13 => 'Бас-гитары',
    14 => 'Гитары классические',
    15 => 'Электрогитары',
    16 => 'Гитарное оборудование',
    17 => 'Комбики',
    22 => 'Клавишные',
    24 => 'Синтезаторы',
    27 => 'Струнные'
];


foreach ($categories as $catId => $catName) {
    $url = $baseUrl . '/index.php?route=product/category&path=' . $catId;
    $html = @file_get_contents($url);
    if (!$html) {
        echo "Cat $catId ($catName): FAILED TO FETCH\n";
        continue;
    }
    
    $prodCount = substr_count($html, 'class="product-layout');
    $hasEmptyNotice = strpos($html, 'empty-category-notice') !== false;
    $hasSubcats = strpos($html, 'studio-subcat-pill') !== false;
    $titleMatches = [];
    preg_match('/<title>(.*?)<\/title>/', $html, $titleMatches);
    $title = $titleMatches[1] ?? 'No Title';
    
    echo sprintf(
        "Cat %-3d | %-35s | Prods: %-3d | Subcats: %-5s | EmptyNotice: %-5s | Title: %s\n",
        $catId,
        mb_substr($catName, 0, 35),
        $prodCount,
        $hasSubcats ? 'YES' : 'NO',
        $hasEmptyNotice ? 'YES' : 'NO',
        $title
    );
}
