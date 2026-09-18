<?php
namespace SoundNet\StopKran;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;

class Tester {
    /**
     * Cached path to PHP CLI executable
     *
     * @var string|null
     */
    protected static $phpCliPath = null;

    /**
     * Detect and return the path to the PHP CLI executable
     *
     * @return string|null
     */
    public static function getPhpCliPath() {
        if (self::$phpCliPath !== null) {
            return self::$phpCliPath;
        }

        $candidates = [
            'D:/OSPanel/modules/php/PHP_7.4/php.exe',
            'D:\\OSPanel\\modules\\php\\PHP_7.4\\php.exe',
            'D:/OSPanel/modules/php/PHP_7.2/php.exe',
            'D:\\OSPanel\\modules\\php\\PHP_7.2\\php.exe',
            defined('PHP_BINARY') ? PHP_BINARY : '',
            PHP_BINDIR ? (PHP_BINDIR . DIRECTORY_SEPARATOR . (DIRECTORY_SEPARATOR === '\\' ? 'php.exe' : 'php')) : ''
        ];

        foreach ($candidates as $candidate) {
            if (!empty($candidate) && is_file($candidate) && is_executable($candidate)) {
                self::$phpCliPath = str_replace('\\', '/', $candidate);
                return self::$phpCliPath;
            }
        }

        // Fallback test via shell
        $testCmd = (DIRECTORY_SEPARATOR === '\\') ? 'where php 2>nul' : 'which php 2>/dev/null';
        $out = @shell_exec($testCmd);
        if ($out) {
            $lines = explode("\n", trim($out));
            if (!empty($lines[0]) && is_file(trim($lines[0]))) {
                self::$phpCliPath = str_replace('\\', '/', trim($lines[0]));
                return self::$phpCliPath;
            }
        }

        return null;
    }

    /**
     * Resolve root project path
     *
     * @return string
     */
    public static function getRootDir() {
        if (defined('DIR_CATALOG')) {
            return rtrim(str_replace('\\', '/', dirname(DIR_CATALOG)), '/') . '/';
        }
        if (defined('DIR_SYSTEM')) {
            return rtrim(str_replace('\\', '/', dirname(DIR_SYSTEM)), '/') . '/';
        }
        return rtrim(str_replace('\\', '/', realpath(__DIR__ . '/../../..')), '/') . '/';
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
        if (defined('DIR_STORAGE') && is_dir(DIR_STORAGE . 'logs/')) {
            return rtrim(str_replace('\\', '/', DIR_STORAGE . 'logs/'), '/') . '/';
        }
        if (is_dir('D:/OSPanel/domains/storage/logs/')) {
            return 'D:/OSPanel/domains/storage/logs/';
        }
        $fallback = self::getRootDir() . 'system/storage/logs/';
        if (is_dir($fallback)) {
            return $fallback;
        }
        return rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/') . '/';
    }

    /**
     * Locate all files associated with a module code
     *
     * @param string $moduleCode
     * @return array
     */
    public static function getModuleFiles($moduleCode) {
        $root = self::getRootDir();
        $code = preg_replace('/[^a-zA-Z0-9_\-]/', '', strtolower($moduleCode));
        $files = [
            'controllers' => [],
            'models'      => [],
            'templates'   => [],
            'languages'   => [],
            'libraries'   => [],
            'all'         => []
        ];

        // Specific potential paths
        $candidates = [
            'controllers' => [
                $root . 'admin/controller/extension/module/' . $code . '.php',
                $root . 'catalog/controller/extension/module/' . $code . '.php',
            ],
            'models' => [
                $root . 'admin/model/extension/module/' . $code . '.php',
                $root . 'catalog/model/extension/module/' . $code . '.php',
            ],
            'templates' => [
                $root . 'admin/view/template/extension/module/' . $code . '.twig',
                $root . 'catalog/view/theme/default/template/extension/module/' . $code . '.twig',
            ],
            'libraries' => [
                $root . 'system/library/' . $code . '.php',
            ]
        ];

        // Library folder check (shallow or excluding virtualenvs/vendors)
        $libDir = $root . 'system/library/' . $code;
        if (is_dir($libDir)) {
            $libPhp = glob($libDir . '/*.php');
            if ($libPhp) {
                foreach ($libPhp as $lp) {
                    $files['libraries'][] = str_replace('\\', '/', $lp);
                }
            }
        }

        // Language files in all languages (single directory level)
        $langDirs = glob($root . 'admin/language/*/extension/module/' . $code . '.php');
        if ($langDirs) {
            foreach ($langDirs as $lf) {
                $files['languages'][] = str_replace('\\', '/', $lf);
            }
        }
        $langDirs2 = glob($root . 'catalog/language/*/extension/module/' . $code . '.php');
        if ($langDirs2) {
            foreach ($langDirs2 as $lf) {
                $files['languages'][] = str_replace('\\', '/', $lf);
            }
        }

        // Add verified candidate files
        foreach ($candidates as $category => $paths) {
            foreach ($paths as $p) {
                if (is_file($p)) {
                    $files[$category][] = str_replace('\\', '/', $p);
                }
            }
        }

        // Combine all unique files
        $all = [];
        foreach ($files as $k => $list) {
            if ($k !== 'all') {
                $all = array_merge($all, $list);
            }
        }
        $files['all'] = array_values(array_unique($all));

        return $files;
    }

    /**
     * Test single PHP file for syntax errors using php -l or tokenizer
     *
     * @param string $filePath
     * @return array [ 'ok' => bool, 'error' => string, 'line' => int ]
     */
    public static function checkPhpSyntax($filePath) {
        if (!is_file($filePath)) {
            return ['ok' => false, 'error' => 'Файл не найден', 'line' => 0];
        }

        $phpCli = self::getPhpCliPath();
        if ($phpCli && is_executable($phpCli)) {
            $cmd = escapeshellarg($phpCli) . ' -l ' . escapeshellarg($filePath) . ' 2>&1';
            $output = @shell_exec($cmd);

            if ($output && stripos($output, 'No syntax errors detected') !== false) {
                return ['ok' => true, 'error' => '', 'line' => 0];
            }

            if ($output && preg_match('/Parse error:\s*(.+?)\s+in\s+.+?\s+on line\s+(\d+)/i', $output, $m)) {
                return [
                    'ok'    => false,
                    'error' => trim($m[1]),
                    'line'  => (int)$m[2]
                ];
            }

            if ($output && preg_match('/Fatal error:\s*(.+?)\s+in\s+.+?\s+on line\s+(\d+)/i', $output, $m)) {
                return [
                    'ok'    => false,
                    'error' => trim($m[1]),
                    'line'  => (int)$m[2]
                ];
            }
        }

        // Fallback tokenizer test
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return ['ok' => false, 'error' => 'Не удалось прочитать файл', 'line' => 0];
        }

        try {
            $tokens = @token_get_all($content, TOKEN_PARSE);
            return ['ok' => true, 'error' => '', 'line' => 0];
        } catch (Exception $e) {
            return [
                'ok'    => false,
                'error' => $e->getMessage(),
                'line'  => (int)$e->getLine()
            ];
        } catch (\ParseError $e) {
            return [
                'ok'    => false,
                'error' => $e->getMessage(),
                'line'  => (int)$e->getLine()
            ];
        }
    }

    /**
     * Check Twig template syntax (basic balancing of tags)
     *
     * @param string $filePath
     * @return array [ 'ok' => bool, 'error' => string ]
     */
    public static function checkTwigSyntax($filePath) {
        if (!is_file($filePath)) {
            return ['ok' => false, 'error' => 'Шаблон не найден'];
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return ['ok' => false, 'error' => 'Не удалось прочитать шаблон'];
        }

        // Check matching {{ }} and {% %}
        $openPrint = substr_count($content, '{{');
        $closePrint = substr_count($content, '}}');
        if ($openPrint !== $closePrint) {
            return [
                'ok'    => false,
                'error' => "Несовпадение тегов вывода: '{{' ({$openPrint}) != '}}' ({$closePrint})"
            ];
        }

        $openBlock = substr_count($content, '{%');
        $closeBlock = substr_count($content, '%}');
        if ($openBlock !== $closeBlock) {
            return [
                'ok'    => false,
                'error' => "Несовпадение управляющих тегов: '{%' ({$openBlock}) != '%}' ({$closeBlock})"
            ];
        }

        // Check common block unclosed tags (for / endfor, if / endif)
        $ifCount = preg_match_all('/\{%\s*if\s+/', $content);
        $endifCount = preg_match_all('/\{%\s*endif\s*%\}/', $content);
        if ($ifCount !== $endifCount) {
            return [
                'ok'    => false,
                'error' => "Несовпадение блоков условия: if ({$ifCount}) != endif ({$endifCount})"
            ];
        }

        $forCount = preg_match_all('/\{%\s*for\s+/', $content);
        $endforCount = preg_match_all('/\{%\s*endfor\s*%\}/', $content);
        if ($forCount !== $endforCount) {
            return [
                'ok'    => false,
                'error' => "Несовпадение блоков цикла: for ({$forCount}) != endfor ({$endforCount})"
            ];
        }

        return ['ok' => true, 'error' => ''];
    }

    /**
     * Check OpenCart class naming and structure
     *
     * @param string $filePath
     * @param string $expectedPrefix e.g. ControllerExtensionModule
     * @return array [ 'ok' => bool, 'class_name' => string, 'error' => string ]
     */
    public static function checkClassStructure($filePath, $expectedPrefix) {
        $content = @file_get_contents($filePath);
        if (!$content) {
            return ['ok' => false, 'class_name' => '', 'error' => 'Файл пуст или недоступен'];
        }

        if (preg_match('/class\s+([a-zA-Z0-9_]+)(?:\s+extends\s+([a-zA-Z0-9_\\\\]+))?/i', $content, $m)) {
            $className = $m[1];
            $parent = $m[2] ?? '';

            if (stripos($className, $expectedPrefix) === false) {
                return [
                    'ok'         => false,
                    'class_name' => $className,
                    'error'      => "Класс '{$className}' не содержит ожидаемого префикса '{$expectedPrefix}'"
                ];
            }

            return [
                'ok'         => true,
                'class_name' => $className,
                'parent'     => $parent,
                'error'      => ''
            ];
        }

        return [
            'ok'         => false,
            'class_name' => '',
            'error'      => 'В файле не обнаружено объявление PHP класса'
        ];
    }

    /**
     * Run full diagnostic test on a module
     *
     * @param string $moduleCode
     * @param string $trigger 'install' | 'add' | 'update' | 'manual'
     * @return array
     */
    public static function testModule($moduleCode, $trigger = 'manual') {
        $startTime = microtime(true);
        $code = strtolower(trim($moduleCode));
        $files = self::getModuleFiles($code);

        $results = [
            'module'         => $code,
            'trigger'        => $trigger,
            'timestamp'      => date('Y-m-d H:i:s'),
            'passed'         => true,
            'status'         => 'success', // 'success' | 'warning' | 'danger'
            'total_files'    => count($files['all']),
            'syntax_checks'  => [],
            'class_checks'   => [],
            'twig_checks'    => [],
            'language_checks'=> [],
            'errors'         => [],
            'warnings'       => [],
            'execution_time' => 0
        ];

        if (empty($files['all'])) {
            $results['passed'] = false;
            $results['status'] = 'warning';
            $results['warnings'][] = "Файлы модуля '{$code}' не найдены в файловой системе магазина.";
            $results['execution_time'] = round((microtime(true) - $startTime) * 1000, 2);
            self::logTestResult($results);
            return $results;
        }

        // 1. Syntax Check on all PHP files
        foreach ($files['all'] as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext === 'php') {
                $check = self::checkPhpSyntax($file);
                $relPath = str_replace(self::getRootDir(), '', $file);
                $results['syntax_checks'][] = [
                    'file'  => $relPath,
                    'ok'    => $check['ok'],
                    'error' => $check['error'],
                    'line'  => $check['line']
                ];

                if (!$check['ok']) {
                    $results['passed'] = false;
                    $results['status'] = 'danger';
                    $results['errors'][] = "Синтаксическая ошибка в {$relPath}:{$check['line']} — {$check['error']}";
                }
            }
        }

        // 2. Class structure checks
        foreach ($files['controllers'] as $ctrlFile) {
            $relPath = str_replace(self::getRootDir(), '', $ctrlFile);
            $classCheck = self::checkClassStructure($ctrlFile, 'ControllerExtensionModule');
            $results['class_checks'][] = array_merge(['file' => $relPath], $classCheck);

            if (!$classCheck['ok']) {
                $results['warnings'][] = "Предупреждение структуры контроллера {$relPath}: {$classCheck['error']}";
            }
        }

        foreach ($files['models'] as $modelFile) {
            $relPath = str_replace(self::getRootDir(), '', $modelFile);
            $classCheck = self::checkClassStructure($modelFile, 'ModelExtensionModule');
            $results['class_checks'][] = array_merge(['file' => $relPath], $classCheck);

            if (!$classCheck['ok']) {
                $results['warnings'][] = "Предупреждение структуры модели {$relPath}: {$classCheck['error']}";
            }
        }

        // 3. Twig Template Checks
        foreach ($files['templates'] as $tplFile) {
            $relPath = str_replace(self::getRootDir(), '', $tplFile);
            $twigCheck = self::checkTwigSyntax($tplFile);
            $results['twig_checks'][] = array_merge(['file' => $relPath], $twigCheck);

            if (!$twigCheck['ok']) {
                $results['warnings'][] = "Ошибка в шаблоне Twig {$relPath}: {$twigCheck['error']}";
                if ($results['status'] !== 'danger') {
                    $results['status'] = 'warning';
                }
            }
        }

        // 4. Language Files Check
        foreach ($files['languages'] as $langFile) {
            $relPath = str_replace(self::getRootDir(), '', $langFile);
            $check = self::checkPhpSyntax($langFile);
            $results['language_checks'][] = [
                'file'  => $relPath,
                'ok'    => $check['ok'],
                'error' => $check['error']
            ];

            if (!$check['ok']) {
                $results['errors'][] = "Ошибка в языковом файле {$relPath}: {$check['error']}";
                $results['passed'] = false;
                $results['status'] = 'danger';
            }
        }

        if (!empty($results['warnings']) && $results['status'] === 'success') {
            $results['status'] = 'warning';
        }

        $results['execution_time'] = round((microtime(true) - $startTime) * 1000, 2);

        // Audit log in stopkran_test.log
        self::logTestResult($results);

        // Store into last tests registry
        self::saveTestAudit($results);

        return $results;
    }

    /**
     * Test all installed/available modules in the store
     *
     * @return array
     */
    public static function testAllModules() {
        $root = self::getRootDir();
        $moduleFiles = glob($root . 'admin/controller/extension/module/*.php');
        $reports = [];

        if ($moduleFiles) {
            foreach ($moduleFiles as $mf) {
                $code = basename($mf, '.php');
                $reports[$code] = self::testModule($code, 'manual_all');
            }
        }

        return $reports;
    }

    /**
     * Write human-readable test log entry to stopkran_test.log in Russian
     *
     * @param array $res
     * @return void
     */
    public static function logTestResult(array $res) {
        $logFile = self::getLogsDir() . 'stopkran_test.log';
        $time = $res['timestamp'] ?? date('Y-m-d H:i:s');
        $module = $res['module'] ?? 'unknown';
        $trigger = $res['trigger'] ?? 'manual';

        $triggerLabels = [
            'install'      => 'Установка модуля',
            'add'          => 'Добавление модуля',
            'update'       => 'Обновление настроек',
            'manual'       => 'Ручной запуск',
            'manual_all'   => 'Пакетная проверка',
            'auto_trigger' => 'Автоматический перехват'
        ];
        $triggerText = $triggerLabels[$trigger] ?? $trigger;

        $statusLabel = $res['passed'] ? '[УСПЕШНО]' : '[ОШИБКА]';
        $summary = "{$statusLabel} [{$time}] Модуль: {$module} | Событие: {$triggerText} | Файлов: {$res['total_files']} | Время: {$res['execution_time']} мс";

        $lines = [];
        $lines[] = str_repeat('-', 72);
        $lines[] = $summary;

        if (!empty($res['errors'])) {
            $lines[] = "  КРИТИЧЕСКИЕ ОШИБКИ (" . count($res['errors']) . "):";
            foreach ($res['errors'] as $err) {
                $lines[] = "    - " . $err;
            }
        }

        if (!empty($res['warnings'])) {
            $lines[] = "  ПРЕДУПРЕЖДЕНИЯ (" . count($res['warnings']) . "):";
            foreach ($res['warnings'] as $warn) {
                $lines[] = "    - " . $warn;
            }
        }

        if (empty($res['errors']) && empty($res['warnings'])) {
            $lines[] = "  Все проверки успешно пройдены: синтаксис PHP корректен, шаблоны валидны.";
        }

        $lines[] = "";

        @file_put_contents($logFile, implode(PHP_EOL, $lines), FILE_APPEND | LOCK_EX);
    }

    /**
     * Store recent test result in JSON file for UI display
     *
     * @param array $res
     * @return void
     */
    public static function saveTestAudit(array $res) {
        $historyFile = self::getLogsDir() . 'stopkran_test_history.json';
        $history = [];

        if (is_file($historyFile)) {
            $raw = @file_get_contents($historyFile);
            if ($raw) {
                $history = json_decode($raw, true) ?: [];
            }
        }

        // Keep last 50 tests
        array_unshift($history, [
            'module'         => $res['module'],
            'trigger'        => $res['trigger'],
            'timestamp'      => $res['timestamp'],
            'status'         => $res['status'],
            'passed'         => $res['passed'],
            'total_files'    => $res['total_files'],
            'execution_time' => $res['execution_time'],
            'errors_count'   => count($res['errors'] ?? []),
            'warnings_count' => count($res['warnings'] ?? []),
            'errors'         => $res['errors'] ?? [],
            'warnings'       => $res['warnings'] ?? []
        ]);

        if (count($history) > 50) {
            $history = array_slice($history, 0, 50);
        }

        @file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * Get recent test audit history
     *
     * @param int $limit
     * @return array
     */
    public static function getTestAuditHistory($limit = 20) {
        $historyFile = self::getLogsDir() . 'stopkran_test_history.json';
        if (!is_file($historyFile)) {
            return [];
        }

        $raw = @file_get_contents($historyFile);
        if (!$raw) {
            return [];
        }

        $history = json_decode($raw, true) ?: [];
        return array_slice($history, 0, $limit);
    }
}
