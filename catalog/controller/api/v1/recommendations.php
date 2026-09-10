<?php
/**
 * Headless Recommendations REST API Controller
 *
 * Route: api/v1/recommendations/similar
 * Route: api/v1/recommendations/matching_gear
 *
 * Architecture: Pure JSON Headless REST API (Content-Type: application/json, no Twig rendering)
 * Constraints: Strictly single quotes for all strings, queries, array keys, and paths.
 */

class ControllerApiV1Recommendations extends Controller {
	public function similar() {
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');

		if (!isset($this->request->get['track_id']) || !is_numeric($this->request->get['track_id'])) {
			$this->response->setOutput(json_encode(array(
				'status'  => 'error',
				'message' => 'Missing or invalid track_id parameter'
			), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			return;
		}

		$track_id = (int)$this->request->get['track_id'];
		$limit = isset($this->request->get['limit']) ? min(max((int)$this->request->get['limit'], 1), 50) : 10;

		$params = array(
			'limit' => $limit
		);
		if (!empty($this->request->get['genre'])) {
			$params['genre'] = trim($this->request->get['genre']);
		}
		if (isset($this->request->get['year_from']) && is_numeric($this->request->get['year_from'])) {
			$params['year_from'] = (int)$this->request->get['year_from'];
		}
		if (isset($this->request->get['year_to']) && is_numeric($this->request->get['year_to'])) {
			$params['year_to'] = (int)$this->request->get['year_to'];
		}

		$server_url = $this->config->get('module_soundnet_ai_server_url');
		if (!$server_url) {
			$server_url = 'http://127.0.0.1:8000';
		}

		$endpoint = rtrim($server_url, '/') . '/internal/v1/tracks/' . $track_id . '/similar?' . http_build_query($params);

		$ai_response = $this->executeCurlGet($endpoint);
		if (!$ai_response) {
			$this->response->setOutput(json_encode(array(
				'status'          => 'error',
				'message'         => 'SoundNet AI microservice unreachable or returned error',
				'source_track_id' => $track_id,
				'total'           => 0,
				'tracks'          => array()
			), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			return;
		}

		$decoded = json_decode($ai_response, true);
		if (!isset($decoded['similar_track_ids']) || !is_array($decoded['similar_track_ids'])) {
			$this->response->setOutput(json_encode(array(
				'status'          => 'error',
				'message'         => 'Invalid response from SoundNet AI service',
				'source_track_id' => $track_id,
				'total'           => 0,
				'tracks'          => array()
			), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			return;
		}

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$tracks = array();
		foreach ($decoded['similar_track_ids'] as $sim_id) {
			$product_info = $this->model_catalog_product->getProduct((int)$sim_id);
			if ($product_info) {
				$attributes = $this->extractProductAttributes((int)$sim_id);

				$image_url = '';
				if (!empty($product_info['image'])) {
					if (is_file(DIR_IMAGE . $product_info['image'])) {
						$image_url = $this->model_tool_image->resize($product_info['image'], 300, 300);
					} else {
						$image_url = $product_info['image'];
					}
				}

				$tracks[] = array(
					'product_id' => (int)$product_info['product_id'],
					'name'       => $product_info['name'],
					'price'      => (float)$product_info['price'],
					'image'      => $image_url,
					'artist'     => isset($attributes['artist']) ? $attributes['artist'] : '',
					'genre'      => isset($attributes['genre']) ? $attributes['genre'] : ''
				);
			}
		}

		$this->response->setOutput(json_encode(array(
			'status'          => 'success',
			'source_track_id' => $track_id,
			'total'           => count($tracks),
			'tracks'          => $tracks
		), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	public function matching_gear() {
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');

		if (!isset($this->request->get['track_id']) || !is_numeric($this->request->get['track_id'])) {
			$this->response->setOutput(json_encode(array(
				'status'  => 'error',
				'message' => 'Missing or invalid track_id parameter'
			), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			return;
		}

		$track_id = (int)$this->request->get['track_id'];
		$limit = isset($this->request->get['limit']) ? min(max((int)$this->request->get['limit'], 1), 20) : 5;

		$params = array(
			'limit' => $limit
		);
		if (isset($this->request->get['category_id']) && is_numeric($this->request->get['category_id'])) {
			$params['category_id'] = (int)$this->request->get['category_id'];
		}

		$server_url = $this->config->get('module_soundnet_ai_server_url');
		if (!$server_url) {
			$server_url = 'http://127.0.0.1:8000';
		}

		$endpoint = rtrim($server_url, '/') . '/internal/v1/tracks/' . $track_id . '/instruments?' . http_build_query($params);

		$ai_response = $this->executeCurlGet($endpoint);
		if (!$ai_response) {
			$this->response->setOutput(json_encode(array(
				'status'          => 'error',
				'message'         => 'SoundNet AI microservice unreachable or returned error',
				'source_track_id' => $track_id,
				'total'           => 0,
				'instruments'     => array()
			), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			return;
		}

		$decoded = json_decode($ai_response, true);
		if (!isset($decoded['matching_instrument_ids']) || !is_array($decoded['matching_instrument_ids'])) {
			$this->response->setOutput(json_encode(array(
				'status'          => 'error',
				'message'         => 'Invalid response from SoundNet AI service',
				'source_track_id' => $track_id,
				'total'           => 0,
				'instruments'     => array()
			), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			return;
		}

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$instruments = array();
		foreach ($decoded['matching_instrument_ids'] as $inst_id) {
			$product_info = $this->model_catalog_product->getProduct((int)$inst_id);
			if ($product_info) {
				$attributes = $this->extractProductAttributes((int)$inst_id);

				$image_url = '';
				if (!empty($product_info['image'])) {
					if (is_file(DIR_IMAGE . $product_info['image'])) {
						$image_url = $this->model_tool_image->resize($product_info['image'], 300, 300);
					} else {
						$image_url = $product_info['image'];
					}
				}

				$instruments[] = array(
					'product_id' => (int)$product_info['product_id'],
					'name'       => $product_info['name'],
					'price'      => (float)$product_info['price'],
					'image'      => $image_url,
					'artist'     => isset($attributes['artist']) ? $attributes['artist'] : '',
					'genre'      => isset($attributes['genre']) ? $attributes['genre'] : ''
				);
			}
		}

		$this->response->setOutput(json_encode(array(
			'status'          => 'success',
			'source_track_id' => $track_id,
			'total'           => count($instruments),
			'instruments'     => $instruments
		), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	private function extractProductAttributes($product_id) {
		$result = array(
			'artist' => '',
			'genre'  => ''
		);

		$query = $this->db->query('SELECT ad.`name`, pa.`text` FROM `' . DB_PREFIX . 'product_attribute` pa LEFT JOIN `' . DB_PREFIX . 'attribute_description` ad ON (pa.`attribute_id` = ad.`attribute_id`) WHERE pa.`product_id` = \'' . (int)$product_id . '\'');

		foreach ($query->rows as $row) {
			$name = mb_strtolower(trim($row['name']), 'UTF-8');
			$val = trim($row['text']);

			if ($name === 'artist' || $name === 'исполнитель' || $name === 'бренд' || $name === 'brand') {
				$result['artist'] = $val;
			} elseif ($name === 'genre' || $name === 'жанр' || $name === 'тип инструмента' || strpos($name, 'стиль') !== false) {
				$result['genre'] = $val;
			}
		}

		return $result;
	}

	private function executeCurlGet($url) {
		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, 2000);
			curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

			$response = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($response === false || $http_code >= 400) {
				return null;
			}

			return $response;
		} catch (\Throwable $e) {
			return null;
		}
	}
}
