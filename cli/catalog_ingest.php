<?php
/**
 * LiveStore (OpenCart 3.0.3.x Fork) High-Throughput Scraper Ingestion Utility
 *
 * Architecture: Fault-Tolerant, Idempotent Batch Catalog Ingestion
 * Code Standards: Strictly single quotes for all string literals, array keys, SQL queries, and paths.
 *
 * Exit Codes:
 *   0 = Success (all items processed or partially processed with fault tolerance)
 *   1 = Fatal Runtime Error
 *   2 = Missing Arguments / Invalid Payload Input
 */

namespace LiveStore\Cli;

// Enforce non-blocking runtime execution outside HTTP/web timeouts
if (function_exists('set_time_limit')) {
	@set_time_limit(0);
}
if (function_exists('ignore_user_abort')) {
	@ignore_user_abort(true);
}
if (function_exists('ini_set')) {
	@ini_set('memory_limit', '512M');
	@ini_set('display_errors', '0');
	@ini_set('display_startup_errors', '0');
}
error_reporting(E_ALL);

// Intercept notices and warnings from corrupting STDOUT
set_error_handler(function($severity, $message, $file, $line) {
	if (!(error_reporting() & $severity)) {
		return false;
	}
	fwrite(STDERR, '[INGEST NOTICE ' . $severity . '] ' . $message . ' in ' . $file . ' on line ' . $line . PHP_EOL);
	return true;
});

// Intercept uncaught exceptions
set_exception_handler(function(\Throwable $e) {
	$payload = array(
		'status'    => 'error',
		'processed' => 0,
		'inserted'  => 0,
		'updated'   => 0,
		'failed'    => 0,
		'message'   => 'Fatal Uncaught Exception: ' . $e->getMessage(),
		'errors'    => array(
			'type' => get_class($e),
			'code' => $e->getCode(),
			'file' => $e->getFile(),
			'line' => $e->getLine()
		)
	);
	echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
	exit(1);
});

// 1. Headless CLI Bootstrap
$cliDir = str_replace('\\', '/', __DIR__);
$rootDir = dirname($cliDir);
$adminDir = $rootDir . '/admin';

$adminConfigFile = $adminDir . '/config.php';
$rootConfigFile  = $rootDir . '/config.php';

if (is_file($adminConfigFile)) {
	require_once($adminConfigFile);
} elseif (is_file($rootConfigFile)) {
	require_once($rootConfigFile);
} else {
	fwrite(STDERR, '[ERROR] OpenCart configuration file not found.' . PHP_EOL);
	exit(1);
}

// Ensure Core Directory Constants
if (!defined('DIR_APPLICATION')) {
	define('DIR_APPLICATION', $adminDir . '/');
}
if (!defined('DIR_SYSTEM')) {
	define('DIR_SYSTEM', $rootDir . '/system/');
}
if (!defined('DIR_STORAGE')) {
	define('DIR_STORAGE', $rootDir . '/system/storage/');
}
if (!defined('DIR_CONFIG')) {
	define('DIR_CONFIG', DIR_SYSTEM . 'config/');
}
if (!defined('DIR_IMAGE')) {
	define('DIR_IMAGE', $rootDir . '/image/');
}
if (!defined('DIR_CACHE')) {
	define('DIR_CACHE', DIR_STORAGE . 'cache/');
}

// Require OpenCart Core Startup
require_once(DIR_SYSTEM . 'startup.php');

$registry = new \Registry();

$config = new \Config();
$config->load('default');
if (is_file(DIR_CONFIG . 'admin.php')) {
	$config->load('admin');
}
$registry->set('config', $config);

$log = new \Log($config->get('error_filename'));
$registry->set('log', $log);

$event = new \Event($registry);
$registry->set('event', $event);

// Register Config Action Events
if ($config->has('action_event')) {
	foreach ($config->get('action_event') as $key => $value) {
		foreach ($value as $priority => $action) {
			$event->register($key, new \Action($action), $priority);
		}
	}
}

$loader = new \Loader($registry);
$registry->set('load', $loader);

$request = new \Request();
if (!isset($request->server['REMOTE_ADDR'])) {
	$request->server['REMOTE_ADDR'] = '127.0.0.1';
}
$registry->set('request', $request);

$response = new \Response();
$registry->set('response', $response);

$db = new \DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);
$db->query('SET time_zone = \'' . $db->escape(date('P')) . '\'');

// Register DB Events (including admin/model hooks for AI Vector sync)
$eventRows = $db->query('SELECT * FROM `' . DB_PREFIX . 'event` WHERE `status` = \'1\' ORDER BY `sort_order` ASC');
foreach ($eventRows->rows as $evt) {
	$trigger = $evt['trigger'];
	$action = new \Action($evt['action']);
	$priority = (int)$evt['sort_order'];

	if (substr($trigger, 0, 6) === 'admin/') {
		$event->register(substr($trigger, 6), $action, $priority);
		$event->register($trigger, $action, $priority);
	} else {
		$event->register($trigger, $action, $priority);
	}
}

$session = new \Session($config->get('session_engine'), $registry);
$registry->set('session', $session);

$cache = new \Cache($config->get('cache_engine'), $config->get('cache_expire'));
$registry->set('cache', $cache);

$url = new \Url($config->get('site_url'), $config->get('site_ssl'));
$registry->set('url', $url);

$registry->set('document', new \Document());

// Setup Administrative Context & User Proxy (Always Authorized)
$cliUser = new class(1, 'admin') {
	private $id;
	private $name;
	public function __construct($id, $name) {
		$this->id = $id;
		$this->name = $name;
	}
	public function isLogged() { return $this->id; }
	public function getId() { return $this->id; }
	public function getUserName() { return $this->name; }
	public function getGroupId() { return 1; }
	public function hasPermission($key, $value) { return true; }
};
$registry->set('user', $cliUser);

// Load Installed Store Languages
$languages = array();
$langRows = $db->query('SELECT `language_id`, `code`, `name` FROM `' . DB_PREFIX . 'language` WHERE `status` = \'1\' ORDER BY `sort_order` ASC');
foreach ($langRows->rows as $l) {
	$languages[(int)$l['language_id']] = array(
		'code' => strtolower($l['code']),
		'name' => $l['name']
	);
}
if (empty($languages)) {
	$languages[1] = array('code' => 'ru-ru', 'name' => 'Russian');
}

$adminLangCode = $config->get('config_admin_language') ? $config->get('config_admin_language') : 'ru-ru';
$language = new \Language($adminLangCode);
$language->load($adminLangCode);
$registry->set('language', $language);

// Dynamically Load Native Product Model
$loader->model('catalog/product');
$modelProduct = $registry->get('model_catalog_product');

// 2. Parse CLI Options
$format = 'json';
$dryRun = false;
$skipImages = false;
$filePath = null;
$readStdin = false;
$showHelp = false;

foreach ($argv as $arg) {
	if (strpos($arg, '--format=') === 0) {
		$format = substr($arg, 9);
	} elseif ($arg === '--format=text') {
		$format = 'text';
	} elseif ($arg === '--dry-run') {
		$dryRun = true;
	} elseif ($arg === '--skip-images') {
		$skipImages = true;
	} elseif (strpos($arg, '--file=') === 0) {
		$filePath = substr($arg, 7);
	} elseif (strpos($arg, '-f=') === 0) {
		$filePath = substr($arg, 3);
	} elseif ($arg === '--stdin') {
		$readStdin = true;
	} elseif ($arg === '--help' || $arg === '-h') {
		$showHelp = true;
	}
}

if ($showHelp) {
	if ($format === 'json') {
		$helpPayload = array(
			'utility'     => 'LiveStore Catalog Ingestion CLI',
			'usage'       => 'php cli/catalog_ingest.php [options]',
			'options'     => array(
				'--file=<path>, -f=<path>' => 'Path to JSON payload file containing scraped entities.',
				'--stdin'                  => 'Read JSON payload directly from STDIN stream pipe.',
				'--format=json|text'       => 'Output format (default: json).',
				'--dry-run'                => 'Validate and simulate ingestion without database mutations.',
				'--skip-images'            => 'Bypass downloading and associating external images.',
				'--help, -h'               => 'Display this command-line manual.'
			),
			'contract'    => array(
				'model'             => 'string (required, unique catalog identifier / SKU)',
				'name'              => 'string (product title)',
				'price'             => 'float (store unit price)',
				'quantity'          => 'int (available stock)',
				'category_ids'      => 'array<int> (mapped category IDs)',
				'image_url'         => 'string (remote primary image URL)',
				'additional_images' => 'array<string> (remote secondary image URLs)',
				'description'       => 'string (HTML or markdown product description)',
				'attributes'        => 'object<string, string> (attribute name to value map)',
				'tracklist'         => 'array<object|string> (album tracklist: objects with track_num, title, duration, preview_file or strings like "01. Title (3:45)")'
			)
		);
		echo json_encode($helpPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
	} else {
		echo 'LiveStore Catalog Ingestion CLI Utility' . PHP_EOL;
		echo 'Usage: php cli/catalog_ingest.php [options]' . PHP_EOL . PHP_EOL;
		echo 'Options:' . PHP_EOL;
		echo '  --file=<path>, -f=<path>  Path to JSON payload file' . PHP_EOL;
		echo '  --stdin                   Stream payload from STDIN pipe' . PHP_EOL;
		echo '  --format=json|text        Output format (default: json)' . PHP_EOL;
		echo '  --dry-run                 Simulate operations without database commits' . PHP_EOL;
		echo '  --skip-images             Skip downloading external images' . PHP_EOL;
		echo '  --help, -h                Show this help screen' . PHP_EOL;
	}
	exit(0);
}

// 3. Ingest Payload (File or STDIN)
$rawPayload = '';

if ($readStdin) {
	$stdinHandle = fopen('php://stdin', 'r');
	if ($stdinHandle) {
		while (!feof($stdinHandle)) {
			$rawPayload .= fread($stdinHandle, 8192);
		}
		fclose($stdinHandle);
	}
} elseif ($filePath !== null) {
	if (!is_file($filePath) || !is_readable($filePath)) {
		$errPayload = array(
			'status'    => 'error',
			'processed' => 0,
			'inserted'  => 0,
			'updated'   => 0,
			'failed'    => 0,
			'message'   => 'Payload file not found or not readable: ' . $filePath,
			'errors'    => array('Invalid file path')
		);
		echo json_encode($errPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
		exit(2);
	}
	$rawPayload = file_get_contents($filePath);
} else {
	// If piped without explicit --file flag
	if (function_exists('stream_isatty')) {
		if (!@stream_isatty(STDIN)) {
			$rawPayload = (string)@stream_get_contents(STDIN);
		}
	} elseif (function_exists('posix_isatty')) {
		if (!@posix_isatty(STDIN)) {
			$rawPayload = (string)@stream_get_contents(STDIN);
		}
	}
}

// Strip UTF-8 Byte Order Mark (BOM) if present from Windows/PowerShell streams
$utf8Bom = chr(239) . chr(187) . chr(191);
if (substr($rawPayload, 0, 3) === $utf8Bom) {
	$rawPayload = substr($rawPayload, 3);
}
$rawPayload = trim($rawPayload);

if ($rawPayload === '') {
	$errPayload = array(
		'status'    => 'error',
		'processed' => 0,
		'inserted'  => 0,
		'updated'   => 0,
		'failed'    => 0,
		'message'   => 'No input payload provided. Supply payload via --file=<path> or pipe via --stdin.',
		'errors'    => array('Empty input payload')
	);
	echo json_encode($errPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
	exit(2);
}

$decodedPayload = json_decode($rawPayload, true);
if (json_last_error() !== JSON_ERROR_NONE) {
	$errPayload = array(
		'status'    => 'error',
		'processed' => 0,
		'inserted'  => 0,
		'updated'   => 0,
		'failed'    => 0,
		'message'   => 'Invalid JSON payload: ' . json_last_error_msg(),
		'errors'    => array('JSON parse error')
	);
	echo json_encode($errPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
	exit(2);
}

// Support single object or array of objects
$items = is_array($decodedPayload) && isset($decodedPayload['model']) ? array($decodedPayload) : $decodedPayload;
if (!is_array($items)) {
	$errPayload = array(
		'status'    => 'error',
		'processed' => 0,
		'inserted'  => 0,
		'updated'   => 0,
		'failed'    => 0,
		'message'   => 'Payload must be an array of product entities or a single product entity.',
		'errors'    => array('Expected array of objects')
	);
	echo json_encode($errPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
	exit(2);
}

// 4. Attribute Resolver & In-Memory Dictionary
$attributeNameMap = array();
$attrQuery = $db->query('SELECT a.`attribute_id`, a.`attribute_group_id`, ad.`name` FROM `' . DB_PREFIX . 'attribute` a LEFT JOIN `' . DB_PREFIX . 'attribute_description` ad ON (a.`attribute_id` = ad.`attribute_id`)');
foreach ($attrQuery->rows as $ar) {
	$normName = mb_strtolower(trim($ar['name']), 'UTF-8');
	$attributeNameMap[$normName] = (int)$ar['attribute_id'];
}

// Fallback / default attribute group ID
$defaultGroupId = 1;
$groupQuery = $db->query('SELECT `attribute_group_id` FROM `' . DB_PREFIX . 'attribute_group` ORDER BY `sort_order` ASC LIMIT 1');
if ($groupQuery->num_rows) {
	$defaultGroupId = (int)$groupQuery->row['attribute_group_id'];
}

$resolveAttributeId = function($attrName) use (&$attributeNameMap, $db, $languages, $defaultGroupId) {
	$normName = mb_strtolower(trim($attrName), 'UTF-8');
	if (isset($attributeNameMap[$normName])) {
		return $attributeNameMap[$normName];
	}

	// Dynamically create missing attribute for fault tolerance
	$db->query('INSERT INTO `' . DB_PREFIX . 'attribute` SET `attribute_group_id` = \'' . (int)$defaultGroupId . '\', `sort_order` = \'0\'');
	$newAttrId = (int)$db->getLastId();

	foreach ($languages as $langId => $langInfo) {
		$db->query('INSERT INTO `' . DB_PREFIX . 'attribute_description` SET `attribute_id` = \'' . (int)$newAttrId . '\', `language_id` = \'' . (int)$langId . '\', `name` = \'' . $db->escape($attrName) . '\'');
	}

	$attributeNameMap[$normName] = $newAttrId;
	return $newAttrId;
};

// 5. Remote Image Pipeline (cURL)
$downloadImage = function($url, $modelIdentifier) use ($skipImages) {
	if ($skipImages || empty($url) || !is_string($url)) {
		return null;
	}

	$url = trim($url);

	// Already local relative path
	if (strpos($url, 'catalog/') === 0 && is_file(DIR_IMAGE . $url)) {
		return $url;
	}

	// Only process remote URLs
	if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
		return null;
	}

	$cleanModel = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $modelIdentifier);
	$targetSubdir = 'catalog/products/' . $cleanModel . '/';
	$targetDir = DIR_IMAGE . $targetSubdir;

	if (!is_dir($targetDir)) {
		@mkdir($targetDir, 0777, true);
	}

	$urlPath = parse_url($url, PHP_URL_PATH);
	$ext = pathinfo($urlPath, PATHINFO_EXTENSION);
	if (empty($ext) || strlen($ext) > 5) {
		$ext = 'jpg';
	}
	$ext = strtolower($ext);

	$filename = 'img_' . substr(md5($url), 0, 12) . '.' . $ext;
	$destFile = $targetDir . $filename;
	$relPath  = $targetSubdir . $filename;

	// Deduplication: reuse already downloaded image
	if (is_file($destFile) && filesize($destFile) > 0) {
		return $relPath;
	}

	// cURL Download with connect and execution timeouts
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($ch, CURLOPT_TIMEOUT, 6);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'LiveStore-CatalogIngest/1.0');

	$data = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	curl_close($ch);

	if ($data !== false && $httpCode === 200 && strlen($data) > 0) {
		@file_put_contents($destFile, $data);
		return $relPath;
	}

	fwrite(STDERR, '[IMAGE NOTICE] Could not download \'' . $url . '\' (HTTP ' . $httpCode . ' ' . $curlError . '). Proceeding without image.' . PHP_EOL);
	return null;
};

// 6. Batch Ingestion Engine
$processed = 0;
$inserted = 0;
$updated = 0;
$failed = 0;
$tracksIngested = 0;
$itemResults = array();
$itemErrors = array();

foreach ($items as $idx => $item) {
	$processed++;

	$model = isset($item['model']) ? trim((string)$item['model']) : '';
	$name  = isset($item['name']) ? trim((string)$item['name']) : '';

	if ($model === '') {
		$failed++;
		$itemErrors[] = array(
			'index'   => $idx,
			'model'   => '',
			'message' => 'Missing required identifier: \'model\'.'
		);
		continue;
	}

	try {
		$price       = isset($item['price']) ? (float)$item['price'] : 0.00;
		$quantity    = isset($item['quantity']) ? (int)$item['quantity'] : 0;
		$description = isset($item['description']) ? (string)$item['description'] : '';
		$categoryIds = isset($item['category_ids']) && is_array($item['category_ids']) ? array_map('intval', $item['category_ids']) : array();
		$itemType    = isset($item['type']) ? strtolower(trim((string)$item['type'])) : '';

		// Extract or generate tag characteristics
		$itemTags = '';
		if (isset($item['tags'])) {
			$itemTags = is_array($item['tags']) ? implode(', ', $item['tags']) : trim((string)$item['tags']);
		} elseif (isset($item['tag'])) {
			$itemTags = trim((string)$item['tag']);
		}
		if ($itemTags === '' && isset($item['attributes']) && is_array($item['attributes'])) {
			$tagParts = array();
			foreach ($item['attributes'] as $attrK => $attrV) {
				if (is_scalar($attrV) && strlen(trim((string)$attrV)) > 0) {
					$tagParts[] = trim((string)$attrV);
				}
			}
			if (!empty($tagParts)) {
				$itemTags = implode(', ', $tagParts);
			}
		}

		// Download primary and additional images
		$mainImage = null;
		if (isset($item['image_url'])) {
			$mainImage = $downloadImage($item['image_url'], $model);
		}

		$additionalImages = array();
		if (isset($item['additional_images']) && is_array($item['additional_images'])) {
			$sortOrder = 0;
			foreach ($item['additional_images'] as $addUrl) {
				$savedAddImg = $downloadImage($addUrl, $model);
				if ($savedAddImg) {
					$additionalImages[] = array(
						'image'      => $savedAddImg,
						'sort_order' => $sortOrder++
					);
				}
			}
		}

		// Extract and normalize tracklist
		$rawTracklist = null;
		if (isset($item['tracklist']) && is_array($item['tracklist'])) {
			$rawTracklist = $item['tracklist'];
		} elseif (isset($item['tracks']) && is_array($item['tracks'])) {
			$rawTracklist = $item['tracks'];
		}

		$parsedTracks = array();
		if ($rawTracklist !== null) {
			foreach ($rawTracklist as $tIdx => $trackItem) {
				if (is_string($trackItem)) {
					$trackStr = trim($trackItem);
					$trackNum = $tIdx + 1;
					$duration = '0:00';
					$title = $trackStr;
					$preview = '';

					if (preg_match('/^(\d+)[\.\s\-]+(.*)/', $trackStr, $tm)) {
						$trackNum = (int)$tm[1];
						$title = trim($tm[2]);
					}
					if (preg_match('/^(.*?)\s*[\(\[]?(\d+:\d{2})[\)\]]?$/', $title, $dm)) {
						$title = trim($dm[1], " \t\n\r\0\x0B-");
						$duration = $dm[2];
					}

					$parsedTracks[] = array(
						'track_num'    => $trackNum,
						'title'        => $title,
						'duration'     => $duration,
						'preview_file' => $preview,
						'status'       => 1,
						'sort_order'   => $tIdx
					);
				} elseif (is_array($trackItem)) {
					$trackNum = isset($trackItem['track_num']) ? (int)$trackItem['track_num'] : ($tIdx + 1);
					$title = isset($trackItem['title']) ? trim((string)$trackItem['title']) : ('Track ' . $trackNum);
					$duration = isset($trackItem['duration']) ? trim((string)$trackItem['duration']) : '0:00';
					$preview = isset($trackItem['preview_file']) ? trim((string)$trackItem['preview_file']) : (isset($trackItem['preview']) ? trim((string)$trackItem['preview']) : (isset($trackItem['url']) ? trim((string)$trackItem['url']) : ''));
					$status = isset($trackItem['status']) ? (int)$trackItem['status'] : 1;
					$sortOrder = isset($trackItem['sort_order']) ? (int)$trackItem['sort_order'] : $tIdx;

					$parsedTracks[] = array(
						'track_num'    => $trackNum,
						'title'        => $title,
						'duration'     => $duration,
						'preview_file' => $preview,
						'status'       => $status,
						'sort_order'   => $sortOrder
					);
				}
			}
		}

		// Map Scraper Attributes
		$productAttributes = array();
		if (isset($item['attributes']) && is_array($item['attributes'])) {
			foreach ($item['attributes'] as $attrKey => $attrVal) {
				$attrId = $resolveAttributeId($attrKey);
				$attrDesc = array();
				foreach ($languages as $langId => $langInfo) {
					$attrDesc[$langId] = array('text' => (string)$attrVal);
				}
				$productAttributes[] = array(
					'attribute_id'                  => $attrId,
					'product_attribute_description' => $attrDesc
				);
			}
		}

		// Check if Product Exists
		$checkSql = 'SELECT * FROM `' . DB_PREFIX . 'product` WHERE `model` = \'' . $db->escape($model) . '\' LIMIT 1';
		$checkQuery = $db->query($checkSql);

		if ($checkQuery->num_rows > 0) {
			// --- UPDATE EXISTING PRODUCT ---
			$productId = (int)$checkQuery->row['product_id'];
			$existing  = $checkQuery->row;
			$existDescriptions = $modelProduct->getProductDescriptions($productId);
			$existCategories   = $modelProduct->getProductCategories($productId);
			$existImages       = $modelProduct->getProductImages($productId);
			$existStores       = $modelProduct->getProductStores($productId);
			$existDiscounts    = $modelProduct->getProductDiscounts($productId);
			$existSpecials     = $modelProduct->getProductSpecials($productId);
			$existDownloads    = $modelProduct->getProductDownloads($productId);
			$existFilters      = $modelProduct->getProductFilters($productId);
			$existRelated      = $modelProduct->getProductRelated($productId);
			$existRewards      = $modelProduct->getProductRewards($productId);
			$existSeoUrls      = $modelProduct->getProductSeoUrls($productId);
			$existLayouts      = $modelProduct->getProductLayouts($productId);

			// Merge descriptions
			foreach ($languages as $langId => $langInfo) {
				if (!isset($existDescriptions[$langId])) {
					$existDescriptions[$langId] = array(
						'name'             => $name !== '' ? $name : $model,
						'description'      => $description,
						'tag'              => $itemTags,
						'meta_title'       => $name !== '' ? $name : $model,
						'meta_h1'          => $name !== '' ? $name : $model,
						'meta_description' => '',
						'meta_keyword'     => ''
					);
				} else {
					if ($name !== '') {
						$existDescriptions[$langId]['name'] = $name;
						if (empty($existDescriptions[$langId]['meta_title'])) {
							$existDescriptions[$langId]['meta_title'] = $name;
						}
					}
					if ($description !== '') {
						$existDescriptions[$langId]['description'] = $description;
					}
					if ($itemTags !== '') {
						$existDescriptions[$langId]['tag'] = $itemTags;
					}
				}
			}

			$mergedCategories = !empty($categoryIds) ? $categoryIds : $existCategories;
			$mergedMainCategory = !empty($mergedCategories) ? $mergedCategories[0] : 0;
			$mergedImage = ($mainImage !== null) ? $mainImage : $existing['image'];
			$mergedImages = !empty($additionalImages) ? $additionalImages : $existImages;

			$mergedData = array(
				'type'               => $itemType,
				'model'              => $model,
				'sku'                => $existing['sku'],
				'upc'                => $existing['upc'],
				'ean'                => $existing['ean'],
				'jan'                => $existing['jan'],
				'isbn'               => $existing['isbn'],
				'mpn'                => $existing['mpn'],
				'location'           => $existing['location'],
				'certification_link' => isset($existing['certification_link']) ? $existing['certification_link'] : '',
				'quantity'           => $quantity,
				'minimum'            => (int)$existing['minimum'],
				'subtract'           => (int)$existing['subtract'],
				'stock_status_id'    => (int)$existing['stock_status_id'],
				'date_available'     => $existing['date_available'],
				'manufacturer_id'    => (int)$existing['manufacturer_id'],
				'shipping'           => (int)$existing['shipping'],
				'price'              => $price,
				'points'             => (int)$existing['points'],
				'weight'             => (float)$existing['weight'],
				'weight_class_id'    => (int)$existing['weight_class_id'],
				'length'             => (float)$existing['length'],
				'width'              => (float)$existing['width'],
				'height'             => (float)$existing['height'],
				'length_class_id'    => (int)$existing['length_class_id'],
				'status'             => 1,
				'noindex'            => isset($existing['noindex']) ? (int)$existing['noindex'] : 0,
				'tax_class_id'       => (int)$existing['tax_class_id'],
				'sort_order'         => (int)$existing['sort_order'],
				'image'              => $mergedImage,
				'product_description'=> $existDescriptions,
				'product_category'   => $mergedCategories,
				'main_category_id'   => $mergedMainCategory,
				'product_store'      => !empty($existStores) ? $existStores : array(0),
				'product_attribute'  => !empty($productAttributes) ? $productAttributes : $modelProduct->getProductAttributes($productId),
				'product_option'     => array(),
				'product_discount'   => $existDiscounts,
				'product_special'    => $existSpecials,
				'product_image'      => $mergedImages,
				'product_download'   => $existDownloads,
				'product_filter'     => $existFilters,
				'product_related'    => $existRelated,
				'product_reward'     => $existRewards,
				'product_seo_url'    => $existSeoUrls,
				'product_layout'     => $existLayouts,
				'product_track'      => !empty($parsedTracks) ? $parsedTracks : array()
			);

			if (!$dryRun) {
				$modelProduct->editProduct($productId, $mergedData);

				if ($rawTracklist !== null) {
					$db->query('DELETE FROM `' . DB_PREFIX . 'product_tracklist` WHERE `product_id` = \'' . (int)$productId . '\'');
					foreach ($parsedTracks as $pt) {
						$db->query('INSERT INTO `' . DB_PREFIX . 'product_tracklist` SET
							`product_id` = \'' . (int)$productId . '\',
							`track_num` = \'' . (int)$pt['track_num'] . '\',
							`title` = \'' . $db->escape($pt['title']) . '\',
							`duration` = \'' . $db->escape($pt['duration']) . '\',
							`preview_file` = \'' . $db->escape($pt['preview_file']) . '\',
							`status` = \'' . (int)$pt['status'] . '\',
							`sort_order` = \'' . (int)$pt['sort_order'] . '\'');
					}
				}
			}

			$updated++;
			$tracksIngested += count($parsedTracks);
			$itemResults[] = array(
				'model'       => $model,
				'action'      => 'updated',
				'product_id'  => $productId,
				'name'        => $name,
				'price'       => $price,
				'quantity'    => $quantity,
				'track_count' => count($parsedTracks)
			);
		} else {
			// --- INSERT NEW PRODUCT ---
			$productDescriptions = array();
			foreach ($languages as $langId => $langInfo) {
				$productDescriptions[$langId] = array(
					'name'             => $name !== '' ? $name : $model,
					'description'      => $description,
					'tag'              => $itemTags,
					'meta_title'       => $name !== '' ? $name : $model,
					'meta_h1'          => $name !== '' ? $name : $model,
					'meta_description' => '',
					'meta_keyword'     => ''
				);
			}

			$mainCategory = !empty($categoryIds) ? $categoryIds[0] : 0;

			$preparedData = array(
				'type'               => $itemType,
				'model'              => $model,
				'sku'                => '',
				'upc'                => '',
				'ean'                => '',
				'jan'                => '',
				'isbn'               => '',
				'mpn'                => '',
				'location'           => '',
				'certification_link' => '',
				'quantity'           => $quantity,
				'minimum'            => 1,
				'subtract'           => 1,
				'stock_status_id'    => (int)$config->get('config_stock_status_id'),
				'date_available'     => date('Y-m-d'),
				'manufacturer_id'    => 0,
				'shipping'           => 1,
				'price'              => $price,
				'points'             => 0,
				'weight'             => 0.0,
				'weight_class_id'    => (int)$config->get('config_weight_class_id'),
				'length'             => 0.0,
				'width'              => 0.0,
				'height'             => 0.0,
				'length_class_id'    => (int)$config->get('config_length_class_id'),
				'status'             => 1,
				'noindex'            => 0,
				'tax_class_id'       => 0,
				'sort_order'         => 0,
				'image'              => $mainImage ? $mainImage : '',
				'product_description'=> $productDescriptions,
				'product_store'      => array(0),
				'product_category'   => $categoryIds,
				'main_category_id'   => $mainCategory,
				'product_attribute'  => $productAttributes,
				'product_image'      => $additionalImages,
				'product_track'      => !empty($parsedTracks) ? $parsedTracks : array()
			);

			$newId = 0;
			if (!$dryRun) {
				$newId = (int)$modelProduct->addProduct($preparedData);

				if ($rawTracklist !== null && $newId > 0) {
					$db->query('DELETE FROM `' . DB_PREFIX . 'product_tracklist` WHERE `product_id` = \'' . (int)$newId . '\'');
					foreach ($parsedTracks as $pt) {
						$db->query('INSERT INTO `' . DB_PREFIX . 'product_tracklist` SET
							`product_id` = \'' . (int)$newId . '\',
							`track_num` = \'' . (int)$pt['track_num'] . '\',
							`title` = \'' . $db->escape($pt['title']) . '\',
							`duration` = \'' . $db->escape($pt['duration']) . '\',
							`preview_file` = \'' . $db->escape($pt['preview_file']) . '\',
							`status` = \'' . (int)$pt['status'] . '\',
							`sort_order` = \'' . (int)$pt['sort_order'] . '\'');
					}
				}
			}

			$inserted++;
			$tracksIngested += count($parsedTracks);
			$itemResults[] = array(
				'model'       => $model,
				'action'      => 'inserted',
				'product_id'  => $newId,
				'name'        => $name,
				'price'       => $price,
				'quantity'    => $quantity,
				'track_count' => count($parsedTracks)
			);
		}
	} catch (\Throwable $itemException) {
		$failed++;
		$itemErrors[] = array(
			'index'   => $idx,
			'model'   => $model,
			'message' => $itemException->getMessage(),
			'file'    => $itemException->getFile(),
			'line'    => $itemException->getLine()
		);
		fwrite(STDERR, '[INGEST ERROR] Failed processing model \'' . $model . '\': ' . $itemException->getMessage() . PHP_EOL);
	}
}

// 7. Cache Invalidation
if (!$dryRun && ($inserted > 0 || $updated > 0)) {
	$cache->delete('product');
	$cache->delete('category');
}

// 8. Deterministic Output Response
$overallStatus = ($failed === 0) ? 'success' : (($inserted > 0 || $updated > 0) ? 'partial' : 'error');

$response = array(
	'status'          => $overallStatus,
	'dry_run'         => $dryRun,
	'processed'       => $processed,
	'inserted'        => $inserted,
	'updated'         => $updated,
	'failed'          => $failed,
	'tracks_ingested' => $tracksIngested,
	'items'           => $itemResults,
	'errors'          => $itemErrors,
	'message'         => 'Catalog ingestion completed: ' . $inserted . ' inserted, ' . $updated . ' updated, ' . $tracksIngested . ' tracks ingested, ' . $failed . ' failed.'
);

if ($format === 'text') {
	echo ($dryRun ? '[DRY-RUN] ' : '[INGEST COMPLETE] ') . $response['message'] . PHP_EOL;
	echo 'Total Processed: ' . $processed . PHP_EOL;
	echo 'Inserted:        ' . $inserted . PHP_EOL;
	echo 'Updated:         ' . $updated . PHP_EOL;
	echo 'Tracks Ingested: ' . $tracksIngested . PHP_EOL;
	echo 'Failed:          ' . $failed . PHP_EOL;
	if (!empty($itemErrors)) {
		echo 'Errors encountered:' . PHP_EOL;
		foreach ($itemErrors as $ie) {
			echo '  - Model \'' . $ie['model'] . '\': ' . $ie['message'] . PHP_EOL;
		}
	}
} else {
	echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

exit($overallStatus === 'error' ? 1 : 0);
