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

        // Ensure Recovery class is accessible
        if (!class_exists('SoundNet\StopKran\Recovery')) {
            $recFile = DIR_SYSTEM . 'library/stopkran/recovery.php';
            if (file_exists($recFile)) {
                require_once($recFile);
            }
        }

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

        // AJAX Action URLs
        $data['url_kill_all']            = $this->url->link('extension/module/stopkran/killAll', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_toggle_modification'] = $this->url->link('extension/module/stopkran/toggleModification', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_toggle_event']        = $this->url->link('extension/module/stopkran/toggleEvent', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_purge_cache']         = $this->url->link('extension/module/stopkran/purgeCache', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_clear_log']           = $this->url->link('extension/module/stopkran/clearLog', 'user_token=' . $this->session->data['user_token'], true);

        // Status Setting
        if (isset($this->request->post['module_stopkran_status'])) {
            $data['module_stopkran_status'] = $this->request->post['module_stopkran_status'];
        } else {
            $data['module_stopkran_status'] = $this->config->get('module_stopkran_status');
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

        // Crash Log Reader (Tail last 100 lines)
        $logFile = (defined('DIR_LOGS') ? DIR_LOGS : (DIR_STORAGE . 'logs/')) . 'stopkran_crash.log';
        $logEntries = [];
        if (is_file($logFile) && is_readable($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!empty($lines)) {
                $logEntries = array_slice($lines, -100);
            }
        }
        $data['log_entries'] = array_reverse($logEntries);
        $data['has_logs']    = !empty($logEntries);

        // Layout standard parts
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/stopkran', $data));
    }

    /**
     * AJAX Action: Master Emergency Stop-Kran (Total Blackout)
     *
     * @return void
     */
    public function killAll() {
        $this->load->language('extension/module/stopkran');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/stopkran')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->ensureRecoveryClass();
            $result = \SoundNet\StopKran\Recovery::totalBlackout();

            $json['success'] = true;
            $json['message'] = $this->language->get('text_blackout_success');
            $json['result']  = $result;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * AJAX Action: Toggle specific modification status
     *
     * @return void
     */
    public function toggleModification() {
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

            $this->ensureRecoveryClass();
            \SoundNet\StopKran\Recovery::purgeModificationCache();

            $json['success'] = true;
            $json['modification_id'] = $modId;
            $json['status'] = $newStatus;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * AJAX Action: Toggle specific event status
     *
     * @return void
     */
    public function toggleEvent() {
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

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * AJAX Action: Purge modification & system cache
     *
     * @return void
     */
    public function purgeCache() {
        $this->load->language('extension/module/stopkran');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/stopkran')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->ensureRecoveryClass();
            $modPurge = \SoundNet\StopKran\Recovery::purgeModificationCache();
            $sysPurge = \SoundNet\StopKran\Recovery::purgeSystemCache();

            $json['success'] = true;
            $json['message'] = $this->language->get('text_cache_purged');
            $json['details'] = [
                'modification' => $modPurge,
                'system'       => $sysPurge
            ];
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * AJAX Action: Clear crash diagnostics log
     *
     * @return void
     */
    public function clearLog() {
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

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
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

        $token = bin2hex(random_bytes(16));
        $defaults = [
            'module_stopkran_status'       => '1',
            'module_stopkran_secret_token' => $token
        ];

        $this->model_setting_setting->editSetting('module_stopkran', $defaults);

        $this->ensureRecoveryClass();
        if (class_exists('SoundNet\StopKran\Recovery')) {
            \SoundNet\StopKran\Recovery::setSecretToken($token);
        }
    }

    /**
     * Module uninstallation hook
     *
     * @return void
     */
    public function uninstall() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('module_stopkran');
    }

    /**
     * Ensure recovery class is loaded
     *
     * @return void
     */
    protected function ensureRecoveryClass() {
        if (!class_exists('SoundNet\StopKran\Recovery')) {
            $recFile = DIR_SYSTEM . 'library/stopkran/recovery.php';
            if (file_exists($recFile)) {
                require_once($recFile);
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
