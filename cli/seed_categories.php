<?php
/**
 * LiveStore / OpenCart 3.0.3.x Category Seeder Migration
 *
 * Architecture: Standalone, Idempotent CLI Execution
 * Code Standards: Strictly single quotes for all string literals, arrays, and SQL queries.
 *
 * Exit Codes:
 *   0 = Success
 *   1 = Runtime / Database Error
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

// CLI Arguments parsing
$format = 'text';
if (isset($argv) && is_array($argv)) {
	foreach ($argv as $arg) {
		if ($arg === '--format=json' || strpos($arg, '--format=json') === 0) {
			$format = 'json';
		} elseif ($arg === '--format=text' || strpos($arg, '--format=text') === 0) {
			$format = 'text';
		}
	}
}

// Intercept notices and warnings from leaking into STDOUT
set_error_handler(function($severity, $message, $file, $line) {
	if (!(error_reporting() & $severity)) {
		return false;
	}
	fwrite(STDERR, '[NOTICE/WARNING ' . $severity . '] ' . $message . ' in ' . $file . ' on line ' . $line . PHP_EOL);
	return true;
});

// Intercept uncaught exceptions
set_exception_handler(function(\Throwable $e) use (&$format) {
	if ($format === 'json') {
		$payload = array(
			'status'  => 'error',
			'message' => 'Uncaught Exception: ' . $e->getMessage()
		);
		echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
	} else {
		fwrite(STDERR, '[ERROR] ' . $e->getMessage() . PHP_EOL);
	}
	exit(1);
});

// Step 1: Headless CLI Bootstrap
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

// Database instantiation via Registry
$db = new \DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);
$db = $registry->get('db');

$cache = new \Cache($config->get('cache_engine'), $config->get('cache_expire'));
$registry->set('cache', $cache);

// Step 2: Fetch Active Languages
$languages = array();
$langQuery = $db->query('SELECT language_id, code, name FROM `' . DB_PREFIX . 'language`');
if ($langQuery->num_rows) {
	foreach ($langQuery->rows as $l) {
		$languages[(int)$l['language_id']] = $l;
	}
}
if (empty($languages)) {
	$languages[1] = array(
		'language_id' => 1,
		'code'        => 'ru-ru',
		'name'        => 'Russian'
	);
}

// Inspect Schema for Optional / Fork-Specific Columns
$catColumns = array();
$catColQuery = $db->query('SHOW COLUMNS FROM `' . DB_PREFIX . 'category`');
foreach ($catColQuery->rows as $col) {
	$catColumns[$col['Field']] = true;
}

$descColumns = array();
$descColQuery = $db->query('SHOW COLUMNS FROM `' . DB_PREFIX . 'category_description`');
foreach ($descColQuery->rows as $col) {
	$descColumns[$col['Field']] = true;
}

$seoTableExists = false;
$seoCheck = $db->query('SHOW TABLES LIKE \'' . DB_PREFIX . 'seo_url\'');
if ($seoCheck->num_rows) {
	$seoTableExists = true;
}

// Helper: Transliteration to Clean Latin SEO Keyword Slug
$slugify = function($text) {
	$trans = array(
		'а' => 'a',   'б' => 'b',   'в' => 'v',   'г' => 'g',   'д' => 'd',
		'е' => 'e',   'ё' => 'yo',  'ж' => 'zh',  'з' => 'z',   'и' => 'i',
		'й' => 'y',   'к' => 'k',   'л' => 'l',   'м' => 'm',   'н' => 'n',
		'о' => 'o',   'п' => 'p',   'р' => 'r',   'с' => 's',   'т' => 't',
		'у' => 'u',   'ф' => 'f',   'х' => 'kh',  'ц' => 'ts',  'ч' => 'ch',
		'ш' => 'sh',  'щ' => 'shch', 'ъ' => '',   'ы' => 'y',   'ь' => '',
		'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
		'А' => 'a',   'Б' => 'b',   'В' => 'v',   'Г' => 'g',   'Д' => 'd',
		'Е' => 'e',   'Ё' => 'yo',  'Ж' => 'zh',  'З' => 'z',   'И' => 'i',
		'Й' => 'y',   'К' => 'k',   'Л' => 'l',   'М' => 'm',   'Н' => 'n',
		'О' => 'o',   'П' => 'p',   'Р' => 'r',   'С' => 's',   'Т' => 't',
		'У' => 'u',   'Ф' => 'f',   'Х' => 'kh',  'Ц' => 'ts',  'Ч' => 'ch',
		'Ш' => 'sh',  'Щ' => 'shch', 'Ъ' => '',   'Ы' => 'y',   'Ь' => '',
		'Э' => 'e',   'Ю' => 'yu',  'Я' => 'ya'
	);
	$str = strtr($text, $trans);
	$str = mb_strtolower($str, 'UTF-8');
	$str = preg_replace('/[^a-z0-9\-]+/', '-', $str);
	$str = trim($str, '-');
	$str = preg_replace('/-+/', '-', $str);
	return $str !== '' ? $str : 'category';
};

// Helper: Ensure Unique SEO Keyword
$resolveUniqueKeyword = function($baseSlug, $categoryId, $db) {
	$keyword = $baseSlug;
	$check = $db->query('SELECT seo_url_id FROM `' . DB_PREFIX . 'seo_url` WHERE `keyword` = \'' . $db->escape($keyword) . '\' AND `query` != \'category_id=' . (int)$categoryId . '\' LIMIT 1');
	if ($check->num_rows) {
		$keyword = $baseSlug . '-' . (int)$categoryId;
	}
	return $keyword;
};

// Helper: Find Existing Category by Name and Parent ID
$findCategoryByNameAndParent = function($name, $parentId, $db) {
	$sql = 'SELECT c.category_id FROM `' . DB_PREFIX . 'category` c ' .
	       'INNER JOIN `' . DB_PREFIX . 'category_description` cd ON (c.category_id = cd.category_id) ' .
	       'WHERE c.parent_id = \'' . (int)$parentId . '\' ' .
	       'AND cd.name = \'' . $db->escape($name) . '\' ' .
	       'LIMIT 1';
	$query = $db->query($sql);
	if ($query->num_rows) {
		return (int)$query->row['category_id'];
	}
	return null;
};

// Step 3: Define Target Category Hierarchy
$categoryHierarchy = array(
	array(
		'name'          => 'Гитары',
		'top'           => 1,
		'subcategories' => array(
			'Акустические гитары',
			'Бас-гитары',
			'Гитары классические',
			'Электрогитары'
		)
	),
	array(
		'name'          => 'Гитарное оборудование',
		'top'           => 1,
		'subcategories' => array(
			'Комбики',
			'Усилители для гитар',
			'Кабинеты',
			'Педали для гитар',
			'Педали для электроакустической гитары'
		)
	),
	array(
		'name'          => 'Клавишные',
		'top'           => 1,
		'subcategories' => array(
			'Цифровые пианино',
			'Синтезаторы',
			'Midi-контроллеры',
			'MIDI-клавиатуры'
		)
	),
	array(
		'name'          => 'Струнные',
		'top'           => 1,
		'subcategories' => array(
			'Электроскрипки',
			'Скрипки',
			'Контрабасы',
			'Виолончели'
		)
	)
);

// Helper: Insert Category Entity with All Relational Bindings
$insertCategory = function($name, $parentId, $top, $db, $languages, $catColumns, $descColumns, $seoTableExists, $slugify, $resolveUniqueKeyword) {
	// 1. Insert into oc_category
	$catFields = array(
		'`parent_id` = \'' . (int)$parentId . '\'',
		'`top` = \'' . (int)$top . '\'',
		'`column` = \'1\'',
		'`sort_order` = \'0\'',
		'`status` = \'1\'',
		'`date_added` = NOW()',
		'`date_modified` = NOW()'
	);
	if (isset($catColumns['noindex'])) {
		$catFields[] = '`noindex` = \'0\'';
	}
	if (isset($catColumns['image'])) {
		$catFields[] = '`image` = \'\'';
	}

	$db->query('INSERT INTO `' . DB_PREFIX . 'category` SET ' . implode(', ', $catFields));
	$categoryId = (int)$db->getLastId();

	// 2. Insert into oc_category_description for all installed languages
	foreach ($languages as $langId => $langInfo) {
		$descFields = array(
			'`category_id` = \'' . (int)$categoryId . '\'',
			'`language_id` = \'' . (int)$langId . '\'',
			'`name` = \'' . $db->escape($name) . '\'',
			'`meta_title` = \'' . $db->escape($name) . '\''
		);
		if (isset($descColumns['description'])) {
			$descFields[] = '`description` = \'\'';
		}
		if (isset($descColumns['meta_h1'])) {
			$descFields[] = '`meta_h1` = \'' . $db->escape($name) . '\'';
		}
		if (isset($descColumns['meta_description'])) {
			$descFields[] = '`meta_description` = \'\'';
		}
		if (isset($descColumns['meta_keyword'])) {
			$descFields[] = '`meta_keyword` = \'\'';
		}

		$db->query('INSERT INTO `' . DB_PREFIX . 'category_description` SET ' . implode(', ', $descFields));
	}

	// 3. Link to default store (store_id = 0)
	$db->query('INSERT INTO `' . DB_PREFIX . 'category_to_store` SET `category_id` = \'' . (int)$categoryId . '\', `store_id` = \'0\'');

	// 4. Map to layout (store_id = 0, layout_id = 0)
	$db->query('INSERT INTO `' . DB_PREFIX . 'category_to_layout` SET `category_id` = \'' . (int)$categoryId . '\', `store_id` = \'0\', `layout_id` = \'0\'');

	// 5. Build hierarchical breadcrumb path in oc_category_path
	$level = 0;
	if ((int)$parentId > 0) {
		$parentPaths = $db->query('SELECT `path_id` FROM `' . DB_PREFIX . 'category_path` WHERE `category_id` = \'' . (int)$parentId . '\' ORDER BY `level` ASC');
		foreach ($parentPaths->rows as $pRow) {
			$db->query('INSERT INTO `' . DB_PREFIX . 'category_path` SET `category_id` = \'' . (int)$categoryId . '\', `path_id` = \'' . (int)$pRow['path_id'] . '\', `level` = \'' . (int)$level . '\'');
			$level++;
		}
	}
	$db->query('INSERT INTO `' . DB_PREFIX . 'category_path` SET `category_id` = \'' . (int)$categoryId . '\', `path_id` = \'' . (int)$categoryId . '\', `level` = \'' . (int)$level . '\'');

	// 6. Generate clean SEO keyword slug in oc_seo_url
	if ($seoTableExists) {
		$baseSlug = $slugify($name);
		$keyword = $resolveUniqueKeyword($baseSlug, $categoryId, $db);
		foreach ($languages as $langId => $langInfo) {
			$db->query('INSERT INTO `' . DB_PREFIX . 'seo_url` SET `store_id` = \'0\', `language_id` = \'' . (int)$langId . '\', `query` = \'category_id=' . (int)$categoryId . '\', `keyword` = \'' . $db->escape($keyword) . '\'');
		}
	}

	return $categoryId;
};

// Step 4: Execute Idempotent Hierarchy Seeding
$categoriesCreated = 0;
$categoriesSkipped = 0;

foreach ($categoryHierarchy as $rootDef) {
	$rootName = $rootDef['name'];
	$existingRootId = $findCategoryByNameAndParent($rootName, 0, $db);

	if ($existingRootId !== null) {
		$rootId = $existingRootId;
		$categoriesSkipped++;
		if ($format === 'text') {
			echo '[EXISTS] Root Category: \'' . $rootName . '\' (ID: ' . $rootId . ')' . PHP_EOL;
		}
	} else {
		$rootId = $insertCategory(
			$rootName,
			0,
			(int)$rootDef['top'],
			$db,
			$languages,
			$catColumns,
			$descColumns,
			$seoTableExists,
			$slugify,
			$resolveUniqueKeyword
		);
		$categoriesCreated++;
		if ($format === 'text') {
			echo '[CREATED] Root Category: \'' . $rootName . '\' (ID: ' . $rootId . ')' . PHP_EOL;
		}
	}

	// Process Subcategories
	foreach ($rootDef['subcategories'] as $subName) {
		$existingSubId = $findCategoryByNameAndParent($subName, $rootId, $db);

		if ($existingSubId !== null) {
			$categoriesSkipped++;
			if ($format === 'text') {
				echo '  -> [EXISTS] Subcategory: \'' . $subName . '\' (ID: ' . $existingSubId . ')' . PHP_EOL;
			}
		} else {
			$subId = $insertCategory(
				$subName,
				$rootId,
				0,
				$db,
				$languages,
				$catColumns,
				$descColumns,
				$seoTableExists,
				$slugify,
				$resolveUniqueKeyword
			);
			$categoriesCreated++;
			if ($format === 'text') {
				echo '  -> [CREATED] Subcategory: \'' . $subName . '\' (ID: ' . $subId . ')' . PHP_EOL;
			}
		}
	}
}

// Step 5: Flush Category Database Cache
$cacheObj = $registry->get('cache');
if ($cacheObj instanceof \Cache) {
	$cacheObj->delete('category');
}

$cacheDirs = array(
	DIR_CACHE,
	DIR_STORAGE . 'cache/',
	DIR_SYSTEM . 'storage/cache/',
	$rootDir . '/system/storage/cache/'
);
$cacheDirs = array_unique(array_filter($cacheDirs, 'is_dir'));

$clearedCacheFiles = 0;
foreach ($cacheDirs as $cDir) {
	$cDir = rtrim(str_replace('\\', '/', $cDir), '/') . '/';
	$files = glob($cDir . 'cache.category.*');
	if ($files) {
		foreach ($files as $f) {
			if (is_file($f)) {
				@unlink($f);
				$clearedCacheFiles++;
			}
		}
	}
}

// Step 6: Format Output and Exit
if ($format === 'json') {
	$response = array(
		'status'             => 'success',
		'categories_created' => $categoriesCreated
	);
	echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
	echo PHP_EOL . '=== Seeding Complete ===' . PHP_EOL;
	echo 'Categories created: ' . $categoriesCreated . PHP_EOL;
	echo 'Categories skipped: ' . $categoriesSkipped . PHP_EOL;
	echo 'Cache files cleared: ' . $clearedCacheFiles . PHP_EOL;
}

exit(0);
