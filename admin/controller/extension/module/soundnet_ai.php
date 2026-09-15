<?php
/**
 * SoundNet AI OpenCart 3 Administration Module Controller
 *
 * Architecture: MVC-L & Events Engine
 * Constraints: Strictly single quotes for all strings, paths, and queries.
 */

class ControllerExtensionModuleSoundnetAi extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/soundnet_ai');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_soundnet_ai', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['server_url'])) {
			$data['error_server_url'] = $this->error['server_url'];
		} else {
			$data['error_server_url'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/soundnet_ai', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/soundnet_ai', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_soundnet_ai_status'])) {
			$data['module_soundnet_ai_status'] = $this->request->post['module_soundnet_ai_status'];
		} else {
			$data['module_soundnet_ai_status'] = $this->config->get('module_soundnet_ai_status');
		}

		if (isset($this->request->post['module_soundnet_ai_server_url'])) {
			$data['module_soundnet_ai_server_url'] = $this->request->post['module_soundnet_ai_server_url'];
		} elseif ($this->config->get('module_soundnet_ai_server_url')) {
			$data['module_soundnet_ai_server_url'] = $this->config->get('module_soundnet_ai_server_url');
		} else {
			$data['module_soundnet_ai_server_url'] = 'http://127.0.0.1:8000';
		}

		if (isset($this->request->post['module_soundnet_ai_secret'])) {
			$data['module_soundnet_ai_secret'] = $this->request->post['module_soundnet_ai_secret'];
		} elseif ($this->config->get('module_soundnet_ai_secret')) {
			$data['module_soundnet_ai_secret'] = $this->config->get('module_soundnet_ai_secret');
		} else {
			$data['module_soundnet_ai_secret'] = 'soundnet_secret_key';
		}

		if (isset($this->request->post['module_soundnet_ai_timeout'])) {
			$data['module_soundnet_ai_timeout'] = $this->request->post['module_soundnet_ai_timeout'];
		} elseif ($this->config->get('module_soundnet_ai_timeout')) {
			$data['module_soundnet_ai_timeout'] = $this->config->get('module_soundnet_ai_timeout');
		} else {
			$data['module_soundnet_ai_timeout'] = '500';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/soundnet_ai', $data));
	}

	public function install() {
		// 1. Create table oc_product_vector_status
		$this->db->query('CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'product_vector_status` (
			`product_id` INT(11) NOT NULL,
			`is_indexed` TINYINT(1) DEFAULT 0,
			`has_audio` TINYINT(1) DEFAULT 0,
			`audio_path` VARCHAR(255) NULL,
			`content_hash` VARCHAR(32) NOT NULL DEFAULT \'\',
			`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`product_id`),
			KEY `idx_is_indexed` (`is_indexed`),
			KEY `idx_has_audio` (`has_audio`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

		$cols = array();
		$cols_q = $this->db->query('DESCRIBE `' . DB_PREFIX . 'product_vector_status`');
		foreach ($cols_q->rows as $c) {
			$cols[$c['Field']] = true;
		}
		if (!isset($cols['is_indexed'])) {
			$this->db->query('ALTER TABLE `' . DB_PREFIX . 'product_vector_status` ADD `is_indexed` TINYINT(1) DEFAULT 0');
		}
		if (!isset($cols['has_audio'])) {
			$this->db->query('ALTER TABLE `' . DB_PREFIX . 'product_vector_status` ADD `has_audio` TINYINT(1) DEFAULT 0');
		}
		if (!isset($cols['audio_path'])) {
			$this->db->query('ALTER TABLE `' . DB_PREFIX . 'product_vector_status` ADD `audio_path` VARCHAR(255) NULL');
		}
		if (!isset($cols['content_hash'])) {
			$this->db->query('ALTER TABLE `' . DB_PREFIX . 'product_vector_status` ADD `content_hash` VARCHAR(32) NOT NULL DEFAULT \'\'');
		}
		if (!isset($cols['updated_at'])) {
			$this->db->query('ALTER TABLE `' . DB_PREFIX . 'product_vector_status` ADD `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
		}

		// 2. Set default configuration
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('module_soundnet_ai', array(
			'module_soundnet_ai_status'     => '1',
			'module_soundnet_ai_server_url' => 'http://127.0.0.1:8000',
			'module_soundnet_ai_secret'     => 'soundnet_secret_key',
			'module_soundnet_ai_timeout'    => '500'
		));

		// 3. Register Event Triggers
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('soundnet_ai');

		$this->model_setting_event->addEvent(
			'soundnet_ai',
			'admin/model/catalog/product/addProduct/after',
			'extension/module/soundnet_ai/onProductSave'
		);

		$this->model_setting_event->addEvent(
			'soundnet_ai',
			'admin/model/catalog/product/editProduct/after',
			'extension/module/soundnet_ai/onProductSave'
		);

		$this->model_setting_event->addEvent(
			'soundnet_ai',
			'admin/model/catalog/product/deleteProduct/after',
			'extension/module/soundnet_ai/onProductDelete'
		);

		$this->model_setting_event->addEvent(
			'soundnet_ai',
			'admin/view/catalog/product_form/before',
			'extension/module/soundnet_ai/onProductForm'
		);
	}

	public function uninstall() {
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('soundnet_ai');

		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_soundnet_ai');
	}

	public function onProductSave(&$route, &$args, &$output) {
		// 1. Resolve Product ID
		$product_id = 0;
		if (!empty($output) && is_numeric($output)) {
			$product_id = (int)$output;
		} elseif (isset($args[0]) && is_numeric($args[0])) {
			$product_id = (int)$args[0];
		} elseif (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		}

		if ($product_id <= 0) {
			return;
		}

		$data = array();
		if (isset($args[1]) && is_array($args[1])) {
			$data = $args[1];
		} elseif (isset($args[0]) && is_array($args[0])) {
			$data = $args[0];
		} elseif (!empty($this->request->post)) {
			$data = $this->request->post;
		}

		// 2. Extract Title, Description
		$title = '';
		$description = '';
		$language_id = (int)$this->config->get('config_language_id');

		if (!empty($data['product_description'])) {
			if (isset($data['product_description'][$language_id]['name'])) {
				$title = $data['product_description'][$language_id]['name'];
				$description = $data['product_description'][$language_id]['description'];
			} else {
				$first_desc = reset($data['product_description']);
				if (isset($first_desc['name'])) {
					$title = $first_desc['name'];
					$description = $first_desc['description'];
				}
			}
		}

		if (empty($title)) {
			$q = $this->db->query('SELECT `name`, `description` FROM `' . DB_PREFIX . 'product_description` WHERE `product_id` = \'' . (int)$product_id . '\' LIMIT 1');
			if ($q->num_rows) {
				$title = $q->row['name'];
				$description = $q->row['description'];
			}
		}

		// 3. Extract Categories
		$category_names = array();
		$category_ids = array();
		$cat_query = $this->db->query('SELECT p2c.`category_id`, cd.`name` FROM `' . DB_PREFIX . 'product_to_category` p2c LEFT JOIN `' . DB_PREFIX . 'category_description` cd ON (p2c.`category_id` = cd.`category_id`) WHERE p2c.`product_id` = \'' . (int)$product_id . '\'');
		foreach ($cat_query->rows as $c) {
			if (!empty($c['name'])) {
				$category_names[] = $c['name'];
			}
			$category_ids[] = (int)$c['category_id'];
		}

		// 4. Extract Tags
		$tags = '';
		if (!empty($data['product_description'])) {
			if (isset($data['product_description'][$language_id]['tag']) && !empty($data['product_description'][$language_id]['tag'])) {
				$tags = trim($data['product_description'][$language_id]['tag']);
			} else {
				foreach ($data['product_description'] as $pd) {
					if (!empty($pd['tag'])) {
						$tags = trim($pd['tag']);
						break;
					}
				}
			}
		}

		if (empty($tags)) {
			$tag_q = $this->db->query('SELECT `tag` FROM `' . DB_PREFIX . 'product_description` WHERE `product_id` = \'' . (int)$product_id . '\' AND `tag` != \'\' LIMIT 1');
			if ($tag_q->num_rows) {
				$tags = trim($tag_q->row['tag']);
			}
		}

		// 5. Extract ALL Attributes & Tag Characteristics
		$all_attributes = array();
		$artist = '';
		$genre = '';
		$year = '';
		$vibe = '';
		$brand = '';
		$instrument_type = '';
		$sound_style = '';
		$pickups = '';

		// Inspect attributes passed in $data
		if (!empty($data['product_attribute']) && is_array($data['product_attribute'])) {
			foreach ($data['product_attribute'] as $pa) {
				if (!empty($pa['name']) && !empty($pa['product_attribute_description'][$language_id]['text'])) {
					$all_attributes[trim($pa['name'])] = trim($pa['product_attribute_description'][$language_id]['text']);
				} elseif (!empty($pa['attribute_id'])) {
					$aid = (int)$pa['attribute_id'];
					$ad_q = $this->db->query('SELECT `name` FROM `' . DB_PREFIX . 'attribute_description` WHERE `attribute_id` = \'' . $aid . '\' AND `language_id` = \'' . $language_id . '\' LIMIT 1');
					if ($ad_q->num_rows && isset($pa['product_attribute_description'][$language_id]['text'])) {
						$all_attributes[trim($ad_q->row['name'])] = trim($pa['product_attribute_description'][$language_id]['text']);
					}
				}
			}
		}

		// Also query DB for all persisted attributes
		$attr_query = $this->db->query('SELECT ad.`name`, pa.`text` FROM `' . DB_PREFIX . 'product_attribute` pa LEFT JOIN `' . DB_PREFIX . 'attribute_description` ad ON (pa.`attribute_id` = ad.`attribute_id`) WHERE pa.`product_id` = \'' . (int)$product_id . '\'');
		foreach ($attr_query->rows as $attr) {
			$raw_name = trim($attr['name']);
			$raw_val  = trim($attr['text']);
			if ($raw_name !== '' && $raw_val !== '') {
				$all_attributes[$raw_name] = $raw_val;
			}
		}

		foreach ($all_attributes as $raw_name => $raw_val) {
			$norm_name = mb_strtolower($raw_name, 'UTF-8');
			if ($norm_name === 'artist' || $norm_name === 'исполнитель') {
				$artist = $raw_val;
			} elseif ($norm_name === 'genre' || $norm_name === 'жанр') {
				$genre = $raw_val;
			} elseif ($norm_name === 'year' || $norm_name === 'год выпуска' || $norm_name === 'год') {
				$year = $raw_val;
			} elseif (strpos($norm_name, 'вайб') !== false || strpos($norm_name, 'vibe') !== false) {
				$vibe = $raw_val;
			} elseif (strpos($norm_name, 'стиль') !== false || strpos($norm_name, 'sound') !== false || strpos($norm_name, 'звучан') !== false) {
				$sound_style = $raw_val;
			} elseif ($norm_name === 'бренд' || $norm_name === 'brand' || $norm_name === 'производитель') {
				$brand = $raw_val;
			} elseif (strpos($norm_name, 'тип инструмента') !== false || strpos($norm_name, 'инструмент') !== false || $norm_name === 'instrument' || $norm_name === 'тип') {
				$instrument_type = $raw_val;
			} elseif (strpos($norm_name, 'звукоснимател') !== false || strpos($norm_name, 'pickup') !== false) {
				$pickups = $raw_val;
			}
		}

		// 6. Robust Entity Type Detection (Musical Instrument vs Music Track)
		$entity_type = 'track';
		if (!empty($data['type'])) {
			$entity_type = ($data['type'] === 'instrument') ? 'instrument' : 'track';
		} else {
			$indicators = array_merge(
				array($title, $tags, $instrument_type, $brand, $sound_style, $pickups),
				$category_names,
				array_keys($all_attributes),
				array_values($all_attributes)
			);
			$check_str = mb_strtolower(implode(' ', $indicators), 'UTF-8');
			$gear_keywords = array('инструмент', 'гитар', 'guitar', 'bass', 'бас', 'барабан', 'drum', 'клавиш', 'keyboard', 'синтезатор', 'synth', 'piano', 'пианино', 'рояль', 'усилител', 'amp', 'комбик', 'педал', 'pedal', 'звукоснимател', 'pickup', 'fender', 'gibson', 'ibanez', 'yamaha', 'roland', 'korg', 'stratocaster', 'telecaster', 'les paul');
			foreach ($gear_keywords as $kw) {
				if (strpos($check_str, $kw) !== false) {
					$entity_type = 'instrument';
					break;
				}
			}
		}

		// 7. Audio File Handling
		$has_audio = 0;
		$audio_path = null;

		// Check for newly uploaded audio file in admin product form
		$uploaded_file = null;
		if (isset($this->request->files['audio_file']) && !empty($this->request->files['audio_file']['tmp_name'])) {
			$uploaded_file = $this->request->files['audio_file'];
		} elseif (isset($_FILES['audio_file']) && !empty($_FILES['audio_file']['tmp_name'])) {
			$uploaded_file = $_FILES['audio_file'];
		}

		if ($uploaded_file && $uploaded_file['error'] === UPLOAD_ERR_OK && is_uploaded_file($uploaded_file['tmp_name'])) {
			$allowed_exts = array('mp3', 'wav', 'flac', 'ogg', 'm4a', 'aac');
			$orig_name = $uploaded_file['name'];
			$ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

			if (in_array($ext, $allowed_exts)) {
				$upload_base = defined('DIR_UPLOAD') ? DIR_UPLOAD : (defined('DIR_STORAGE') ? DIR_STORAGE . 'upload/' : DIR_SYSTEM . 'storage/upload/');
				$target_dir = rtrim($upload_base, '/\\') . '/music/';
				if (!is_dir($target_dir)) {
					@mkdir($target_dir, 0777, true);
				}

				$new_filename = 'track_' . $product_id . '_' . md5(uniqid(mt_rand(), true)) . '.' . $ext;
				$target_file = $target_dir . $new_filename;

				if (@move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
					$has_audio = 1;
					$audio_path = str_replace('\\', '/', $target_file);
				}
			}
		}

		// If no newly uploaded audio file, check existing audio record
		if (!$has_audio) {
			$stat_query = $this->db->query('SELECT `has_audio`, `audio_path` FROM `' . DB_PREFIX . 'product_vector_status` WHERE `product_id` = \'' . (int)$product_id . '\' LIMIT 1');
			if ($stat_query->num_rows && !empty($stat_query->row['audio_path']) && is_file($stat_query->row['audio_path'])) {
				$has_audio = 1;
				$audio_path = $stat_query->row['audio_path'];
			}
		}

		// 8. Construct text_for_embedding from Tag Characteristics
		$text_parts = array();
		$clean_desc = trim(strip_tags(html_entity_decode($description, ENT_QUOTES, 'UTF-8')));

		if ($entity_type === 'instrument') {
			$text_parts[] = 'Instrument: ' . $title;
			if (!empty($instrument_type)) {
				$text_parts[] = 'Type: ' . $instrument_type;
			}
			if (!empty($sound_style)) {
				$text_parts[] = 'Sound Style: ' . $sound_style;
			}
			if (!empty($vibe)) {
				$text_parts[] = 'Vibe: ' . $vibe;
			}
			if (!empty($brand)) {
				$text_parts[] = 'Brand: ' . $brand;
			}
			if (!empty($pickups)) {
				$text_parts[] = 'Pickups: ' . $pickups;
			}
			if (!empty($category_names)) {
				$text_parts[] = 'Categories: ' . implode(', ', $category_names);
			}

			// Extract only relevant musical attributes (filter out physical dimensions, package size, weight, warranty, country)
			$noise_keys = array('габарит', 'размер', 'вес', 'гаранти', 'страна', 'упаковк', 'питани', 'ток');
			$clean_specs = array();
			foreach ($all_attributes as $k => $v) {
				$k_lower = mb_strtolower($k, 'UTF-8');
				$is_noise = false;
				foreach ($noise_keys as $nk) {
					if (strpos($k_lower, $nk) !== false) {
						$is_noise = true;
						break;
					}
				}
				if (!$is_noise && mb_strlen($v, 'UTF-8') < 50) {
					$clean_specs[] = $k . ': ' . $v;
				}
			}
			if (!empty($clean_specs)) {
				$text_parts[] = 'Characteristics: ' . implode(', ', array_slice($clean_specs, 0, 8));
			}

			// Clean tags (remove dimensions/weights from tags)
			if (!empty($tags)) {
				$tag_items = array_map('trim', explode(',', $tags));
				$filtered_tags = array();
				foreach ($tag_items as $ti) {
					if (!preg_match('/(\d+\s*x\s*\d+|\d+\s*кг|\d+\s*г|\d+\s*м\b|китай|россия|гарантия)/ui', $ti) && mb_strlen($ti, 'UTF-8') < 30) {
						$filtered_tags[] = $ti;
					}
				}
				if (!empty($filtered_tags)) {
					$text_parts[] = 'Tags: ' . implode(', ', array_slice($filtered_tags, 0, 10));
				}
			}

			if (!empty($clean_desc)) {
				$text_parts[] = 'Description: ' . mb_substr($clean_desc, 0, 250, 'UTF-8');
			}
		} else {
			$text_parts[] = 'Track: ' . $title;
			if (!empty($artist)) {
				$text_parts[] = 'Artist: ' . $artist;
			}
			if (!empty($genre)) {
				$text_parts[] = 'Genre: ' . $genre;
			}
			if (!empty($year)) {
				$text_parts[] = 'Year: ' . $year;
			}
			if (!empty($vibe)) {
				$text_parts[] = 'Vibe: ' . $vibe;
			}
			if (!empty($tags)) {
				$text_parts[] = 'Tags: ' . $tags;
			}
			if (!empty($category_names)) {
				$text_parts[] = 'Categories: ' . implode(', ', $category_names);
			}
			if (!empty($clean_desc)) {
				$text_parts[] = 'Description: ' . mb_substr($clean_desc, 0, 500, 'UTF-8');
			}
		}

		$text_for_embedding = implode(' | ', $text_parts);
		$audio_mtime = ($has_audio && $audio_path && is_file($audio_path)) ? (string)filemtime($audio_path) : '';
		$content_hash = md5($text_for_embedding . $audio_mtime);

		// 9. Update oc_product_vector_status table
		$this->db->query('INSERT INTO `' . DB_PREFIX . 'product_vector_status` SET
			`product_id` = \'' . (int)$product_id . '\',
			`is_indexed` = 1,
			`has_audio` = \'' . (int)$has_audio . '\',
			`audio_path` = ' . ($audio_path ? '\'' . $this->db->escape($audio_path) . '\'' : 'NULL') . ',
			`content_hash` = \'' . $this->db->escape($content_hash) . '\'
			ON DUPLICATE KEY UPDATE
			`is_indexed` = 1,
			`has_audio` = \'' . (int)$has_audio . '\',
			`audio_path` = ' . ($audio_path ? '\'' . $this->db->escape($audio_path) . '\'' : 'NULL') . ',
			`content_hash` = \'' . $this->db->escape($content_hash) . '\',
			`updated_at` = NOW()');

		// 10. Dispatch Webhook cURL Request to Python Daemon
		$server_url = $this->config->get('module_soundnet_ai_server_url');
		if (!$server_url) {
			$server_url = 'http://127.0.0.1:8000';
		}
		$secret = $this->config->get('module_soundnet_ai_secret');
		if (!$secret) {
			$secret = 'soundnet_secret_key';
		}
		$timeout_ms = (int)$this->config->get('module_soundnet_ai_timeout');
		if ($timeout_ms <= 0) {
			$timeout_ms = 500;
		}

		$endpoint = rtrim($server_url, '/') . '/internal/webhooks/product-sync';

		$primary_cat_id = !empty($category_ids) ? (int)$category_ids[0] : 0;

		$post_data = array(
			'product_id'         => (int)$product_id,
			'action'             => 'upsert',
			'entity_type'        => $entity_type,
			'text_for_embedding' => $text_for_embedding,
			'tags'               => $tags,
			'category_id'        => $primary_cat_id
		);

		if ($has_audio && $audio_path && is_file($audio_path)) {
			if (class_exists('CURLFile')) {
				$mime = 'audio/mpeg';
				if (function_exists('mime_content_type')) {
					$detected = @mime_content_type($audio_path);
					if ($detected) {
						$mime = $detected;
					}
				}
				$post_data['audio_file'] = new \CURLFile($audio_path, $mime, basename($audio_path));
			}
		}

		$this->sendWebhookAsync($endpoint, $post_data, $secret, $timeout_ms);
	}

	public function onProductDelete(&$route, &$args, &$output) {
		$product_id = 0;
		if (isset($args[0]) && is_numeric($args[0])) {
			$product_id = (int)$args[0];
		}

		if ($product_id <= 0) {
			return;
		}

		// Remove audio file if locally stored
		$stat_query = $this->db->query('SELECT `audio_path` FROM `' . DB_PREFIX . 'product_vector_status` WHERE `product_id` = \'' . (int)$product_id . '\' LIMIT 1');
		if ($stat_query->num_rows && !empty($stat_query->row['audio_path']) && is_file($stat_query->row['audio_path'])) {
			@unlink($stat_query->row['audio_path']);
		}

		// Delete record from database
		$this->db->query('DELETE FROM `' . DB_PREFIX . 'product_vector_status` WHERE `product_id` = \'' . (int)$product_id . '\'');

		// Dispatch deletion webhook to Python daemon
		$server_url = $this->config->get('module_soundnet_ai_server_url');
		if (!$server_url) {
			$server_url = 'http://127.0.0.1:8000';
		}
		$secret = $this->config->get('module_soundnet_ai_secret');
		if (!$secret) {
			$secret = 'soundnet_secret_key';
		}
		$timeout_ms = (int)$this->config->get('module_soundnet_ai_timeout');
		if ($timeout_ms <= 0) {
			$timeout_ms = 500;
		}

		$endpoint = rtrim($server_url, '/') . '/internal/webhooks/product-sync';
		$post_data = array(
			'product_id' => (int)$product_id,
			'action'     => 'delete'
		);

		$this->sendWebhookAsync($endpoint, $post_data, $secret, $timeout_ms);
	}

	public function onProductForm(&$route, &$args, &$output) {
		$product_id = 0;
		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		}

		$args['soundnet_audio_path'] = '';
		if ($product_id > 0) {
			$q = $this->db->query('SELECT `audio_path` FROM `' . DB_PREFIX . 'product_vector_status` WHERE `product_id` = \'' . (int)$product_id . '\' AND `has_audio` = \'1\' LIMIT 1');
			if ($q->num_rows && !empty($q->row['audio_path'])) {
				$args['soundnet_audio_path'] = basename($q->row['audio_path']);
			}
		}
	}

	private function sendWebhookAsync($url, $post_data, $secret, $timeout_ms) {
		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'X-Internal-Secret: ' . $secret
			));
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, min(300, $timeout_ms));
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout_ms);
			curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

			$response = curl_exec($ch);
			curl_close($ch);
			return $response;
		} catch (\Throwable $e) {
			// Fail gracefully without blocking dashboard execution
			$this->log->write('[SoundNet AI Notice] Webhook failed: ' . $e->getMessage());
			return null;
		}
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/soundnet_ai')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!isset($this->request->post['module_soundnet_ai_server_url']) || empty($this->request->post['module_soundnet_ai_server_url'])) {
			$this->error['server_url'] = $this->language->get('error_server_url');
		}

		return !$this->error;
	}
}
