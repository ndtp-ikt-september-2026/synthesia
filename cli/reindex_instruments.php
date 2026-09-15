<?php
/**
 * Re-index all instruments into SoundNet AI Qdrant collection
 */

$rootDir = dirname(__DIR__);
require_once($rootDir . '/config.php');

$db = new \mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error . PHP_EOL);
}
$db->set_charset('utf8mb4');

echo "=== Re-indexing Instruments into SoundNet AI ===" . PHP_EOL;

// Fetch all instrument product IDs (categories 11 to 31)
$inst_cats = '11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31';
$sql = "SELECT DISTINCT p.product_id, pd.name AS title, pd.description, m.name AS brand,
        (SELECT p2c.category_id FROM " . DB_PREFIX . "product_to_category p2c WHERE p2c.product_id = p.product_id AND p2c.category_id IN ($inst_cats) ORDER BY FIELD(p2c.category_id, 15, 12, 14, 13, 20, 19, 16, 24, 22, 11) LIMIT 1) AS primary_cat_id
        FROM " . DB_PREFIX . "product p
        JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
        JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
        LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
        WHERE p.status = '1' AND p2c.category_id IN ($inst_cats)
        ORDER BY p.product_id ASC";

$query = $db->query($sql);
echo "Found " . $query->num_rows . " instruments in catalog." . PHP_EOL;

$server_url = 'http://127.0.0.1:8000';
$endpoint = rtrim($server_url, '/') . '/internal/webhooks/product-sync';
$secret = 'soundnet_secret_key';

$success_count = 0;
$noise_keys = array('габарит', 'размер', 'вес', 'гаранти', 'страна', 'упаковк', 'питани', 'ток');

while ($row = $query->fetch_assoc()) {
    $product_id = (int)$row['product_id'];
    $title = trim($row['title']);
    $brand = trim($row['brand'] ?? '');
    $primary_cat_id = (int)$row['primary_cat_id'];

    // Get category names
    $cat_query = $db->query("SELECT cd.name FROM " . DB_PREFIX . "category_description cd JOIN " . DB_PREFIX . "product_to_category p2c ON cd.category_id = p2c.category_id WHERE p2c.product_id = '$product_id' AND cd.language_id = 1");
    $cat_names = array();
    while ($cr = $cat_query->fetch_assoc()) {
        $cat_names[] = trim($cr['name']);
    }

    // Get attributes
    $attr_query = $db->query("SELECT ad.name, pa.text FROM " . DB_PREFIX . "product_attribute pa JOIN " . DB_PREFIX . "attribute_description ad ON (pa.attribute_id = ad.attribute_id AND ad.language_id = 1) WHERE pa.product_id = '$product_id'");
    $sound_style = '';
    $vibe = '';
    $instrument_type = '';
    $pickups = '';
    $clean_specs = array();

    while ($ar = $attr_query->fetch_assoc()) {
        $aname = trim($ar['name']);
        $aval  = trim($ar['text']);
        $norm = mb_strtolower($aname, 'UTF-8');

        if (strpos($norm, 'стиль') !== false || strpos($norm, 'sound') !== false || strpos($norm, 'звучан') !== false) {
            $sound_style = $aval;
        } elseif (strpos($norm, 'вайб') !== false || strpos($norm, 'vibe') !== false) {
            $vibe = $aval;
        } elseif (strpos($norm, 'тип инструмента') !== false || strpos($norm, 'инструмент') !== false) {
            $instrument_type = $aval;
        } elseif (strpos($norm, 'звукоснимател') !== false || strpos($norm, 'pickup') !== false) {
            $pickups = $aval;
        } else {
            $is_noise = false;
            foreach ($noise_keys as $nk) {
                if (strpos($norm, $nk) !== false) {
                    $is_noise = true;
                    break;
                }
            }
            if (!$is_noise && mb_strlen($aval, 'UTF-8') < 50) {
                $clean_specs[] = $aname . ': ' . $aval;
            }
        }
    }

    // Build clean musical text_for_embedding
    $text_parts = array();
    $text_parts[] = 'Instrument: ' . $title;
    if ($instrument_type) {
        $text_parts[] = 'Type: ' . $instrument_type;
    }
    if ($sound_style) {
        $text_parts[] = 'Sound Style: ' . $sound_style;
    }
    if ($vibe) {
        $text_parts[] = 'Vibe: ' . $vibe;
    }
    if ($brand) {
        $text_parts[] = 'Brand: ' . $brand;
    }
    if ($pickups) {
        $text_parts[] = 'Pickups: ' . $pickups;
    }
    if (!empty($cat_names)) {
        $text_parts[] = 'Categories: ' . implode(', ', array_unique($cat_names));
    }
    if (!empty($clean_specs)) {
        $text_parts[] = 'Characteristics: ' . implode(', ', array_slice($clean_specs, 0, 8));
    }

    $clean_desc = trim(strip_tags(html_entity_decode($row['description'] ?? '', ENT_QUOTES, 'UTF-8')));
    if ($clean_desc) {
        $text_parts[] = 'Description: ' . mb_substr($clean_desc, 0, 200, 'UTF-8');
    }

    $text_for_embedding = implode(' | ', $text_parts);

    // Send to Python AI service
    $post_data = array(
        'product_id'         => $product_id,
        'action'             => 'upsert',
        'entity_type'        => 'instrument',
        'text_for_embedding' => $text_for_embedding,
        'category_id'        => $primary_cat_id,
        'tags'               => $sound_style ? ($sound_style . ($vibe ? ', ' . $vibe : '')) : null
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Internal-Secret: ' . $secret));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $resp = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 202) {
        $success_count++;
    } else {
        echo "Failed for ID $product_id (HTTP $http_code)" . PHP_EOL;
    }
}

echo "Successfully dispatched $success_count / " . $query->num_rows . " instruments for re-indexing." . PHP_EOL;
