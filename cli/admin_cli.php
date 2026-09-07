<?php
/**
 * LiveStore (OpenCart 3.0.3.x Fork) Headless Admin CLI Utility
 *
 * Architecture: Agent-First CLI Dispatcher
 * Code Standards: Strictly single quotes for all string literals, array keys, config keys, and SQL queries.
 *
 * Exit Codes:
 *   0 = Success
 *   1 = Runtime Error / Validation Failure
 *   2 = Missing Arguments / Invalid Command
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

/**
 * CLI Response Envelope & Output Formatter
 */
class CliResponse {
	public static $format = 'json';
	public static $isQuiet = false;

	public static function success($data = array(), $message = 'Operation completed successfully.', $exitCode = 0) {
		self::emit('success', $data, $message, array(), $exitCode);
	}

	public static function error($message = 'An error occurred.', $errors = array(), $exitCode = 1, $data = null) {
		if (is_string($errors) && $errors !== '') {
			$errors = array($errors);
		} elseif (!is_array($errors)) {
			$errors = array();
		}
		self::emit('error', $data, $message, $errors, $exitCode);
	}

	public static function missingArgument($message, $missingParam = '') {
		$errors = array();
		if ($missingParam !== '') {
			$errors[] = 'Missing required argument: ' . $missingParam;
		}
		self::emit('error', null, $message, $errors, 2);
	}

	public static function invalidCommand($commandName) {
		self::emit(
			'error',
			null,
			'Unknown command: ' . $commandName,
			array('Run \'help\' to view all available commands and schemas.'),
			2
		);
	}

	public static function emit($status, $data, $message, array $errors, $exitCode = 0) {
		if (self::$format === 'text') {
			if ($status === 'success') {
				echo '[SUCCESS] ' . $message . PHP_EOL;
				if (!empty($data)) {
					echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
				}
			} else {
				fwrite(STDERR, '[ERROR] ' . $message . PHP_EOL);
				foreach ($errors as $err) {
					fwrite(STDERR, '  - ' . $err . PHP_EOL);
				}
			}
		} else {
			$payload = array(
				'status'  => $status,
				'data'    => $data,
				'message' => $message,
				'errors'  => $errors
			);
			$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			if ($status === 'success') {
				echo $json . PHP_EOL;
			} else {
				// Output error JSON envelope on STDOUT so AI agents can parse deterministic envelopes
				echo $json . PHP_EOL;
			}
		}
		exit($exitCode);
	}

	public static function logStderr($message) {
		fwrite(STDERR, '[CLI NOTICE] ' . $message . PHP_EOL);
	}
}

// Intercept notices and warnings so they never corrupt STDOUT
set_error_handler(function($severity, $message, $file, $line) {
	if (!(error_reporting() & $severity)) {
		return false;
	}
	CliResponse::logStderr('PHP Warning/Notice (' . $severity . '): ' . $message . ' in ' . $file . ' on line ' . $line);
	return true;
});

// Intercept uncaught exceptions and fatal errors
set_exception_handler(function(\Throwable $e) {
	CliResponse::error(
		'Uncaught Exception: ' . $e->getMessage(),
		array(
			'type' => get_class($e),
			'code' => $e->getCode(),
			'file' => $e->getFile(),
			'line' => $e->getLine()
		),
		1
	);
});

register_shutdown_function(function() {
	$error = error_get_last();
	if ($error !== null && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
		CliResponse::error(
			'Fatal Error: ' . $error['message'],
			array(
				'file' => $error['file'],
				'line' => $error['line'],
				'type' => $error['type']
			),
			1
		);
	}
});

/**
 * Command-Line Argument Parser
 */
class CliArgs {
	private $command = 'help';
	private $options = array();
	private $positionals = array();
	private $dryRun = false;
	private $format = 'json';

	public function __construct(array $argv) {
		array_shift($argv); // Remove script name

		while (!empty($argv)) {
			$arg = array_shift($argv);

			if ($arg === '--dry-run') {
				$this->dryRun = true;
			} elseif (strpos($arg, '--format=') === 0) {
				$this->format = substr($arg, 9);
			} elseif (strpos($arg, '--') === 0) {
				$optionPart = substr($arg, 2);
				$eqPos = strpos($optionPart, '=');
				if ($eqPos !== false) {
					$key = substr($optionPart, 0, $eqPos);
					$val = substr($optionPart, $eqPos + 1);
					$this->options[$key] = $val;
				} else {
					$this->options[$optionPart] = true;
				}
			} elseif (strpos($arg, '-') === 0) {
				$flag = substr($arg, 1);
				$this->options[$flag] = true;
			} else {
				$this->positionals[] = $arg;
			}
		}

		if (!empty($this->positionals)) {
			$this->command = $this->positionals[0];
		}

		if (isset($this->options['help']) || isset($this->options['h'])) {
			$this->command = 'help';
		}

		if (isset($this->options['dry-run'])) {
			$this->dryRun = true;
		}

		if (isset($this->options['format'])) {
			$this->format = $this->options['format'];
		}

		CliResponse::$format = ($this->format === 'text') ? 'text' : 'json';
	}

	public function getCommand() {
		return $this->command;
	}

	public function getOption($name, $default = null) {
		if (array_key_exists($name, $this->options)) {
			return $this->options[$name];
		}
		return $default;
	}

	public function hasOption($name) {
		return array_key_exists($name, $this->options);
	}

	public function isDryRun() {
		return $this->dryRun;
	}

	public function getPayload() {
		if ($this->hasOption('payload')) {
			$raw = $this->getOption('payload');
			$decoded = json_decode($raw, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				CliResponse::error('Invalid JSON provided in --payload: ' . json_last_error_msg(), array(), 1);
			}
			return $decoded;
		}

		if ($this->hasOption('payload-file')) {
			$filePath = $this->getOption('payload-file');
			if (!is_file($filePath) || !is_readable($filePath)) {
				CliResponse::error('Payload file not found or not readable: ' . $filePath, array(), 1);
			}
			$raw = file_get_contents($filePath);
			$decoded = json_decode($raw, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				CliResponse::error('Invalid JSON in payload file: ' . json_last_error_msg(), array(), 1);
			}
			return $decoded;
		}

		return null;
	}
}

/**
 * Headless Admin Bootstrap for LiveStore / OpenCart 3
 */
class HeadlessBootstrap {
	private static $registry;

	public static function init() {
		if (self::$registry !== null) {
			return self::$registry;
		}

		// Calculate root and admin directories
		$cliDir = str_replace('\\', '/', __DIR__);
		$rootDir = dirname($cliDir);
		$adminDir = $rootDir . '/admin';

		// Load admin config or fallback to root config
		$adminConfigFile = $adminDir . '/config.php';
		$rootConfigFile  = $rootDir . '/config.php';

		if (is_file($adminConfigFile)) {
			require_once($adminConfigFile);
		} elseif (is_file($rootConfigFile)) {
			require_once($rootConfigFile);
		} else {
			CliResponse::error('OpenCart configuration file not found in admin or root directory.', array(), 1);
		}

		// Define Version & LiveStore constants if missing
		if (!defined('VERSION')) {
			define('VERSION', '3.0.4.5');
		}
		if (!defined('IS_LIVESTORE')) {
			define('IS_LIVESTORE', true);
		}

		// Ensure all path constants are defined
		if (!defined('DIR_APPLICATION')) {
			define('DIR_APPLICATION', $adminDir . '/');
		}
		if (!defined('DIR_SYSTEM')) {
			define('DIR_SYSTEM', $rootDir . '/system/');
		}
		if (!defined('DIR_IMAGE')) {
			define('DIR_IMAGE', $rootDir . '/image/');
		}
		if (!defined('DIR_STORAGE')) {
			define('DIR_STORAGE', $rootDir . '/system/storage/');
		}
		if (!defined('DIR_CATALOG')) {
			define('DIR_CATALOG', $rootDir . '/catalog/');
		}
		if (!defined('DIR_LANGUAGE')) {
			define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
		}
		if (!defined('DIR_TEMPLATE')) {
			define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
		}
		if (!defined('DIR_CONFIG')) {
			define('DIR_CONFIG', DIR_SYSTEM . 'config/');
		}
		if (!defined('DIR_CACHE')) {
			define('DIR_CACHE', DIR_STORAGE . 'cache/');
		}
		if (!defined('DIR_DOWNLOAD')) {
			define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
		}
		if (!defined('DIR_LOGS')) {
			define('DIR_LOGS', DIR_STORAGE . 'logs/');
		}
		if (!defined('DIR_MODIFICATION')) {
			define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
		}
		if (!defined('DIR_SESSION')) {
			define('DIR_SESSION', DIR_STORAGE . 'session/');
		}
		if (!defined('DIR_UPLOAD')) {
			define('DIR_UPLOAD', DIR_STORAGE . 'upload/');
		}

		// CLI Server Environment Sanitization
		if (!isset($_SERVER['REMOTE_ADDR'])) {
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		}
		if (!isset($_SERVER['SERVER_NAME'])) {
			$_SERVER['SERVER_NAME'] = 'localhost';
		}
		if (!isset($_SERVER['HTTP_HOST'])) {
			$_SERVER['HTTP_HOST'] = 'localhost';
		}
		if (!isset($_SERVER['REQUEST_URI'])) {
			$_SERVER['REQUEST_URI'] = '/cli/admin_cli.php';
		}

		// Require OpenCart core startup
		require_once(DIR_SYSTEM . 'startup.php');

		// 1. Registry
		$registry = new \Registry();

		// 2. Config
		$config = new \Config();
		$config->load('default');
		$config->load('admin');
		$registry->set('config', $config);

		// 3. Log
		$log = new \Log($config->get('error_filename'));
		$registry->set('log', $log);

		// 4. Event System
		$event = new \Event($registry);
		$registry->set('event', $event);

		// Register action events from config
		if ($config->has('action_event')) {
			foreach ($config->get('action_event') as $key => $value) {
				foreach ($value as $priority => $action) {
					$event->register($key, new \Action($action), $priority);
				}
			}
		}

		// 5. Loader
		$loader = new \Loader($registry);
		$registry->set('load', $loader);

		// 6. Request
		$request = new \Request();
		if (!isset($request->server['REMOTE_ADDR'])) {
			$request->server['REMOTE_ADDR'] = '127.0.0.1';
		}
		$registry->set('request', $request);

		// 7. Response
		$response = new \Response();
		$registry->set('response', $response);

		// 8. Database
		$db = new \DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
		$registry->set('db', $db);
		$db->query('SET time_zone = \'' . $db->escape(date('P')) . '\'');

		// 9. Load Store Settings from oc_setting
		$settingsQuery = $db->query('SELECT * FROM ' . DB_PREFIX . 'setting WHERE store_id = \'0\'');
		foreach ($settingsQuery->rows as $setting) {
			if (!$setting['serialized']) {
				$config->set($setting['key'], $setting['value']);
			} else {
				$config->set($setting['key'], json_decode($setting['value'], true));
			}
		}

		// 10. Register DB Events (including admin/model hooks for AI Vector sync)
		$eventRows = $db->query('SELECT * FROM ' . DB_PREFIX . 'event WHERE status = \'1\' ORDER BY sort_order ASC');
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

		// 11. Session
		$session = new \Session($config->get('session_engine'), $registry);
		$registry->set('session', $session);

		// 12. Cache
		$cache = new \Cache($config->get('cache_engine'), $config->get('cache_expire'));
		$registry->set('cache', $cache);

		// 13. Url
		$url = new \Url($config->get('site_url'), $config->get('site_ssl'));
		$registry->set('url', $url);

		// 14. Document
		$registry->set('document', new \Document());

		// 15. Language
		$adminLangCode = $config->get('config_admin_language');
		if (!$adminLangCode) {
			$adminLangCode = $config->get('config_language');
		}
		if (!$adminLangCode) {
			$adminLangCode = 'en-gb';
		}

		$langQuery = $db->query('SELECT * FROM ' . DB_PREFIX . 'language WHERE code = \'' . $db->escape($adminLangCode) . '\'');
		if ($langQuery->num_rows) {
			$config->set('config_language_id', (int)$langQuery->row['language_id']);
		} else {
			$firstLang = $db->query('SELECT * FROM ' . DB_PREFIX . 'language WHERE status = \'1\' ORDER BY sort_order ASC LIMIT 1');
			if ($firstLang->num_rows) {
				$config->set('config_language_id', (int)$firstLang->row['language_id']);
				$adminLangCode = $firstLang->row['code'];
			} else {
				$config->set('config_language_id', 1);
			}
		}

		$language = new \Language($adminLangCode);
		$language->load($adminLangCode);
		$registry->set('language', $language);

		// 16. Administrative Session Context & Auth Guard Bypass
		$adminUserQuery = $db->query('SELECT * FROM ' . DB_PREFIX . 'user WHERE status = \'1\' ORDER BY user_id ASC LIMIT 1');
		$adminUserId = $adminUserQuery->num_rows ? (int)$adminUserQuery->row['user_id'] : 1;
		$adminUsername = $adminUserQuery->num_rows ? $adminUserQuery->row['username'] : 'admin';

		$session->data['user_id'] = $adminUserId;
		$session->data['user_token'] = 'cli_token_' . md5(microtime());

		// CLI Administrator User Proxy (Always authorized)
		$cliUser = new class($adminUserId, $adminUsername) {
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

		// 17. Load Core Admin Models dynamically into Registry
		$loader->model('catalog/product');
		$loader->model('catalog/category');
		$loader->model('setting/setting');
		$loader->model('setting/event');
		$loader->model('localisation/language');

		self::$registry = $registry;
		return self::$registry;
	}
}

/**
 * CLI Command Handlers & Dispatcher
 */
class AdminCliApp {
	private $registry;
	private $args;

	public function __construct(CliArgs $args) {
		$this->args = $args;
		$this->registry = HeadlessBootstrap::init();
	}

	public function run() {
		$cmd = $this->args->getCommand();

		switch ($cmd) {
			case 'help':
			case '--help':
			case '-h':
				$this->commandHelp();
				break;

			case 'product:list':
				$this->commandProductList();
				break;

			case 'product:get':
				$this->commandProductGet();
				break;

			case 'product:create':
				$this->commandProductCreate();
				break;

			case 'product:update':
				$this->commandProductUpdate();
				break;

			case 'product:delete':
				$this->commandProductDelete();
				break;

			case 'category:list':
				$this->commandCategoryList();
				break;

			case 'cache:clear':
				$this->commandCacheClear();
				break;

			case 'setting:get':
				$this->commandSettingGet();
				break;

			case 'setting:set':
				$this->commandSettingSet();
				break;

			default:
				CliResponse::invalidCommand($cmd);
				break;
		}
	}

	/**
	 * 1. help: Self-Discovery Machine-Readable JSON Schema
	 */
	private function commandHelp() {
		$schema = array(
			'title'       => 'LiveStore Admin CLI Tool for AI Agents and Administrators',
			'version'     => '1.0.0',
			'environment' => array(
				'opencart_version' => defined('VERSION') ? VERSION : '3.0.3.x',
				'php_version'      => PHP_VERSION,
				'sapi'             => php_sapi_name()
			),
			'global_flags' => array(
				'--format=json|text'  => 'Response envelope formatting (default: json).',
				'--dry-run'           => 'Validate commands and schemas without persisting changes to the database or filesystem.',
				'--help, -h'          => 'Display this discovery schema.'
			),
			'exit_codes' => array(
				'0' => 'Success',
				'1' => 'Runtime Error / Validation Failure',
				'2' => 'Missing Arguments / Invalid Command'
			),
			'envelope_format' => array(
				'status'  => 'success|error',
				'data'    => 'Object or null with execution output',
				'message' => 'Descriptive summary message',
				'errors'  => 'Array of error strings or empty array on success'
			),
			'commands' => array(
				'help' => array(
					'description' => 'Returns this machine-readable schema for AI self-discovery.',
					'options'     => array()
				),
				'product:list' => array(
					'description' => 'Returns paginated product summaries.',
					'options'     => array(
						'--limit'       => array('type' => 'int', 'default' => 20, 'description' => 'Number of items per page'),
						'--page'        => array('type' => 'int', 'default' => 1, 'description' => 'Page number'),
						'--category_id' => array('type' => 'int', 'default' => null, 'description' => 'Filter by category ID'),
						'--status'      => array('type' => 'int', 'default' => null, 'description' => 'Filter by status (0=disabled, 1=enabled)'),
						'--filter_name' => array('type' => 'string', 'default' => null, 'description' => 'Filter by product name')
					)
				),
				'product:get' => array(
					'description' => 'Returns full product data including descriptions, attributes, categories, and vector sync state.',
					'options'     => array(
						'--id' => array('type' => 'int', 'required' => true, 'description' => 'Target product ID')
					)
				),
				'product:create' => array(
					'description' => 'Creates a new product with full event hooks.',
					'options'     => array(
						'--payload'      => array('type' => 'json_string', 'description' => 'JSON payload'),
						'--payload-file' => array('type' => 'filepath', 'description' => 'Path to JSON payload file'),
						'--dry-run'      => array('type' => 'flag', 'description' => 'Validate schema without inserting')
					),
					'required_payload_fields' => array('name', 'model', 'price'),
					'example_payload' => array(
						'name'             => 'Studio Pro Microphone',
						'model'            => 'SPM-900',
						'price'            => 199.99,
						'quantity'         => 50,
						'status'           => 1,
						'product_category' => array(20, 25),
						'description'      => '<p>High-end studio microphone for recording and streaming.</p>'
					)
				),
				'product:update' => array(
					'description' => 'Deep-merges and updates target fields for an existing product.',
					'options'     => array(
						'--id'           => array('type' => 'int', 'required' => true, 'description' => 'Product ID to update'),
						'--payload'      => array('type' => 'json_string', 'description' => 'JSON payload with updated fields'),
						'--payload-file' => array('type' => 'filepath', 'description' => 'Path to JSON payload file'),
						'--dry-run'      => array('type' => 'flag', 'description' => 'Validate changes without saving')
					)
				),
				'product:delete' => array(
					'description' => 'Removes a product via ModelCatalogProduct::deleteProduct.',
					'options'     => array(
						'--id'      => array('type' => 'int', 'required' => true, 'description' => 'Product ID to remove'),
						'--dry-run' => array('type' => 'flag', 'description' => 'Simulate deletion without committing')
					)
				),
				'category:list' => array(
					'description' => 'Returns category tree and flat listing with IDs, names, and parent mappings.',
					'options'     => array(
						'--parent_id' => array('type' => 'int', 'default' => null, 'description' => 'Filter by parent category ID'),
						'--format'    => array('type' => 'string', 'default' => 'all', 'description' => 'Output format (all|tree|flat)')
					)
				),
				'cache:clear' => array(
					'description' => 'Clears Twig template cache, OCMOD modifier cache, and system database caches.',
					'options'     => array(
						'--type'    => array('type' => 'string', 'default' => 'all', 'description' => 'Cache type: all|template|modification|system'),
						'--dry-run' => array('type' => 'flag', 'description' => 'Scan and count files without deleting')
					)
				),
				'setting:get' => array(
					'description' => 'Reads setting value by key or entire group by code from oc_setting.',
					'options'     => array(
						'--code'     => array('type' => 'string', 'description' => 'Setting group code'),
						'--key'      => array('type' => 'string', 'description' => 'Specific setting key'),
						'--store_id' => array('type' => 'int', 'default' => 0, 'description' => 'Store ID')
					)
				),
				'setting:set' => array(
					'description' => 'Safely updates or creates setting key/value or code group in oc_setting.',
					'options'     => array(
						'--code'     => array('type' => 'string', 'required' => true, 'description' => 'Setting group code'),
						'--key'      => array('type' => 'string', 'description' => 'Setting key'),
						'--value'    => array('type' => 'string|json', 'description' => 'Setting value'),
						'--store_id' => array('type' => 'int', 'default' => 0, 'description' => 'Store ID'),
						'--payload'  => array('type' => 'json_string', 'description' => 'Batch key-value dictionary'),
						'--dry-run'  => array('type' => 'flag', 'description' => 'Simulate mutation without persisting')
					)
				)
			)
		);

		CliResponse::success($schema, 'Available commands and machine-readable discovery schema.');
	}

	/**
	 * 2. product:list: Filtered & Paginated List of Products
	 */
	private function commandProductList() {
		$modelProduct = $this->registry->get('model_catalog_product');

		$page = max(1, (int)$this->args->getOption('page', 1));
		$limit = max(1, min(200, (int)$this->args->getOption('limit', 20)));
		$start = ($page - 1) * $limit;

		$filterData = array(
			'start' => $start,
			'limit' => $limit,
			'sort'  => 'p.product_id',
			'order' => 'DESC'
		);

		if ($this->args->hasOption('filter_name')) {
			$filterData['filter_name'] = $this->args->getOption('filter_name');
		}

		if ($this->args->hasOption('status')) {
			$filterData['filter_status'] = (int)$this->args->getOption('status');
		}

		if ($this->args->hasOption('category_id')) {
			$filterData['filter_category'] = (int)$this->args->getOption('category_id');
		}

		$products = $modelProduct->getProducts($filterData);
		$total = (int)$modelProduct->getTotalProducts($filterData);

		$summaries = array();
		foreach ($products as $p) {
			$summaries[] = array(
				'product_id' => (int)$p['product_id'],
				'name'       => isset($p['name']) ? html_entity_decode($p['name'], ENT_QUOTES, 'UTF-8') : '',
				'model'      => $p['model'],
				'price'      => (float)$p['price'],
				'quantity'   => (int)$p['quantity'],
				'status'     => (int)$p['status'],
				'image'      => !empty($p['image']) ? $p['image'] : null,
				'date_added' => $p['date_added']
			);
		}

		$data = array(
			'total'        => $total,
			'page'         => $page,
			'limit'        => $limit,
			'total_pages'  => ceil($total / $limit),
			'count'        => count($summaries),
			'products'     => $summaries
		);

		CliResponse::success($data, 'Retrieved ' . count($summaries) . ' products.');
	}

	/**
	 * 3. product:get: Detailed Product Data with Descriptions, Attributes, and Vector Sync State
	 */
	private function commandProductGet() {
		$productId = (int)$this->args->getOption('id');
		if ($productId <= 0) {
			CliResponse::missingArgument('The --id option is required and must be a positive integer.', '--id');
		}

		$modelProduct = $this->registry->get('model_catalog_product');
		$product = $modelProduct->getProduct($productId);

		if (!$product) {
			CliResponse::error('Product not found: ' . $productId, array(), 1);
		}

		$descriptions = $modelProduct->getProductDescriptions($productId);
		$categories   = $modelProduct->getProductCategories($productId);
		$mainCategory = $modelProduct->getProductMainCategoryId($productId);
		$attributes   = $modelProduct->getProductAttributes($productId);
		$options      = $modelProduct->getProductOptions($productId);
		$images       = $modelProduct->getProductImages($productId);
		$specials     = $modelProduct->getProductSpecials($productId);
		$discounts    = $modelProduct->getProductDiscounts($productId);
		$rewards      = $modelProduct->getProductRewards($productId);
		$stores       = $modelProduct->getProductStores($productId);
		$seoUrls      = $modelProduct->getProductSeoUrls($productId);
		$related      = $modelProduct->getProductRelated($productId);

		// Resolve Vector Sync State if available
		$vectorSyncState = $this->resolveVectorSyncState($productId);

		$result = array(
			'product_id'        => (int)$product['product_id'],
			'model'             => $product['model'],
			'sku'               => $product['sku'],
			'upc'               => $product['upc'],
			'ean'               => $product['ean'],
			'jan'               => $product['jan'],
			'isbn'              => $product['isbn'],
			'mpn'               => $product['mpn'],
			'location'          => $product['location'],
			'quantity'          => (int)$product['quantity'],
			'stock_status_id'   => (int)$product['stock_status_id'],
			'image'             => $product['image'],
			'manufacturer_id'   => (int)$product['manufacturer_id'],
			'shipping'          => (int)$product['shipping'],
			'price'             => (float)$product['price'],
			'points'            => (int)$product['points'],
			'tax_class_id'      => (int)$product['tax_class_id'],
			'date_available'    => $product['date_available'],
			'weight'            => (float)$product['weight'],
			'weight_class_id'   => (int)$product['weight_class_id'],
			'length'            => (float)$product['length'],
			'width'             => (float)$product['width'],
			'height'            => (float)$product['height'],
			'length_class_id'   => (int)$product['length_class_id'],
			'subtract'          => (int)$product['subtract'],
			'minimum'           => (int)$product['minimum'],
			'sort_order'        => (int)$product['sort_order'],
			'status'            => (int)$product['status'],
			'date_added'        => $product['date_added'],
			'date_modified'     => $product['date_modified'],
			'main_category_id'  => (int)$mainCategory,
			'descriptions'      => $descriptions,
			'categories'        => $categories,
			'attributes'        => $attributes,
			'options'           => $options,
			'images'            => $images,
			'specials'          => $specials,
			'discounts'         => $discounts,
			'rewards'           => $rewards,
			'stores'            => $stores,
			'seo_urls'          => $seoUrls,
			'related_ids'       => $related,
			'vector_sync'       => $vectorSyncState
		);

		CliResponse::success($result, 'Product #' . $productId . ' fetched successfully.');
	}

	/**
	 * 4. product:create: Validate & Add Product via Model
	 */
	private function commandProductCreate() {
		$payload = $this->args->getPayload();
		if ($payload === null) {
			CliResponse::missingArgument('Provide product data via --payload=\'<json>\' or --payload-file=<path>.', '--payload');
		}

		$errors = $this->validateProductPayload($payload, true);
		if (!empty($errors)) {
			CliResponse::error('Product payload validation failed.', $errors, 1);
		}

		$preparedData = $this->normalizeProductData($payload);

		if ($this->args->isDryRun()) {
			CliResponse::success(
				array(
					'dry_run'       => true,
					'action'        => 'product:create',
					'prepared_data' => $preparedData
				),
				'[DRY-RUN] Product validation passed. No database record was created.'
			);
		}

		$modelProduct = $this->registry->get('model_catalog_product');
		$productId = $modelProduct->addProduct($preparedData);

		CliResponse::success(
			array(
				'product_id' => (int)$productId,
				'model'      => $preparedData['model'],
				'price'      => (float)$preparedData['price'],
				'status'     => (int)$preparedData['status']
			),
			'Product created successfully with ID #' . $productId . '.'
		);
	}

	/**
	 * 5. product:update: Deep-Merge & Update Existing Product via Model
	 */
	private function commandProductUpdate() {
		$productId = (int)$this->args->getOption('id');
		if ($productId <= 0) {
			CliResponse::missingArgument('The --id option is required and must be a positive integer.', '--id');
		}

		$payload = $this->args->getPayload();
		if ($payload === null) {
			CliResponse::missingArgument('Provide update fields via --payload=\'<json>\' or --payload-file=<path>.', '--payload');
		}

		$modelProduct = $this->registry->get('model_catalog_product');
		$existing = $modelProduct->getProduct($productId);
		if (!$existing) {
			CliResponse::error('Cannot update. Product not found: ' . $productId, array(), 1);
		}

		$mergedData = $this->mergeProductData($productId, $existing, $payload);

		if ($this->args->isDryRun()) {
			CliResponse::success(
				array(
					'dry_run'        => true,
					'action'         => 'product:update',
					'product_id'     => $productId,
					'updated_fields' => array_keys($payload),
					'merged_data'    => $mergedData
				),
				'[DRY-RUN] Product update validation passed for #' . $productId . '. No changes persisted.'
			);
		}

		$modelProduct->editProduct($productId, $mergedData);

		CliResponse::success(
			array(
				'product_id'     => $productId,
				'updated_fields' => array_keys($payload),
				'model'          => $mergedData['model'],
				'price'          => (float)$mergedData['price'],
				'quantity'       => (int)$mergedData['quantity'],
				'status'         => (int)$mergedData['status']
			),
			'Product #' . $productId . ' updated successfully.'
		);
	}

	/**
	 * 6. product:delete: Remove Product via Model
	 */
	private function commandProductDelete() {
		$productId = (int)$this->args->getOption('id');
		if ($productId <= 0) {
			CliResponse::missingArgument('The --id option is required and must be a positive integer.', '--id');
		}

		$modelProduct = $this->registry->get('model_catalog_product');
		$existing = $modelProduct->getProduct($productId);
		if (!$existing) {
			CliResponse::error('Cannot delete. Product not found: ' . $productId, array(), 1);
		}

		if ($this->args->isDryRun()) {
			CliResponse::success(
				array(
					'dry_run'    => true,
					'action'     => 'product:delete',
					'product_id' => $productId,
					'model'      => $existing['model']
				),
				'[DRY-RUN] Product #' . $productId . ' exists and would be deleted. No changes persisted.'
			);
		}

		$modelProduct->deleteProduct($productId);

		CliResponse::success(
			array(
				'product_id' => $productId,
				'model'      => $existing['model']
			),
			'Product #' . $productId . ' deleted successfully.'
		);
	}

	/**
	 * 7. category:list: Flat & Hierarchical Category Tree
	 */
	private function commandCategoryList() {
		$modelCategory = $this->registry->get('model_catalog_category');
		$parentIdFilter = $this->args->hasOption('parent_id') ? (int)$this->args->getOption('parent_id') : null;
		$format = $this->args->getOption('format', 'all');

		$categories = $modelCategory->getCategories();

		$flatList = array();
		$byParent = array();

		foreach ($categories as $cat) {
			$cleanName = str_replace(array('&nbsp;&nbsp;&gt;&nbsp;&nbsp;', '&gt;', chr(194) . chr(160)), array(' > ', '>', ' '), $cat['name']);
			$cleanName = html_entity_decode($cleanName, ENT_QUOTES, 'UTF-8');
			$cleanName = trim(preg_replace('/\s+/', ' ', str_replace(chr(194) . chr(160), ' ', $cleanName)));

			$item = array(
				'category_id' => (int)$cat['category_id'],
				'parent_id'   => (int)$cat['parent_id'],
				'name'        => $cleanName,
				'sort_order'  => (int)$cat['sort_order'],
				'status'      => isset($cat['status']) ? (int)$cat['status'] : 1
			);

			$flatList[] = $item;
			$byParent[$item['parent_id']][] = $item;
		}

		$buildTree = function($parentId) use (&$buildTree, &$byParent) {
			$branch = array();
			if (isset($byParent[$parentId])) {
				foreach ($byParent[$parentId] as $child) {
					$node = $child;
					$children = $buildTree($child['category_id']);
					if (!empty($children)) {
						$node['children'] = $children;
					} else {
						$node['children'] = array();
					}
					$branch[] = $node;
				}
			}
			return $branch;
		};

		$rootParent = ($parentIdFilter !== null) ? $parentIdFilter : 0;
		$tree = $buildTree($rootParent);

		if ($format === 'flat') {
			$data = array('total' => count($flatList), 'categories' => $flatList);
		} elseif ($format === 'tree') {
			$data = array('total' => count($tree), 'tree' => $tree);
		} else {
			$data = array(
				'total'      => count($flatList),
				'categories' => $flatList,
				'tree'       => $tree
			);
		}

		CliResponse::success($data, 'Retrieved ' . count($flatList) . ' categories.');
	}

	/**
	 * 8. cache:clear: Template (Twig), Modifier (OCMOD), & Database Caches
	 */
	private function commandCacheClear() {
		$targetType = $this->args->getOption('type', 'all');
		$dryRun = $this->args->isDryRun();

		$cacheDirs = array(
			DIR_CACHE,
			DIR_SYSTEM . 'storage/cache/'
		);
		$cacheDirs = array_unique(array_filter($cacheDirs, 'is_dir'));

		$clearedTargets = array();
		$totalFilesDeleted = 0;
		$totalDirsDeleted = 0;

		// 1. Template Cache (Twig)
		if ($targetType === 'all' || $targetType === 'template') {
			$twigDirs = array();
			foreach ($cacheDirs as $baseDir) {
				$templateDir = $baseDir . 'template';
				if (is_dir($templateDir)) {
					$twigDirs[] = $templateDir;
				}
			}

			$templateStats = $this->purgeDirectoryContents($twigDirs, $dryRun, true);
			$totalFilesDeleted += $templateStats['files'];
			$totalDirsDeleted += $templateStats['directories'];
			$clearedTargets['template'] = $templateStats;
		}

		// 2. System DB / Data File Cache
		if ($targetType === 'all' || $targetType === 'system') {
			$systemFiles = array();
			foreach ($cacheDirs as $baseDir) {
				$matched = glob($baseDir . 'cache.*');
				if ($matched) {
					$systemFiles = array_merge($systemFiles, $matched);
				}
			}

			$systemStats = array('files' => 0, 'dry_run' => $dryRun);
			foreach ($systemFiles as $file) {
				if (is_file($file)) {
					$systemStats['files']++;
					if (!$dryRun) {
						@unlink($file);
					}
				}
			}
			$totalFilesDeleted += $systemStats['files'];
			$clearedTargets['system'] = $systemStats;
		}

		// 3. Modifier Cache (OCMOD)
		if ($targetType === 'all' || $targetType === 'modification') {
			$modDirs = array();
			if (defined('DIR_MODIFICATION') && is_dir(DIR_MODIFICATION)) {
				$modDirs[] = DIR_MODIFICATION;
			}
			$modStats = $this->purgeDirectoryContents($modDirs, $dryRun, false, array('index.html'));
			$totalFilesDeleted += $modStats['files'];
			$totalDirsDeleted += $modStats['directories'];
			$clearedTargets['modification'] = $modStats;
		}

		$msg = $dryRun ? '[DRY-RUN] Scanned caches. Found ' . $totalFilesDeleted . ' files across selected caches.'
		               : 'Cleared ' . $totalFilesDeleted . ' cache files successfully.';

		CliResponse::success(
			array(
				'dry_run'               => $dryRun,
				'target_type'           => $targetType,
				'total_files_affected'  => $totalFilesDeleted,
				'total_dirs_affected'   => $totalDirsDeleted,
				'details'               => $clearedTargets
			),
			$msg
		);
	}

	/**
	 * 9. setting:get: Read Store Setting
	 */
	private function commandSettingGet() {
		$code = $this->args->getOption('code');
		$key  = $this->args->getOption('key');
		$storeId = (int)$this->args->getOption('store_id', 0);

		if (!$code && !$key) {
			CliResponse::missingArgument('Provide either --code=<code> or --key=<key>.', '--code');
		}

		$modelSetting = $this->registry->get('model_setting_setting');

		if ($key !== null) {
			$rawVal = $modelSetting->getSettingValue($key, $storeId);
			if ($rawVal === null) {
				CliResponse::error('Setting key not found: ' . $key . ' for store_id ' . $storeId, array(), 1);
			}

			$parsedVal = $rawVal;
			$decoded = json_decode($rawVal, true);
			if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
				$parsedVal = $decoded;
			}

			CliResponse::success(
				array(
					'key'      => $key,
					'value'    => $parsedVal,
					'store_id' => $storeId
				),
				'Setting value retrieved successfully.'
			);
		}

		if ($code !== null) {
			$settings = $modelSetting->getSetting($code, $storeId);
			CliResponse::success(
				array(
					'code'     => $code,
					'store_id' => $storeId,
					'count'    => count($settings),
					'settings' => $settings
				),
				'Retrieved ' . count($settings) . ' settings for code \'' . $code . '\'.'
			);
		}
	}

	/**
	 * 10. setting:set: Safely Mutate Store Setting
	 */
	private function commandSettingSet() {
		$code    = $this->args->getOption('code');
		$key     = $this->args->getOption('key');
		$value   = $this->args->getOption('value');
		$storeId = (int)$this->args->getOption('store_id', 0);
		$payload = $this->args->getPayload();

		if (!$code) {
			CliResponse::missingArgument('The --code option is required.', '--code');
		}

		if ($key === null && $payload === null) {
			CliResponse::missingArgument('Provide either --key=<name> and --value=<val>, or --payload=\'<json>\'.', '--key');
		}

		$modelSetting = $this->registry->get('model_setting_setting');
		$db = $this->registry->get('db');

		if ($this->args->isDryRun()) {
			CliResponse::success(
				array(
					'dry_run'  => true,
					'code'     => $code,
					'key'      => $key,
					'value'    => ($payload !== null) ? $payload : $value,
					'store_id' => $storeId
				),
				'[DRY-RUN] Setting update validated. No changes written to oc_setting.'
			);
		}

		if ($payload !== null && is_array($payload)) {
			// Batch edit code group
			$modelSetting->editSetting($code, $payload, $storeId);
			CliResponse::success(
				array(
					'code'     => $code,
					'store_id' => $storeId,
					'settings' => $payload
				),
				'Settings for code \'' . $code . '\' updated successfully.'
			);
		}

		// Single Key/Value mutation
		$existingQuery = $db->query('SELECT setting_id FROM ' . DB_PREFIX . 'setting WHERE store_id = \'' . (int)$storeId . '\' AND `code` = \'' . $db->escape($code) . '\' AND `key` = \'' . $db->escape($key) . '\'');

		if ($existingQuery->num_rows) {
			$modelSetting->editSettingValue($code, $key, $value, $storeId);
		} else {
			$serialized = (is_array($value) || is_object($value)) ? 1 : 0;
			$valString = $serialized ? json_encode($value) : (string)$value;
			$db->query('INSERT INTO ' . DB_PREFIX . 'setting SET store_id = \'' . (int)$storeId . '\', `code` = \'' . $db->escape($code) . '\', `key` = \'' . $db->escape($key) . '\', `value` = \'' . $db->escape($valString) . '\', serialized = \'' . (int)$serialized . '\'');
		}

		CliResponse::success(
			array(
				'code'     => $code,
				'key'      => $key,
				'value'    => $value,
				'store_id' => $storeId
			),
			'Setting \'' . $key . '\' updated successfully.'
		);
	}

	/**
	 * Helper: Resolve Vector Sync State for AI agents
	 */
	private function resolveVectorSyncState($productId) {
		$db = $this->registry->get('db');

		// 1. Check for dedicated vector tables
		$vectorTables = array(
			DB_PREFIX . 'product_vector_status',
			DB_PREFIX . 'product_vector',
			DB_PREFIX . 'vector_sync',
			DB_PREFIX . 'ai_vector_sync'
		);

		foreach ($vectorTables as $tbl) {
			$checkTable = $db->query('SHOW TABLES LIKE \'' . $db->escape($tbl) . '\'');
			if ($checkTable->num_rows) {
				$syncRow = $db->query('SELECT * FROM `' . $tbl . '` WHERE product_id = \'' . (int)$productId . '\' LIMIT 1');
				if ($syncRow->num_rows) {
					return array(
						'available' => true,
						'synced'    => true,
						'table'     => $tbl,
						'data'      => $syncRow->row
					);
				}
				return array(
					'available' => true,
					'synced'    => false,
					'table'     => $tbl,
					'data'      => null
				);
			}
		}

		// 2. Check if vector events are registered in oc_event
		$eventCheck = $db->query('SELECT * FROM ' . DB_PREFIX . 'event WHERE `trigger` LIKE \'%vector%\' OR `action` LIKE \'%vector%\'');
		$vectorHooksActive = ($eventCheck->num_rows > 0);

		return array(
			'available'          => $vectorHooksActive,
			'synced'             => null,
			'vector_hooks_count' => $eventCheck->num_rows,
			'notice'             => 'No dedicated product vector table found; automatic sync triggered via model event listeners.'
		);
	}

	/**
	 * Helper: Payload Schema Validation
	 */
	private function validateProductPayload(array $payload, $isCreate = true) {
		$errors = array();

		if ($isCreate) {
			if (empty($payload['model'])) {
				$errors[] = 'Field \'model\' is required.';
			}
			if (!isset($payload['price'])) {
				$errors[] = 'Field \'price\' is required.';
			} elseif (!is_numeric($payload['price']) || $payload['price'] < 0) {
				$errors[] = 'Field \'price\' must be a non-negative number.';
			}

			$hasName = !empty($payload['name']);
			$hasDescriptions = !empty($payload['product_description']) && is_array($payload['product_description']);

			if (!$hasName && !$hasDescriptions) {
				$errors[] = 'Either \'name\' or \'product_description\' with localized name is required.';
			}
		} else {
			if (isset($payload['price']) && (!is_numeric($payload['price']) || $payload['price'] < 0)) {
				$errors[] = 'Field \'price\' must be a non-negative number.';
			}
		}

		return $errors;
	}

	/**
	 * Helper: Normalize Product Data with Defaults for ModelCatalogProduct
	 */
	private function normalizeProductData(array $payload) {
		$config = $this->registry->get('config');
		$db = $this->registry->get('db');

		// Get all active store languages
		$languages = array();
		$langQuery = $db->query('SELECT language_id FROM ' . DB_PREFIX . 'language WHERE status = \'1\'');
		foreach ($langQuery->rows as $l) {
			$languages[] = (int)$l['language_id'];
		}
		if (empty($languages)) {
			$languages[] = (int)$config->get('config_language_id');
		}

		// Normalize descriptions
		$productDescription = array();
		if (!empty($payload['product_description']) && is_array($payload['product_description'])) {
			foreach ($languages as $langId) {
				if (isset($payload['product_description'][$langId])) {
					$desc = $payload['product_description'][$langId];
					$productDescription[$langId] = array(
						'name'             => isset($desc['name']) ? (string)$desc['name'] : '',
						'description'      => isset($desc['description']) ? (string)$desc['description'] : '',
						'tag'              => isset($desc['tag']) ? (string)$desc['tag'] : '',
						'meta_title'       => !empty($desc['meta_title']) ? (string)$desc['meta_title'] : (isset($desc['name']) ? (string)$desc['name'] : ''),
						'meta_h1'          => !empty($desc['meta_h1']) ? (string)$desc['meta_h1'] : (isset($desc['name']) ? (string)$desc['name'] : ''),
						'meta_description' => isset($desc['meta_description']) ? (string)$desc['meta_description'] : '',
						'meta_keyword'     => isset($desc['meta_keyword']) ? (string)$desc['meta_keyword'] : ''
					);
				} elseif (!empty($payload['name'])) {
					$productDescription[$langId] = array(
						'name'             => (string)$payload['name'],
						'description'      => isset($payload['description']) ? (string)$payload['description'] : '',
						'tag'              => isset($payload['tag']) ? (string)$payload['tag'] : '',
						'meta_title'       => (string)$payload['name'],
						'meta_h1'          => (string)$payload['name'],
						'meta_description' => isset($payload['meta_description']) ? (string)$payload['meta_description'] : '',
						'meta_keyword'     => isset($payload['meta_keyword']) ? (string)$payload['meta_keyword'] : ''
					);
				}
			}
		} else {
			$name = isset($payload['name']) ? (string)$payload['name'] : '';
			$desc = isset($payload['description']) ? (string)$payload['description'] : '';
			foreach ($languages as $langId) {
				$productDescription[$langId] = array(
					'name'             => $name,
					'description'      => $desc,
					'tag'              => isset($payload['tag']) ? (string)$payload['tag'] : '',
					'meta_title'       => $name,
					'meta_h1'          => $name,
					'meta_description' => isset($payload['meta_description']) ? (string)$payload['meta_description'] : '',
					'meta_keyword'     => isset($payload['meta_keyword']) ? (string)$payload['meta_keyword'] : ''
				);
			}
		}

		$data = array(
			'model'              => (string)$payload['model'],
			'sku'                => isset($payload['sku']) ? (string)$payload['sku'] : '',
			'upc'                => isset($payload['upc']) ? (string)$payload['upc'] : '',
			'ean'                => isset($payload['ean']) ? (string)$payload['ean'] : '',
			'jan'                => isset($payload['jan']) ? (string)$payload['jan'] : '',
			'isbn'               => isset($payload['isbn']) ? (string)$payload['isbn'] : '',
			'mpn'                => isset($payload['mpn']) ? (string)$payload['mpn'] : '',
			'location'           => isset($payload['location']) ? (string)$payload['location'] : '',
			'certification_link' => isset($payload['certification_link']) ? (string)$payload['certification_link'] : '',
			'quantity'           => isset($payload['quantity']) ? (int)$payload['quantity'] : 0,
			'minimum'            => isset($payload['minimum']) ? (int)$payload['minimum'] : 1,
			'subtract'           => isset($payload['subtract']) ? (int)$payload['subtract'] : 1,
			'stock_status_id'    => isset($payload['stock_status_id']) ? (int)$payload['stock_status_id'] : (int)$config->get('config_stock_status_id'),
			'date_available'     => isset($payload['date_available']) ? (string)$payload['date_available'] : date('Y-m-d'),
			'manufacturer_id'    => isset($payload['manufacturer_id']) ? (int)$payload['manufacturer_id'] : 0,
			'shipping'           => isset($payload['shipping']) ? (int)$payload['shipping'] : 1,
			'price'              => isset($payload['price']) ? (float)$payload['price'] : 0.00,
			'points'             => isset($payload['points']) ? (int)$payload['points'] : 0,
			'weight'             => isset($payload['weight']) ? (float)$payload['weight'] : 0.0,
			'weight_class_id'    => isset($payload['weight_class_id']) ? (int)$payload['weight_class_id'] : (int)$config->get('config_weight_class_id'),
			'length'             => isset($payload['length']) ? (float)$payload['length'] : 0.0,
			'width'              => isset($payload['width']) ? (float)$payload['width'] : 0.0,
			'height'             => isset($payload['height']) ? (float)$payload['height'] : 0.0,
			'length_class_id'    => isset($payload['length_class_id']) ? (int)$payload['length_class_id'] : (int)$config->get('config_length_class_id'),
			'status'             => isset($payload['status']) ? (int)$payload['status'] : 1,
			'noindex'            => isset($payload['noindex']) ? (int)$payload['noindex'] : 0,
			'tax_class_id'       => isset($payload['tax_class_id']) ? (int)$payload['tax_class_id'] : 0,
			'sort_order'         => isset($payload['sort_order']) ? (int)$payload['sort_order'] : 0,
			'product_description'=> $productDescription,
			'product_store'      => isset($payload['product_store']) ? (array)$payload['product_store'] : array(0),
			'product_category'   => isset($payload['product_category']) ? (array)$payload['product_category'] : array(),
			'main_category_id'   => isset($payload['main_category_id']) ? (int)$payload['main_category_id'] : 0
		);

		if (isset($payload['image'])) {
			$data['image'] = (string)$payload['image'];
		}

		if (isset($payload['product_attribute'])) {
			$data['product_attribute'] = (array)$payload['product_attribute'];
		}
		if (isset($payload['product_option'])) {
			$data['product_option'] = (array)$payload['product_option'];
		}
		if (isset($payload['product_discount'])) {
			$data['product_discount'] = (array)$payload['product_discount'];
		}
		if (isset($payload['product_special'])) {
			$data['product_special'] = (array)$payload['product_special'];
		}
		if (isset($payload['product_image'])) {
			$data['product_image'] = (array)$payload['product_image'];
		}

		return $data;
	}

	/**
	 * Helper: Merge Partial Update Payload onto Existing Product Data
	 */
	private function mergeProductData($productId, array $existing, array $payload) {
		$modelProduct = $this->registry->get('model_catalog_product');

		// Fetch relational structures from DB
		$descriptions = $modelProduct->getProductDescriptions($productId);
		$categories   = $modelProduct->getProductCategories($productId);
		$mainCategory = $modelProduct->getProductMainCategoryId($productId);
		$stores       = $modelProduct->getProductStores($productId);
		$attributes   = $modelProduct->getProductAttributes($productId);
		$options      = $modelProduct->getProductOptions($productId);
		$discounts    = $modelProduct->getProductDiscounts($productId);
		$specials     = $modelProduct->getProductSpecials($productId);
		$images       = $modelProduct->getProductImages($productId);
		$downloads    = $modelProduct->getProductDownloads($productId);
		$filters      = $modelProduct->getProductFilters($productId);
		$related      = $modelProduct->getProductRelated($productId);
		$rewards      = $modelProduct->getProductRewards($productId);
		$seoUrls      = $modelProduct->getProductSeoUrls($productId);
		$layouts      = $modelProduct->getProductLayouts($productId);

		// Handle localized name/description changes
		if (!empty($payload['name']) || !empty($payload['description'])) {
			foreach ($descriptions as $langId => &$desc) {
				if (!empty($payload['name'])) {
					$desc['name'] = (string)$payload['name'];
					if (empty($desc['meta_title'])) {
						$desc['meta_title'] = (string)$payload['name'];
					}
					if (empty($desc['meta_h1'])) {
						$desc['meta_h1'] = (string)$payload['name'];
					}
				}
				if (isset($payload['description'])) {
					$desc['description'] = (string)$payload['description'];
				}
			}
		}

		if (isset($payload['product_description']) && is_array($payload['product_description'])) {
			foreach ($payload['product_description'] as $langId => $newDesc) {
				if (isset($descriptions[$langId])) {
					$descriptions[$langId] = array_merge($descriptions[$langId], $newDesc);
				} else {
					$descriptions[$langId] = $newDesc;
				}
			}
		}

		$data = array(
			'model'              => isset($payload['model']) ? (string)$payload['model'] : $existing['model'],
			'sku'                => isset($payload['sku']) ? (string)$payload['sku'] : $existing['sku'],
			'upc'                => isset($payload['upc']) ? (string)$payload['upc'] : $existing['upc'],
			'ean'                => isset($payload['ean']) ? (string)$payload['ean'] : $existing['ean'],
			'jan'                => isset($payload['jan']) ? (string)$payload['jan'] : $existing['jan'],
			'isbn'               => isset($payload['isbn']) ? (string)$payload['isbn'] : $existing['isbn'],
			'mpn'                => isset($payload['mpn']) ? (string)$payload['mpn'] : $existing['mpn'],
			'location'           => isset($payload['location']) ? (string)$payload['location'] : $existing['location'],
			'certification_link' => isset($payload['certification_link']) ? (string)$payload['certification_link'] : (isset($existing['certification_link']) ? $existing['certification_link'] : ''),
			'quantity'           => isset($payload['quantity']) ? (int)$payload['quantity'] : (int)$existing['quantity'],
			'minimum'            => isset($payload['minimum']) ? (int)$payload['minimum'] : (int)$existing['minimum'],
			'subtract'           => isset($payload['subtract']) ? (int)$payload['subtract'] : (int)$existing['subtract'],
			'stock_status_id'    => isset($payload['stock_status_id']) ? (int)$payload['stock_status_id'] : (int)$existing['stock_status_id'],
			'date_available'     => isset($payload['date_available']) ? (string)$payload['date_available'] : $existing['date_available'],
			'manufacturer_id'    => isset($payload['manufacturer_id']) ? (int)$payload['manufacturer_id'] : (int)$existing['manufacturer_id'],
			'shipping'           => isset($payload['shipping']) ? (int)$payload['shipping'] : (int)$existing['shipping'],
			'price'              => isset($payload['price']) ? (float)$payload['price'] : (float)$existing['price'],
			'points'             => isset($payload['points']) ? (int)$payload['points'] : (int)$existing['points'],
			'weight'             => isset($payload['weight']) ? (float)$payload['weight'] : (float)$existing['weight'],
			'weight_class_id'    => isset($payload['weight_class_id']) ? (int)$payload['weight_class_id'] : (int)$existing['weight_class_id'],
			'length'             => isset($payload['length']) ? (float)$payload['length'] : (float)$existing['length'],
			'width'              => isset($payload['width']) ? (float)$payload['width'] : (float)$existing['width'],
			'height'             => isset($payload['height']) ? (float)$payload['height'] : (float)$existing['height'],
			'length_class_id'    => isset($payload['length_class_id']) ? (int)$payload['length_class_id'] : (int)$existing['length_class_id'],
			'status'             => isset($payload['status']) ? (int)$payload['status'] : (int)$existing['status'],
			'noindex'            => isset($payload['noindex']) ? (int)$payload['noindex'] : (isset($existing['noindex']) ? (int)$existing['noindex'] : 0),
			'tax_class_id'       => isset($payload['tax_class_id']) ? (int)$payload['tax_class_id'] : (int)$existing['tax_class_id'],
			'sort_order'         => isset($payload['sort_order']) ? (int)$payload['sort_order'] : (int)$existing['sort_order'],
			'image'              => isset($payload['image']) ? (string)$payload['image'] : $existing['image'],
			'product_description'=> $descriptions,
			'product_category'   => isset($payload['product_category']) ? (array)$payload['product_category'] : $categories,
			'main_category_id'   => isset($payload['main_category_id']) ? (int)$payload['main_category_id'] : (int)$mainCategory,
			'product_store'      => isset($payload['product_store']) ? (array)$payload['product_store'] : (!empty($stores) ? $stores : array(0)),
			'product_attribute'  => isset($payload['product_attribute']) ? (array)$payload['product_attribute'] : $attributes,
			'product_option'     => isset($payload['product_option']) ? (array)$payload['product_option'] : $options,
			'product_discount'   => isset($payload['product_discount']) ? (array)$payload['product_discount'] : $discounts,
			'product_special'    => isset($payload['product_special']) ? (array)$payload['product_special'] : $specials,
			'product_image'      => isset($payload['product_image']) ? (array)$payload['product_image'] : $images,
			'product_download'   => isset($payload['product_download']) ? (array)$payload['product_download'] : $downloads,
			'product_filter'     => isset($payload['product_filter']) ? (array)$payload['product_filter'] : $filters,
			'product_related'    => isset($payload['product_related']) ? (array)$payload['product_related'] : $related,
			'product_reward'     => isset($payload['product_reward']) ? (array)$payload['product_reward'] : $rewards,
			'product_seo_url'    => isset($payload['product_seo_url']) ? (array)$payload['product_seo_url'] : $seoUrls,
			'product_layout'     => isset($payload['product_layout']) ? (array)$payload['product_layout'] : $layouts
		);

		return $data;
	}

	/**
	 * Helper: Purge Directory Contents Recursively
	 */
	private function purgeDirectoryContents(array $directories, $dryRun, $removeSubdirs = true, array $ignoreFiles = array('index.html', '.htaccess', '.gitignore')) {
		$stats = array(
			'files'       => 0,
			'directories' => 0,
			'dry_run'     => $dryRun
		);

		foreach ($directories as $dir) {
			if (!is_dir($dir)) {
				continue;
			}

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($iterator as $item) {
				$subPath = $item->getPathname();
				$basename = $item->getBasename();

				if (in_array($basename, $ignoreFiles)) {
					continue;
				}

				if ($item->isDir()) {
					if ($removeSubdirs) {
						$stats['directories']++;
						if (!$dryRun) {
							@rmdir($subPath);
						}
					}
				} else {
					$stats['files']++;
					if (!$dryRun) {
						@unlink($subPath);
					}
				}
			}
		}

		return $stats;
	}
}

// Execution Entrypoint
$args = new CliArgs($argv);
$app = new AdminCliApp($args);
$app->run();
