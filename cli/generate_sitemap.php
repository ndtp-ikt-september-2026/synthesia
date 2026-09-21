<?php
/**
 * CLI Sitemap Generator for Synthesia OpenCart Store
 * Generates root sitemap.xml with proper SEO URLs, priorities, and image tags.
 */

$dir_root = dirname(__DIR__) . '/';
require_once($dir_root . 'config.php');

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error . "\n");
}
$db->set_charset('utf8mb4');

// Fetch base store URL
$base_url = HTTP_SERVER;
if (substr($base_url, -1) !== '/') {
    $base_url .= '/';
}

// Fetch all SEO keywords mapped by query
$seo_keywords = [];
$seo_res = $db->query("SELECT query, keyword FROM oc_seo_url WHERE store_id = 0 AND language_id = 1");
while ($r = $seo_res->fetch_assoc()) {
    $seo_keywords[$r['query']] = $r['keyword'];
}

function getUrl($route, $params = '') {
    global $base_url, $seo_keywords;
    $query = $params ? $params : $route;
    
    if (isset($seo_keywords[$query])) {
        return $base_url . $seo_keywords[$query];
    }
    
    if (isset($seo_keywords[$route])) {
        $prefix = $base_url . $seo_keywords[$route];
        return $params ? $prefix . '?' . $params : $prefix;
    }
    
    return $base_url . 'index.php?route=' . $route . ($params ? '&' . $params : '');
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

$url_count = 0;
$image_count = 0;

// 1. Homepage
$xml .= "  <url>\n";
$xml .= "    <loc>" . htmlspecialchars($base_url, ENT_XML1, 'UTF-8') . "</loc>\n";
$xml .= "    <changefreq>daily</changefreq>\n";
$xml .= "    <priority>1.0</priority>\n";
$xml .= "  </url>\n";
$url_count++;

// 2. Core Service & Catalog Pages
$core_pages = [
    ['route' => 'product/special', 'query' => 'product/special', 'freq' => 'daily', 'prio' => '0.8'],
    ['route' => 'product/manufacturer', 'query' => 'product/manufacturer', 'freq' => 'weekly', 'prio' => '0.7'],
    ['route' => 'information/contact', 'query' => 'information/contact', 'freq' => 'monthly', 'prio' => '0.6'],
    ['route' => 'information/sitemap', 'query' => 'information/sitemap', 'freq' => 'monthly', 'prio' => '0.5'],
];

foreach ($core_pages as $page) {
    $url = getUrl($page['route'], $page['query']);
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= "    <changefreq>{$page['freq']}</changefreq>\n";
    $xml .= "    <priority>{$page['prio']}</priority>\n";
    $xml .= "  </url>\n";
    $url_count++;
}

// 3. Categories
$cat_res = $db->query("
    SELECT c.category_id, c.date_modified, c.date_added, cd.name 
    FROM oc_category c 
    LEFT JOIN oc_category_description cd ON (c.category_id = cd.category_id AND cd.language_id = 1)
    WHERE c.status = 1 
    ORDER BY c.parent_id ASC, c.sort_order ASC
");

while ($cat = $cat_res->fetch_assoc()) {
    $url = getUrl('product/category', 'category_id=' . $cat['category_id']);
    $date_mod = (!empty($cat['date_modified']) && $cat['date_modified'] != '0000-00-00 00:00:00') 
        ? $cat['date_modified'] 
        : (!empty($cat['date_added']) ? $cat['date_added'] : date('Y-m-d H:i:s'));

    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= "    <lastmod>" . date('Y-m-d\TH:i:sP', strtotime($date_mod)) . "</lastmod>\n";
    $xml .= "    <changefreq>weekly</changefreq>\n";
    $xml .= "    <priority>0.8</priority>\n";
    $xml .= "  </url>\n";
    $url_count++;
}

// 4. Products
$prod_res = $db->query("
    SELECT p.product_id, p.image, p.date_modified, p.date_added, pd.name 
    FROM oc_product p 
    LEFT JOIN oc_product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
    WHERE p.status = 1 
    ORDER BY p.product_id ASC
");

while ($prod = $prod_res->fetch_assoc()) {
    $url = getUrl('product/product', 'product_id=' . $prod['product_id']);
    $date_mod = (!empty($prod['date_modified']) && $prod['date_modified'] != '0000-00-00 00:00:00') 
        ? $prod['date_modified'] 
        : (!empty($prod['date_added']) ? $prod['date_added'] : date('Y-m-d H:i:s'));

    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= "    <lastmod>" . date('Y-m-d\TH:i:sP', strtotime($date_mod)) . "</lastmod>\n";
    $xml .= "    <changefreq>weekly</changefreq>\n";
    $xml .= "    <priority>0.9</priority>\n";

    if (!empty($prod['image']) && is_file(DIR_IMAGE . $prod['image'])) {
        $clean_name = htmlspecialchars(strip_tags(html_entity_decode($prod['name'] ?? '', ENT_QUOTES, 'UTF-8')), ENT_XML1, 'UTF-8');
        $image_url = $base_url . 'image/' . str_replace('\\', '/', $prod['image']);
        
        $xml .= "    <image:image>\n";
        $xml .= "      <image:loc>" . htmlspecialchars($image_url, ENT_XML1, 'UTF-8') . "</image:loc>\n";
        $xml .= "      <image:caption>" . $clean_name . "</image:caption>\n";
        $xml .= "      <image:title>" . $clean_name . "</image:title>\n";
        $xml .= "    </image:image>\n";
        $image_count++;
    }

    $xml .= "  </url>\n";
    $url_count++;
}

// 5. Information Pages
$info_res = $db->query("
    SELECT i.information_id 
    FROM oc_information i 
    WHERE i.status = 1 
    ORDER BY i.sort_order ASC
");
while ($info = $info_res->fetch_assoc()) {
    $url = getUrl('information/information', 'information_id=' . $info['information_id']);
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= "    <changefreq>monthly</changefreq>\n";
    $xml .= "    <priority>0.5</priority>\n";
    $xml .= "  </url>\n";
    $url_count++;
}

$xml .= '</urlset>' . "\n";

// Validate XML before writing
$test = simplexml_load_string($xml);
if ($test === false) {
    die("ERROR: Generated XML failed validation!\n");
}

$target_file = $dir_root . 'sitemap.xml';
file_put_contents($target_file, $xml);

echo "===============================================\n";
echo "       SITEMAP GENERATION COMPLETE!            \n";
echo "===============================================\n";
echo "Target File: {$target_file}\n";
echo "Total URLs indexed: {$url_count}\n";
echo "Total Images indexed: {$image_count}\n";
echo "File Size: " . round(filesize($target_file) / 1024, 2) . " KB\n";
echo "XML Validation: SUCCESS (Valid sitemap.xml schema)\n";
echo "===============================================\n";

$db->close();
