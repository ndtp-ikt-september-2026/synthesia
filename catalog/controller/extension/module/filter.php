<?php
class ControllerExtensionModuleFilter extends Controller {
	public function index() {
		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
		} else {
			$parts = array();
		}

		$category_id = (int)end($parts);

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');

		$category_info = $this->model_catalog_category->getCategory($category_id);

		if ($category_info) {
			$this->load->language('extension/module/filter');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['action'] = str_replace('&amp;', '&', $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url));
			$data['category_id'] = $category_id;

			// 1. Price Range
			$price_query = $this->db->query("SELECT MIN(p.price) as min_price, MAX(p.price) as max_price 
				FROM " . DB_PREFIX . "product p 
				JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id) 
				WHERE ptc.category_id = '" . $category_id . "' AND p.status = '1'");

			$min_val = ($price_query->row && $price_query->row['min_price'] !== null) ? floor((float)$price_query->row['min_price']) : 0;
			$max_val = ($price_query->row && $price_query->row['max_price'] !== null) ? ceil((float)$price_query->row['max_price']) : 1000;

			if ($max_val <= $min_val) {
				$max_val = $min_val + 500;
			}

			$data['price_min'] = $min_val;
			$data['price_max'] = $max_val;

			$data['current_price_min'] = isset($this->request->get['filter_price_min']) && $this->request->get['filter_price_min'] !== '' ? (float)$this->request->get['filter_price_min'] : $min_val;
			$data['current_price_max'] = isset($this->request->get['filter_price_max']) && $this->request->get['filter_price_max'] !== '' ? (float)$this->request->get['filter_price_max'] : $max_val;

			// 2. Brands / Artists in Category
			$data['brands'] = array();
			$brand_query = $this->db->query("SELECT pa.text as brand_name, COUNT(DISTINCT p.product_id) as total 
				FROM " . DB_PREFIX . "product p 
				JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id) 
				JOIN " . DB_PREFIX . "product_attribute pa ON (p.product_id = pa.product_id) 
				JOIN " . DB_PREFIX . "attribute_description ad ON (pa.attribute_id = ad.attribute_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "') 
				WHERE ptc.category_id = '" . $category_id . "' AND p.status = '1' 
				  AND (LOWER(TRIM(ad.name)) IN ('бренд', 'brand', 'исполнитель', 'artist')) 
				GROUP BY pa.text 
				ORDER BY total DESC, pa.text ASC 
				LIMIT 10");

			$selected_brands = isset($this->request->get['filter_brand']) ? explode(',', $this->request->get['filter_brand']) : array();

			foreach ($brand_query->rows as $b_row) {
				$b_name = trim($b_row['brand_name']);
				if ($b_name !== '') {
					$data['brands'][] = array(
						'name'    => $b_name,
						'count'   => $b_row['total'],
						'checked' => in_array($b_name, $selected_brands)
					);
				}
			}

			// 3. Domain Specific Attributes (Vinyl formats, Instrument types)
			$data['formats'] = array();
			$format_query = $this->db->query("SELECT pa.text as format_name, COUNT(DISTINCT p.product_id) as total 
				FROM " . DB_PREFIX . "product p 
				JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id) 
				JOIN " . DB_PREFIX . "product_attribute pa ON (p.product_id = pa.product_id) 
				JOIN " . DB_PREFIX . "attribute_description ad ON (pa.attribute_id = ad.attribute_id AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "') 
				WHERE ptc.category_id = '" . $category_id . "' AND p.status = '1' 
				  AND (LOWER(TRIM(ad.name)) IN ('формат издания', 'format', 'тип инструмента', 'gear type', 'материал корпуса')) 
				GROUP BY pa.text 
				ORDER BY total DESC, pa.text ASC 
				LIMIT 8");

			$selected_formats = isset($this->request->get['filter_format']) ? explode(',', $this->request->get['filter_format']) : array();

			foreach ($format_query->rows as $f_row) {
				$f_name = trim($f_row['format_name']);
				if ($f_name !== '') {
					$data['formats'][] = array(
						'name'    => $f_name,
						'count'   => $f_row['total'],
						'checked' => in_array($f_name, $selected_formats)
					);
				}
			}

			// 4. Native OpenCart Filter Groups (if configured)
			if (isset($this->request->get['filter'])) {
				$data['filter_category'] = explode(',', $this->request->get['filter']);
			} else {
				$data['filter_category'] = array();
			}

			$data['filter_groups'] = array();
			$filter_groups = $this->model_catalog_category->getCategoryFilters($category_id);

			if ($filter_groups) {
				foreach ($filter_groups as $filter_group) {
					$childen_data = array();

					foreach ($filter_group['filter'] as $filter) {
						$filter_data = array(
							'filter_category_id' => $category_id,
							'filter_filter'      => $filter['filter_id']
						);

						$childen_data[] = array(
							'filter_id' => $filter['filter_id'],
							'name'      => $filter['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : '')
						);
					}

					$data['filter_groups'][] = array(
						'filter_group_id' => $filter_group['filter_group_id'],
						'name'            => $filter_group['name'],
						'filter'          => $childen_data
					);
				}
			}

			return $this->load->view('extension/module/filter', $data);
		}
	}
}