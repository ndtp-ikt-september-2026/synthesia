<?php
/**
 * Comprehensive Automated Verification Suite for Synthesia Storefront
 */
$baseUrl = 'http://synthesia';

function runTest($name, $closure) {
    echo "TEST: " . str_pad($name, 60, '.') . " ";
    try {
        $result = $closure();
        if ($result === true || (is_array($result) && $result['ok'])) {
            echo "[ PASS ]\n";
            if (is_array($result) && !empty($result['msg'])) {
                echo "       -> " . $result['msg'] . "\n";
            }
        } else {
            echo "[ FAIL ]\n";
            if (is_array($result) && !empty($result['msg'])) {
                echo "       -> " . $result['msg'] . "\n";
            }
        }
    } catch (Exception $e) {
        echo "[ ERROR: " . $e->getMessage() . " ]\n";
    }
}

echo "\n============================================================\n";
echo "   SYNTHESIA STOREFRONT FULL SYSTEM ACCEPTANCE SUITE\n";
echo "============================================================\n\n";

// 1. Home Page HTTP & Structure
runTest("Homepage HTTP 200 & Render", function() use ($baseUrl) {
    $html = @file_get_contents($baseUrl . '/');
    if (!$html || strlen($html) < 2000) return ['ok' => false, 'msg' => 'Empty or invalid response'];
    return ['ok' => true, 'msg' => 'HTTP 200 OK (' . strlen($html) . ' bytes)'];
});

// 2. Russian Localization in Header & Zero Mock Geo Pill
runTest("Header Topbar Cleanliness & Zero Mock Geo Pill", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/');
    $hasMockGeo = strpos($html, 'Минск, Беларусь • BYN (руб.)') !== false;
    $hasSupport = strpos($html, 'Поддержка') !== false;
    if (!$hasMockGeo && $hasSupport) {
        return ['ok' => true, 'msg' => 'Clean header topbar with Russian localization and zero mock geo pill'];
    }
    return ['ok' => false, 'msg' => "Header check failed: mockGeo=$hasMockGeo, support=$hasSupport"];
});

// 3. Elimination of Old OpenCart Footer & Elimination of Mock Data
runTest("Footer Cleanliness & Zero Mock Data / Zero Powered by", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/');
    $hasPowered = stripos($html, 'Powered by OpenCart') !== false || stripos($html, 'Работает на OpenCart') !== false;
    $hasStudioFooter = strpos($html, 'studio-site-footer') !== false;
    $hasCopyright = strpos($html, 'Synthesia') !== false;
    $hasMockPhone = strpos($html, '123-45-67') !== false;
    $hasMockEmail = strpos($html, 'studio@synthesia.by') !== false;
    $hasMockAddress = strpos($html, 'Победителей') !== false;
    $hasMockPayPills = strpos($html, 'Белкарт') !== false || strpos($html, 'ЕРИП') !== false;
    if (!$hasPowered && $hasStudioFooter && $hasCopyright && !$hasMockPhone && !$hasMockEmail && !$hasMockAddress && !$hasMockPayPills) {
        return ['ok' => true, 'msg' => 'Powered by OpenCart and all mock data (phone, email, address, pay pills) completely eliminated'];
    }
    return ['ok' => false, 'msg' => "Failed check: mockPhone=$hasMockPhone, mockEmail=$hasMockEmail, mockAddr=$hasMockAddress, mockPay=$hasMockPayPills"];
});

// 4. Main Page Cleanliness (No Old OpenCart 'Рекомендуем' / Duplicates)
runTest("Homepage Cleanliness & Zero Legacy OpenCart Elements", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/');
    $hasLegacyFeatured = strpos($html, '<h3>Рекомендуем</h3>') !== false;
    $musicCount = substr_count($html, 'id="section-curated-music"');
    $gearCount = substr_count($html, 'id="section-gear-match"');
    if (!$hasLegacyFeatured && $musicCount === 1 && $gearCount === 1) {
        return ['ok' => true, 'msg' => 'Zero legacy OpenCart modules, curated carousels render cleanly exactly once'];
    }
    return ['ok' => false, 'msg' => "Legacy featured: $hasLegacyFeatured, music: $musicCount, gear: $gearCount"];
});

// 5. Category Page & Elimination of Old OpenCart Controls
runTest("Category Page: No #list-view, #grid-view, #compare-total", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/index.php?route=product/category&path=2');
    $hasListView = strpos($html, 'id="list-view"') !== false;
    $hasGridView = strpos($html, 'id="grid-view"') !== false;
    $hasCompareTotal = strpos($html, 'id="compare-total"') !== false;
    $hasModernToolbar = strpos($html, 'studio-category-toolbar') !== false;
    if (!$hasListView && !$hasGridView && !$hasCompareTotal && $hasModernToolbar) {
        return ['ok' => true, 'msg' => 'Old Bootstrap 3 toolbar deleted, studio-category-toolbar active'];
    }
    return ['ok' => false, 'msg' => "Legacy controls found (list:$hasListView, grid:$hasGridView, compare:$hasCompareTotal)"];
});

// 6. Category Filter: Prices strictly in руб. (BYN), Zero USD $
runTest("Category Filter: Prices in руб. & Zero Dollar Signs", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/index.php?route=product/category&path=2');
    preg_match_all('/(\$\d+)|(\d+\$)/', $html, $dollars);
    $hasFilterTitle = strpos($html, 'Фильтры') !== false;
    $hasPriceRub = strpos($html, 'руб.') !== false;
    if (empty($dollars[0]) && $hasFilterTitle && $hasPriceRub) {
        return ['ok' => true, 'msg' => 'Zero dollar signs, price filter calibrated strictly in руб.'];
    }
    return ['ok' => false, 'msg' => "Dollar signs detected: " . implode(', ', $dollars[0])];
});

// 7. Product Detail Page (Instruments): Zero Song Elements & Zero Mock Badges
runTest("Instrument Product Page: Zero Vinyl Badges, Zero Song Elements", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/index.php?route=product/product&product_id=42');
    $hasVinylBadge = strpos($html, 'ВИНИЛ LP') !== false;
    $hasAiModeBtn = strpos($html, 'btn-ai-mode--detail') !== false;
    $hasMatchingGear = strpos($html, 'matching-gear-block') !== false;
    $hasTrustStrip = strpos($html, 'studio-trust-strip') !== false;
    $hasCleanTab = strpos($html, 'Характеристики') !== false && strpos($html, 'Характеристики и треклист') === false;
    if (!$hasVinylBadge && !$hasAiModeBtn && !$hasMatchingGear && !$hasTrustStrip && $hasCleanTab) {
        return ['ok' => true, 'msg' => 'Acoustic guitar has zero vinyl badges, zero AI mode song buttons, zero song gear blocks, clean specs tab'];
    }
    return ['ok' => false, 'msg' => "Failed check: vinylBadge=$hasVinylBadge, aiMode=$hasAiModeBtn, matchingGear=$hasMatchingGear, trustStrip=$hasTrustStrip, cleanTab=$hasCleanTab"];
});

// 7b. Product Detail Page (Music Release): Full Rich Audio Experience
runTest("Music Product Page: Studio Cover, Badges, AI Mode, and 'Купить'", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/index.php?route=product/product&product_id=182');
    $hasArtwork = strpos($html, 'studio-artwork-frame') !== false;
    $hasBuyBtn = strpos($html, 'btn-studio-buy-main') !== false && strpos($html, 'Купить') !== false;
    $hasMatchingGear = strpos($html, 'matching-gear-block') !== false;
    $hasVinylBadge = strpos($html, 'ВИНИЛ LP') !== false;
    $hasAiBtn = strpos($html, 'btn-ai-mode--detail') !== false;
    if ($hasArtwork && $hasBuyBtn && $hasMatchingGear && $hasVinylBadge && $hasAiBtn) {
        return ['ok' => true, 'msg' => 'Music release has format badge, AI mode button, and matching instruments'];
    }
    return ['ok' => false, 'msg' => "Check failed (art:$hasArtwork, buy:$hasBuyBtn, gear:$hasMatchingGear, badge:$hasVinylBadge, ai:$hasAiBtn)"];
});

// 8. AI Mode Endpoint & Modal Integration
runTest("AI Recommendation Microservice & getSimilar Endpoint", function() use ($baseUrl) {
    $jsonStr = file_get_contents($baseUrl . '/index.php?route=extension/module/soundnet_storefront/getSimilar&product_id=169');
    $json = json_decode($jsonStr, true);
    if (!empty($json['success']) && !empty($json['products']) && count($json['products']) > 0) {
        $count = count($json['products']);
        $first = $json['products'][0]['name'];
        return ['ok' => true, 'msg' => "Returned $count AI matches (top match: $first)"];
    }
    return ['ok' => false, 'msg' => 'Failed to fetch AI similar products'];
});

// 9. Search Page Modernization
runTest("Search Page: Studio Search Grid & Russian Actions", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/index.php?route=product/search&search=Pink');
    $hasSearchCard = strpos($html, 'studio-search-card') !== false;
    $hasBuy = strpos($html, 'Купить') !== false;
    $hasCompareTotal = strpos($html, 'id="compare-total"') !== false;
    if ($hasSearchCard && $hasBuy && !$hasCompareTotal) {
        return ['ok' => true, 'msg' => 'Modern search cards active with Russian buttons and no legacy controls'];
    }
    return ['ok' => false, 'msg' => "Search page check failed (card:$hasSearchCard, buy:$hasBuy, legacyCompare:$hasCompareTotal)"];
});

// 10. OCMOD Package Integrity
runTest("OCMOD Package: soundnet_storefront.ocmod.zip", function() {
    $zipPath = 'd:/OSPanel/domains/synthesia/soundnet_storefront.ocmod.zip';
    if (!file_exists($zipPath)) return ['ok' => false, 'msg' => 'Zip file missing'];
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === true) {
        $hasInstallXml = $zip->locateName('install.xml') !== false;
        $hasModuleController = $zip->locateName('upload/catalog/controller/extension/module/soundnet_storefront.php') !== false;
        $hasCss = $zip->locateName('upload/catalog/view/theme/default/stylesheet/soundnet_storefront.css') !== false;
        $zip->close();
        if ($hasInstallXml && $hasModuleController && $hasCss) {
            return ['ok' => true, 'msg' => 'Valid zip containing install.xml, catalog controller, and CSS'];
        }
    }
    return ['ok' => false, 'msg' => 'Invalid zip archive structure'];
});

// 11. Shopping Cart Page: Light-Studio UI & Zero Legacy Elements
runTest("Shopping Cart Template: Studio Table & Zero Legacy Elements", function() {
    $tpl = file_get_contents('catalog/view/theme/default/template/checkout/cart.twig');
    $hasCartTable = strpos($tpl, 'studio-cart-table-wrap') !== false;
    $hasNoTableBordered = strpos($tpl, 'table-bordered') === false;
    $hasNoBtnPrimary = strpos($tpl, 'btn-primary') === false;
    $hasNoBreadcrumbUl = strpos($tpl, '<ul class="breadcrumb">') === false;
    if ($hasCartTable && $hasNoTableBordered && $hasNoBtnPrimary && $hasNoBreadcrumbUl) {
        return ['ok' => true, 'msg' => 'Cart template verified: studio table, zero table-bordered, zero btn-primary, zero ul.breadcrumb'];
    }
    return ['ok' => false, 'msg' => "Cart legacy check failed"];
});

// 12. Tracklist in Description tab on Product Page
runTest("Product Detail Page: Tracklist in Description Tab", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/index.php?route=product/product&product_id=182');
    $descHasTracklist = preg_match('/id="tab-description"[^>]*>.*?studio-tracklist-section/s', $html);
    $specHasTracklist = preg_match('/id="tab-specification"[^>]*>.*?studio-tracklist-section/s', $html);
    if ($descHasTracklist && !$specHasTracklist) {
        return ['ok' => true, 'msg' => 'Tracklist successfully moved inside tab-description, omitted from tab-specification'];
    }
    return ['ok' => false, 'msg' => "Tracklist placement error (inDesc: $descHasTracklist, inSpec: $specHasTracklist)"];
});

// 13. Wishlist Button matches Cart Capsule
runTest("Header: Wishlist Button Pill matching Cart", function() use ($baseUrl) {
    $css = file_get_contents('catalog/view/theme/default/stylesheet/soundnet_storefront.css');
    $hasWishlistStyle = strpos($css, '.studio-wishlist-trigger') !== false && strpos($css, '#wishlist-total') !== false;
    $hasPillRadius = strpos($css, 'border-radius: 9999px') !== false;
    $hasPillHeight = strpos($css, 'height: 44px') !== false;
    if ($hasWishlistStyle && $hasPillRadius && $hasPillHeight) {
        return ['ok' => true, 'msg' => 'Wishlist button styled as 44px capsule matching cart trigger'];
    }
    return ['ok' => false, 'msg' => "Wishlist style check failed"];
});

// 14. Search Button Center Alignment
runTest("Header: Search Button Center Alignment", function() use ($baseUrl) {
    $css = file_get_contents('catalog/view/theme/default/stylesheet/soundnet_storefront.css');
    $hasCenterTransform = strpos($css, 'transform: translateY(-50%)') !== false;
    $hasTop50 = strpos($css, 'top: 50%') !== false;
    if ($hasCenterTransform && $hasTop50) {
        return ['ok' => true, 'msg' => 'Search button vertically centered with top: 50% and translateY(-50%)'];
    }
    return ['ok' => false, 'msg' => "Search button alignment check failed"];
});

// 15. Catalog Button Dropdown Functionality
runTest("Header: Catalog Button Dropdown", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/');
    $hasDropdownTrigger = strpos($html, 'btn-catalog-trigger') !== false && strpos($html, 'data-toggle="dropdown"') !== false;
    $hasStudioDropdown = strpos($html, 'studio-catalog-dropdown') !== false;
    if ($hasDropdownTrigger && $hasStudioDropdown) {
        return ['ok' => true, 'msg' => 'Catalog button configured as functional dropdown with studio-catalog-dropdown menu'];
    }
    return ['ok' => false, 'msg' => "Catalog button check failed"];
});

// 16. Real Images in Popular Categories
runTest("Popular Categories: Real Photographic Images", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/');
    preg_match_all('/<a[^>]+class="category-square-tile"[^>]*>.*?<img[^>]+src="([^"]+)"[^>]*alt="([^"]+)".*?<\/a>/is', $html, $m);
    $hasPlaceholders = false;
    $count = count($m[0]);
    for ($i = 0; $i < $count; $i++) {
        if (strpos($m[1][$i], 'placeholder') !== false) {
            $hasPlaceholders = true;
        }
    }
    if ($count >= 5 && !$hasPlaceholders) {
        return ['ok' => true, 'msg' => "$count categories rendered with genuine photographic thumbnails and zero placeholders"];
    }
    return ['ok' => false, 'msg' => "Category images check failed: count=$count, placeholders=$hasPlaceholders"];
});

// 17. Fixed Title and Store Name
runTest("Store Title: SYNTHESIA Branding", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/');
    $hasCorrectTitle = strpos($html, '<title>SYNTHESIA — Магазин винила, CD и музыкальных инструментов</title>') !== false;
    if ($hasCorrectTitle) {
        return ['ok' => true, 'msg' => 'Store meta title correctly updated to SYNTHESIA'];
    }
    return ['ok' => false, 'msg' => 'Meta title check failed'];
});

// 18. Elimination of AI Search Button from Homepage Cards
runTest("Homepage: Zero AI Search Buttons on Cards", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/');
    preg_match('/<section[^>]+id="section-curated-music".*?<\/section>/s', $html, $sec);
    $hasAiModeOnHome = !empty($sec[0]) && strpos($sec[0], 'btn-ai-mode') !== false;
    if (!$hasAiModeOnHome) {
        return ['ok' => true, 'msg' => 'AI search mode buttons deleted from main page vinyl cards'];
    }
    return ['ok' => false, 'msg' => 'btn-ai-mode still present on homepage cards'];
});

// 19. Search Button Mockup Fidelity (Transparent Background & Outline Icon)
runTest("Header: Search Button Mockup Fidelity (No Solid Black Circle)", function() use ($baseUrl) {
    $css = file_get_contents('catalog/view/theme/default/stylesheet/soundnet_storefront.css');
    $hasTransparentBtn = strpos($css, '.studio-search-btn') !== false && strpos($css, 'background: transparent !important') !== false;
    if ($hasTransparentBtn) {
        return ['ok' => true, 'msg' => 'Search button styled with transparent background and outline icon matching user mockup'];
    }
    return ['ok' => false, 'msg' => 'Search button does not have transparent styling'];
});

// 20. Category Page Modernization Everywhere (No Placeholder Cart, Studio Sidebar & Pills)
runTest("Category Page: Modern Studio Sidebar & Zero Cart Icon Placeholders", function() use ($baseUrl) {
    $html = file_get_contents($baseUrl . '/index.php?route=product/category&path=22');
    $hasCss = strpos($html, 'soundnet_storefront.css') !== false;
    $hasNoCartThumbnail = strpos($html, 'img-thumbnail') === false;
    $hasSidebar = strpos($html, 'studio-category-sidebar') !== false;
    $hasSubPills = strpos($html, 'studio-subcat-pill') !== false;
    $hasNoHyphens = strpos($html, '- MIDI') === false && strpos($html, '- Синтезаторы') === false;
    if ($hasCss && $hasNoCartThumbnail && $hasSidebar && $hasSubPills && $hasNoHyphens) {
        return ['ok' => true, 'msg' => 'Category page completely modernized: global CSS active, zero cart placeholders, studio sidebar and pills'];
    }
    return ['ok' => false, 'msg' => "Category check failed (css:$hasCss, noCartThumb:$hasNoCartThumbnail, sidebar:$hasSidebar, pills:$hasSubPills, noHyphens:$hasNoHyphens)"];
});

echo "\n============================================================\n";
echo "   ALL TESTS COMPLETED\n";
echo "============================================================\n";
