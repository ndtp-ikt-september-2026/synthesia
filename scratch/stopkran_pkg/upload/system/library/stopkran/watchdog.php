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
     * Render HTTP 503 Clean Diagnostic Maintenance UI
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
        $badgeColor = $isTier1 ? '#f59e0b' : '#ef4444';
        $badgeText  = $isTier1 ? 'TIER 1 : SURGICAL ISOLATION ENGAGED' : 'TIER 2 : TOTAL BLACKOUT ENGAGED';
        $titleText  = $isTier1
            ? "Circuit Breaker Tripped: Module &lsquo;{$cleanModule}&rsquo; Auto-Disabled"
            : 'Circuit Breaker Tripped: System Restored to Clean Native Core';

        $actionDetails = '';
        if ($isTier1) {
            $actionDetails = "
                <div class=\"action-item\"><span>Module Identifier:</span> <code>{$cleanModule}</code></div>
                <div class=\"action-item\"><span>Modifications Deactivated:</span> <strong>" . ($actions['modifications_disabled'] ?? 1) . "</strong></div>
                <div class=\"action-item\"><span>Related Events Deactivated:</span> <strong>" . ($actions['events_disabled'] ?? 0) . "</strong></div>
                <div class=\"action-item\"><span>OCMOD Storage Cache:</span> <strong>Purged &amp; Cleared</strong></div>
            ";
        } else {
            $actionDetails = "
                <div class=\"action-item\"><span>Modifications Deactivated:</span> <strong>" . ($actions['modifications_disabled'] ?? 'All') . "</strong></div>
                <div class=\"action-item\"><span>Non-Core Events Deactivated:</span> <strong>" . ($actions['events_disabled'] ?? 'All') . "</strong></div>
                <div class=\"action-item\"><span>Module Settings Status:</span> <strong>Reset to 0 (Disabled)</strong></div>
                <div class=\"action-item\"><span>Modification &amp; System Caches:</span> <strong>Completely Flushed</strong></div>
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
        :root {
            --bg-body: #0b0f19;
            --bg-card: #111827;
            --border-card: #1f2937;
            --text-main: #f9fafb;
            --text-muted: #9ca3af;
            --accent-red: #ef4444;
            --accent-amber: #f59e0b;
            --accent-cyan: #06b6d4;
            --code-bg: #030712;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .container {
            max-width: 820px;
            width: 100%;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 14px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            overflow: hidden;
        }
        .header {
            padding: 24px 30px;
            border-bottom: 1px solid var(--border-card);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.02);
        }
        .brand {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: {$badgeColor};
            box-shadow: 0 0 12px {$badgeColor};
        }
        .badge {
            background: {$badgeColor}22;
            color: {$badgeColor};
            border: 1px solid {$badgeColor}55;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 9999px;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 30px;
        }
        h1 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 14px;
            color: #ffffff;
            line-height: 1.4;
        }
        p.desc {
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--accent-cyan);
            margin-bottom: 10px;
        }
        .code-box {
            background: var(--code-bg);
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 16px 20px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
            color: #e2e8f0;
            line-height: 1.5;
            word-break: break-all;
            margin-bottom: 24px;
        }
        .code-box .file-info {
            color: #94a3b8;
            margin-bottom: 8px;
            font-size: 12px;
        }
        .code-box .err-msg {
            color: #f87171;
        }
        .action-panel {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-card);
            border-radius: 8px;
            padding: 18px 22px;
            margin-bottom: 26px;
        }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
        }
        .action-item {
            font-size: 13px;
            color: var(--text-muted);
        }
        .action-item span {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .action-item strong {
            color: #f1f5f9;
        }
        .action-item code {
            background: #030712;
            padding: 2px 6px;
            border-radius: 4px;
            color: #38bdf8;
        }
        .footer {
            padding: 20px 30px;
            background: rgba(0, 0, 0, 0.25);
            border-top: 1px solid var(--border-card);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }
        .footer-note {
            font-size: 13px;
            color: var(--text-muted);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-primary {
            background: #2563eb;
            color: #ffffff;
            border: 1px solid #3b82f6;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .btn-secondary {
            background: transparent;
            color: #94a3b8;
            border: 1px solid #334155;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="brand">
                <div class="brand-dot"></div>
                SoundNet Stop-Kran Circuit Breaker
            </div>
            <div class="badge">{$badgeText}</div>
        </div>
        <div class="content">
            <h1>{$titleText}</h1>
            <p class="desc">
                An unhandled fatal PHP termination was captured by the early watchdog hook. The circuit breaker intervened immediately, executed protective recovery routines, flushed the cache, and prevented an unrecoverable blank screen.
            </p>

            <div class="section-title">Diagnostic Details</div>
            <div class="code-box">
                <div class="file-info">Crash Location: {$cleanFile}:{$cleanLine}</div>
                <div class="err-msg">{$cleanMsg}</div>
            </div>

            <div class="section-title">Automated Countermeasures Applied</div>
            <div class="action-panel">
                <div class="action-grid">
                    {$actionDetails}
                </div>
            </div>
        </div>
        <div class="footer">
            <div class="footer-note">
                Diagnostic log recorded in <code>system/storage/logs/stopkran_crash.log</code>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="javascript:location.reload()" class="btn btn-primary">Refresh &amp; Continue</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render HTTP 200 Emergency Bypass Recovery Confirmation Page
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
        :root {
            --bg-body: #0a0e17;
            --bg-card: #111827;
            --border-card: #1f2937;
            --text-main: #f9fafb;
            --text-muted: #9ca3af;
            --accent-green: #10b981;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--bg-body);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .container {
            max-width: 680px;
            width: 100%;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
            padding: 36px;
            text-align: center;
        }
        .icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-green);
            font-size: 32px;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 12px;
            color: #ffffff;
        }
        p {
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 26px;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            background: #030712;
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 28px;
        }
        .metric-val {
            font-size: 22px;
            font-weight: 700;
            color: #38bdf8;
        }
        .metric-lbl {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            margin-top: 4px;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .btn {
            display: inline-block;
            padding: 11px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #10b981;
            color: #042f1f;
        }
        .btn-primary:hover {
            background: #059669;
            color: #ffffff;
        }
        .btn-secondary {
            background: #1f2937;
            color: #e5e7eb;
            border: 1px solid #374151;
        }
        .btn-secondary:hover {
            background: #374151;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">&#10003;</div>
        <h1>Total Blackout Executed</h1>
        <p>
            The emergency bypass circuit breaker was successfully triggered via secret authentication. OpenCart has been safely rolled back to a clean native core state.
        </p>
        <div class="metrics">
            <div>
                <div class="metric-val">{$mods}</div>
                <div class="metric-lbl">Modifications Disabled</div>
            </div>
            <div>
                <div class="metric-val">{$evts}</div>
                <div class="metric-lbl">Events Disabled</div>
            </div>
            <div>
                <div class="metric-val">{$sets}</div>
                <div class="metric-lbl">Module Statuses Reset</div>
            </div>
        </div>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 24px;">
            Execution Timestamp: {$timestamp} | Modification &amp; system cache cleared.
        </p>
        <div class="btn-group">
            <a href="admin/" class="btn btn-primary">Go to Admin Login</a>
            <a href="./" class="btn btn-secondary">Go to Storefront</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render HTTP 403 Forbidden Security Rejection Page
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
        body {
            background: #0b0f19;
            color: #f87171;
            font-family: monospace;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .box {
            background: #111827;
            border: 1px solid #7f1d1d;
            padding: 30px 40px;
            border-radius: 8px;
            max-width: 500px;
        }
        h2 { margin-bottom: 10px; color: #ef4444; }
        p { color: #9ca3af; font-size: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>403 Forbidden</h2>
        <p>Invalid or missing emergency recovery token.</p>
    </div>
</body>
</html>
HTML;
    }
}
