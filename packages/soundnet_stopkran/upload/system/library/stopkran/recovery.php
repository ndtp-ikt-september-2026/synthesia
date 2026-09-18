<?php
namespace SoundNet\StopKran;

use PDO;
use PDOException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Recovery {
    /**
     * Cached PDO instance
     *
     * @var PDO|null
     */
    protected static $pdo = null;

    /**
     * Fallback database credentials
     *
     * @var array
     */
    protected static $dbConfig = [];

    /**
     * Set explicit DB config for standalone CLI usage
     *
     * @param array $config
     * @return void
     */
    public static function setDbConfig(array $config) {
        self::$dbConfig = $config;
        self::$pdo = null;
    }

    /**
     * Direct PDO connection using OpenCart constants or configured credentials
     *
     * @return PDO
     * @throws PDOException
     */
    public static function getPdo() {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = self::$dbConfig['hostname'] ?? (defined('DB_HOSTNAME') ? DB_HOSTNAME : 'localhost');
        $user = self::$dbConfig['username'] ?? (defined('DB_USERNAME') ? DB_USERNAME : 'root');
        $pass = self::$dbConfig['password'] ?? (defined('DB_PASSWORD') ? DB_PASSWORD : '');
        $name = self::$dbConfig['database'] ?? (defined('DB_DATABASE') ? DB_DATABASE : '');
        $port = self::$dbConfig['port'] ?? (defined('DB_PORT') ? DB_PORT : '3306');

        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 2,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];

        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
            return self::$pdo;
        } catch (PDOException $e) {
            self::logCrash('PDO Connection Failure: ' . $e->getMessage(), ['host' => $host, 'database' => $name]);
            throw $e;
        }
    }

    /**
     * Resolve database table prefix
     *
     * @return string
     */
    public static function getPrefix() {
        if (!empty(self::$dbConfig['prefix'])) {
            return self::$dbConfig['prefix'];
        }

        return defined('DB_PREFIX') ? DB_PREFIX : 'oc_';
    }

    /**
     * Resolve storage directory
     *
     * @return string
     */
    public static function getStorageDir() {
        if (defined('DIR_STORAGE') && is_dir(DIR_STORAGE)) {
            return rtrim(str_replace('\\', '/', DIR_STORAGE), '/') . '/';
        }

        if (defined('DIR_SYSTEM') && is_dir(DIR_SYSTEM . 'storage/')) {
            return rtrim(str_replace('\\', '/', DIR_SYSTEM), '/') . '/storage/';
        }

        $fallback = __DIR__ . '/../../../../storage/';
        if (is_dir($fallback)) {
            return rtrim(str_replace('\\', '/', realpath($fallback)), '/') . '/';
        }

        return rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/') . '/storage/';
    }

    /**
     * Resolve modification cache directory
     *
     * @return string
     */
    public static function getModificationDir() {
        if (defined('DIR_MODIFICATION') && is_dir(DIR_MODIFICATION)) {
            return rtrim(str_replace('\\', '/', DIR_MODIFICATION), '/') . '/';
        }

        return self::getStorageDir() . 'modification/';
    }

    /**
     * Resolve system cache directory
     *
     * @return string
     */
    public static function getCacheDir() {
        if (defined('DIR_CACHE') && is_dir(DIR_CACHE)) {
            return rtrim(str_replace('\\', '/', DIR_CACHE), '/') . '/';
        }

        return self::getStorageDir() . 'cache/';
    }

    /**
     * Resolve logs directory
     *
     * @return string
     */
    public static function getLogsDir() {
        if (defined('DIR_LOGS') && is_dir(DIR_LOGS)) {
            return rtrim(str_replace('\\', '/', DIR_LOGS), '/') . '/';
        }

        return self::getStorageDir() . 'logs/';
    }

    /**
     * Recursively purge modification cache directory
     *
     * @return array
     */
    public static function purgeModificationCache() {
        $dir = self::getModificationDir();
        $deletedFiles = 0;
        $deletedDirs = 0;

        if (!is_dir($dir)) {
            return ['status' => true, 'files' => 0, 'dirs' => 0];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $filename = $item->getFilename();

            // Preserve top-level index.html if exists
            if ($filename === 'index.html' && dirname($path) === rtrim($dir, '/')) {
                continue;
            }

            if ($item->isDir()) {
                if (file_exists($path)) {
                    @rmdir($path);
                    $deletedDirs++;
                }
            } else {
                if (file_exists($path)) {
                    @unlink($path);
                    $deletedFiles++;
                }
            }
        }

        return [
            'status' => true,
            'files'  => $deletedFiles,
            'dirs'   => $deletedDirs
        ];
    }

    /**
     * Recursively purge system cache directory
     *
     * @return array
     */
    public static function purgeSystemCache() {
        $dir = self::getCacheDir();
        $deletedFiles = 0;
        $deletedDirs = 0;

        if (!is_dir($dir)) {
            return ['status' => true, 'files' => 0, 'dirs' => 0];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $filename = $item->getFilename();

            if ($filename === 'index.html' && dirname($path) === rtrim($dir, '/')) {
                continue;
            }

            if ($item->isDir()) {
                if (file_exists($path)) {
                    @rmdir($path);
                    $deletedDirs++;
                }
            } else {
                if (file_exists($path)) {
                    @unlink($path);
                    $deletedFiles++;
                }
            }
        }

        return [
            'status' => true,
            'files'  => $deletedFiles,
            'dirs'   => $deletedDirs
        ];
    }

    /**
     * Compile and write all active OCMOD modifications into modification cache directory
     *
     * @return array
     */
    public static function rebuildModificationCache() {
        $prefix = self::getPrefix();
        $pdo = self::getPdo();
        $modDir = self::getModificationDir();

        $dirCatalog = defined('DIR_CATALOG') ? DIR_CATALOG : (defined('DIR_APPLICATION') ? rtrim(DIR_APPLICATION, '/') . '/../catalog/' : '');
        $dirAdmin = defined('DIR_APPLICATION') && !defined('DIR_CATALOG') ? DIR_APPLICATION : (defined('DIR_APPLICATION') ? rtrim(DIR_APPLICATION, '/') . '/' : '');
        if (defined('DIR_CATALOG')) {
            $dirAdmin = defined('DIR_APPLICATION') ? DIR_APPLICATION : '';
            $dirCatalog = DIR_CATALOG;
        } else {
            $dirCatalog = defined('DIR_APPLICATION') ? DIR_APPLICATION : '';
            $dirAdmin = defined('DIR_APPLICATION') ? rtrim(DIR_APPLICATION, '/') . '/../admin/' : '';
        }
        $dirSystem = defined('DIR_SYSTEM') ? DIR_SYSTEM : '';

        // Normalize slashes
        $dirCatalog = rtrim(str_replace('\\', '/', $dirCatalog), '/') . '/';
        $dirAdmin   = rtrim(str_replace('\\', '/', $dirAdmin), '/') . '/';
        $dirSystem  = rtrim(str_replace('\\', '/', $dirSystem), '/') . '/';
        $modDir     = rtrim(str_replace('\\', '/', $modDir), '/') . '/';

        $xmlList = [];
        if (is_file($dirSystem . 'modification.xml')) {
            $xmlList[] = file_get_contents($dirSystem . 'modification.xml');
        }
        foreach (glob($dirSystem . '*.ocmod.xml') as $f) {
            $xmlList[] = file_get_contents($f);
        }

        try {
            $stmt = $pdo->query('SELECT `xml` FROM `' . $prefix . 'modification` WHERE `status` = 1');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $xmlList[] = $row['xml'];
            }
        } catch (PDOException $e) {
            self::logCrash('Failed to fetch active modifications: ' . $e->getMessage());
        }

        $modification = [];
        $original = [];

        foreach ($xmlList as $xml) {
            if (empty($xml)) continue;

            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            if (!@$dom->loadXml($xml)) continue;

            $modNode = $dom->getElementsByTagName('modification')->item(0);
            if (!$modNode) continue;

            $files = $modNode->getElementsByTagName('file');
            foreach ($files as $fileNode) {
                $operations = $fileNode->getElementsByTagName('operation');
                $filePaths = explode('|', str_replace('\\', '/', $fileNode->getAttribute('path')));

                foreach ($filePaths as $relPath) {
                    $fullPath = '';
                    if (substr($relPath, 0, 7) === 'catalog') {
                        $fullPath = $dirCatalog . substr($relPath, 8);
                    } elseif (substr($relPath, 0, 5) === 'admin') {
                        $fullPath = $dirAdmin . substr($relPath, 6);
                    } elseif (substr($relPath, 0, 6) === 'system') {
                        $fullPath = $dirSystem . substr($relPath, 7);
                    }

                    if (!$fullPath) continue;

                    $matchedFiles = glob($fullPath, GLOB_BRACE);
                    if (!$matchedFiles) continue;

                    foreach ($matchedFiles as $mFile) {
                        $mFile = str_replace('\\', '/', $mFile);
                        $key = '';
                        if (substr($mFile, 0, strlen($dirCatalog)) === $dirCatalog) {
                            $key = 'catalog/' . substr($mFile, strlen($dirCatalog));
                        } elseif (substr($mFile, 0, strlen($dirAdmin)) === $dirAdmin) {
                            $key = 'admin/' . substr($mFile, strlen($dirAdmin));
                        } elseif (substr($mFile, 0, strlen($dirSystem)) === $dirSystem) {
                            $key = 'system/' . substr($mFile, strlen($dirSystem));
                        }

                        if (!$key) continue;

                        if (!isset($modification[$key])) {
                            $content = file_get_contents($mFile);
                            $modification[$key] = preg_replace('~\r?\n~', "\n", $content);
                            $original[$key] = preg_replace('~\r?\n~', "\n", $content);
                        }

                        foreach ($operations as $operation) {
                            $searchNode = $operation->getElementsByTagName('search')->item(0);
                            $addNode = $operation->getElementsByTagName('add')->item(0);
                            if (!$searchNode || !$addNode) continue;

                            $search = $searchNode->textContent;
                            $trim = $searchNode->getAttribute('trim');
                            $index = $searchNode->getAttribute('index');
                            if (!$trim || $trim === 'true') {
                                $search = trim($search);
                            }

                            $add = $addNode->textContent;
                            $trimAdd = $addNode->getAttribute('trim');
                            $position = $addNode->getAttribute('position') ?: 'replace';
                            $offset = (int)($addNode->getAttribute('offset') ?: 0);
                            if ($trimAdd === 'true') {
                                $add = trim($add);
                            }

                            $indexes = ($index !== '') ? explode(',', $index) : [];
                            $lines = explode("\n", $modification[$key]);
                            $matchCount = 0;

                            for ($line_id = 0; $line_id < count($lines); $line_id++) {
                                $line = $lines[$line_id];
                                if (stripos($line, $search) !== false) {
                                    $isTarget = false;
                                    if (!$indexes || in_array($matchCount, $indexes)) {
                                        $isTarget = true;
                                    }
                                    $matchCount++;

                                    if ($isTarget) {
                                        switch ($position) {
                                            case 'replace':
                                                $new_lines = explode("\n", $add);
                                                if ($offset < 0) {
                                                    array_splice($lines, $line_id + $offset, abs($offset) + 1, [str_replace($search, $add, $line)]);
                                                    $line_id -= $offset;
                                                } else {
                                                    array_splice($lines, $line_id, $offset + 1, [str_replace($search, $add, $line)]);
                                                }
                                                break;
                                            case 'before':
                                                $new_lines = explode("\n", $add);
                                                array_splice($lines, $line_id - $offset, 0, $new_lines);
                                                $line_id += count($new_lines);
                                                break;
                                            case 'after':
                                                $new_lines = explode("\n", $add);
                                                array_splice($lines, ($line_id + 1) + $offset, 0, $new_lines);
                                                $line_id += count($new_lines);
                                                break;
                                        }
                                    }
                                }
                            }

                            $modification[$key] = implode("\n", $lines);
                        }
                    }
                }
            }
        }

        $written = 0;
        foreach ($modification as $key => $value) {
            if ($original[$key] !== $value) {
                $destFile = $modDir . $key;
                $destFolder = dirname($destFile);
                if (!is_dir($destFolder)) {
                    @mkdir($destFolder, 0777, true);
                }
                @file_put_contents($destFile, $value);
                $written++;
            }
        }

        return ['status' => true, 'files_written' => $written];
    }

    /**
     * Tier 1: Surgical disabling of a specific module
     *
     * @param string $code
     * @return array
     */
    public static function disableModule($code) {
        $cleanCode = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$code);
        if (empty($cleanCode)) {
            return ['status' => false, 'error' => 'Invalid module code'];
        }

        $prefix = self::getPrefix();
        $pdo = self::getPdo();

        $modCount = 0;
        $eventCount = 0;
        $settingCount = 0;

        try {
            // 1. Disable in oc_modification
            $stmtMod = $pdo->prepare('UPDATE `' . $prefix . 'modification` SET `status` = 0 WHERE `code` = :code');
            $stmtMod->execute([':code' => $cleanCode]);
            $modCount = $stmtMod->rowCount();

            // 2. Disable in oc_event (direct match or prefix match)
            $stmtEvt = $pdo->prepare('UPDATE `' . $prefix . 'event` SET `status` = 0 WHERE `code` = :code OR `code` LIKE :code_prefix');
            $stmtEvt->execute([
                ':code'        => $cleanCode,
                ':code_prefix' => $cleanCode . '_%'
            ]);
            $eventCount = $stmtEvt->rowCount();

            // 3. Disable module setting status
            $stmtSet = $pdo->prepare('UPDATE `' . $prefix . 'setting` SET `value` = \'0\' WHERE `key` = :set_key');
            $stmtSet->execute([':set_key' => 'module_' . $cleanCode . '_status']);
            $settingCount = $stmtSet->rowCount();

            // 4. Purge modification cache and rebuild active
            $purgeResult = self::purgeModificationCache();
            self::rebuildModificationCache();

            self::logCrash('Tier 1: Surgically disabled module \'' . $cleanCode . '\'', [
                'modifications_disabled' => $modCount,
                'events_disabled'        => $eventCount,
                'settings_disabled'      => $settingCount,
                'cache_purged'           => $purgeResult
            ]);

            return [
                'status'                 => true,
                'code'                   => $cleanCode,
                'modifications_disabled' => $modCount,
                'events_disabled'        => $eventCount,
                'settings_disabled'      => $settingCount,
                'cache_purged'           => $purgeResult
            ];
        } catch (PDOException $e) {
            self::logCrash('Failed to surgically disable module: ' . $e->getMessage(), ['code' => $cleanCode]);
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Tier 2: Total Blackout Mode (Fail-Safe Factory Reset)
     *
     * @return array
     */
    public static function totalBlackout() {
        $prefix = self::getPrefix();
        $pdo = self::getPdo();

        $modCount = 0;
        $eventCount = 0;
        $settingCount = 0;

        try {
            // 1. Disable ALL active modifications
            $stmtMod = $pdo->query('UPDATE `' . $prefix . 'modification` SET `status` = 0');
            $modCount = $stmtMod ? $stmtMod->rowCount() : 0;

            // 2. Disable non-core extension events only (protect core OpenCart activity/mail/statistics/advertising events)
            $stmtEvt = $pdo->query('UPDATE `' . $prefix . 'event` SET `status` = 0 WHERE `code` NOT LIKE \'core_%\' AND `code` NOT LIKE \'activity_%\' AND `code` NOT LIKE \'mail_%\' AND `code` NOT LIKE \'statistics_%\' AND `code` NOT LIKE \'admin_mail_%\' AND `code` NOT LIKE \'advertise_google%\'');
            $eventCount = $stmtEvt ? $stmtEvt->rowCount() : 0;

            // 3. Disable 3rd-party module settings (protect core category, account, filter modules)
            $stmtSet = $pdo->query('UPDATE `' . $prefix . 'setting` SET `value` = \'0\' WHERE `key` LIKE \'module_%_status\' AND `key` NOT IN (\'module_category_status\', \'module_account_status\', \'module_filter_status\', \'module_blog_category_status\')');
            $settingCount = $stmtSet ? $stmtSet->rowCount() : 0;

            // 4. Completely clear modification and system caches
            $modPurge = self::purgeModificationCache();
            $sysPurge = self::purgeSystemCache();

            self::logCrash('Tier 2: Total Blackout engaged (All extensions & non-core events disabled)', [
                'modifications_disabled' => $modCount,
                'events_disabled'        => $eventCount,
                'settings_disabled'      => $settingCount,
                'modification_cache'     => $modPurge,
                'system_cache'           => $sysPurge
            ]);

            return [
                'status'                 => true,
                'modifications_disabled' => $modCount,
                'events_disabled'        => $eventCount,
                'settings_disabled'      => $settingCount,
                'modification_cache'     => $modPurge,
                'system_cache'           => $sysPurge
            ];
        } catch (PDOException $e) {
            self::logCrash('Total Blackout execution error: ' . $e->getMessage());
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Retrieve emergency secret token
     *
     * @return string
     */
    public static function getSecretToken() {
        // 1. Check environment variable
        $envToken = getenv('STOPKRAN_SECRET_TOKEN');
        if (!empty($envToken)) {
            return (string)$envToken;
        }

        if (!empty($_ENV['STOPKRAN_SECRET_TOKEN'])) {
            return (string)$_ENV['STOPKRAN_SECRET_TOKEN'];
        }

        // 2. Check OpenCart oc_setting
        try {
            $pdo = self::getPdo();
            $prefix = self::getPrefix();
            $stmt = $pdo->prepare('SELECT `value` FROM `' . $prefix . 'setting` WHERE `key` = \'module_stopkran_secret_token\' LIMIT 1');
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row && !empty($row['value'])) {
                return (string)$row['value'];
            }
        } catch (PDOException $e) {
            // DB might be down or not initialized
        }

        // 3. Check persistent token file in storage
        $tokenFile = self::getStorageDir() . 'stopkran_token.txt';
        if (is_file($tokenFile)) {
            $fileToken = trim((string)@file_get_contents($tokenFile));
            if (!empty($fileToken)) {
                return $fileToken;
            }
        }

        // 4. Default fallback master token
        return 'soundnet_stopkran_safe_bypass_token_9921';
    }

    /**
     * Save/persist emergency secret token
     *
     * @param string $token
     * @return bool
     */
    public static function setSecretToken($token) {
        $token = trim((string)$token);
        if (empty($token)) {
            return false;
        }

        // Save to DB
        try {
            $pdo = self::getPdo();
            $prefix = self::getPrefix();
            $stmt = $pdo->prepare('REPLACE INTO `' . $prefix . 'setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES (0, \'module_stopkran\', \'module_stopkran_secret_token\', :val, 0)');
            $stmt->execute([':val' => $token]);
        } catch (PDOException $e) {
            // Ignore DB error
        }

        // Save to token file in storage
        $tokenFile = self::getStorageDir() . 'stopkran_token.txt';
        @file_put_contents($tokenFile, $token);

        return true;
    }

    /**
     * Calculate directory size in bytes
     *
     * @param string $path
     * @return int
     */
    public static function getDirectorySize($path) {
        $totalBytes = 0;
        if (!is_dir($path)) {
            return 0;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalBytes += $file->getSize();
            }
        }

        return $totalBytes;
    }

    /**
     * Get system circuit status summary
     *
     * @return array
     */
    public static function getStatus() {
        $prefix = self::getPrefix();
        $pdo = self::getPdo();

        $activeMods = 0;
        $totalMods = 0;
        $activeEvents = 0;
        $totalEvents = 0;

        try {
            $stmt = $pdo->query('SELECT SUM(CASE WHEN `status` = 1 THEN 1 ELSE 0 END) AS active_count, COUNT(*) AS total_count FROM `' . $prefix . 'modification`');
            $row = $stmt->fetch();
            if ($row) {
                $activeMods = (int)$row['active_count'];
                $totalMods  = (int)$row['total_count'];
            }

            $stmt2 = $pdo->query('SELECT SUM(CASE WHEN `status` = 1 THEN 1 ELSE 0 END) AS active_count, COUNT(*) AS total_count FROM `' . $prefix . 'event` WHERE `code` NOT LIKE \'core_%\'');
            $row2 = $stmt2->fetch();
            if ($row2) {
                $activeEvents = (int)$row2['active_count'];
                $totalEvents  = (int)$row2['total_count'];
            }
        } catch (PDOException $e) {
            // Ignore for status
        }

        $modDir = self::getModificationDir();
        $cacheDir = self::getCacheDir();

        return [
            'active_modifications' => $activeMods,
            'total_modifications'  => $totalMods,
            'active_events'        => $activeEvents,
            'total_events'         => $totalEvents,
            'modification_size'    => self::getDirectorySize($modDir),
            'cache_size'           => self::getDirectorySize($cacheDir),
            'modification_dir'     => $modDir,
            'cache_dir'            => $cacheDir
        ];
    }

    /**
     * Structured crash logging
     *
     * @param string $message
     * @param array  $context
     * @return void
     */
    public static function logCrash($message, array $context = []) {
        $logsDir = self::getLogsDir();
        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0777, true);
        }

        $logFile = $logsDir . 'stopkran_crash.log';
        $timestamp = gmdate('Y-m-d H:i:s') . ' UTC';

        $entry = sprintf(
            "[%s] %s | Context: %s\n",
            $timestamp,
            $message,
            empty($context) ? '{}' : json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
