<?php
class ModelExtensionModuleStopkran extends Model {
    /**
     * Get list of all installed modifications
     *
     * @return array
     */
    public function getModifications() {
        $query = $this->db->query('SELECT `modification_id`, `name`, `code`, `author`, `version`, `status`, `date_added` FROM `' . DB_PREFIX . 'modification` ORDER BY `name` ASC');
        return $query->rows;
    }

    /**
     * Get single modification by ID
     *
     * @param int $modification_id
     * @return array|null
     */
    public function getModification($modification_id) {
        $query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'modification` WHERE `modification_id` = \'' . (int)$modification_id . '\' LIMIT 1');
        return $query->row;
    }

    /**
     * Toggle or set modification status
     *
     * @param int $modification_id
     * @param int $status
     * @return void
     */
    public function setModificationStatus($modification_id, $status) {
        $this->db->query('UPDATE `' . DB_PREFIX . 'modification` SET `status` = \'' . (int)$status . '\' WHERE `modification_id` = \'' . (int)$modification_id . '\'');
    }

    /**
     * Get list of all registered events
     *
     * @return array
     */
    public function getEvents() {
        $query = $this->db->query('SELECT `event_id`, `code`, `trigger`, `action`, `status`, `sort_order` FROM `' . DB_PREFIX . 'event` ORDER BY `code` ASC, `sort_order` ASC');
        return $query->rows;
    }

    /**
     * Get single event by ID
     *
     * @param int $event_id
     * @return array|null
     */
    public function getEvent($event_id) {
        $query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'event` WHERE `event_id` = \'' . (int)$event_id . '\' LIMIT 1');
        return $query->row;
    }

    /**
     * Toggle or set event status
     *
     * @param int $event_id
     * @param int $status
     * @return void
     */
    public function setEventStatus($event_id, $status) {
        $this->db->query('UPDATE `' . DB_PREFIX . 'event` SET `status` = \'' . (int)$status . '\' WHERE `event_id` = \'' . (int)$event_id . '\'');
    }

    /**
     * Get circuit counts summary
     *
     * @return array
     */
    public function getCounts() {
        $modQuery = $this->db->query('SELECT SUM(CASE WHEN `status` = 1 THEN 1 ELSE 0 END) AS `active`, COUNT(*) AS `total` FROM `' . DB_PREFIX . 'modification`');
        $evtQuery = $this->db->query('SELECT SUM(CASE WHEN `status` = 1 THEN 1 ELSE 0 END) AS `active`, COUNT(*) AS `total` FROM `' . DB_PREFIX . 'event` WHERE `code` NOT LIKE \'core_%\'');

        return [
            'modifications_active' => (int)($modQuery->row['active'] ?? 0),
            'modifications_total'  => (int)($modQuery->row['total'] ?? 0),
            'events_active'        => (int)($evtQuery->row['active'] ?? 0),
            'events_total'         => (int)($evtQuery->row['total'] ?? 0)
        ];
    }

    /**
     * Get list of all available modules in the store with human-readable titles
     *
     * @return array
     */
    public function getAvailableModules() {
        $modules = [];
        $files = glob(DIR_APPLICATION . 'controller/extension/module/*.php');

        if ($files) {
            foreach ($files as $file) {
                $code = basename($file, '.php');

                // Try to load language title
                $title = $code;
                $this->load->language('extension/module/' . $code, 'mod_lang');
                if ($this->language->get('mod_lang')->get('heading_title')) {
                    $title = strip_tags(html_entity_decode($this->language->get('mod_lang')->get('heading_title'), ENT_QUOTES, 'UTF-8'));
                }

                $modules[] = [
                    'code'  => $code,
                    'title' => $title
                ];
            }
        }

        usort($modules, function($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });

        return $modules;
    }

    /**
     * Ensure Stop-Kran automated test hook events are registered in oc_event
     *
     * @return void
     */
    public function ensureEventsRegistered() {
        $events = [
            [
                'code'    => 'stopkran_test_ext_install',
                'trigger' => 'admin/model/setting/extension/install/after',
                'action'  => 'extension/module/stopkran/eventOnExtensionInstall'
            ],
            [
                'code'    => 'stopkran_test_mod_add',
                'trigger' => 'admin/model/setting/module/addModule/after',
                'action'  => 'extension/module/stopkran/eventOnModuleAdd'
            ],
            [
                'code'    => 'stopkran_test_mod_edit',
                'trigger' => 'admin/model/setting/module/editModule/after',
                'action'  => 'extension/module/stopkran/eventOnModuleEdit'
            ],
            [
                'code'    => 'stopkran_test_setting_edit',
                'trigger' => 'admin/model/setting/setting/editSetting/after',
                'action'  => 'extension/module/stopkran/eventOnSettingEdit'
            ],
            [
                'code'    => 'stopkran_test_mod_refresh',
                'trigger' => 'admin/model/setting/modification/addModification/after',
                'action'  => 'extension/module/stopkran/eventOnModificationChange'
            ],
            [
                'code'    => 'stopkran_test_mod_edit_ocmod',
                'trigger' => 'admin/model/setting/modification/editModification/after',
                'action'  => 'extension/module/stopkran/eventOnModificationChange'
            ]
        ];

        foreach ($events as $ev) {
            $q = $this->db->query("SELECT `event_id` FROM `" . DB_PREFIX . "event` WHERE `code` = '" . $this->db->escape($ev['code']) . "' LIMIT 1");
            if (!$q->num_rows) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = '" . $this->db->escape($ev['code']) . "', `trigger` = '" . $this->db->escape($ev['trigger']) . "', `action` = '" . $this->db->escape($ev['action']) . "', `status` = 1, `sort_order` = 99");
            }
        }
    }

    /**
     * Remove Stop-Kran automated test hook events
     *
     * @return void
     */
    public function removeEvents() {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` LIKE 'stopkran_test_%'");
    }
}
