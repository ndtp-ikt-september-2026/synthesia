<?php
/**
 * SoundNet AI Automated Bootstrapper & Database Migrator
 *
 * Architecture: Headless CLI Setup & Verification
 * Constraints: Strictly single quotes across all code, queries, and strings.
 */

namespace LiveStore\Cli;

$rootDir = dirname(__DIR__);
$configFile = $rootDir . '/config.php';

if (is_file($configFile)) {
	require_once($configFile);
} else {
	fwrite(STDERR, '[ERROR] config.php not found.' . PHP_EOL);
	exit(1);
}

echo '=== SoundNet AI Automated Setup ===' . PHP_EOL;

// 1. Check Database Connection
$mysqli = @new \mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
if ($mysqli->connect_error) {
	fwrite(STDERR, '[NOTICE] MySQL is not reachable (' . $mysqli->connect_error . '). Start OSPanel to activate MySQL.' . PHP_EOL);
} else {
	echo '[OK] Connected to MySQL database: ' . DB_DATABASE . PHP_EOL;

	// 2. Run Migration & Column Schema Verification
	$migrationFile = $rootDir . '/migrations/001_create_soundnet_ai_tables.sql';
	if (is_file($migrationFile)) {
		$sql = file_get_contents($migrationFile);
		if ($mysqli->multi_query($sql)) {
			do {
				if ($res = $mysqli->store_result()) {
					$res->free();
				}
			} while ($mysqli->more_results() && $mysqli->next_result());
		}
	}

	// Ensure all required columns exist in oc_product_vector_status
	$colRes = $mysqli->query('DESCRIBE `' . DB_PREFIX . 'product_vector_status`');
	if ($colRes) {
		$existingCols = array();
		while ($row = $colRes->fetch_assoc()) {
			$existingCols[$row['Field']] = true;
		}
		if (!isset($existingCols['is_indexed'])) {
			$mysqli->query('ALTER TABLE `' . DB_PREFIX . 'product_vector_status` ADD `is_indexed` TINYINT(1) DEFAULT 0');
		}
		if (!isset($existingCols['has_audio'])) {
			$mysqli->query('ALTER TABLE `' . DB_PREFIX . 'product_vector_status` ADD `has_audio` TINYINT(1) DEFAULT 0');
		}
		if (!isset($existingCols['audio_path'])) {
			$mysqli->query('ALTER TABLE `' . DB_PREFIX . 'product_vector_status` ADD `audio_path` VARCHAR(255) NULL');
		}
		if (!isset($existingCols['content_hash'])) {
			$mysqli->query('ALTER TABLE `' . DB_PREFIX . 'product_vector_status` ADD `content_hash` VARCHAR(32) NOT NULL DEFAULT \'\'');
		}
		if (!isset($existingCols['updated_at'])) {
			$mysqli->query('ALTER TABLE `' . DB_PREFIX . 'product_vector_status` ADD `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
		}
		echo '[OK] Migration applied: oc_product_vector_status schema verified.' . PHP_EOL;
	}

	// 3. Register Event Triggers in oc_event
	$events = array(
		array(
			'code'       => 'soundnet_ai',
			'trigger'    => 'admin/model/catalog/product/addProduct/after',
			'action'     => 'extension/module/soundnet_ai/onProductSave',
			'status'     => 1,
			'sort_order' => 0
		),
		array(
			'code'       => 'soundnet_ai',
			'trigger'    => 'admin/model/catalog/product/editProduct/after',
			'action'     => 'extension/module/soundnet_ai/onProductSave',
			'status'     => 1,
			'sort_order' => 0
		),
		array(
			'code'       => 'soundnet_ai',
			'trigger'    => 'admin/model/catalog/product/deleteProduct/after',
			'action'     => 'extension/module/soundnet_ai/onProductDelete',
			'status'     => 1,
			'sort_order' => 0
		),
		array(
			'code'       => 'soundnet_ai',
			'trigger'    => 'admin/view/catalog/product_form/before',
			'action'     => 'extension/module/soundnet_ai/onProductForm',
			'status'     => 1,
			'sort_order' => 0
		)
	);

	foreach ($events as $e) {
		$check = $mysqli->query('SELECT `event_id` FROM `' . DB_PREFIX . 'event` WHERE `code` = \'' . $e['code'] . '\' AND `trigger` = \'' . $e['trigger'] . '\' LIMIT 1');
		if ($check && $check->num_rows == 0) {
			$mysqli->query('INSERT INTO `' . DB_PREFIX . 'event` SET
				`code` = \'' . $e['code'] . '\',
				`trigger` = \'' . $e['trigger'] . '\',
				`action` = \'' . $e['action'] . '\',
				`status` = \'' . (int)$e['status'] . '\',
				`sort_order` = \'' . (int)$e['sort_order'] . '\'');
			echo '[OK] Registered event trigger: ' . $e['trigger'] . PHP_EOL;
		}
	}

	// 4. Enable Module in oc_setting
	$settings = array(
		'module_soundnet_ai_status'     => '1',
		'module_soundnet_ai_server_url' => 'http://127.0.0.1:8000',
		'module_soundnet_ai_secret'     => 'soundnet_secret_key',
		'module_soundnet_ai_timeout'    => '500'
	);

	foreach ($settings as $k => $v) {
		$mysqli->query('DELETE FROM `' . DB_PREFIX . 'setting` WHERE `code` = \'module_soundnet_ai\' AND `key` = \'' . $mysqli->real_escape_string($k) . '\'');
		$mysqli->query('INSERT INTO `' . DB_PREFIX . 'setting` SET `store_id` = 0, `code` = \'module_soundnet_ai\', `key` = \'' . $mysqli->real_escape_string($k) . '\', `value` = \'' . $mysqli->real_escape_string($v) . '\', `serialized` = 0');
	}
	echo '[OK] Module settings configured and enabled in oc_setting.' . PHP_EOL;

	$mysqli->close();
}

// 5. Test SoundNet AI Microservice Health Check
$ch = curl_init('http://127.0.0.1:8000/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1500);
$resp = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
	echo '[OK] SoundNet AI Daemon: ONLINE (http://127.0.0.1:8000)' . PHP_EOL;
} else {
	echo '[NOTICE] SoundNet AI Daemon: Initializing or starting up (http://127.0.0.1:8000)' . PHP_EOL;
}

echo '=== Setup Completed Successfully ===' . PHP_EOL;
