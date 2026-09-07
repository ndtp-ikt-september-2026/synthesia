<?php
/**
 * LiveStore (OpenCart 3.0.3.x Fork) Belarusian Ruble (BYN) Currency & Icon Registration Utility
 *
 * Architecture: Standalone CLI Utility
 * Code Standards: Strictly single quotes for all string literals, array keys, SQL queries, and paths.
 *
 * Exit Codes:
 *   0 = Success
 *   1 = Failure / Missing file / Error
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
	fwrite(STDERR, '[NOTICE/WARNING ' . $severity . '] ' . $message . ' in ' . $file . ' on line ' . $line . PHP_EOL);
	return true;
});

// Intercept uncaught exceptions
set_exception_handler(function(\Throwable $e) {
	$payload = array(
		'status'  => 'error',
		'message' => 'Uncaught Exception: ' . $e->getMessage(),
		'errors'  => array(
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

$db = new \DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);
$db->query('SET time_zone = \'' . $db->escape(date('P')) . '\'');

$cache = new \Cache($config->get('cache_engine'), $config->get('cache_expire'));
$registry->set('cache', $cache);

// 2. Parse CLI Options
$options = array();
$format = 'json';
$rate = 1.00000000;
$sourceImagePath = null;

foreach ($argv as $arg) {
	if (strpos($arg, '--format=') === 0) {
		$format = substr($arg, 9);
	} elseif ($arg === '--format=text') {
		$format = 'text';
	} elseif (strpos($arg, '--rate=') === 0) {
		$rate = (float)substr($arg, 7);
	} elseif (strpos($arg, '--image=') === 0) {
		$sourceImagePath = substr($arg, 8);
	} elseif (strpos($arg, '--source-image=') === 0) {
		$sourceImagePath = substr($arg, 15);
	}
}

// 3. Locate Source Image
$candidatePaths = array();
if ($sourceImagePath !== null) {
	$candidatePaths[] = $sourceImagePath;
	$candidatePaths[] = $rootDir . '/' . ltrim($sourceImagePath, '/\\');
}

// Common default fallback paths
$candidatePaths[] = DIR_IMAGE . 'catalog/source_currency.png';
$candidatePaths[] = DIR_IMAGE . 'catalog/currency/source_byn.png';
$candidatePaths[] = $rootDir . '/upload/image/catalog/source_currency.png';
$candidatePaths[] = $rootDir . '/source_currency.png';

$resolvedSourceImage = null;
foreach ($candidatePaths as $path) {
	if ($path && is_file($path) && is_readable($path)) {
		$resolvedSourceImage = str_replace('\\', '/', realpath($path));
		break;
	}
}

// Fallback search: scan DIR_IMAGE . 'catalog/' for newly added currency images
if ($resolvedSourceImage === null && is_dir(DIR_IMAGE . 'catalog/')) {
	$scanned = glob(DIR_IMAGE . 'catalog/*currency*.*');
	if (!empty($scanned)) {
		foreach ($scanned as $sf) {
			if (is_file($sf)) {
				$resolvedSourceImage = str_replace('\\', '/', realpath($sf));
				break;
			}
		}
	}
}

if ($resolvedSourceImage === null) {
	$payload = array(
		'status'  => 'error',
		'message' => 'Source currency image not found. Provide path via --image=<path> or place file in image/catalog/source_currency.png.',
		'errors'  => array('Missing source image file')
	);
	echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
	exit(1);
}

// 4. Image Processing Pipeline
$imageInfo = @getimagesize($resolvedSourceImage);
if (!$imageInfo) {
	$payload = array(
		'status'  => 'error',
		'message' => 'Invalid image format or unreadable file: ' . $resolvedSourceImage,
		'errors'  => array('getimagesize failed on source image')
	);
	echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
	exit(1);
}

$srcWidth  = $imageInfo[0];
$srcHeight = $imageInfo[1];
$mimeType  = $imageInfo['mime'];

$sourceImg = null;
switch ($mimeType) {
	case 'image/png':
		$sourceImg = @imagecreatefrompng($resolvedSourceImage);
		break;
	case 'image/jpeg':
	case 'image/jpg':
		$sourceImg = @imagecreatefromjpeg($resolvedSourceImage);
		break;
	case 'image/webp':
		if (function_exists('imagecreatefromwebp')) {
			$sourceImg = @imagecreatefromwebp($resolvedSourceImage);
		}
		break;
	case 'image/gif':
		$sourceImg = @imagecreatefromgif($resolvedSourceImage);
		break;
}

if (!$sourceImg) {
	$payload = array(
		'status'  => 'error',
		'message' => 'Failed to load source image resource with GD: ' . $resolvedSourceImage,
		'errors'  => array('imagecreatefrom* failed for mime ' . $mimeType)
	);
	echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
	exit(1);
}

// Prepare Destination Directories
$targetDirs = array(
	DIR_IMAGE . 'catalog/currency/',
	$rootDir . '/upload/image/catalog/currency/'
);

foreach ($targetDirs as $td) {
	if (!is_dir($td)) {
		@mkdir($td, 0777, true);
	}
}

// Helper function to render a square icon with transparent background and centered aspect ratio
$renderSquareIcon = function($sourceImg, $srcWidth, $srcHeight, $canvasSize, $padding) {
	$canvas = imagecreatetruecolor($canvasSize, $canvasSize);
	imagealphablending($canvas, false);
	imagesavealpha($canvas, true);

	$transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
	imagefilledrectangle($canvas, 0, 0, $canvasSize, $canvasSize, $transparent);

	$usableSize = $canvasSize - (2 * $padding);
	$scale = min($usableSize / $srcWidth, $usableSize / $srcHeight);

	$dstWidth  = max(1, (int)round($srcWidth * $scale));
	$dstHeight = max(1, (int)round($srcHeight * $scale));

	$dstX = (int)round(($canvasSize - $dstWidth) / 2);
	$dstY = (int)round(($canvasSize - $dstHeight) / 2);

	imagealphablending($canvas, true);
	imagecopyresampled($canvas, $sourceImg, $dstX, $dstY, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
	imagealphablending($canvas, false);
	imagesavealpha($canvas, true);

	return $canvas;
};

// Generate 64x64 standard icon and 32x32 compact icon
$icon64 = $renderSquareIcon($sourceImg, $srcWidth, $srcHeight, 64, 4);
$icon32 = $renderSquareIcon($sourceImg, $srcWidth, $srcHeight, 32, 2);

$generatedFiles = array();

foreach ($targetDirs as $dir) {
	$mainPngFile = $dir . 'byn.png';
	$smallPngFile = $dir . 'byn_32.png';
	$webpFile = $dir . 'byn.webp';

	// Save 64x64 PNG
	imagepng($icon64, $mainPngFile, 9);
	$generatedFiles[] = $mainPngFile;

	// Save 32x32 PNG
	imagepng($icon32, $smallPngFile, 9);
	$generatedFiles[] = $smallPngFile;

	// Save WebP if supported
	if (function_exists('imagewebp')) {
		imagewebp($icon64, $webpFile, 95);
		$generatedFiles[] = $webpFile;
	}
}

imagedestroy($sourceImg);
imagedestroy($icon64);
imagedestroy($icon32);

// 5. Database Upsert ('oc_currency')
$currencyTitle  = 'Белорусский рубль';
$currencyCode   = 'BYN';
$symbolLeft     = '';
$symbolRight    = ' руб.';
$decimalPlace   = 2;
$status         = 1;
$formattedRate  = number_format($rate, 8, '.', '');

$checkQuery = $db->query('SELECT currency_id FROM `' . DB_PREFIX . 'currency` WHERE `code` = \'' . $db->escape($currencyCode) . '\'');

$action = 'inserted';
$currencyId = 0;

if ($checkQuery->num_rows) {
	$currencyId = (int)$checkQuery->row['currency_id'];
	$db->query('UPDATE `' . DB_PREFIX . 'currency` SET
		`title` = \'' . $db->escape($currencyTitle) . '\',
		`symbol_left` = \'' . $db->escape($symbolLeft) . '\',
		`symbol_right` = \'' . $db->escape($symbolRight) . '\',
		`decimal_place` = \'' . $db->escape((string)$decimalPlace) . '\',
		`value` = \'' . (float)$formattedRate . '\',
		`status` = \'' . (int)$status . '\',
		`date_modified` = NOW()
		WHERE `currency_id` = \'' . (int)$currencyId . '\'');
	$action = 'updated';
} else {
	$db->query('INSERT INTO `' . DB_PREFIX . 'currency` SET
		`title` = \'' . $db->escape($currencyTitle) . '\',
		`code` = \'' . $db->escape($currencyCode) . '\',
		`symbol_left` = \'' . $db->escape($symbolLeft) . '\',
		`symbol_right` = \'' . $db->escape($symbolRight) . '\',
		`decimal_place` = \'' . $db->escape((string)$decimalPlace) . '\',
		`value` = \'' . (float)$formattedRate . '\',
		`status` = \'' . (int)$status . '\',
		`date_modified` = NOW()');
	$currencyId = (int)$db->getLastId();
	$action = 'inserted';
}

// 6. Cache Invalidation
$cache->delete('currency');

// Purge cache files directly from storage/cache
$cachePurgeDirs = array(DIR_CACHE, DIR_STORAGE . 'cache/');
$cachePurgeDirs = array_unique(array_filter($cachePurgeDirs, 'is_dir'));

$purgedCacheFiles = 0;
foreach ($cachePurgeDirs as $cd) {
	$matched = glob($cd . 'cache.currency.*');
	if ($matched) {
		foreach ($matched as $cf) {
			if (is_file($cf)) {
				@unlink($cf);
				$purgedCacheFiles++;
			}
		}
	}
}

// 7. Output Response
$relIconPath = 'catalog/currency/byn.png';

$responsePayload = array(
	'status'    => 'success',
	'currency'  => $currencyCode,
	'icon_path' => $relIconPath,
	'data'      => array(
		'currency_id'     => $currencyId,
		'action'          => $action,
		'title'           => $currencyTitle,
		'code'            => $currencyCode,
		'symbol_left'     => $symbolLeft,
		'symbol_right'    => $symbolRight,
		'decimal_place'   => $decimalPlace,
		'value'           => $formattedRate,
		'status'          => $status,
		'source_image'    => $resolvedSourceImage,
		'generated_files' => $generatedFiles,
		'cache_cleared'   => true
	),
	'message'   => 'Currency BYN ' . $action . ' and icon generated successfully.',
	'errors'    => array()
);

if ($format === 'text') {
	echo '[SUCCESS] ' . $responsePayload['message'] . PHP_EOL;
	echo 'Currency ID: ' . $currencyId . ' (' . $currencyCode . ' - ' . $currencyTitle . ')' . PHP_EOL;
	echo 'Rate: ' . $formattedRate . PHP_EOL;
	echo 'Symbol: ' . $symbolRight . PHP_EOL;
	echo 'Icon: ' . DIR_IMAGE . $relIconPath . PHP_EOL;
	echo 'Cache purged: ' . $purgedCacheFiles . ' file(s)' . PHP_EOL;
} else {
	echo json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

exit(0);
