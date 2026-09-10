<?php
/**
 * SoundNet Dynamic Tracklist & Audio Player
 * Admin Model
 *
 * Architecture: OpenCart 3 MVC-L
 * Strictly single quotes for PHP strings, SQL statements, and array indices.
 */

class ModelExtensionModuleSoundnetTracklist extends Model {
	public function install() {
		$this->db->query('CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'product_tracklist` (
			`track_id` INT(11) NOT NULL AUTO_INCREMENT,
			`product_id` INT(11) NOT NULL,
			`track_num` INT(3) NOT NULL DEFAULT 1,
			`title` VARCHAR(255) NOT NULL,
			`duration` VARCHAR(10) NOT NULL DEFAULT \'0:00\',
			`preview_file` VARCHAR(255) NULL,
			`status` TINYINT(1) NOT NULL DEFAULT 1,
			`sort_order` INT(3) NOT NULL DEFAULT 0,
			PRIMARY KEY (`track_id`),
			KEY `idx_product_id` (`product_id`),
			KEY `idx_status` (`status`),
			KEY `idx_sort_order` (`sort_order`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
	}

	public function uninstall() {
		$this->db->query('DROP TABLE IF EXISTS `' . DB_PREFIX . 'product_tracklist`');
	}

	public function getTracks($product_id) {
		$query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'product_tracklist` WHERE `product_id` = \'' . (int)$product_id . '\' ORDER BY `sort_order` ASC, `track_num` ASC, `track_id` ASC');

		return $query->rows;
	}

	public function saveTracks($product_id, array $tracks) {
		$product_id = (int)$product_id;

		$this->db->query('DELETE FROM `' . DB_PREFIX . 'product_tracklist` WHERE `product_id` = \'' . $product_id . '\'');

		if (!empty($tracks)) {
			foreach ($tracks as $index => $track) {
				if (empty($track['title']) && empty($track['preview_file'])) {
					continue;
				}

				$track_num = isset($track['track_num']) && (int)$track['track_num'] > 0 ? (int)$track['track_num'] : ($index + 1);
				$title = isset($track['title']) ? $this->db->escape(trim($track['title'])) : 'Track ' . $track_num;
				$duration = isset($track['duration']) ? $this->db->escape(trim($track['duration'])) : '0:00';
				$preview_file = isset($track['preview_file']) ? $this->db->escape(trim($track['preview_file'])) : '';
				$status = isset($track['status']) ? (int)$track['status'] : 1;
				$sort_order = isset($track['sort_order']) ? (int)$track['sort_order'] : $index;

				$this->db->query('INSERT INTO `' . DB_PREFIX . 'product_tracklist` SET
					`product_id` = \'' . $product_id . '\',
					`track_num` = \'' . $track_num . '\',
					`title` = \'' . $title . '\',
					`duration` = \'' . $duration . '\',
					`preview_file` = \'' . $preview_file . '\',
					`status` = \'' . $status . '\',
					`sort_order` = \'' . $sort_order . '\'');
			}
		}
	}

	public function deleteTracks($product_id) {
		$this->db->query('DELETE FROM `' . DB_PREFIX . 'product_tracklist` WHERE `product_id` = \'' . (int)$product_id . '\'');
	}
}
