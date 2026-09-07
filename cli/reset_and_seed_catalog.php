<?php
/**
 * LiveStore (OpenCart 3.0.3.x Fork) Database Reset & Catalog Seed Utility
 *
 * Architecture: Standalone, Idempotent CLI Execution
 * Code Standards: Strictly single quotes for all string literals, array keys, SQL queries, and paths.
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
		'data'    => null,
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

// Step 1: Headless Bootstrap
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

// Parse CLI Format
$format = 'json';
foreach ($argv as $arg) {
	if ($arg === '--format=text') {
		$format = 'text';
	}
}

// Query Active Store Languages
$languages = array();
$langRows = $db->query('SELECT language_id, code, name FROM ' . DB_PREFIX . 'language WHERE status = \'1\' ORDER BY sort_order ASC');
foreach ($langRows->rows as $l) {
	$languages[(int)$l['language_id']] = array(
		'code' => strtolower($l['code']),
		'name' => $l['name']
	);
}

if (empty($languages)) {
	$languages[1] = array('code' => 'ru-ru', 'name' => 'Russian');
}

// Helper: Translation Resolver
$resolveText = function($ruText, $enText, $code) {
	if (strpos($code, 'ru') === 0) {
		return $ruText;
	}
	return $enText;
};

// Helper: Safe Slugify
$slugify = function($text) {
	$trans = array(
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
		'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
		'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
		'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
		'ы' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', 'ъ' => '', 'ь' => ''
	);
	$str = mb_strtolower($text, 'UTF-8');
	$str = strtr($str, $trans);
	$str = preg_replace('/[^a-z0-9\-]+/i', '-', $str);
	$str = trim($str, '-');
	return $str !== '' ? $str : 'item';
};

// Step 2: Full Catalog Purge & Vector Sync State Setup
$db->query('SET FOREIGN_KEY_CHECKS = 0');

$tablesToPurge = array(
	// Product tables
	'product',
	'product_description',
	'product_to_category',
	'product_to_store',
	'product_to_layout',
	'product_attribute',
	'product_option',
	'product_option_value',
	'product_image',
	'product_related',
	'product_discount',
	'product_special',
	'product_reward',
	'product_filter',
	'product_recurring',
	'product_related_article',
	'product_related_mn',
	'product_related_wb',
	'article_related_product',
	'coupon_product',
	'review',

	// Category tables
	'category',
	'category_description',
	'category_to_store',
	'category_to_layout',
	'category_path',
	'category_filter',
	'article_related_wb',

	// Attribute tables
	'attribute',
	'attribute_description',
	'attribute_group',
	'attribute_group_description'
);

$purgedTables = array();
foreach ($tablesToPurge as $tableBase) {
	$fullTable = DB_PREFIX . $tableBase;
	$check = $db->query('SHOW TABLES LIKE \'' . $db->escape($fullTable) . '\'');
	if ($check->num_rows) {
		$db->query('TRUNCATE TABLE `' . $fullTable . '`');
		$db->query('ALTER TABLE `' . $fullTable . '` AUTO_INCREMENT = 1');
		$purgedTables[] = $fullTable;
	}
}

// Remove Category & Product Slugs from oc_seo_url
$seoCheck = $db->query('SHOW TABLES LIKE \'' . DB_PREFIX . 'seo_url\'');
if ($seoCheck->num_rows) {
	$db->query('DELETE FROM `' . DB_PREFIX . 'seo_url` WHERE `query` LIKE \'category_id=%\' OR `query` LIKE \'product_id=%\'');
}

// Reset / Initialize oc_product_vector_status
$vectorStatusTable = DB_PREFIX . 'product_vector_status';
$checkVector = $db->query('SHOW TABLES LIKE \'' . $db->escape($vectorStatusTable) . '\'');

if ($checkVector->num_rows) {
	$db->query('TRUNCATE TABLE `' . $vectorStatusTable . '`');
} else {
	$db->query('CREATE TABLE `' . $vectorStatusTable . '` (
		`product_id` int NOT NULL,
		`status` varchar(32) NOT NULL DEFAULT \'pending\',
		`vector_id` varchar(128) DEFAULT NULL,
		`payload_hash` varchar(64) DEFAULT NULL,
		`dimensions` int DEFAULT NULL,
		`last_sync` datetime DEFAULT NULL,
		`error_message` text DEFAULT NULL,
		`date_added` datetime NOT NULL,
		`date_modified` datetime NOT NULL,
		PRIMARY KEY (`product_id`),
		KEY `idx_status` (`status`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}
$purgedTables[] = $vectorStatusTable;

$db->query('SET FOREIGN_KEY_CHECKS = 1');

// Resolve Default Layout for Categories
$layoutId = 3;
$layoutCheck = $db->query('SELECT layout_id FROM ' . DB_PREFIX . 'layout WHERE name LIKE \'%Category%\' OR name LIKE \'%Категория%\' LIMIT 1');
if ($layoutCheck->num_rows) {
	$layoutId = (int)$layoutCheck->row['layout_id'];
}

// Step 3: Insert Categories & Hierarchical Paths
$categoryBlueprint = array(
	array(
		'ru_name'     => 'Музыка / Винил / CD',
		'en_name'     => 'Music / Vinyl / CD',
		'top'         => 1,
		'column'      => 1,
		'sort_order'  => 1,
		'slug_ru'     => 'muzyka-vinil-cd',
		'slug_en'     => 'music-vinyl-cd',
		'children'    => array(
			array(
				'ru_name'    => 'Виниловые пластинки',
				'en_name'    => 'Vinyl Records',
				'sort_order' => 1,
				'slug_ru'    => 'vinilovye-plastinki',
				'slug_en'    => 'vinyl-records'
			),
			array(
				'ru_name'    => 'Компакт-диски',
				'en_name'    => 'CDs & Box Sets',
				'sort_order' => 2,
				'slug_ru'    => 'kompakt-diski',
				'slug_en'    => 'cds-box-sets'
			),
			array(
				'ru_name'    => 'Цифровые релизы',
				'en_name'    => 'Digital Releases',
				'sort_order' => 3,
				'slug_ru'    => 'tsifrovye-relizy',
				'slug_en'    => 'digital-releases'
			)
		)
	),
	array(
		'ru_name'     => 'Музыкальные инструменты и оборудование',
		'en_name'     => 'Musical Instruments & Gear',
		'top'         => 1,
		'column'      => 1,
		'sort_order'  => 2,
		'slug_ru'     => 'muzykalnye-instrumenty-i-oborudovanie',
		'slug_en'     => 'musical-instruments-gear',
		'children'    => array(
			array(
				'ru_name'    => 'Гитары',
				'en_name'    => 'Guitars',
				'sort_order' => 1,
				'slug_ru'    => 'gitary',
				'slug_en'    => 'guitars'
			),
			array(
				'ru_name'    => 'Клавишные и синтезаторы',
				'en_name'    => 'Keyboards & Synthesizers',
				'sort_order' => 2,
				'slug_ru'    => 'klavishnye-i-sintezatory',
				'slug_en'    => 'keyboards-synthesizers'
			),
			array(
				'ru_name'    => 'Усилители и кабинеты',
				'en_name'    => 'Amplifiers & Cabinets',
				'sort_order' => 3,
				'slug_ru'    => 'usiliteli-i-kabinety',
				'slug_en'    => 'amplifiers-cabinets'
			),
			array(
				'ru_name'    => 'Педали эффектов',
				'en_name'    => 'Effect Pedals',
				'sort_order' => 4,
				'slug_ru'    => 'pedali-effektov',
				'slug_en'    => 'effect-pedals'
			),
			array(
				'ru_name'    => 'DJ-оборудование',
				'en_name'    => 'DJ Gear',
				'sort_order' => 5,
				'slug_ru'    => 'dj-oborudovanie',
				'slug_en'    => 'dj-gear'
			)
		)
	)
);

$createdCategories = array();

foreach ($categoryBlueprint as $rootDef) {
	// Insert Root Category
	$db->query('INSERT INTO `' . DB_PREFIX . 'category` SET `parent_id` = \'0\', `top` = \'' . (int)$rootDef['top'] . '\', `column` = \'' . (int)$rootDef['column'] . '\', `sort_order` = \'' . (int)$rootDef['sort_order'] . '\', `status` = \'1\', `noindex` = \'0\', `date_added` = NOW(), `date_modified` = NOW()');
	$rootId = (int)$db->getLastId();

	// Root Descriptions
	foreach ($languages as $langId => $langInfo) {
		$name = $resolveText($rootDef['ru_name'], $rootDef['en_name'], $langInfo['code']);
		$db->query('INSERT INTO `' . DB_PREFIX . 'category_description` SET `category_id` = \'' . (int)$rootId . '\', `language_id` = \'' . (int)$langId . '\', `name` = \'' . $db->escape($name) . '\', `description` = \'\', `meta_title` = \'' . $db->escape($name) . '\', `meta_h1` = \'' . $db->escape($name) . '\', `meta_description` = \'\', `meta_keyword` = \'\'');
	}

	// Root Relations
	$db->query('INSERT INTO `' . DB_PREFIX . 'category_to_store` SET `category_id` = \'' . (int)$rootId . '\', `store_id` = \'0\'');
	$db->query('INSERT INTO `' . DB_PREFIX . 'category_to_layout` SET `category_id` = \'' . (int)$rootId . '\', `store_id` = \'0\', `layout_id` = \'' . (int)$layoutId . '\'');
	$db->query('INSERT INTO `' . DB_PREFIX . 'category_path` SET `category_id` = \'' . (int)$rootId . '\', `path_id` = \'' . (int)$rootId . '\', `level` = \'0\'');

	// Root SEO URLs
	if ($seoCheck->num_rows) {
		foreach ($languages as $langId => $langInfo) {
			$slug = (strpos($langInfo['code'], 'ru') === 0) ? $rootDef['slug_ru'] : $rootDef['slug_en'];
			$db->query('INSERT INTO `' . DB_PREFIX . 'seo_url` SET `store_id` = \'0\', `language_id` = \'' . (int)$langId . '\', `query` = \'category_id=' . (int)$rootId . '\', `keyword` = \'' . $db->escape($slug) . '\'');
		}
	}

	$rootSummary = array(
		'category_id' => $rootId,
		'name_ru'     => $rootDef['ru_name'],
		'name_en'     => $rootDef['en_name'],
		'parent_id'   => 0,
		'subcategories' => array()
	);

	// Insert Child Categories
	foreach ($rootDef['children'] as $childDef) {
		$db->query('INSERT INTO `' . DB_PREFIX . 'category` SET `parent_id` = \'' . (int)$rootId . '\', `top` = \'0\', `column` = \'1\', `sort_order` = \'' . (int)$childDef['sort_order'] . '\', `status` = \'1\', `noindex` = \'0\', `date_added` = NOW(), `date_modified` = NOW()');
		$childId = (int)$db->getLastId();

		// Child Descriptions
		foreach ($languages as $langId => $langInfo) {
			$childName = $resolveText($childDef['ru_name'], $childDef['en_name'], $langInfo['code']);
			$db->query('INSERT INTO `' . DB_PREFIX . 'category_description` SET `category_id` = \'' . (int)$childId . '\', `language_id` = \'' . (int)$langId . '\', `name` = \'' . $db->escape($childName) . '\', `description` = \'\', `meta_title` = \'' . $db->escape($childName) . '\', `meta_h1` = \'' . $db->escape($childName) . '\', `meta_description` = \'\', `meta_keyword` = \'\'');
		}

		// Child Relations
		$db->query('INSERT INTO `' . DB_PREFIX . 'category_to_store` SET `category_id` = \'' . (int)$childId . '\', `store_id` = \'0\'');
		$db->query('INSERT INTO `' . DB_PREFIX . 'category_to_layout` SET `category_id` = \'' . (int)$childId . '\', `store_id` = \'0\', `layout_id` = \'' . (int)$layoutId . '\'');

		// Hierarchy Path
		$db->query('INSERT INTO `' . DB_PREFIX . 'category_path` SET `category_id` = \'' . (int)$childId . '\', `path_id` = \'' . (int)$rootId . '\', `level` = \'0\'');
		$db->query('INSERT INTO `' . DB_PREFIX . 'category_path` SET `category_id` = \'' . (int)$childId . '\', `path_id` = \'' . (int)$childId . '\', `level` = \'1\'');

		// Child SEO URLs
		if ($seoCheck->num_rows) {
			foreach ($languages as $langId => $langInfo) {
				$childSlug = (strpos($langInfo['code'], 'ru') === 0) ? $childDef['slug_ru'] : $childDef['slug_en'];
				$db->query('INSERT INTO `' . DB_PREFIX . 'seo_url` SET `store_id` = \'0\', `language_id` = \'' . (int)$langId . '\', `query` = \'category_id=' . (int)$childId . '\', `keyword` = \'' . $db->escape($childSlug) . '\'');
			}
		}

		$rootSummary['subcategories'][] = array(
			'category_id' => $childId,
			'name_ru'     => $childDef['ru_name'],
			'name_en'     => $childDef['en_name'],
			'parent_id'   => $rootId
		);
	}

	$createdCategories[] = $rootSummary;
}

// Step 4: Insert Attribute Groups and Attributes
$attributeBlueprint = array(
	array(
		'ru_group'   => 'Музыкальные параметры',
		'en_group'   => 'Music Parameters',
		'sort_order' => 1,
		'attributes' => array(
			array('ru' => 'Исполнитель',                'en' => 'Artist'),
			array('ru' => 'Жанр',                       'en' => 'Genre'),
			array('ru' => 'Год выпуска',                'en' => 'Year'),
			array('ru' => 'Лейбл',                      'en' => 'Label'),
			array('ru' => 'Формат издания',             'en' => 'Format'),
			array('ru' => 'Вайб / Характер звучания',  'en' => 'Vibe / Sound Character')
		)
	),
	array(
		'ru_group'   => 'Спецификации оборудования',
		'en_group'   => 'Gear Specifications',
		'sort_order' => 2,
		'attributes' => array(
			array('ru' => 'Бренд',             'en' => 'Brand'),
			array('ru' => 'Тип инструмента',   'en' => 'Gear Type'),
			array('ru' => 'Стиль звучания',    'en' => 'Sound Style'),
			array('ru' => 'Звукосниматели',    'en' => 'Pickups'),
			array('ru' => 'Материал корпуса',  'en' => 'Body Material')
		)
	)
);

$createdAttributeGroups = array();

foreach ($attributeBlueprint as $groupDef) {
	$db->query('INSERT INTO `' . DB_PREFIX . 'attribute_group` SET `sort_order` = \'' . (int)$groupDef['sort_order'] . '\'');
	$groupId = (int)$db->getLastId();

	foreach ($languages as $langId => $langInfo) {
		$groupName = $resolveText($groupDef['ru_group'], $groupDef['en_group'], $langInfo['code']);
		$db->query('INSERT INTO `' . DB_PREFIX . 'attribute_group_description` SET `attribute_group_id` = \'' . (int)$groupId . '\', `language_id` = \'' . (int)$langId . '\', `name` = \'' . $db->escape($groupName) . '\'');
	}

	$attrSummary = array(
		'attribute_group_id' => $groupId,
		'name_ru'            => $groupDef['ru_group'],
		'name_en'            => $groupDef['en_group'],
		'attributes'         => array()
	);

	$attrSort = 1;
	foreach ($groupDef['attributes'] as $attrDef) {
		$db->query('INSERT INTO `' . DB_PREFIX . 'attribute` SET `attribute_group_id` = \'' . (int)$groupId . '\', `sort_order` = \'' . (int)$attrSort . '\'');
		$attrId = (int)$db->getLastId();

		foreach ($languages as $langId => $langInfo) {
			$attrName = $resolveText($attrDef['ru'], $attrDef['en'], $langInfo['code']);
			$db->query('INSERT INTO `' . DB_PREFIX . 'attribute_description` SET `attribute_id` = \'' . (int)$attrId . '\', `language_id` = \'' . (int)$langId . '\', `name` = \'' . $db->escape($attrName) . '\'');
		}

		$attrSummary['attributes'][] = array(
			'attribute_id' => $attrId,
			'name_ru'      => $attrDef['ru'],
			'name_en'      => $attrDef['en']
		);
		$attrSort++;
	}

	$createdAttributeGroups[] = $attrSummary;
}

// Step 5: Cache Invalidation
$cachePurgeDirs = array(DIR_CACHE, DIR_STORAGE . 'cache/');
$cachePurgeDirs = array_unique(array_filter($cachePurgeDirs, 'is_dir'));

$clearedCacheFiles = 0;
foreach ($cachePurgeDirs as $cDir) {
	// Template cache
	$tmplDir = $cDir . 'template';
	if (is_dir($tmplDir)) {
		$it = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($tmplDir, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($it as $f) {
			if ($f->isFile()) {
				@unlink($f->getPathname());
				$clearedCacheFiles++;
			} elseif ($f->isDir()) {
				@rmdir($f->getPathname());
			}
		}
	}

	// System cache files
	$sysFiles = glob($cDir . 'cache.*');
	if ($sysFiles) {
		foreach ($sysFiles as $sf) {
			if (is_file($sf)) {
				@unlink($sf);
				$clearedCacheFiles++;
			}
		}
	}
}

// Final Summary Data
$totalRootCategories = count($createdCategories);
$totalSubcategories = 0;
foreach ($createdCategories as $rc) {
	$totalSubcategories += count($rc['subcategories']);
}

$totalAttributes = 0;
foreach ($createdAttributeGroups as $ag) {
	$totalAttributes += count($ag['attributes']);
}

$outputData = array(
	'status'  => 'success',
	'data'    => array(
		'purged_tables'          => $purgedTables,
		'vector_sync_table'      => $vectorStatusTable,
		'created_categories'     => $createdCategories,
		'created_attribute_groups' => $createdAttributeGroups,
		'stats' => array(
			'root_categories'     => $totalRootCategories,
			'subcategories'       => $totalSubcategories,
			'total_categories'    => $totalRootCategories + $totalSubcategories,
			'attribute_groups'    => count($createdAttributeGroups),
			'total_attributes'    => $totalAttributes,
			'cleared_cache_files' => $clearedCacheFiles
		)
	),
	'message' => 'Catalog database reset and initialized successfully.',
	'errors'  => array()
);

if ($format === 'text') {
	echo '[SUCCESS] ' . $outputData['message'] . PHP_EOL;
	echo 'Purged tables: ' . count($purgedTables) . PHP_EOL;
	echo 'Created ' . ($totalRootCategories + $totalSubcategories) . ' categories (' . $totalRootCategories . ' roots, ' . $totalSubcategories . ' subcategories).' . PHP_EOL;
	echo 'Created ' . count($createdAttributeGroups) . ' attribute groups with ' . $totalAttributes . ' attributes.' . PHP_EOL;
	echo 'Vector status table verified: ' . $vectorStatusTable . PHP_EOL;
	echo 'Cleared cache files: ' . $clearedCacheFiles . PHP_EOL;
} else {
	echo json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

exit(0);
