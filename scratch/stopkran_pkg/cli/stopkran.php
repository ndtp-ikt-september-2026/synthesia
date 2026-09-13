<?php
/**
 * SoundNet Stop-Kran Standalone CLI Recovery Tool
 *
 * Designed to execute in 100% standalone CLI mode without HTTP server dependencies,
 * framework classes, or OpenCart engine initialization.
 *
 * Usage:
 *   php cli/stopkran.php --status
 *   php cli/stopkran.php --kill-all
 *   php cli/stopkran.php --purge-cache
 *   php cli/stopkran.php --disable=<code_name>
 *   php cli/stopkran.php --disable-module=<code_name>
 */

declare(strict_types=1);

// Enforce CLI SAPI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Access denied: CLI tool only.\n";
    exit(1);
}

class StopKranCli {
    protected array $config = [];
    protected ?PDO $pdo = null;

    public function run(array $argv): int {
        $this->printBanner();

        // 1. Load configuration safely
        if (!$this->loadConfig()) {
            $this->logError("Failed to locate or parse OpenCart config.php. Ensure you run this from the project root or cli/ directory.");
            return 1;
        }

        // 2. Parse arguments
        $options = $this->parseArgs($argv);

        if (empty($options) || isset($options['help']) || isset($options['h'])) {
            $this->printUsage();
            return 0;
        }

        if (isset($options['status'])) {
            return $this->handleStatus();
        }

        if (isset($options['kill-all'])) {
            return $this->handleKillAll();
        }

        if (isset($options['purge-cache'])) {
            return $this->handlePurgeCache();
        }

        $disableCode = $options['disable-module'] ?? ($options['disable'] ?? null);
        if (!empty($disableCode)) {
            return $this->handleDisableModule((string)$disableCode);
        }

        $this->logWarn("Unrecognized command option.");
        $this->printUsage();
        return 1;
    }

    protected function printBanner(): void {
        echo "====================================================================\n";
        echo "   SOUNDNET STOP-KRAN // INDUSTRIAL EMERGENCY RECOVERY UTILITY      \n";
        echo "====================================================================\n";
    }

    protected function printUsage(): void {
        echo "Usage:\n";
        echo "  php cli/stopkran.php [options]\n\n";
        echo "Available Options:\n";
        echo "  --status                     Display circuit breaker status, active mods, & cache usage\n";
        echo "  --kill-all                   Engage Total Blackout: disable all mods, non-core events, & purge cache\n";
        echo "  --purge-cache                Purge modification & system storage cache without touching database\n";
        echo "  --disable=<code_name>        Surgically disable a single modification & related events\n";
        echo "  --disable-module=<code_name> Alias for --disable\n";
        echo "  --help, -h                   Show this help message\n\n";
    }

    protected function parseArgs(array $argv): array {
        $options = [];
        array_shift($argv); // Remove script name

        foreach ($argv as $arg) {
            if (strpos($arg, '--') === 0) {
                $arg = substr($arg, 2);
                if (strpos($arg, '=') !== false) {
                    [$k, $v] = explode('=', $arg, 2);
                    $options[$k] = $v;
                } else {
                    $options[$arg] = true;
                }
            } elseif (strpos($arg, '-') === 0) {
                $options[substr($arg, 1)] = true;
            }
        }

        return $options;
    }

    protected function loadConfig(): bool {
        $searchDirs = [
            __DIR__ . '/../admin/config.php',
            __DIR__ . '/../config.php',
            dirname(__DIR__) . '/admin/config.php',
            dirname(__DIR__) . '/config.php'
        ];

        $configFile = null;
        foreach ($searchDirs as $path) {
            if (is_file($path) && is_readable($path)) {
                $configFile = realpath($path);
                break;
            }
        }

        if (!$configFile) {
            return false;
        }

        $content = file_get_contents($configFile);
        if ($content === false) {
            return false;
        }

        // Parse constants using regex to bypass any external includes
        $patterns = [
            'DB_HOSTNAME'      => '/define\(\s*[\'"]DB_HOSTNAME[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',
            'DB_USERNAME'      => '/define\(\s*[\'"]DB_USERNAME[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',
            'DB_PASSWORD'      => '/define\(\s*[\'"]DB_PASSWORD[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',
            'DB_DATABASE'      => '/define\(\s*[\'"]DB_DATABASE[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',
            'DB_PORT'          => '/define\(\s*[\'"]DB_PORT[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',
            'DB_PREFIX'        => '/define\(\s*[\'"]DB_PREFIX[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',
            'DIR_STORAGE'      => '/define\(\s*[\'"]DIR_STORAGE[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',
            'DIR_MODIFICATION' => '/define\(\s*[\'"]DIR_MODIFICATION[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',
            'DIR_CACHE'        => '/define\(\s*[\'"]DIR_CACHE[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',
            'DIR_LOGS'         => '/define\(\s*[\'"]DIR_LOGS[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $this->config[$key] = $matches[1];
            }
        }

        // If DIR_STORAGE was parsed, set default derived directories
        if (!empty($this->config['DIR_STORAGE'])) {
            $storage = rtrim(str_replace('\\', '/', $this->config['DIR_STORAGE']), '/') . '/';
            if (empty($this->config['DIR_MODIFICATION'])) {
                $this->config['DIR_MODIFICATION'] = $storage . 'modification/';
            }
            if (empty($this->config['DIR_CACHE'])) {
                $this->config['DIR_CACHE'] = $storage . 'cache/';
            }
            if (empty($this->config['DIR_LOGS'])) {
                $this->config['DIR_LOGS'] = $storage . 'logs/';
            }
        }

        // Default fallbacks
        $this->config['DB_HOSTNAME'] = $this->config['DB_HOSTNAME'] ?? 'localhost';
        $this->config['DB_PORT']     = $this->config['DB_PORT'] ?? '3306';
        $this->config['DB_PREFIX']   = $this->config['DB_PREFIX'] ?? 'oc_';

        return !empty($this->config['DB_DATABASE']);
    }

    protected function getPdo(): PDO {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->config['DB_HOSTNAME'],
            $this->config['DB_PORT'],
            $this->config['DB_DATABASE']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 2,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];

        $this->pdo = new PDO($dsn, $this->config['DB_USERNAME'] ?? 'root', $this->config['DB_PASSWORD'] ?? '', $options);
        return $this->pdo;
    }

    protected function handleStatus(): int {
        $this->logInfo("Gathering system status...");

        try {
            $pdo = $this->getPdo();
            $prefix = $this->config['DB_PREFIX'];

            // Query modifications
            $stmt = $pdo->query("SELECT SUM(CASE WHEN `status` = 1 THEN 1 ELSE 0 END) AS active_cnt, COUNT(*) AS total_cnt FROM `{$prefix}modification`");
            $modData = $stmt->fetch();

            // Query non-core events
            $stmt2 = $pdo->query("SELECT SUM(CASE WHEN `status` = 1 THEN 1 ELSE 0 END) AS active_cnt, COUNT(*) AS total_cnt FROM `{$prefix}event` WHERE `code` NOT LIKE 'core_%'");
            $evtData = $stmt2->fetch();

            $modSize = $this->calcDirSize($this->config['DIR_MODIFICATION'] ?? '');
            $cacheSize = $this->calcDirSize($this->config['DIR_CACHE'] ?? '');

            echo "\n";
            echo "  [DATABASE]           Host: {$this->config['DB_HOSTNAME']}:{$this->config['DB_PORT']} | DB: {$this->config['DB_DATABASE']}\n";
            echo "  [MODIFICATIONS]      Active: " . ($modData['active_cnt'] ?? 0) . " / Total: " . ($modData['total_cnt'] ?? 0) . "\n";
            echo "  [CUSTOM EVENTS]      Active: " . ($evtData['active_cnt'] ?? 0) . " / Total: " . ($evtData['total_cnt'] ?? 0) . "\n";
            echo "  [MODIFICATION CACHE] " . $this->formatBytes($modSize) . " (" . ($this->config['DIR_MODIFICATION'] ?? 'N/A') . ")\n";
            echo "  [SYSTEM CACHE]       " . $this->formatBytes($cacheSize) . " (" . ($this->config['DIR_CACHE'] ?? 'N/A') . ")\n";
            echo "\n";

            $this->logOk("Status query completed successfully.");
            return 0;
        } catch (PDOException $e) {
            $this->logError("Database query failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function handleKillAll(): int {
        $this->logWarn("ENGAGING TOTAL BLACKOUT...");

        try {
            $pdo = $this->getPdo();
            $prefix = $this->config['DB_PREFIX'];

            // 1. Disable all active modifications
            $stmt1 = $pdo->query("UPDATE `{$prefix}modification` SET `status` = 0");
            $modsDisabled = $stmt1 ? $stmt1->rowCount() : 0;
            $this->logOk("Deactivated {$modsDisabled} modifications in `{$prefix}modification`.");

            // 2. Disable all non-core events
            $stmt2 = $pdo->query("UPDATE `{$prefix}event` SET `status` = 0 WHERE `code` NOT LIKE 'core_%'");
            $evtsDisabled = $stmt2 ? $stmt2->rowCount() : 0;
            $this->logOk("Deactivated {$evtsDisabled} non-core events in `{$prefix}event`.");

            // 3. Disable all module settings
            $stmt3 = $pdo->query("UPDATE `{$prefix}setting` SET `value` = '0' WHERE `key` LIKE 'module_%_status'");
            $setsReset = $stmt3 ? $stmt3->rowCount() : 0;
            $this->logOk("Reset {$setsReset} module statuses in `{$prefix}setting`.");

            // 4. Purge caches
            $modPurge = $this->purgeDirectory($this->config['DIR_MODIFICATION'] ?? '', true);
            $cachePurge = $this->purgeDirectory($this->config['DIR_CACHE'] ?? '', false);

            $this->logOk("Purged modification cache ({$modPurge['files']} files, {$modPurge['dirs']} dirs deleted).");
            $this->logOk("Purged system cache ({$cachePurge['files']} files, {$cachePurge['dirs']} dirs deleted).");

            $this->logOk("TOTAL BLACKOUT COMPLETE: OpenCart restored to clean native core.");
            return 0;
        } catch (PDOException $e) {
            $this->logError("Failed to execute Total Blackout: " . $e->getMessage());
            return 1;
        }
    }

    protected function handlePurgeCache(): int {
        $this->logInfo("Purging storage cache directories...");

        $modPurge = $this->purgeDirectory($this->config['DIR_MODIFICATION'] ?? '', true);
        $cachePurge = $this->purgeDirectory($this->config['DIR_CACHE'] ?? '', false);

        $this->logOk("Purged modification storage: {$modPurge['files']} files, {$modPurge['dirs']} directories removed.");
        $this->logOk("Purged system cache storage: {$cachePurge['files']} files, {$cachePurge['dirs']} directories removed.");
        return 0;
    }

    protected function handleDisableModule(string $code): int {
        $cleanCode = preg_replace('/[^a-zA-Z0-9_\-]/', '', $code);
        if (empty($cleanCode)) {
            $this->logError("Invalid module code provided.");
            return 1;
        }

        $this->logInfo("Surgically disabling module '{$cleanCode}'...");

        try {
            $pdo = $this->getPdo();
            $prefix = $this->config['DB_PREFIX'];

            // 1. Disable in oc_modification
            $stmt1 = $pdo->prepare("UPDATE `{$prefix}modification` SET `status` = 0 WHERE `code` = :code");
            $stmt1->execute([':code' => $cleanCode]);
            $modCount = $stmt1->rowCount();

            // 2. Disable in oc_event
            $stmt2 = $pdo->prepare("UPDATE `{$prefix}event` SET `status` = 0 WHERE `code` = :code OR `code` LIKE :code_prefix");
            $stmt2->execute([
                ':code'        => $cleanCode,
                ':code_prefix' => $cleanCode . '_%'
            ]);
            $evtCount = $stmt2->rowCount();

            // 3. Disable in oc_setting
            $stmt3 = $pdo->prepare("UPDATE `{$prefix}setting` SET `value` = '0' WHERE `key` = :setting_key");
            $stmt3->execute([':setting_key' => 'module_' . $cleanCode . '_status']);
            $setCount = $stmt3->rowCount();

            // 4. Purge modification cache
            $purge = $this->purgeDirectory($this->config['DIR_MODIFICATION'] ?? '', true);

            $this->logOk("Disabled {$modCount} modifications, {$evtCount} events, {$setCount} module settings.");
            $this->logOk("Modification cache purged ({$purge['files']} files removed).");
            $this->logOk("Module '{$cleanCode}' surgically isolated and neutralized.");
            return 0;
        } catch (PDOException $e) {
            $this->logError("Database operation failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function purgeDirectory(string $dir, bool $preserveIndexHtml = true): array {
        $count = ['files' => 0, 'dirs' => 0];
        if (empty($dir) || !is_dir($dir)) {
            return $count;
        }

        $dir = rtrim(str_replace('\\', '/', $dir), '/') . '/';

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $filename = $item->getFilename();

            if ($preserveIndexHtml && $filename === 'index.html' && dirname($path) === rtrim($dir, '/')) {
                continue;
            }

            if ($item->isDir()) {
                if (file_exists($path)) {
                    @rmdir($path);
                    $count['dirs']++;
                }
            } else {
                if (file_exists($path)) {
                    @unlink($path);
                    $count['files']++;
                }
            }
        }

        return $count;
    }

    protected function calcDirSize(string $dir): int {
        if (empty($dir) || !is_dir($dir)) {
            return 0;
        }

        $total = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $total += $item->getSize();
            }
        }

        return $total;
    }

    protected function formatBytes(int $bytes): string {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    protected function logOk(string $msg): void {
        echo "  [OK]    {$msg}\n";
    }

    protected function logInfo(string $msg): void {
        echo "  [INFO]  {$msg}\n";
    }

    protected function logWarn(string $msg): void {
        echo "  [WARN]  {$msg}\n";
    }

    protected function logError(string $msg): void {
        echo "  [ERROR] {$msg}\n";
    }
}

// Execute script
$cli = new StopKranCli();
exit($cli->run($argv));
