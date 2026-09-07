<?php
/**
 * LiveStore (OpenCart 3.0.3.x Fork) Template Junk Purge Utility
 *
 * Architecture: Standalone, Idempotent CLI Execution
 * Code Standards: Strictly single quotes for all string literals, array keys, SQL queries, and paths.
 *
 * Exit Codes:
 *   0 = Success
 *   1 = Failure / Runtime Error
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
$format = 'json';
$dryRun = false;
$keepInformation = false;
$purgeImages = true;

foreach ($argv as $arg) {
	if (strpos($arg, '--format=') === 0) {
		$format = substr($arg, 9);
	} elseif ($arg === '--format=text') {
		$format = 'text';
	} elseif ($arg === '--dry-run') {
		$dryRun = true;
	} elseif ($arg === '--keep-information') {
		$keepInformation = true;
	} elseif ($arg === '--no-images') {
		$purgeImages = false;
	}
}

// Helper: Check Table Existence
$tableExists = function($tableName) use ($db) {
	$check = $db->query('SHOW TABLES LIKE \'' . $db->escape($tableName) . '\'');
	return ($check->num_rows > 0);
};

// Helper: Get Table Row Count
$getRowCount = function($tableName) use ($db) {
	$query = $db->query('SELECT COUNT(*) AS total FROM `' . $tableName . '`');
	return $query->num_rows ? (int)$query->row['total'] : 0;
};

// 3. Define Candidate Junk Tables
$junkTableBases = array(
	// 1. Manufacturers & Brands
	'manufacturer',
	'manufacturer_description',
	'manufacturer_to_layout',
	'manufacturer_to_store',

	// 2. Template Blogs, Articles, News & Article Relations
	'blog',
	'blog_description',
	'blog_category',
	'blog_category_description',
	'blog_category_path',
	'blog_category_to_layout',
	'blog_category_to_store',
	'article',
	'article_description',
	'article_image',
	'article_related',
	'article_related_mn',
	'article_related_product',
	'article_related_wb',
	'article_to_blog_category',
	'article_to_download',
	'article_to_layout',
	'article_to_store',
	'news',
	'news_description',

	// 3. Marketing, Promotions & Affiliates
	'coupon',
	'coupon_category',
	'coupon_history',
	'coupon_product',
	'voucher',
	'voucher_history',
	'voucher_theme',
	'voucher_theme_description',
	'marketing',
	'affiliate',
	'customer_affiliate',
	'customer_transaction',

	// 4. Reviews & Ratings
	'review',
	'review_article',

	// 5. Default Banners & Carousels
	'banner',
	'banner_image'
);

// Conditionally append information tables
if (!$keepInformation) {
	$junkTableBases[] = 'information';
	$junkTableBases[] = 'information_description';
	$junkTableBases[] = 'information_to_layout';
	$junkTableBases[] = 'information_to_store';
}

// 4. Execute Table Purge
$purgedSummary = array();
$totalRowsPurged = 0;

if (!$dryRun) {
	$db->query('SET FOREIGN_KEY_CHECKS = 0');
}

foreach ($junkTableBases as $baseName) {
	$fullTable = DB_PREFIX . $baseName;

	if ($tableExists($fullTable)) {
		$rowCount = $getRowCount($fullTable);
		$purgedSummary[$fullTable] = $rowCount;
		$totalRowsPurged += $rowCount;

		if (!$dryRun) {
			$db->query('TRUNCATE TABLE `' . $fullTable . '`');
			// Reset auto increment
			$db->query('ALTER TABLE `' . $fullTable . '` AUTO_INCREMENT = 1');
		}
	}
}

// 5. Clean Layout Module Bindings
$layoutModulesCount = 0;
$moduleMatchSql = 'SELECT COUNT(*) AS total FROM `' . DB_PREFIX . 'layout_module` WHERE `code` LIKE \'banner%\' OR `code` LIKE \'carousel%\' OR `code` LIKE \'slideshow%\' OR `code` LIKE \'blog%\'';
$lmQuery = $db->query($moduleMatchSql);
$layoutModulesCount = $lmQuery->num_rows ? (int)$lmQuery->row['total'] : 0;

if (!$dryRun && $layoutModulesCount > 0) {
	$db->query('DELETE FROM `' . DB_PREFIX . 'layout_module` WHERE `code` LIKE \'banner%\' OR `code` LIKE \'carousel%\' OR `code` LIKE \'slideshow%\' OR `code` LIKE \'blog%\'');
}

// 6. Clean Orphaned SEO URLs
$seoUrlsCount = 0;
if ($tableExists(DB_PREFIX . 'seo_url')) {
	$seoWhere = '`query` LIKE \'manufacturer_id=%\' OR `query` LIKE \'article_id=%\' OR `query` LIKE \'blog%\'';
	if (!$keepInformation) {
		$seoWhere .= ' OR `query` LIKE \'information_id=%\'';
	}
	$seoQuery = $db->query('SELECT COUNT(*) AS total FROM `' . DB_PREFIX . 'seo_url` WHERE ' . $seoWhere);
	$seoUrlsCount = $seoQuery->num_rows ? (int)$seoQuery->row['total'] : 0;

	if (!$dryRun && $seoUrlsCount > 0) {
		$db->query('DELETE FROM `' . DB_PREFIX . 'seo_url` WHERE ' . $seoWhere);
	}
}

if (!$dryRun) {
	$db->query('SET FOREIGN_KEY_CHECKS = 1');
}

// 7. Demo Image Cleanup (Safely Ignoring Custom Currencies)
$deletedImageFiles = 0;
$deletedImageDirs = 0;

if ($purgeImages) {
	$demoImageDirs = array(
		DIR_IMAGE . 'catalog/demo',
		$rootDir . '/upload/image/catalog/demo',
		DIR_IMAGE . 'catalog/banners',
		$rootDir . '/upload/image/catalog/banners'
	);

	foreach ($demoImageDirs as $dir) {
		if (!is_dir($dir)) {
			continue;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $item) {
			$subPath = $item->getPathname();
			if ($item->isFile()) {
				$deletedImageFiles++;
				if (!$dryRun) {
					@unlink($subPath);
				}
			} elseif ($item->isDir()) {
				$deletedImageDirs++;
				if (!$dryRun) {
					@rmdir($subPath);
				}
			}
		}

		if (!$dryRun) {
			@rmdir($dir);
			$deletedImageDirs++;
		}
	}
}

// 8. Cache Purge
$purgedCacheFiles = 0;
if (!$dryRun) {
	$cache->delete('information');
	$cache->delete('manufacturer');
	$cache->delete('banner');

	$cacheDirs = array(DIR_CACHE, DIR_STORAGE . 'cache/');
	$cacheDirs = array_unique(array_filter($cacheDirs, 'is_dir'));

	foreach ($cacheDirs as $cd) {
		// Template cache
		$tmplDir = $cd . 'template';
		if (is_dir($tmplDir)) {
			$it = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($tmplDir, \RecursiveDirectoryIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ($it as $f) {
				if ($f->isFile()) {
					@unlink($f->getPathname());
					$purgedCacheFiles++;
				} elseif ($f->isDir()) {
					@rmdir($f->getPathname());
				}
			}
		}

		// System cache files
		$sysFiles = glob($cd . 'cache.*');
		if ($sysFiles) {
			foreach ($sysFiles as $sf) {
				if (is_file($sf)) {
					@unlink($sf);
					$purgedCacheFiles++;
				}
			}
		}
	}
}

// 9. Response Formatting
$response = array(
	'status'  => 'success',
	'purged'  => array(
		'dry_run'                 => $dryRun,
		'tables_checked'          => count($purgedSummary),
		'total_table_rows'        => $totalRowsPurged,
		'table_details'           => $purgedSummary,
		'layout_modules_removed'  => $layoutModulesCount,
		'seo_urls_removed'        => $seoUrlsCount,
		'demo_images_removed'     => $deletedImageFiles,
		'demo_dirs_removed'       => $deletedImageDirs,
		'cache_files_cleared'     => $purgedCacheFiles
	),
	'preserved' => array(
		'core_settings' => 'oc_setting',
		'currencies'    => 'oc_currency',
		'languages'     => 'oc_language',
		'categories'    => 'oc_category (Synesthesia catalog)',
		'attributes'    => 'oc_attribute (Synesthesia attributes)',
		'vector_sync'   => 'oc_product_vector_status',
		'currency_icon' => 'image/catalog/currency/byn.png'
	),
	'message' => $dryRun ? '[DRY-RUN] Template junk identified without database modification.' : 'Template junk successfully purged.',
	'errors'  => array()
);

if ($format === 'text') {
	echo ($dryRun ? '[DRY-RUN] ' : '[SUCCESS] ') . $response['message'] . PHP_EOL;
	echo 'Tables checked: ' . count($purgedSummary) . ' (' . $totalRowsPurged . ' rows)' . PHP_EOL;
	echo 'Layout modules removed: ' . $layoutModulesCount . PHP_EOL;
	echo 'SEO URLs removed: ' . $seoUrlsCount . PHP_EOL;
	echo 'Demo image files removed: ' . $deletedImageFiles . PHP_EOL;
	echo 'Cache files cleared: ' . $purgedCacheFiles . PHP_EOL;
} else {
	echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

exit(0);
