<?php
class ControllerExtensionModuleSoundnetStorefront extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/soundnet_storefront');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_soundnet_storefront', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
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
			'href' => $this->url->link('extension/module/soundnet_storefront', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/soundnet_storefront', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_soundnet_storefront_status'])) {
			$data['module_soundnet_storefront_status'] = $this->request->post['module_soundnet_storefront_status'];
		} else {
			$data['module_soundnet_storefront_status'] = $this->config->get('module_soundnet_storefront_status');
		}

		if (isset($this->request->post['module_soundnet_storefront_ai_url'])) {
			$data['module_soundnet_storefront_ai_url'] = $this->request->post['module_soundnet_storefront_ai_url'];
		} elseif ($this->config->get('module_soundnet_storefront_ai_url')) {
			$data['module_soundnet_storefront_ai_url'] = $this->config->get('module_soundnet_storefront_ai_url');
		} else {
			$data['module_soundnet_storefront_ai_url'] = 'http://127.0.0.1:8000';
		}

		if (isset($this->request->post['module_soundnet_storefront_limit'])) {
			$data['module_soundnet_storefront_limit'] = $this->request->post['module_soundnet_storefront_limit'];
		} elseif ($this->config->get('module_soundnet_storefront_limit')) {
			$data['module_soundnet_storefront_limit'] = $this->config->get('module_soundnet_storefront_limit');
		} else {
			$data['module_soundnet_storefront_limit'] = 10;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/soundnet_storefront', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/soundnet_storefront')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function install() {
		$this->load->model('setting/setting');

		$defaults = array(
			'module_soundnet_storefront_status' => '1',
			'module_soundnet_storefront_ai_url' => 'http://127.0.0.1:8000',
			'module_soundnet_storefront_limit'  => '10'
		);

		$this->model_setting_setting->editSetting('module_soundnet_storefront', $defaults);
	}

	public function uninstall() {
		$this->load->model('setting/setting');

		$this->model_setting_setting->deleteSetting('module_soundnet_storefront');
	}
}
