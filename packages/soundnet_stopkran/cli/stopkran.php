<?php
/**
 * SoundNet Стоп-Кран — Автономная CLI утилита аварийного восстановления и тестирования
 *
 * Работает в 100% автономном режиме без зависимостей от веб-сервера.
 *
 * Использование:
 *   php cli/stopkran.php --status
 *   php cli/stopkran.php --kill-all
 *   php cli/stopkran.php --purge-cache
 *   php cli/stopkran.php --test=<code_name>
 *   php cli/stopkran.php --test-all
 *   php cli/stopkran.php --disable=<code_name>
 */

declare(strict_types=1);

// Проверка запуска из командной строки
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Доступ запрещен: утилита предназначена только для CLI.\n";
    exit(1);
}

class StopKranCli {
    protected array $config = [];
    protected ?PDO $pdo = null;

    public function run(array $argv): int {
        $this->printBanner();

        // 1. Загрузка конфигурации
        if (!$this->loadConfig()) {
            $this->logError("Не удалось найти или прочитать OpenCart config.php. Запустите скрипт из корневой директории проекта.");
            return 1;
        }

        // 2. Разбор аргументов
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

        if (isset($options['test-all'])) {
            return $this->handleTestAll();
        }

        if (isset($options['test'])) {
            return $this->handleTestModule((string)$options['test']);
        }

        $disableCode = $options['disable-module'] ?? ($options['disable'] ?? null);
        if (!empty($disableCode)) {
            return $this->handleDisableModule((string)$disableCode);
        }

        $this->logWarn("Неизвестный параметр команды.");
        $this->printUsage();
        return 1;
    }

    protected function printBanner(): void {
        echo "====================================================================\n";
        echo "   SOUNDNET СТОП-КРАН // АВАРИЙНЫЙ ВЫКЛЮЧАТЕЛЬ И ТЕСТИРОВАНИЕ       \n";
        echo "====================================================================\n";
    }

    protected function printUsage(): void {
        echo "Использование:\n";
        echo "  php cli/stopkran.php [параметры]\n\n";
        echo "Доступные параметры:\n";
        echo "  --status                     Вывести состояние защитного контура и размер кэша\n";
        echo "  --kill-all                   Полный блэкаут: отключить все модификаторы, события и кэш\n";
        echo "  --purge-cache                Очистить кэш модификаций и системное хранилище\n";
        echo "  --test=<code_name>           Протестировать модуль (синтаксис PHP, классы, шаблоны)\n";
        echo "  --test-all                   Протестировать все установленные модули магазина\n";
        echo "  --disable=<code_name>        Точечно отключить модификатор и события модуля\n";
        echo "  --help, -h                   Показать эту справку\n\n";
    }

    protected function parseArgs(array $argv): array {
        $options = [];
        array_shift($argv);

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
        $this->logInfo("Сбор сведений о состоянии системы...");

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
            echo "  [БАЗА ДАННЫХ]        Хост: {$this->config['DB_HOSTNAME']}:{$this->config['DB_PORT']} | БД: {$this->config['DB_DATABASE']}\n";
            echo "  [МОДИФИКАЦИИ]        Активно: " . ($modData['active_cnt'] ?? 0) . " / Всего: " . ($modData['total_cnt'] ?? 0) . "\n";
            echo "  [СОБЫТИЯ]            Активно: " . ($evtData['active_cnt'] ?? 0) . " / Всего: " . ($evtData['total_cnt'] ?? 0) . "\n";
            echo "  [КЭШ МОДИФИКАЦИЙ]    " . $this->formatBytes($modSize) . "\n";
            echo "  [СИСТЕМНЫЙ КЭШ]      " . $this->formatBytes($cacheSize) . "\n";
            echo "\n";

            $this->logOk("Запрос статуса успешно выполнен.");
            return 0;
        } catch (PDOException $e) {
            $this->logError("Ошибка запроса к базе данных: " . $e->getMessage());
            return 1;
        }
    }

    protected function handleKillAll(): int {
        $this->logWarn("АКТИВАЦИЯ АВАРИЙНОГО БЛЭКАУТА...");

        try {
            $pdo = $this->getPdo();
            $prefix = $this->config['DB_PREFIX'];

            // 1. Disable all active modifications
            $stmt1 = $pdo->query("UPDATE `{$prefix}modification` SET `status` = 0");
            $modsDisabled = $stmt1 ? $stmt1->rowCount() : 0;
            $this->logOk("Отключено {$modsDisabled} модификаций в `{$prefix}modification`.");

            // 2. Disable all non-core events
            $stmt2 = $pdo->query("UPDATE `{$prefix}event` SET `status` = 0 WHERE `code` NOT LIKE 'core_%'");
            $evtsDisabled = $stmt2 ? $stmt2->rowCount() : 0;
            $this->logOk("Отключено {$evtsDisabled} сторонних событий в `{$prefix}event`.");

            // 3. Disable all module settings
            $stmt3 = $pdo->query("UPDATE `{$prefix}setting` SET `value` = '0' WHERE `key` LIKE 'module_%_status'");
            $setsReset = $stmt3 ? $stmt3->rowCount() : 0;
            $this->logOk("Сброшено {$setsReset} статусов модулей в `{$prefix}setting`.");

            // 4. Purge caches
            $modPurge = $this->purgeDirectory($this->config['DIR_MODIFICATION'] ?? '', true);
            $cachePurge = $this->purgeDirectory($this->config['DIR_CACHE'] ?? '', false);

            $this->logOk("Очищен кэш модификаций ({$modPurge['files']} файлов, {$modPurge['dirs']} папок удалено).");
            $this->logOk("Очищен системный кэш ({$cachePurge['files']} файлов, {$cachePurge['dirs']} папок удалено).");

            $this->logOk("ПОЛНЫЙ БЛЭКАУТ ВЫПОЛНЕН: OpenCart возвращен к немодифицированному ядру.");
            return 0;
        } catch (PDOException $e) {
            $this->logError("Ошибка при выполнении полного блэкаута: " . $e->getMessage());
            return 1;
        }
    }

    protected function handlePurgeCache(): int {
        $this->logInfo("Очистка директорий кэша...");

        $modPurge = $this->purgeDirectory($this->config['DIR_MODIFICATION'] ?? '', true);
        $cachePurge = $this->purgeDirectory($this->config['DIR_CACHE'] ?? '', false);

        $this->logOk("Очищен кэш модификаций: {$modPurge['files']} файлов, {$modPurge['dirs']} папок.");
        $this->logOk("Очищен системный кэш: {$cachePurge['files']} файлов, {$cachePurge['dirs']} папок.");
        return 0;
    }

    protected function handleTestModule(string $code): int {
        $cleanCode = preg_replace('/[^a-zA-Z0-9_\-]/', '', strtolower(trim($code)));
        if (empty($cleanCode)) {
            $this->logError("Укажите корректный код модуля.");
            return 1;
        }

        $this->logInfo("Запуск комплексного тестирования модуля '{$cleanCode}'...");

        $testerFile = dirname(__DIR__) . '/system/library/stopkran/tester.php';
        if (!file_exists($testerFile)) {
            $this->logError("Файл тестера не найден: {$testerFile}");
            return 1;
        }

        require_once($testerFile);

        $report = \SoundNet\StopKran\Tester::testModule($cleanCode, 'cli');

        echo "\n";
        echo "  [МОДУЛЬ]            {$report['module']}\n";
        echo "  [РЕЗУЛЬТАТ]         " . ($report['passed'] ? "УСПЕШНО" : "ОБНАРУЖЕНЫ ОШИБКИ") . "\n";
        echo "  [ПРОВЕРЕНО ФАЙЛОВ]  {$report['total_files']}\n";
        echo "  [ВРЕМЯ ПРОВЕРКИ]    {$report['execution_time']} мс\n";
        echo "\n";

        if (!empty($report['errors'])) {
            echo "  КРИТИЧЕСКИЕ ОШИБКИ:\n";
            foreach ($report['errors'] as $err) {
                echo "    [!] {$err}\n";
            }
            echo "\n";
        }

        if (!empty($report['warnings'])) {
            echo "  ПРЕДУПРЕЖДЕНИЯ:\n";
            foreach ($report['warnings'] as $warn) {
                echo "    [*] {$warn}\n";
            }
            echo "\n";
        }

        if ($report['passed']) {
            $this->logOk("Модуль '{$cleanCode}' успешно прошел все тесты синтаксиса и структуры.");
            return 0;
        } else {
            $this->logError("Тестирование выявило критические ошибки в модуле '{$cleanCode}'.");
            return 1;
        }
    }

    protected function handleTestAll(): int {
        $this->logInfo("Запуск пакетного тестирования всех модулей магазина...");

        $testerFile = dirname(__DIR__) . '/system/library/stopkran/tester.php';
        if (!file_exists($testerFile)) {
            $this->logError("Файл тестера не найден: {$testerFile}");
            return 1;
        }

        require_once($testerFile);

        $reports = \SoundNet\StopKran\Tester::testAllModules();

        $total = count($reports);
        $passed = 0;
        $failed = 0;

        echo "\n";
        echo str_repeat('-', 70) . "\n";
        printf("%-30s | %-12s | %-8s | %-10s\n", "Модуль", "Статус", "Файлов", "Время (мс)");
        echo str_repeat('-', 70) . "\n";

        foreach ($reports as $code => $rep) {
            $statusStr = $rep['passed'] ? "OK" : "ОШИБКА";
            printf("%-30s | %-12s | %-8d | %-10.2f\n", $code, $statusStr, $rep['total_files'], $rep['execution_time']);
            if ($rep['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }

        echo str_repeat('-', 70) . "\n\n";

        $this->logOk("Всего модулей: {$total} | Пройдено: {$passed} | С ошибками: {$failed}");
        return $failed > 0 ? 1 : 0;
    }

    protected function handleDisableModule(string $code): int {
        $cleanCode = preg_replace('/[^a-zA-Z0-9_\-]/', '', $code);
        if (empty($cleanCode)) {
            $this->logError("Неверный код модуля.");
            return 1;
        }

        $this->logInfo("Точечное отключение модуля '{$cleanCode}'...");

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

            $this->logOk("Отключено: модификаций — {$modCount}, событий — {$evtCount}, настроек — {$setCount}.");
            $this->logOk("Кэш модификаций очищен ({$purge['files']} файлов удалено).");
            $this->logOk("Модуль '{$cleanCode}' успешно изолирован и обезврежен.");
            return 0;
        } catch (PDOException $e) {
            $this->logError("Ошибка операции с базой данных: " . $e->getMessage());
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
            return number_format($bytes / 1048576, 2) . ' МБ';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' КБ';
        }
        return $bytes . ' Б';
    }

    protected function logOk(string $msg): void {
        echo "  [УСПЕХ]    {$msg}\n";
    }

    protected function logInfo(string $msg): void {
        echo "  [ИНФО]     {$msg}\n";
    }

    protected function logWarn(string $msg): void {
        echo "  [ВНИМАНИЕ] {$msg}\n";
    }

    protected function logError(string $msg): void {
        echo "  [ОШИБКА]   {$msg}\n";
    }
}

// Запуск скрипта
$cli = new StopKranCli();
exit($cli->run($argv));
