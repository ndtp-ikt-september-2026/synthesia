<?php
/**
 * SoundNet Dynamic Tracklist & Audio Player
 * Admin Controller
 *
 * Architecture: OpenCart 3 MVC-L & Events Engine
 * Strictly single quotes for all strings, paths, and queries.
 */

class ControllerExtensionModuleSoundnetTracklist extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/soundnet_tracklist');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_soundnet_tracklist', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
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
			'href' => $this->url->link('extension/module/soundnet_tracklist', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/soundnet_tracklist', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		// Module settings
		if (isset($this->request->post['module_soundnet_tracklist_status'])) {
			$data['module_soundnet_tracklist_status'] = $this->request->post['module_soundnet_tracklist_status'];
		} else {
			$data['module_soundnet_tracklist_status'] = $this->config->get('module_soundnet_tracklist_status');
		}

		if (isset($this->request->post['module_soundnet_tracklist_theme'])) {
			$data['module_soundnet_tracklist_theme'] = $this->request->post['module_soundnet_tracklist_theme'];
		} elseif ($this->config->get('module_soundnet_tracklist_theme')) {
			$data['module_soundnet_tracklist_theme'] = $this->config->get('module_soundnet_tracklist_theme');
		} else {
			$data['module_soundnet_tracklist_theme'] = 'darkstudio';
		}

		if (isset($this->request->post['module_soundnet_tracklist_position'])) {
			$data['module_soundnet_tracklist_position'] = $this->request->post['module_soundnet_tracklist_position'];
		} elseif ($this->config->get('module_soundnet_tracklist_position')) {
			$data['module_soundnet_tracklist_position'] = $this->config->get('module_soundnet_tracklist_position');
		} else {
			$data['module_soundnet_tracklist_position'] = 'bottom';
		}

		if (isset($this->request->post['module_soundnet_tracklist_auto_advance'])) {
			$data['module_soundnet_tracklist_auto_advance'] = $this->request->post['module_soundnet_tracklist_auto_advance'];
		} elseif ($this->config->get('module_soundnet_tracklist_auto_advance') !== null) {
			$data['module_soundnet_tracklist_auto_advance'] = $this->config->get('module_soundnet_tracklist_auto_advance');
		} else {
			$data['module_soundnet_tracklist_auto_advance'] = '1';
		}

		if (isset($this->request->post['module_soundnet_tracklist_allowed_formats'])) {
			$data['module_soundnet_tracklist_allowed_formats'] = $this->request->post['module_soundnet_tracklist_allowed_formats'];
		} elseif ($this->config->get('module_soundnet_tracklist_allowed_formats')) {
			$data['module_soundnet_tracklist_allowed_formats'] = $this->config->get('module_soundnet_tracklist_allowed_formats');
		} else {
			$data['module_soundnet_tracklist_allowed_formats'] = 'mp3,wav,ogg,flac';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/soundnet_tracklist', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/soundnet_tracklist')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function install() {
		$this->load->model('extension/module/soundnet_tracklist');
		$this->model_extension_module_soundnet_tracklist->install();

		// Default configuration
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('module_soundnet_tracklist', array(
			'module_soundnet_tracklist_status'          => '1',
			'module_soundnet_tracklist_theme'           => 'darkstudio',
			'module_soundnet_tracklist_position'        => 'bottom',
			'module_soundnet_tracklist_auto_advance'    => '1',
			'module_soundnet_tracklist_allowed_formats' => 'mp3,wav,ogg,flac'
		));

		// Register Event Triggers
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('soundnet_tracklist');

		$this->model_setting_event->addEvent(
			'soundnet_tracklist',
			'admin/model/catalog/product/addProduct/after',
			'extension/module/soundnet_tracklist/onProductSave'
		);

		$this->model_setting_event->addEvent(
			'soundnet_tracklist',
			'admin/model/catalog/product/editProduct/after',
			'extension/module/soundnet_tracklist/onProductSave'
		);

		$this->model_setting_event->addEvent(
			'soundnet_tracklist',
			'admin/model/catalog/product/deleteProduct/after',
			'extension/module/soundnet_tracklist/onProductDelete'
		);

		$this->model_setting_event->addEvent(
			'soundnet_tracklist',
			'admin/view/catalog/product_form/before',
			'extension/module/soundnet_tracklist/onProductForm'
		);

		$this->model_setting_event->addEvent(
			'soundnet_tracklist',
			'catalog/controller/product/product/before',
			'extension/module/soundnet_tracklist/onCatalogProductBefore'
		);
	}

	public function uninstall() {
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('soundnet_tracklist');

		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_soundnet_tracklist');

		$this->load->model('extension/module/soundnet_tracklist');
		$this->model_extension_module_soundnet_tracklist->uninstall();
	}

	public function upload() {
		$this->load->language('extension/module/soundnet_tracklist');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/soundnet_tracklist') && !$this->user->hasPermission('modify', 'catalog/product')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (empty($json)) {
			if (!empty($this->request->files['file']['name']) && is_uploaded_file($this->request->files['file']['tmp_name'])) {
				$filename = basename(html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8'));

				if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 255)) {
					$json['error'] = $this->language->get('error_file_upload');
				}

				// Allowed file extensions
				$allowed_setting = $this->config->get('module_soundnet_tracklist_allowed_formats');
				if (!empty($allowed_setting)) {
					$allowed_exts = array_map('trim', explode(',', strtolower($allowed_setting)));
				} else {
					$allowed_exts = array('mp3', 'wav', 'ogg', 'flac');
				}

				$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

				if (!in_array($extension, $allowed_exts)) {
					$json['error'] = sprintf($this->language->get('error_file_type'), implode(', ', $allowed_exts));
				}

				// Check error
				if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
					$json['error'] = $this->language->get('error_file_upload');
				}
			} else {
				$json['error'] = $this->language->get('error_file_upload');
			}
		}

		if (empty($json)) {
			$root_path = defined('DIR_CATALOG') ? dirname(DIR_CATALOG) . '/' : (defined('DIR_APPLICATION') ? dirname(DIR_APPLICATION) . '/' : '');
			$target_dir = $root_path . 'upload/audio/';

			if (!is_dir($target_dir)) {
				@mkdir($target_dir, 0777, true);
			}

			$safe_name = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($filename, PATHINFO_FILENAME));
			$clean_filename = 'sn_' . date('Ymd_His') . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 8) . '_' . $safe_name . '.' . $extension;
			$target_file = $target_dir . $clean_filename;

			if (@move_uploaded_file($this->request->files['file']['tmp_name'], $target_file)) {
				$relative_path = 'upload/audio/' . $clean_filename;

				$json['success'] = $this->language->get('text_upload_success');
				$json['preview_file'] = $relative_path;
				$json['filename'] = $filename;
				$json['title_hint'] = ucwords(str_replace(array('_', '-'), ' ', pathinfo($filename, PATHINFO_FILENAME)));
			} else {
				$json['error'] = $this->language->get('error_file_upload');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function onProductForm(&$route, &$data) {
		$this->load->language('extension/module/soundnet_tracklist');
		$this->load->model('extension/module/soundnet_tracklist');

		$product_id = 0;
		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		}

		if (isset($this->request->post['product_track'])) {
			$data['product_tracks'] = $this->request->post['product_track'];
		} elseif ($product_id > 0) {
			$data['product_tracks'] = $this->model_extension_module_soundnet_tracklist->getTracks($product_id);
		} else {
			$data['product_tracks'] = array();
		}

		$data['user_token'] = $this->session->data['user_token'];
		$data['tracklist_upload_url'] = $this->url->link('extension/module/soundnet_tracklist/upload', 'user_token=' . $this->session->data['user_token'], true);
	}

	public function onProductSave(&$route, &$args, &$output) {
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

		$tracks = array();
		if (isset($this->request->post['product_track']) && is_array($this->request->post['product_track'])) {
			$tracks = $this->request->post['product_track'];
		} elseif (isset($args[1]['product_track']) && is_array($args[1]['product_track'])) {
			$tracks = $args[1]['product_track'];
		}

		$this->load->model('extension/module/soundnet_tracklist');
		$this->model_extension_module_soundnet_tracklist->saveTracks($product_id, $tracks);
	}

	public function onProductDelete(&$route, &$args, &$output) {
		$product_id = 0;
		if (isset($args[0]) && is_numeric($args[0])) {
			$product_id = (int)$args[0];
		} elseif (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		}

		if ($product_id > 0) {
			$this->load->model('extension/module/soundnet_tracklist');
			$this->model_extension_module_soundnet_tracklist->deleteTracks($product_id);
		}
	}
}
