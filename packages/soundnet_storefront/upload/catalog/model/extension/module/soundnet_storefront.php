<?php
class ModelExtensionModuleSoundnetStorefront extends Model {

	public function getMusicRecommendations($limit = 12) {
		$this->load->model('tool/image');

		$sql = "SELECT DISTINCT p.product_id, p.image, p.price, pd.name,
				(SELECT pa.text FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 1 LIMIT 1) AS artist,
				(SELECT pa.text FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 3 LIMIT 1) AS year,
				(SELECT pa.text FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 4 LIMIT 1) AS label,
				(SELECT pa.text FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 5 LIMIT 1) AS format
				FROM " . DB_PREFIX . "product p
				JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
				JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
				WHERE p.status = '1' AND p2c.category_id IN (1, 2, 3)
				ORDER BY p.date_added DESC, p.product_id DESC
				LIMIT " . (int)$limit;

		$query = $this->db->query($sql);
		$results = array();

		foreach ($query->rows as $row) {
			$thumb = $row['image'] && is_file(DIR_IMAGE . $row['image']) 
				? $this->model_tool_image->resize($row['image'], 320, 320) 
				: $this->model_tool_image->resize('placeholder.png', 320, 320);

			$format_badge = 'ВИНИЛ LP';
			if (!empty($row['format']) && (stripos($row['format'], 'CD') !== false || stripos($row['format'], 'Компакт') !== false)) {
				$format_badge = 'КОМПАКТ-ДИСК CD';
			}

			$meta_sub = array();
			if (!empty($row['year'])) $meta_sub[] = $row['year'];
			if (!empty($row['label'])) $meta_sub[] = $row['label'];

			$price_formatted = $this->currency->format($row['price'], 'BYN');

			$results[] = array(
				'product_id'   => $row['product_id'],
				'thumb'        => $thumb,
				'name'         => $row['name'],
				'artist'       => !empty($row['artist']) ? $row['artist'] : 'Исполнитель',
				'format_badge' => $format_badge,
				'meta'         => implode(' • ', $meta_sub),
				'price'        => $price_formatted,
				'href'         => $this->url->link('product/product', 'product_id=' . $row['product_id'])
			);
		}

		return $results;
	}

	public function getGearRecommendations($limit = 12) {
		$this->load->model('tool/image');

		$sql = "SELECT DISTINCT p.product_id, p.image, p.price, pd.name,
				m.name AS brand,
				(SELECT cd.name FROM " . DB_PREFIX . "category_description cd JOIN " . DB_PREFIX . "product_to_category p2c ON (cd.category_id = p2c.category_id) WHERE p2c.product_id = p.product_id AND cd.category_id IN (11, 12, 14, 15, 22, 24) LIMIT 1) AS category_name,
				(SELECT pa.text FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 9 LIMIT 1) AS sound_style
				FROM " . DB_PREFIX . "product p
				JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
				JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
				LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
				WHERE p.status = '1' AND p2c.category_id IN (11, 12, 14, 15, 22, 24)
				ORDER BY p.price DESC, p.product_id DESC
				LIMIT " . (int)$limit;

		$query = $this->db->query($sql);
		$results = array();

		foreach ($query->rows as $row) {
			$thumb = $row['image'] && is_file(DIR_IMAGE . $row['image']) 
				? $this->model_tool_image->resize($row['image'], 320, 320) 
				: $this->model_tool_image->resize('placeholder.png', 320, 320);

			$results[] = array(
				'product_id'  => $row['product_id'],
				'thumb'       => $thumb,
				'name'        => $row['name'],
				'brand'       => $row['brand'],
				'gear_type'   => !empty($row['category_name']) ? $row['category_name'] : 'Оборудование',
				'sound_style' => $row['sound_style'],
				'price'       => $this->currency->format($row['price'], 'BYN'),
				'href'        => $this->url->link('product/product', 'product_id=' . $row['product_id'])
			);
		}

		return $results;
	}

	public function getPopularCategories($limit = 5) {
		$this->load->model('tool/image');

		$target_ids = array(2, 3, 15, 12, 24); // Vinyl, CDs, Electric Guitars, Acoustic Guitars, Synths
		$id_list = implode(',', $target_ids);

		$sql = "SELECT c.category_id, c.image, cd.name,
				(SELECT COUNT(DISTINCT p2c.product_id) FROM " . DB_PREFIX . "product_to_category p2c JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id) WHERE p2c.category_id = c.category_id AND p.status = '1') AS total_products
				FROM " . DB_PREFIX . "category c
				JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "')
				WHERE c.category_id IN (" . $id_list . ") AND c.status = '1'
				ORDER BY FIELD(c.category_id, " . $id_list . ")
				LIMIT " . (int)$limit;

		$query = $this->db->query($sql);
		$results = array();

		foreach ($query->rows as $row) {
			$thumb = $row['image'] && is_file(DIR_IMAGE . $row['image']) 
				? $this->model_tool_image->resize($row['image'], 400, 400) 
				: $this->model_tool_image->resize('placeholder.png', 400, 400);

			$results[] = array(
				'category_id' => $row['category_id'],
				'name'        => $row['name'],
				'thumb'       => $thumb,
				'count'       => (int)$row['total_products'],
				'href'        => $this->url->link('product/category', 'path=' . $row['category_id'])
			);
		}

		return $results;
	}

	public function getReleaseMetadata($product_id) {
		$sql = "SELECT pa.attribute_id, ad.name AS attr_name, pa.text
				FROM " . DB_PREFIX . "product_attribute pa
				JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id)
				JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "')
				WHERE pa.product_id = '" . (int)$product_id . "'";

		$query = $this->db->query($sql);

		$meta = array(
			'artist'       => '',
			'year'         => '',
			'label'        => '',
			'format_badge' => '',
			'genre'        => '',
			'vibe'         => '',
			'is_music'     => false
		);

		// Also check if product belongs to music categories (1, 2, 3, 4)
		$cat_check = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND category_id IN (1, 2, 3, 4) LIMIT 1");
		if ($cat_check->num_rows) {
			$meta['is_music'] = true;
		}

		foreach ($query->rows as $r) {
			$name = mb_strtolower(trim($r['attr_name']), 'UTF-8');
			$text = trim($r['text']);

			if ($name == 'исполнитель' || $name == 'artist' || $r['attribute_id'] == 1) {
				$meta['artist'] = $text;
				$meta['is_music'] = true;
			} elseif ($name == 'год выпуска' || $name == 'year' || $r['attribute_id'] == 3) {
				$meta['year'] = $text;
			} elseif ($name == 'лейбл' || $name == 'label' || $r['attribute_id'] == 4) {
				$meta['label'] = $text;
			} elseif ($name == 'формат издания' || $name == 'format' || $r['attribute_id'] == 5) {
				if (stripos($text, 'CD') !== false || stripos($text, 'Компакт') !== false) {
					$meta['format_badge'] = 'КОМПАКТ-ДИСК CD';
				} else {
					$meta['format_badge'] = 'ВИНИЛ LP';
				}
				$meta['is_music'] = true;
			} elseif ($name == 'жанр' || $name == 'genre' || $r['attribute_id'] == 2) {
				$meta['genre'] = $text;
				$meta['is_music'] = true;
			} elseif ($name == 'вайб / характер звучания' || $name == 'vibe' || $r['attribute_id'] == 6) {
				$meta['vibe'] = $text;
			}
		}

		// If it's music and format_badge is still empty, detect from category
		if ($meta['is_music'] && empty($meta['format_badge'])) {
			$cat_id_row = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND category_id IN (2, 3) LIMIT 1");
			if ($cat_id_row->num_rows && $cat_id_row->row['category_id'] == 3) {
				$meta['format_badge'] = 'КОМПАКТ-ДИСК CD';
			} elseif ($cat_id_row->num_rows && $cat_id_row->row['category_id'] == 2) {
				$meta['format_badge'] = 'ВИНИЛ LP';
			}
		}

		// Ensure non-music NEVER gets a format_badge or artist
		if (!$meta['is_music']) {
			$meta['format_badge'] = '';
			$meta['artist'] = '';
		}

		return $meta;
	}

	public function getSimilarReleases($product_id, $limit = 8) {
		$this->load->model('tool/image');

		$meta = $this->getReleaseMetadata($product_id);
		if (!$meta['is_music']) {
			return array();
		}

		$ai_server_url = $this->config->get('module_soundnet_storefront_ai_url');
		if (!$ai_server_url) {
			$ai_server_url = 'http://127.0.0.1:8000';
		}

		$similar_ids = array();

		// 1. Query local AI microservice
		$endpoint = rtrim($ai_server_url, '/') . '/internal/v1/tracks/' . (int)$product_id . '/similar?limit=' . (int)$limit;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 2);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
		$resp = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($resp && $http_code == 200) {
			$json = json_decode($resp, true);
			if (!empty($json['similar_track_ids']) && is_array($json['similar_track_ids'])) {
				$similar_ids = array_map('intval', $json['similar_track_ids']);
			}
		}

		// 2. Fallback to MySQL acoustic/vibe/artist matching if AI service returned empty
		if (empty($similar_ids)) {
			$meta = $this->getReleaseMetadata($product_id);
			$artist = $this->db->escape($meta['artist']);
			$genre  = $this->db->escape($meta['genre']);

			$fallback_sql = "SELECT DISTINCT p.product_id
				FROM " . DB_PREFIX . "product p
				JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
				LEFT JOIN " . DB_PREFIX . "product_attribute pa ON (p.product_id = pa.product_id)
				WHERE p.product_id != '" . (int)$product_id . "' AND p.status = '1' AND p2c.category_id IN (1, 2, 3)";

			if ($artist) {
				$fallback_sql .= " AND (pa.attribute_id = 1 AND pa.text LIKE '%" . $artist . "%')";
			}

			$fallback_sql .= " LIMIT " . (int)$limit;
			$fallback_query = $this->db->query($fallback_sql);

			foreach ($fallback_query->rows as $fr) {
				$similar_ids[] = (int)$fr['product_id'];
			}

			// If still empty, grab latest music releases
			if (empty($similar_ids)) {
				$gen_query = $this->db->query("SELECT DISTINCT p.product_id FROM " . DB_PREFIX . "product p JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) WHERE p.product_id != '" . (int)$product_id . "' AND p.status = '1' AND p2c.category_id IN (1, 2, 3) ORDER BY p.date_added DESC LIMIT " . (int)$limit);
				foreach ($gen_query->rows as $gr) {
					$similar_ids[] = (int)$gr['product_id'];
				}
			}
		}

		if (empty($similar_ids)) {
			return array();
		}

		// 3. Fetch full MySQL details for returned IDs
		$id_list = implode(',', $similar_ids);
		$sql = "SELECT p.product_id, p.image, p.price, pd.name,
				(SELECT pa.text FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 1 LIMIT 1) AS artist,
				(SELECT pa.text FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 3 LIMIT 1) AS year,
				(SELECT pa.text FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 4 LIMIT 1) AS label,
				(SELECT pa.text FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 5 LIMIT 1) AS format
				FROM " . DB_PREFIX . "product p
				JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
				WHERE p.product_id IN (" . $id_list . ") AND p.status = '1'
				ORDER BY FIELD(p.product_id, " . $id_list . ")";

		$query = $this->db->query($sql);
		$results = array();

		foreach ($query->rows as $row) {
			$thumb = $row['image'] && is_file(DIR_IMAGE . $row['image']) 
				? $this->model_tool_image->resize($row['image'], 320, 320) 
				: $this->model_tool_image->resize('placeholder.png', 320, 320);

			$format_badge = 'ВИНИЛ LP';
			if (!empty($row['format']) && (stripos($row['format'], 'CD') !== false || stripos($row['format'], 'Компакт') !== false)) {
				$format_badge = 'КОМПАКТ-ДИСК CD';
			}

			$meta_sub = array();
			if (!empty($row['year'])) $meta_sub[] = $row['year'];
			if (!empty($row['label'])) $meta_sub[] = $row['label'];

			$results[] = array(
				'product_id'   => $row['product_id'],
				'thumb'        => $thumb,
				'name'         => $row['name'],
				'artist'       => !empty($row['artist']) ? $row['artist'] : 'Исполнитель',
				'meta'         => implode(' • ', $meta_sub),
				'format_badge' => $format_badge,
				'price'        => $this->currency->format($row['price'], 'BYN'),
				'href'         => $this->url->link('product/product', 'product_id=' . $row['product_id'])
			);
		}

		return $results;
	}

	public function getMatchingInstruments($product_id, $limit = 6) {
		$this->load->model('tool/image');

		$meta = $this->getReleaseMetadata($product_id);
		if (!$meta['is_music']) {
			return array();
		}

		$ai_server_url = $this->config->get('module_soundnet_storefront_ai_url');
		if (!$ai_server_url) {
			$ai_server_url = 'http://127.0.0.1:8000';
		}

		$instrument_ids = array();

		$params = array(
			'limit' => (int)$limit
		);
		if (!empty($meta['genre'])) {
			$params['genre'] = $meta['genre'];
		}
		if (!empty($meta['vibe'])) {
			$params['vibe'] = $meta['vibe'];
		}
		if (!empty($meta['artist'])) {
			$params['artist'] = $meta['artist'];
		}

		// 1. Query local AI microservice
		$endpoint = rtrim($ai_server_url, '/') . '/internal/v1/tracks/' . (int)$product_id . '/instruments?' . http_build_query($params);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 2);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
		$resp = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($resp && $http_code == 200) {
			$json = json_decode($resp, true);
			if (!empty($json['matching_instrument_ids']) && is_array($json['matching_instrument_ids'])) {
				$instrument_ids = array_map('intval', $json['matching_instrument_ids']);
			}
		}

		// 2. Fallback to MySQL style matching
		if (empty($instrument_ids)) {
			$check_str = mb_strtolower($meta['genre'] . ' ' . $meta['vibe'], 'UTF-8');

			$is_electronic = (strpos($check_str, 'electronic') !== false || strpos($check_str, 'techno') !== false || strpos($check_str, 'house') !== false || strpos($check_str, 'synth') !== false || strpos($check_str, 'ambient') !== false);
			$is_heavy_rock = (strpos($check_str, 'rock') !== false || strpos($check_str, 'metal') !== false || strpos($check_str, 'thrash') !== false || strpos($check_str, 'grunge') !== false || strpos($check_str, 'punk') !== false);
			$is_acoustic   = (strpos($check_str, 'acoustic') !== false || strpos($check_str, 'folk') !== false || strpos($check_str, 'unplugged') !== false);

			if ($is_electronic) {
				$pref_cats = array(24, 22, 20, 16);
			} elseif ($is_heavy_rock && !$is_acoustic) {
				$pref_cats = array(15, 16, 19, 20, 13);
			} elseif ($is_acoustic) {
				$pref_cats = array(12, 14, 11);
			} else {
				$pref_cats = array(15, 12, 24, 16);
			}

			$cat_list = implode(',', $pref_cats);
			$kw_parts = array();
			$vibe_words = preg_split('/[\s,;\/]+/', $meta['vibe'] . ' ' . $meta['genre']);
			foreach ($vibe_words as $vw) {
				$clean_vw = trim($vw);
				if (mb_strlen($clean_vw, 'UTF-8') >= 4) {
					$kw_parts[] = "pa.text LIKE '%" . $this->db->escape($clean_vw) . "%'";
				}
			}

			$fallback_sql = "SELECT DISTINCT p.product_id
				FROM " . DB_PREFIX . "product p
				JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
				LEFT JOIN " . DB_PREFIX . "product_attribute pa ON (p.product_id = pa.product_id AND pa.attribute_id = 9)
				WHERE p.status = '1' AND p2c.category_id IN (" . $cat_list . ")";

			if (!empty($kw_parts)) {
				$fallback_sql .= " AND (" . implode(' OR ', $kw_parts) . ")";
			}

			$fallback_sql .= " ORDER BY p.price DESC LIMIT " . (int)$limit;
			$fallback_query = $this->db->query($fallback_sql);

			foreach ($fallback_query->rows as $fr) {
				$instrument_ids[] = (int)$fr['product_id'];
			}

			if (empty($instrument_ids)) {
				$gen_query = $this->db->query("SELECT DISTINCT p.product_id FROM " . DB_PREFIX . "product p JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) WHERE p.status = '1' AND p2c.category_id IN (" . $cat_list . ") ORDER BY p.price DESC LIMIT " . (int)$limit);
				foreach ($gen_query->rows as $gr) {
					$instrument_ids[] = (int)$gr['product_id'];
				}
			}
		}

		if (empty($instrument_ids)) {
			return array();
		}

		$id_list = implode(',', $instrument_ids);
		$sql = "SELECT p.product_id, p.image, p.price, pd.name,
				m.name AS brand,
				(SELECT cd.name FROM " . DB_PREFIX . "category_description cd JOIN " . DB_PREFIX . "product_to_category p2c ON (cd.category_id = p2c.category_id) WHERE p2c.product_id = p.product_id AND cd.category_id IN (12, 13, 14, 15, 16, 19, 20, 24, 22, 11) ORDER BY FIELD(cd.category_id, 15, 12, 14, 13, 20, 19, 16, 24, 22, 11) LIMIT 1) AS category_name,
				(SELECT pa.text FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND pa.attribute_id = 9 LIMIT 1) AS sound_style
				FROM " . DB_PREFIX . "product p
				JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
				LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
				WHERE p.product_id IN (" . $id_list . ") AND p.status = '1'
				ORDER BY FIELD(p.product_id, " . $id_list . ")";

		$query = $this->db->query($sql);
		$results = array();

		foreach ($query->rows as $row) {
			$thumb = $row['image'] && is_file(DIR_IMAGE . $row['image']) 
				? $this->model_tool_image->resize($row['image'], 360, 360) 
				: $this->model_tool_image->resize('placeholder.png', 360, 360);

			$results[] = array(
				'product_id'  => $row['product_id'],
				'thumb'       => $thumb,
				'name'        => $row['name'],
				'brand'       => $row['brand'],
				'gear_type'   => !empty($row['category_name']) ? $row['category_name'] : 'Оборудование',
				'sound_style' => $row['sound_style'],
				'price'       => $this->currency->format($row['price'], 'BYN'),
				'href'        => $this->url->link('product/product', 'product_id=' . $row['product_id'])
			);
		}

		return $results;
	}

	public function getTracks($product_id) {
		$meta = $this->getReleaseMetadata($product_id);
		if (!$meta['is_music']) {
			return array();
		}

		$table_check = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "product_tracklist'");
		if (!$table_check->num_rows) {
			return array();
		}

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_tracklist WHERE product_id = '" . (int)$product_id . "' AND status = '1' ORDER BY track_num ASC");
		$tracks = array();
		foreach ($query->rows as $row) {
			$audio_url = '';
			if (!empty($row['preview_file'])) {
				if (filter_var($row['preview_file'], FILTER_VALIDATE_URL)) {
					$audio_url = $row['preview_file'];
				} elseif (file_exists(DIR_APPLICATION . '../' . $row['preview_file'])) {
					$audio_url = $row['preview_file'];
				}
			}

			$tracks[] = array(
				'track_id'   => $row['track_id'],
				'track_num'  => $row['track_num'],
				'title'      => $row['title'],
				'duration'   => !empty($row['duration']) ? $row['duration'] : '--:--',
				'audio_url'  => $audio_url,
				'has_preview'=> !empty($audio_url)
			);
		}
		return $tracks;
	}
}

