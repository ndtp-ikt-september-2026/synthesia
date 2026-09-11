<?php
class ControllerExtensionModuleSoundnetStorefront extends Controller {

	public function index($setting = array()) {
		$this->load->language('extension/module/soundnet_storefront');
		$this->load->model('extension/module/soundnet_storefront');

		$this->document->addStyle('catalog/view/theme/default/stylesheet/soundnet_storefront.css');
		$this->document->addScript('catalog/view/javascript/soundnet_storefront.js');

		$limit = !empty($setting['limit']) ? (int)$setting['limit'] : 12;

		$data['heading_curated_music'] = $this->language->get('heading_curated_music');
		$data['subtitle_curated_music']= $this->language->get('subtitle_curated_music');
		$data['heading_gear_match']    = $this->language->get('heading_gear_match');
		$data['subtitle_gear_match']   = $this->language->get('subtitle_gear_match');
		$data['heading_categories']    = $this->language->get('heading_categories');
		$data['subtitle_categories']   = $this->language->get('subtitle_categories');

		$data['button_ai_mode']        = $this->language->get('button_ai_mode');
		$data['button_buy']            = $this->language->get('button_buy');

		$data['music_recommendations'] = $this->model_extension_module_soundnet_storefront->getMusicRecommendations($limit);
		$data['gear_recommendations']  = $this->model_extension_module_soundnet_storefront->getGearRecommendations($limit);
		$data['popular_categories']    = $this->model_extension_module_soundnet_storefront->getPopularCategories(5);

		return $this->load->view('extension/module/soundnet_recommendations', $data);
	}

	public function getSimilar() {
		$this->load->language('extension/module/soundnet_storefront');
		$this->load->model('extension/module/soundnet_storefront');
		$this->load->model('catalog/product');

		$product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;

		$product_info = $this->model_catalog_product->getProduct($product_id);
		if (!$product_info) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(array(
				'success'  => false,
				'message'  => 'Товар не найден',
				'products' => array()
			), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			return;
		}

		$limit = $this->config->get('module_soundnet_storefront_limit') ? (int)$this->config->get('module_soundnet_storefront_limit') : 8;

		$similar_products = $this->model_extension_module_soundnet_storefront->getSimilarReleases($product_id, $limit);
		$meta = $this->model_extension_module_soundnet_storefront->getReleaseMetadata($product_id);

		$json = array(
			'success'      => true,
			'source_id'    => $product_id,
			'product_name' => $product_info['name'],
			'artist'       => $meta['artist'],
			'genre'        => $meta['genre'],
			'vibe'         => $meta['vibe'],
			'total'        => count($similar_products),
			'products'     => $similar_products
		);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	public function getMatchingGear() {
		$this->load->language('extension/module/soundnet_storefront');
		$this->load->model('extension/module/soundnet_storefront');

		$product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;

		$gear = $this->model_extension_module_soundnet_storefront->getMatchingInstruments($product_id, 6);

		$json = array(
			'success'   => true,
			'source_id' => $product_id,
			'total'     => count($gear),
			'gear'      => $gear
		);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
}
