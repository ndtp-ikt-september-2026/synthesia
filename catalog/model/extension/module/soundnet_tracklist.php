<?php
/**
 * SoundNet Dynamic Tracklist & Audio Player
 * Catalog Model
 *
 * Architecture: OpenCart 3 MVC-L
 * Strictly single quotes for PHP strings, SQL statements, and array indices.
 */

class ModelExtensionModuleSoundnetTracklist extends Model {
	public function getTracks($product_id) {
		$query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'product_tracklist` WHERE `product_id` = \'' . (int)$product_id . '\' AND `status` = \'1\' ORDER BY `sort_order` ASC, `track_num` ASC, `track_id` ASC');

		return $query->rows;
	}

	public function getTrack($track_id) {
		$query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'product_tracklist` WHERE `track_id` = \'' . (int)$track_id . '\' AND `status` = \'1\' LIMIT 1');

		return $query->row;
	}
}
