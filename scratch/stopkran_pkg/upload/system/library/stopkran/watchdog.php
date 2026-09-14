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
     * Render HTTP 503 Clean Diagnostic Maintenance UI (Flat Industrial)
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
        $badgeColor = $isTier1 ? '#d29922' : '#da3633';
        $badgeText  = $isTier1 ? 'TIER 1 : SURGICAL DEACTIVATION' : 'TIER 2 : TOTAL BLACKOUT RESET';
        $titleText  = $isTier1
            ? "Circuit Breaker Tripped: Module [{$cleanModule}] Isolated"
            : 'Circuit Breaker Tripped: System Restored to Native Core';

        $actionDetails = '';
        if ($isTier1) {
            $actionDetails = "
                <div class=\"action-row\"><span class=\"action-key\">TARGET MODULE:</span><span class=\"action-val font-mono\">{$cleanModule}</span></div>
                <div class=\"action-row\"><span class=\"action-key\">MODIFICATIONS DEACTIVATED:</span><span class=\"action-val font-mono\">" . ($actions['modifications_disabled'] ?? 1) . "</span></div>
                <div class=\"action-row\"><span class=\"action-key\">RELATED EVENTS DISABLED:</span><span class=\"action-val font-mono\">" . ($actions['events_disabled'] ?? 0) . "</span></div>
                <div class=\"action-row\"><span class=\"action-key\">STORAGE MODIFICATION CACHE:</span><span class=\"action-val font-mono\">UNLINKED &amp; PURGED</span></div>
            ";
        } else {
            $actionDetails = "
                <div class=\"action-row\"><span class=\"action-key\">ALL MODIFICATIONS:</span><span class=\"action-val font-mono\">STATUS = 0 (DISABLED)</span></div>
                <div class=\"action-row\"><span class=\"action-key\">NON-CORE EVENTS:</span><span class=\"action-val font-mono\">STATUS = 0 (DISABLED)</span></div>
                <div class=\"action-row\"><span class=\"action-key\">MODULE SETTINGS:</span><span class=\"action-val font-mono\">RESET TO '0'</span></div>
                <div class=\"action-row\"><span class=\"action-key\">STORAGE &amp; SYSTEM CACHE:</span><span class=\"action-val font-mono\">COMPLETELY FLUSHED</span></div>
            ";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoundNet Stop-Kran &mdash; Circuit Breaker Diagnostic</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #0b0f17;
            color: #e6edf3;
            font-family: -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .container {
            max-width: 820px;
            width: 100%;
            background: #141922;
            border: 1px solid #30363d;
            border-left: 6px solid {$badgeColor};
            border-radius: 0;
            box-shadow: none;
        }
        .header {
            padding: 20px 24px;
            border-bottom: 1px solid #21262d;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0e121a;
        }
        .brand {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #8b949e;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: ui-monospace, Consolas, monospace;
        }
        .brand-marker {
            width: 10px;
            height: 10px;
            background: {$badgeColor};
            display: inline-block;
        }
        .badge {
            background: transparent;
            color: {$badgeColor};
            border: 1px solid {$badgeColor};
            font-size: 11px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 0;
            letter-spacing: 0.08em;
            font-family: ui-monospace, Consolas, monospace;
        }
        .content {
            padding: 24px;
        }
        h1 {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #f0f6fc;
            letter-spacing: -0.01em;
        }
        p.desc {
            color: #8b949e;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .section-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #8b949e;
            margin-bottom: 8px;
            font-family: ui-monospace, Consolas, monospace;
        }
        .code-box {
            background: #080c13;
            border: 1px solid #30363d;
            border-radius: 0;
            padding: 14px 18px;
            font-family: ui-monospace, Consolas, monospace;
            font-size: 12px;
            color: #c9d1d9;
            line-height: 1.5;
            word-break: break-all;
            margin-bottom: 20px;
        }
        .code-box .file-info {
            color: #8b949e;
            margin-bottom: 6px;
            font-size: 11px;
            border-bottom: 1px solid #161b22;
            padding-bottom: 6px;
        }
        .code-box .err-msg {
            color: #f85149;
            font-weight: 600;
        }
        .action-panel {
            background: #0e121a;
            border: 1px solid #30363d;
            border-radius: 0;
            padding: 14px 18px;
            margin-bottom: 24px;
        }
        .action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid #1c222c;
            font-size: 12px;
        }
        .action-row:last-child {
            border-bottom: none;
        }
        .action-key {
            color: #8b949e;
            font-size: 11px;
            letter-spacing: 0.04em;
        }
        .action-val {
            color: #f0f6fc;
            font-weight: 600;
        }
        .footer {
            padding: 16px 24px;
            background: #0e121a;
            border-top: 1px solid #21262d;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .footer-note {
            font-size: 12px;
            color: #8b949e;
            font-family: ui-monospace, Consolas, monospace;
        }
        .btn-refresh {
            background: #238636;
            color: #ffffff;
            border: 1px solid #2ea043;
            border-radius: 0;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color 0.1s ease;
        }
        .btn-refresh:hover {
            background: #2ea043;
        }
        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="brand">
                <span class="brand-marker"></span>
                SoundNet Stop-Kran Watchdog
            </div>
            <div class="badge">{$badgeText}</div>
        </div>
        <div class="content">
            <h1>{$titleText}</h1>
            <p class="desc">
                An unhandled fatal PHP fault was caught prior to page failure. The emergency circuit breaker neutralized the crash source and unlinked cached modifications.
            </p>

            <div class="section-label">Incident Telemetry</div>
            <div class="code-box">
                <div class="file-info">FAULT ORIGIN: {$cleanFile}:{$cleanLine}</div>
                <div class="err-msg">{$cleanMsg}</div>
            </div>

            <div class="section-label">Automated Actions Executed</div>
            <div class="action-panel">
                {$actionDetails}
            </div>
        </div>
        <div class="footer">
            <div class="footer-note">LOG: system/storage/logs/stopkran_crash.log</div>
            <div>
                <a href="javascript:location.reload()" class="btn-refresh">Reload Page</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render HTTP 200 Emergency Bypass Recovery Confirmation Page (Flat Industrial)
     *
     * @param array $result
     * @return string
     */
    protected static function renderRecoveryConfirmationPage(array $result) {
        $timestamp = gmdate('Y-m-d H:i:s') . ' UTC';
        $mods = $result['modifications_disabled'] ?? 0;
        $evts = $result['events_disabled'] ?? 0;
        $sets = $result['settings_disabled'] ?? 0;

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Stop-Kran &mdash; Recovery Executed</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #0b0f17;
            color: #e6edf3;
            font-family: -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .container {
            max-width: 640px;
            width: 100%;
            background: #141922;
            border: 1px solid #30363d;
            border-top: 5px solid #238636;
            border-radius: 0;
            box-shadow: none;
            padding: 32px;
        }
        .status-tag {
            font-family: ui-monospace, Consolas, monospace;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #3fb950;
            margin-bottom: 8px;
        }
        h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #f0f6fc;
            letter-spacing: -0.01em;
        }
        p {
            color: #8b949e;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .telemetry-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: #30363d;
            border: 1px solid #30363d;
            margin-bottom: 24px;
        }
        .telemetry-box {
            background: #0d1117;
            padding: 14px;
            text-align: center;
        }
        .telemetry-val {
            font-size: 24px;
            font-weight: 700;
            font-family: ui-monospace, Consolas, monospace;
            color: #58a6ff;
        }
        .telemetry-lbl {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #8b949e;
            margin-top: 4px;
        }
        .meta-strip {
            font-family: ui-monospace, Consolas, monospace;
            font-size: 11px;
            color: #6e7681;
            margin-bottom: 24px;
            border-top: 1px solid #21262d;
            padding-top: 12px;
        }
        .btn-group {
            display: flex;
            gap: 12px;
        }
        .btn-link {
            flex: 1;
            padding: 10px 16px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            text-decoration: none;
            border-radius: 0;
            transition: background-color 0.1s ease;
        }
        .btn-primary {
            background: #238636;
            color: #ffffff;
            border: 1px solid #2ea043;
        }
        .btn-primary:hover {
            background: #2ea043;
        }
        .btn-secondary {
            background: #21262d;
            color: #c9d1d9;
            border: 1px solid #363b42;
        }
        .btn-secondary:hover {
            background: #30363d;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status-tag">[RECOVERY COMPLETE]</div>
        <h1>Total Blackout Executed</h1>
        <p>
            Emergency circuit breaker activated via bypass authorization. Active modifications, custom events, and extension statuses have been disabled in the database, and caches flushed.
        </p>
        <div class="telemetry-grid">
            <div class="telemetry-box">
                <div class="telemetry-val">{$mods}</div>
                <div class="telemetry-lbl">Modifications Disabled</div>
            </div>
            <div class="telemetry-box">
                <div class="telemetry-val">{$evts}</div>
                <div class="telemetry-lbl">Events Disabled</div>
            </div>
            <div class="telemetry-box">
                <div class="telemetry-val">{$sets}</div>
                <div class="telemetry-lbl">Settings Reset</div>
            </div>
        </div>
        <div class="meta-strip">
            EXECUTION TIME: {$timestamp} | CACHE UNLINKED
        </div>
        <div class="btn-group">
            <a href="admin/" class="btn-link btn-primary">Go to Admin Login</a>
            <a href="./" class="btn-link btn-secondary">Go to Storefront</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render HTTP 403 Forbidden Security Rejection Page (Flat Industrial)
     *
     * @return string
     */
    protected static function renderSecurityRejectPage() {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied &mdash; Emergency Circuit Breaker</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #0b0f17;
            color: #f85149;
            font-family: ui-monospace, Consolas, monospace;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .box {
            background: #141922;
            border: 1px solid #8b1820;
            border-left: 6px solid #da3633;
            border-radius: 0;
            box-shadow: none;
            padding: 24px 30px;
            max-width: 480px;
            width: 100%;
        }
        h2 { font-size: 16px; margin-bottom: 8px; color: #f85149; letter-spacing: 0.05em; text-transform: uppercase; }
        p { color: #8b949e; font-size: 13px; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="box">
        <h2>403 Forbidden</h2>
        <p>Invalid or missing emergency recovery authorization token.</p>
    </div>
</body>
</html>
HTML;
    }
}
