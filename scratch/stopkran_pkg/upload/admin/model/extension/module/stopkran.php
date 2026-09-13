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
}
