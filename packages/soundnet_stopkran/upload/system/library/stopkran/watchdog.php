<?php
namespace SoundNet\StopKran;

class Watchdog {
    /**
     * Initialization flag
     *
     * @var bool
     */
    protected static $initialized = false;

    /**
     * Entry point: Hooked before OpenCart bootstrap
     *
     * @return void
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        // Ensure Recovery class is loaded
        if (!class_exists('SoundNet\StopKran\Recovery')) {
            $recoveryFile = __DIR__ . '/recovery.php';
            if (file_exists($recoveryFile)) {
                require_once($recoveryFile);
            }
        }

        // Check for emergency query token trigger (?stopkran_kill_all=<token>)
        if (isset($_GET['stopkran_kill_all'])) {
            self::handleEmergencyBypass((string)$_GET['stopkran_kill_all']);
        }

        // Register early shutdown handler for fatal errors
        register_shutdown_function([__CLASS__, 'interceptCrash']);
    }

    /**
     * Handles emergency instant recovery without admin login
     *
     * @param string $providedToken
     * @return void
     */
    public static function handleEmergencyBypass($providedToken) {
        $expectedToken = Recovery::getSecretToken();

        if (empty($expectedToken) || !hash_equals($expectedToken, $providedToken)) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            http_response_code(403);
            header('Content-Type: text/html; charset=UTF-8');
            echo self::renderSecurityRejectPage();
            exit;
        }

        // Execute Total Blackout
        $blackoutResult = Recovery::totalBlackout();

        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code(200);
        header('Content-Type: text/html; charset=UTF-8');
        echo self::renderRecoveryConfirmationPage($blackoutResult);
        exit;
    }

    /**
     * Intercepts fatal crashes and applies surgical isolation or blackout
     *
     * @return void
     */
    public static function interceptCrash() {
        $error = error_get_last();
        if (!$error) {
            return;
        }

        $fatalTypes = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
        if (!($error['type'] & $fatalTypes)) {
            return;
        }

        $filePath = str_replace('\\', '/', $error['file']);
        $message  = $error['message'];
        $line     = (int)$error['line'];

        $resolvedModule = self::resolveModuleIdentifier($filePath, $message);

        $actionReport = [];
        $tierLevel = 2;

        if ($resolvedModule) {
            // Tier 1: Surgical Disabling
            $tierLevel = 1;
            $actionReport = Recovery::disableModule($resolvedModule);
            Recovery::logCrash('Watchdog Tier 1 Crash Isolated', [
                'module'  => $resolvedModule,
                'file'    => $filePath,
                'line'    => $line,
                'message' => $message,
                'actions' => $actionReport
            ]);
        } else {
            // Tier 2: Total Blackout Mode
            $tierLevel = 2;
            $actionReport = Recovery::totalBlackout();
            Recovery::logCrash('Watchdog Tier 2 Total Blackout Triggered', [
                'file'    => $filePath,
                'line'    => $line,
                'message' => $message,
                'actions' => $actionReport
            ]);
        }

        // Clear existing buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Output clean HTTP 503 diagnostic maintenance UI
        http_response_code(503);
        header('Retry-After: 3');
        header('Content-Type: text/html; charset=UTF-8');

        echo self::renderDiagnosticCard($tierLevel, $resolvedModule, $error, $actionReport);
        exit;
    }

    /**
     * Analyzes file path, error message, and code context to isolate module code
     *
     * @param string $filePath
     * @param string $message
     * @return string|null
     */
    public static function resolveModuleIdentifier($filePath, $message) {
        // Pattern 1: Direct module path in admin or catalog (e.g., extension/module/soundnet_storefront.php)
        if (preg_match('#(?:catalog|admin)/controller/extension/module/([a-zA-Z0-9_\-]+)\.php#i', $filePath, $m)) {
            return $m[1];
        }
        if (preg_match('#(?:catalog|admin)/model/extension/module/([a-zA-Z0-9_\-]+)\.php#i', $filePath, $m)) {
            return $m[1];
        }
        if (preg_match('#system/library/([a-zA-Z0-9_\-]+)/#i', $filePath, $m)) {
            if (!in_array($m[1], ['cart', 'session', 'template', 'stopkran'])) {
                return $m[1];
            }
        }

        // Pattern 2: Crash inside modification storage directory
        $modDir = Recovery::getModificationDir();
        if (strpos($filePath, $modDir) !== false || strpos($filePath, 'storage/modification') !== false) {
            // Check error message for specific module class or method
            if (preg_match('/(?:Controller|Model)ExtensionModule([A-Z][a-zA-Z0-9_]*)/', $message, $m)) {
                // Convert PascalCase / CamelCase to snake_case module code
                return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $m[1]));
            }

            // Inspect the offending file around the crashed line
            if (is_file($filePath) && is_readable($filePath)) {
                $lines = @file($filePath);
                if ($lines && isset($lines[$errorLine = $filePath])) {
                    $start = max(0, $errorLine - 25);
                    $slice = array_slice($lines, $start, 50);
                    $snippet = implode('', $slice);

                    // Check for comments indicating module code
                    if (preg_match('/(?:MOD|Module|Extension|Author):\s*([a-zA-Z0-9_\-]+)/i', $snippet, $m)) {
                        return strtolower($m[1]);
                    }
                    if (preg_match('/extension\/module\/([a-zA-Z0-9_\-]+)/i', $snippet, $m)) {
                        return strtolower($m[1]);
                    }
                }
            }
        }

        // Pattern 3: Offending module mentioned directly in error message
        if (preg_match('/extension[\/\\_]module[\/\\_]([a-zA-Z0-9_\-]+)/i', $message, $m)) {
            return strtolower($m[1]);
        }
        if (preg_match('/module[\/\\_]([a-zA-Z0-9_\-]+)/i', $message, $m)) {
            return strtolower($m[1]);
        }

        return null;
    }

    /**
     * Render HTTP 503 Clean Diagnostic Maintenance UI (OpenCart Native Bootstrap Style)
     *
     * @param int         $tier
     * @param string|null $moduleCode
     * @param array       $error
     * @param array       $actions
     * @return string
     */
    protected static function renderDiagnosticCard($tier, $moduleCode, array $error, array $actions) {
        $cleanFile = htmlspecialchars($error['file'], ENT_QUOTES, 'UTF-8');
        $cleanMsg  = htmlspecialchars($error['message'], ENT_QUOTES, 'UTF-8');
        $cleanLine = (int)$error['line'];
        $cleanModule = htmlspecialchars((string)$moduleCode, ENT_QUOTES, 'UTF-8');

        $isTier1 = ($tier === 1);
        $badgeClass = $isTier1 ? 'label-warning' : 'label-danger';
        $badgeText  = $isTier1 ? 'УРОВЕНЬ 1: ТОЧЕЧНАЯ ИЗОЛЯЦИЯ МОДУЛЯ' : 'УРОВЕНЬ 2: ПОЛНЫЙ АВАРИЙНЫЙ БЛЭКАУТ';
        $titleText  = $isTier1
            ? "Аварийный выключатель сработал: модуль [{$cleanModule}] изолирован"
            : 'Аварийный выключатель сработал: система возвращена к чистому ядру';

        $actionDetails = '';
        if ($isTier1) {
            $actionDetails = "
                <tr><td><strong>Целевой сбойный модуль:</strong></td><td><code>{$cleanModule}</code></td></tr>
                <tr><td><strong>Отключено связанных модификаций:</strong></td><td><span class=\"badge\">" . ($actions['modifications_disabled'] ?? 1) . "</span></td></tr>
                <tr><td><strong>Отключено событий модуля:</strong></td><td><span class=\"badge\">" . ($actions['events_disabled'] ?? 0) . "</span></td></tr>
                <tr><td><strong>Кэш модификаций OpenCart:</strong></td><td><span class=\"label label-success\">Очищен и сброшен</span></td></tr>
            ";
        } else {
            $actionDetails = "
                <tr><td><strong>Все модификации OCMOD:</strong></td><td><span class=\"label label-danger\">Отключены (status = 0)</span></td></tr>
                <tr><td><strong>Сторонние события OpenCart:</strong></td><td><span class=\"label label-danger\">Отключены (status = 0)</span></td></tr>
                <tr><td><strong>Статусы модулей:</strong></td><td><span class=\"label label-default\">Сброшены в 0</span></td></tr>
                <tr><td><strong>Системный кэш и модификации:</strong></td><td><span class=\"label label-success\">Полностью очищены</span></td></tr>
            ";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoundNet Стоп-Кран &mdash; Диагностика аварийного выключателя</title>
    <link href="//fonts.googleapis.com/css?family=Open+Sans:400,600,700&subset=cyrillic,latin" rel="stylesheet" type="text/css" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #f4f6f8;
            color: #333333;
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 820px;
            width: 100%;
            background: #ffffff;
            border: 1px solid #e1e1e1;
            border-top: 4px solid #d9534f;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .header {
            padding: 18px 24px;
            border-bottom: 1px solid #eeeeee;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafafa;
        }
        .brand {
            font-size: 14px;
            font-weight: 700;
            color: #444;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .label {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            color: #ffffff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 3px;
        }
        .label-danger { background-color: #d9534f; }
        .label-warning { background-color: #f0ad4e; }
        .label-success { background-color: #5cb85c; }
        .label-default { background-color: #777777; }
        .badge {
            display: inline-block;
            min-width: 10px;
            padding: 3px 7px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            color: #ffffff;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            background-color: #777777;
            border-radius: 10px;
        }
        .content {
            padding: 24px;
        }
        h1 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #d9534f;
        }
        p.desc {
            color: #666;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #555;
            margin-bottom: 8px;
        }
        .code-box {
            background: #fdf7f7;
            border: 1px solid #eed3d7;
            border-radius: 3px;
            padding: 12px 16px;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 12px;
            color: #b94a48;
            margin-bottom: 20px;
            word-break: break-all;
        }
        .file-info {
            font-size: 11px;
            color: #888;
            margin-bottom: 6px;
            border-bottom: 1px solid #f2dede;
            padding-bottom: 4px;
        }
        .table-panel {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e1e1e1;
            margin-bottom: 24px;
            font-size: 12px;
        }
        .table-panel td {
            padding: 10px 14px;
            border-bottom: 1px solid #eeeeee;
        }
        .table-panel tr:last-child td {
            border-bottom: none;
        }
        .footer {
            padding: 16px 24px;
            background: #fafafa;
            border-top: 1px solid #eeeeee;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .footer-note {
            font-size: 12px;
            color: #888;
            font-family: monospace;
        }
        .btn {
            display: inline-block;
            padding: 7px 16px;
            margin-bottom: 0;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 3px;
            text-decoration: none;
        }
        .btn-success {
            color: #ffffff;
            background-color: #5cb85c;
            border-color: #4cae4c;
        }
        .btn-success:hover {
            background-color: #449d44;
        }
        .btn-default {
            color: #333333;
            background-color: #ffffff;
            border-color: #cccccc;
        }
        .btn-default:hover {
            background-color: #e6e6e6;
        }
        code {
            padding: 2px 4px;
            font-size: 90%;
            color: #c7254e;
            background-color: #f9f2f4;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="brand">
                <span class="label label-danger">STOP-KRAN</span>
                SoundNet Стоп-Кран &mdash; Защитный сторож OpenCart
            </div>
            <div>
                <span class="label {$badgeClass}">{$badgeText}</span>
            </div>
        </div>
        <div class="content">
            <h1>{$titleText}</h1>
            <p class="desc">
                Необработанная критическая ошибка PHP была перехвачена защитным контуром до полного падения витрины («белого экрана»). Аварийный механизм Стоп-Кран изолировал источник сбоя и очистил кэш модификаций.
            </p>

            <div class="section-title">Сведения об инциденте</div>
            <div class="code-box">
                <div class="file-info">ФАЙЛ СБОЯ: {$cleanFile} (строка {$cleanLine})</div>
                <div>{$cleanMsg}</div>
            </div>

            <div class="section-title">Выполненные автоматические действия</div>
            <table class="table-panel">
                <tbody>
                    {$actionDetails}
                </tbody>
            </table>
        </div>
        <div class="footer">
            <div class="footer-note">Журнал: system/storage/logs/stopkran_crash.log</div>
            <div style="display: flex; gap: 8px;">
                <a href="javascript:location.reload()" class="btn btn-success">Перезагрузить страницу</a>
                <a href="admin/" class="btn btn-default">Панель управления</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render HTTP 200 Emergency Bypass Recovery Confirmation Page (OpenCart Native Bootstrap Style)
     *
     * @param array $result
     * @return string
     */
    protected static function renderRecoveryConfirmationPage(array $result) {
        $timestamp = date('Y-m-d H:i:s');
        $mods = $result['modifications_disabled'] ?? 0;
        $evts = $result['events_disabled'] ?? 0;
        $sets = $result['settings_disabled'] ?? 0;

        return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аварийный Стоп-Кран &mdash; Восстановление завершено</title>
    <link href="//fonts.googleapis.com/css?family=Open+Sans:400,600,700&subset=cyrillic,latin" rel="stylesheet" type="text/css" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #f4f6f8;
            color: #333333;
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 640px;
            width: 100%;
            background: #ffffff;
            border: 1px solid #e1e1e1;
            border-top: 4px solid #5cb85c;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 28px;
        }
        .status-tag {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #5cb85c;
            margin-bottom: 6px;
        }
        h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333333;
        }
        p {
            color: #666666;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 22px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 22px;
        }
        .stat-box {
            background: #fafafa;
            border: 1px solid #e8e8e8;
            border-radius: 3px;
            padding: 14px;
            text-align: center;
        }
        .stat-val {
            font-size: 24px;
            font-weight: 700;
            color: #1e91cf;
        }
        .stat-lbl {
            font-size: 11px;
            font-weight: 600;
            color: #777777;
            margin-top: 4px;
        }
        .meta-strip {
            font-size: 11px;
            color: #888888;
            margin-bottom: 22px;
            border-top: 1px solid #eeeeee;
            padding-top: 10px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
        .btn-link {
            flex: 1;
            padding: 9px 16px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 3px;
        }
        .btn-primary {
            background: #1e91cf;
            color: #ffffff;
            border: 1px solid #197bb0;
        }
        .btn-primary:hover {
            background: #197bb0;
        }
        .btn-default {
            background: #ffffff;
            color: #333333;
            border: 1px solid #cccccc;
        }
        .btn-default:hover {
            background: #e6e6e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status-tag">[ВОССТАНОВЛЕНИЕ ВЫПОЛНЕНО]</div>
        <h1>Аварийный блэкаут успешно активирован</h1>
        <p>
            Аварийный контур защиты Стоп-Кран был приведен в действие через прямой токен обхода. Модификаторы OCMOD, пользовательские события и статусы модулей отключены в базе данных. Кэш модификаций полностью очищен.
        </p>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-val">{$mods}</div>
                <div class="stat-lbl">Модификаторов отключено</div>
            </div>
            <div class="stat-box">
                <div class="stat-val">{$evts}</div>
                <div class="stat-lbl">Событий отключено</div>
            </div>
            <div class="stat-box">
                <div class="stat-val">{$sets}</div>
                <div class="stat-lbl">Модулей сброшено</div>
            </div>
        </div>
        <div class="meta-strip">
            ВРЕМЯ СРАБАТЫВАНИЯ: {$timestamp} | КЭШ ХРАНИЛИЩА ОЧИЩЕН
        </div>
        <div class="btn-group">
            <a href="admin/" class="btn-link btn-primary">Войти в панель управления</a>
            <a href="./" class="btn-link btn-default">Перейти на витрину</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render HTTP 403 Forbidden Security Rejection Page (OpenCart Native Bootstrap Style)
     *
     * @return string
     */
    protected static function renderSecurityRejectPage() {
        return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>403 Доступ запрещен &mdash; Стоп-Кран</title>
    <link href="//fonts.googleapis.com/css?family=Open+Sans:400,600,700&subset=cyrillic,latin" rel="stylesheet" type="text/css" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #f4f6f8;
            color: #333333;
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .box {
            background: #ffffff;
            border: 1px solid #e1e1e1;
            border-top: 4px solid #d9534f;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 24px 30px;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        h2 { font-size: 18px; margin-bottom: 10px; color: #d9534f; font-weight: 700; }
        p { color: #666666; font-size: 13px; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="box">
        <h2>403 Доступ запрещен</h2>
        <p>Неверный или отсутствующий секретный ключ аварийного восстановления Стоп-Кран.</p>
    </div>
</body>
</html>
HTML;
    }
}

