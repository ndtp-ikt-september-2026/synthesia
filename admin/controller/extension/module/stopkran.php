<?php
class ControllerExtensionModuleStopkran extends Controller {
    private $error = [];

    /**
     * Module dashboard & settings view
     *
     * @return void
     */
    public function index() {
        $this->load->language('extension/module/stopkran');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('extension/module/stopkran');

        // Ensure Recovery and Tester classes are loaded
        $this->ensureCoreClasses();

        // Ensure event hooks are registered in DB
        $this->model_extension_module_stopkran->ensureEventsRegistered();

        // Handle POST Save Settings
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_stopkran', $this->request->post);

            if (!empty($this->request->post['module_stopkran_secret_token'])) {
                if (class_exists('SoundNet\StopKran\Recovery')) {
                    \SoundNet\StopKran\Recovery::setSecretToken($this->request->post['module_stopkran_secret_token']);
                }
            }

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/module/stopkran', 'user_token=' . $this->session->data['user_token'], true));
        }

        // Warnings / Errors
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } elseif (isset($this->session->data['warning'])) {
            $data['error_warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        // Breadcrumbs
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/stopkran', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['action'] = $this->url->link('extension/module/stopkran', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['user_token'] = $this->session->data['user_token'];

        // AJAX Action URLs (clean &amp; to & for clean JavaScript AJAX requests)
        $data['url_kill_all']            = str_replace('&amp;', '&', $this->url->link('extension/module/stopkran/killAll', 'user_token=' . $this->session->data['user_token'], true));
        $data['url_toggle_modification'] = str_replace('&amp;', '&', $this->url->link('extension/module/stopkran/toggleModification', 'user_token=' . $this->session->data['user_token'], true));
        $data['url_toggle_event']        = str_replace('&amp;', '&', $this->url->link('extension/module/stopkran/toggleEvent', 'user_token=' . $this->session->data['user_token'], true));
        $data['url_purge_cache']         = str_replace('&amp;', '&', $this->url->link('extension/module/stopkran/purgeCache', 'user_token=' . $this->session->data['user_token'], true));
        $data['url_clear_log']           = str_replace('&amp;', '&', $this->url->link('extension/module/stopkran/clearLog', 'user_token=' . $this->session->data['user_token'], true));
        $data['url_test_module']         = str_replace('&amp;', '&', $this->url->link('extension/module/stopkran/testModule', 'user_token=' . $this->session->data['user_token'], true));
        $data['url_test_all_modules']    = str_replace('&amp;', '&', $this->url->link('extension/module/stopkran/testAllModules', 'user_token=' . $this->session->data['user_token'], true));
        $data['url_clear_test_log']      = str_replace('&amp;', '&', $this->url->link('extension/module/stopkran/clearTestLog', 'user_token=' . $this->session->data['user_token'], true));

        // Status Setting
        if (isset($this->request->post['module_stopkran_status'])) {
            $data['module_stopkran_status'] = $this->request->post['module_stopkran_status'];
        } else {
            $data['module_stopkran_status'] = $this->config->get('module_stopkran_status');
        }

        // Auto Test Setting
        if (isset($this->request->post['module_stopkran_auto_test'])) {
            $data['module_stopkran_auto_test'] = $this->request->post['module_stopkran_auto_test'];
        } elseif ($this->config->has('module_stopkran_auto_test')) {
            $data['module_stopkran_auto_test'] = $this->config->get('module_stopkran_auto_test');
        } else {
            $data['module_stopkran_auto_test'] = '1';
        }

        // Secret Token
        if (isset($this->request->post['module_stopkran_secret_token'])) {
            $token = $this->request->post['module_stopkran_secret_token'];
        } elseif ($this->config->get('module_stopkran_secret_token')) {
            $token = $this->config->get('module_stopkran_secret_token');
        } else {
            if (class_exists('SoundNet\StopKran\Recovery')) {
                $token = \SoundNet\StopKran\Recovery::getSecretToken();
            } else {
                $token = bin2hex(random_bytes(16));
            }
        }
        $data['module_stopkran_secret_token'] = $token;

        // Bypass URLs
        $catalogServer = defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER;
        $data['bypass_url_admin']   = HTTP_SERVER . '?stopkran_kill_all=' . $token;
        $data['bypass_url_catalog'] = $catalogServer . '?stopkran_kill_all=' . $token;

        // Circuit Overview & Counts
        $counts = $this->model_extension_module_stopkran->getCounts();
        $data['modifications_active'] = $counts['modifications_active'];
        $data['modifications_total']  = $counts['modifications_total'];
        $data['events_active']        = $counts['events_active'];
        $data['events_total']         = $counts['events_total'];

        // Available Modules for testing dropdown
        $data['available_modules'] = $this->model_extension_module_stopkran->getAvailableModules();

        // Test history audit
        if (class_exists('SoundNet\StopKran\Tester')) {
            $data['test_history'] = \SoundNet\StopKran\Tester::getTestAuditHistory(25);
        } else {
            $data['test_history'] = [];
        }

        // Modifications Data
        $data['modifications'] = $this->model_extension_module_stopkran->getModifications();

        // Events Data
        $data['events'] = $this->model_extension_module_stopkran->getEvents();

        // Storage & Cache Sizes
        $modDir = defined('DIR_MODIFICATION') ? DIR_MODIFICATION : (DIR_STORAGE . 'modification/');
        $cacheDir = defined('DIR_CACHE') ? DIR_CACHE : (DIR_STORAGE . 'cache/');

        $modSizeBytes = class_exists('SoundNet\StopKran\Recovery') ? \SoundNet\StopKran\Recovery::getDirectorySize($modDir) : 0;
        $cacheSizeBytes = class_exists('SoundNet\StopKran\Recovery') ? \SoundNet\StopKran\Recovery::getDirectorySize($cacheDir) : 0;

        $data['mod_cache_size']   = $this->formatBytes($modSizeBytes);
        $data['system_cache_size'] = $this->formatBytes($cacheSizeBytes);

        // Crash Log Reader (as single string for OpenCart textarea)
        $crashLogFile = (defined('DIR_LOGS') ? DIR_LOGS : (DIR_STORAGE . 'logs/')) . 'stopkran_crash.log';
        if (is_file($crashLogFile) && is_readable($crashLogFile)) {
            $data['crash_log'] = file_get_contents($crashLogFile);
        } else {
            $data['crash_log'] = '';
        }

        // Test Log Reader (as single string for OpenCart textarea)
        $testLogFile = (defined('DIR_LOGS') ? DIR_LOGS : (DIR_STORAGE . 'logs/')) . 'stopkran_test.log';
        if (is_file($testLogFile) && is_readable($testLogFile)) {
            $data['test_log'] = file_get_contents($testLogFile);
        } else {
            $data['test_log'] = '';
        }

        // Layout standard parts
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/stopkran', $data));
    }

    /**
     * Send clean JSON response with proper headers and buffer cleanup
     *
     * @param array $json
     * @return void
     */
    protected function sendJsonResponse(array $json) {
        if (ob_get_length()) {
            ob_clean();
        }

        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }

    /**
     * AJAX Action: Master Emergency Stop-Kran (Total Blackout)
     *
     * @return void
     */
    public function killAll() {
        @ini_set('display_errors', '0');
        $this->load->language('extension/module/stopkran');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/stopkran')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->ensureCoreClasses();
            $result = \SoundNet\StopKran\Recovery::totalBlackout();

            $json['success'] = true;
            $json['message'] = $this->language->get('text_blackout_success');
            $json['result']  = $result;
        }

        $this->sendJsonResponse($json);
    }

    /**
     * AJAX Action: Test specific module on demand
     *
     * @return void
     */
    public function testModule() {
        @ini_set('display_errors', '0');
        $this->load->language('extension/module/stopkran');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/stopkran')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (empty($this->request->post['module_code'])) {
            $json['error'] = $this->language->get('error_module_empty');
        } else {
            $this->ensureCoreClasses();
            $moduleCode = trim($this->request->post['module_code']);
            $report = \SoundNet\StopKran\Tester::testModule($moduleCode, 'manual');

            $json['success'] = true;
            $json['report']  = $report;
            $json['message'] = $report['passed']
                ? "Модуль '{$moduleCode}' успешно протестирован: синтаксис корректен, файлы проверены ({$report['total_files']} шт.)."
                : "Внимание: при проверке модуля '{$moduleCode}' обнаружены ошибки или предупреждения!";
        }

        $this->sendJsonResponse($json);
    }

    /**
     * AJAX Action: Test all modules in system
     *
     * @return void
     */
    public function testAllModules() {
        @ini_set('display_errors', '0');
        $this->load->language('extension/module/stopkran');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/stopkran')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->ensureCoreClasses();
            $reports = \SoundNet\StopKran\Tester::testAllModules();

            $totalPassed = 0;
            $totalErrors = 0;
            foreach ($reports as $rep) {
                if ($rep['passed']) {
                    $totalPassed++;
                } else {
                    $totalErrors++;
                }
            }

            $json['success'] = true;
            $json['reports'] = $reports;
            $json['summary'] = [
                'total'  => count($reports),
                'passed' => $totalPassed,
                'errors' => $totalErrors
            ];
            $json['message'] = "Пакетная проверка завершена: проверено модулей — " . count($reports) . ", без ошибок — {$totalPassed}, с замечаниями — {$totalErrors}.";
        }

        $this->sendJsonResponse($json);
    }

    /**
     * AJAX Action: Clear module test log
     *
     * @return void
     */
    public function clearTestLog() {
        @ini_set('display_errors', '0');
        $this->load->language('extension/module/stopkran');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/stopkran')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $testLogFile = (defined('DIR_LOGS') ? DIR_LOGS : (DIR_STORAGE . 'logs/')) . 'stopkran_test.log';
            if (is_file($testLogFile)) {
                $h = @fopen($testLogFile, 'w');
                if ($h) {
                    fclose($h);
                }
            }

            $historyFile = (defined('DIR_LOGS') ? DIR_LOGS : (DIR_STORAGE . 'logs/')) . 'stopkran_test_history.json';
            if (is_file($historyFile)) {
                @unlink($historyFile);
            }

            $json['success'] = true;
            $json['message'] = $this->language->get('text_test_log_cleared');
        }

        $this->sendJsonResponse($json);
    }

    /**
     * AJAX Action: Toggle specific modification status
     *
     * @return void
     */
    public function toggleModification() {
        @ini_set('display_errors', '0');
        $this->load->language('extension/module/stopkran');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/stopkran')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (!isset($this->request->post['modification_id'])) {
            $json['error'] = $this->language->get('error_invalid_request');
        } else {
            $modId = (int)$this->request->post['modification_id'];
            $newStatus = !empty($this->request->post['status']) ? 1 : 0;

            $this->load->model('extension/module/stopkran');
            $this->model_extension_module_stopkran->setModificationStatus($modId, $newStatus);

            $this->ensureCoreClasses();
            \SoundNet\StopKran\Recovery::purgeModificationCache();
            $rebuildResult = \SoundNet\StopKran\Recovery::rebuildModificationCache();

            $json['success'] = true;
            $json['modification_id'] = $modId;
            $json['status'] = $newStatus;
            $json['rebuild'] = $rebuildResult;
        }

        $this->sendJsonResponse($json);
    }

    /**
     * AJAX Action: Toggle specific event status
     *
     * @return void
     */
    public function toggleEvent() {
        @ini_set('display_errors', '0');
        $this->load->language('extension/module/stopkran');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/stopkran')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (!isset($this->request->post['event_id'])) {
            $json['error'] = $this->language->get('error_invalid_request');
        } else {
            $evtId = (int)$this->request->post['event_id'];
            $newStatus = !empty($this->request->post['status']) ? 1 : 0;

            $this->load->model('extension/module/stopkran');
            $this->model_extension_module_stopkran->setEventStatus($evtId, $newStatus);

            $json['success'] = true;
            $json['event_id'] = $evtId;
            $json['status'] = $newStatus;
        }

        $this->sendJsonResponse($json);
    }

    /**
     * AJAX Action: Purge modification & system cache
     *
     * @return void
     */
    public function purgeCache() {
        @ini_set('display_errors', '0');
        $this->load->language('extension/module/stopkran');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/stopkran')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->ensureCoreClasses();
            $modPurge = \SoundNet\StopKran\Recovery::purgeModificationCache();
            $sysPurge = \SoundNet\StopKran\Recovery::purgeSystemCache();
            $rebuild  = \SoundNet\StopKran\Recovery::rebuildModificationCache();

            $json['success'] = true;
            $json['message'] = $this->language->get('text_cache_purged');
            $json['details'] = [
                'modification' => $modPurge,
                'system'       => $sysPurge,
                'rebuild'      => $rebuild
            ];
        }

        $this->sendJsonResponse($json);
    }

    /**
     * AJAX Action: Clear crash diagnostics log
     *
     * @return void
     */
    public function clearLog() {
        @ini_set('display_errors', '0');
        $this->load->language('extension/module/stopkran');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/stopkran')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $logFile = (defined('DIR_LOGS') ? DIR_LOGS : (DIR_STORAGE . 'logs/')) . 'stopkran_crash.log';
            if (is_file($logFile)) {
                $h = @fopen($logFile, 'w');
                if ($h) {
                    fclose($h);
                }
            }

            $json['success'] = true;
            $json['message'] = $this->language->get('text_log_cleared');
        }

        $this->sendJsonResponse($json);
    }

    /**
     * Event Hook: Triggered when extension is installed
     * Trigger: admin/model/setting/extension/install/after
     */
    public function eventOnExtensionInstall(&$route, &$args, &$output) {
        if (!$this->config->get('module_stopkran_auto_test')) {
            return;
        }

        $type = $args[0] ?? '';
        $code = $args[1] ?? '';

        if ($type === 'module' && !empty($code)) {
            $this->runAutomatedTest($code, 'install');
        }
    }

    /**
     * Event Hook: Triggered when module is added
     * Trigger: admin/model/setting/module/addModule/after
     */
    public function eventOnModuleAdd(&$route, &$args, &$output) {
        if (!$this->config->get('module_stopkran_auto_test')) {
            return;
        }

        $code = $args[0] ?? '';
        if (!empty($code)) {
            $this->runAutomatedTest($code, 'add');
        }
    }

    /**
     * Event Hook: Triggered when module is edited
     * Trigger: admin/model/setting/module/editModule/after
     */
    public function eventOnModuleEdit(&$route, &$args, &$output) {
        if (!$this->config->get('module_stopkran_auto_test')) {
            return;
        }

        $moduleId = (int)($args[0] ?? 0);
        if ($moduleId > 0) {
            $this->load->model('setting/module');
            $q = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "module` WHERE `module_id` = '" . $moduleId . "' LIMIT 1");
            if ($q->num_rows && !empty($q->row['code'])) {
                $this->runAutomatedTest($q->row['code'], 'update');
            }
        }
    }

    /**
     * Event Hook: Triggered when module settings are updated
     * Trigger: admin/model/setting/setting/editSetting/after
     */
    public function eventOnSettingEdit(&$route, &$args, &$output) {
        if (!$this->config->get('module_stopkran_auto_test')) {
            return;
        }

        $code = $args[0] ?? '';
        if (strpos($code, 'module_') === 0) {
            $moduleCode = substr($code, 7);
            if ($moduleCode !== 'stopkran') {
                $this->runAutomatedTest($moduleCode, 'update');
            }
        }
    }

    /**
     * Event Hook: Triggered when modification is added or updated
     * Trigger: admin/model/setting/modification/addModification/after
     */
    public function eventOnModificationChange(&$route, &$args, &$output) {
        if (!$this->config->get('module_stopkran_auto_test')) {
            return;
        }

        $data = $args[0] ?? [];
        $code = $data['code'] ?? '';
        if (!empty($code)) {
            $this->runAutomatedTest($code, 'update');
        }
    }

    /**
     * Helper to execute automated test and set session notification if failed
     *
     * @param string $moduleCode
     * @param string $trigger
     * @return void
     */
    protected function runAutomatedTest($moduleCode, $trigger) {
        $this->ensureCoreClasses();
        if (class_exists('SoundNet\StopKran\Tester')) {
            $report = \SoundNet\StopKran\Tester::testModule($moduleCode, $trigger);
            if (!$report['passed']) {
                $errMsg = !empty($report['errors']) ? implode('; ', $report['errors']) : 'Обнаружены критические ошибки при тестировании.';
                $this->session->data['warning'] = "Внимание: Стоп-Кран протестировал модуль '{$moduleCode}' при сохранении и зафиксировал ошибки: {$errMsg}";
            }
        }
    }

    /**
     * Validate user permissions for module modification
     *
     * @return bool
     */
    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/stopkran')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['module_stopkran_secret_token'])) {
            $this->error['warning'] = $this->language->get('error_token_empty');
        }

        return !$this->error;
    }

    /**
     * Module installation hook
     *
     * @return void
     */
    public function install() {
        $this->load->model('setting/setting');
        $this->load->model('extension/module/stopkran');

        $token = bin2hex(random_bytes(16));
        $defaults = [
            'module_stopkran_status'       => '1',
            'module_stopkran_auto_test'    => '1',
            'module_stopkran_secret_token' => $token
        ];

        $this->model_setting_setting->editSetting('module_stopkran', $defaults);

        $this->ensureCoreClasses();
        if (class_exists('SoundNet\StopKran\Recovery')) {
            \SoundNet\StopKran\Recovery::setSecretToken($token);
        }

        // Register OpenCart event hooks for module add/update testing
        $this->model_extension_module_stopkran->ensureEventsRegistered();
    }

    /**
     * Module uninstallation hook
     *
     * @return void
     */
    public function uninstall() {
        $this->load->model('setting/setting');
        $this->load->model('extension/module/stopkran');

        $this->model_setting_setting->deleteSetting('module_stopkran');
        $this->model_extension_module_stopkran->removeEvents();
    }

    /**
     * Ensure recovery and tester classes are loaded
     *
     * @return void
     */
    protected function ensureCoreClasses() {
        if (!class_exists('SoundNet\StopKran\Recovery')) {
            $recFile = DIR_SYSTEM . 'library/stopkran/recovery.php';
            if (file_exists($recFile)) {
                require_once($recFile);
            }
        }

        if (!class_exists('SoundNet\StopKran\Tester')) {
            $testFile = DIR_SYSTEM . 'library/stopkran/tester.php';
            if (file_exists($testFile)) {
                require_once($testFile);
            }
        }
    }

    /**
     * Format byte count to human-readable string
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes($bytes) {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
