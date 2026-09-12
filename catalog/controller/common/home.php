<?php
class ControllerCommonHome extends Controller {
	public function index() {
		$this->document->setTitle($this->config->get('config_meta_title'));
		$this->document->setDescription($this->config->get('config_meta_description'));
		$this->document->setKeywords($this->config->get('config_meta_keyword'));

		if (isset($this->request->get['route'])) {
			$canonical = $this->url->link('common/home');
			if ($this->config->get('config_seo_pro') && !$this->config->get('config_seopro_addslash')) {
				$canonical = rtrim($canonical, '/');
			}
			$this->document->addLink($canonical, 'canonical');
		}

		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		$this->load->model('tool/image');

		$language_id = (int)$this->config->get('config_language_id');

		// 1. Curated Vinyl & CD Releases (Categories 1, 2, 3)
		$data['music_recommendations'] = array();
		$music_query = $this->db->query("SELECT DISTINCT p.product_id, p.image, p.price, p.tax_class_id, pd.name 
			FROM " . DB_PREFIX . "product p 
			JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . $language_id . "') 
			JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id) 
			WHERE ptc.category_id IN (1, 2, 3) AND p.status = '1' 
			ORDER BY p.date_added DESC, p.product_id DESC 
			LIMIT 12");

		foreach ($music_query->rows as $product) {
			if ($product['image'] && is_file(DIR_IMAGE . $product['image'])) {
				$image = $this->model_tool_image->resize($product['image'], 360, 360);
			} else {
				$image = $this->model_tool_image->resize('placeholder.png', 360, 360);
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$price = false;
			}

			// Fetch product attributes for metadata
			$attrs_query = $this->db->query("SELECT LOWER(TRIM(ad.name)) as attr_name, pa.text 
				FROM " . DB_PREFIX . "product_attribute pa 
				JOIN " . DB_PREFIX . "attribute_description ad ON (pa.attribute_id = ad.attribute_id AND ad.language_id = '" . $language_id . "') 
				WHERE pa.product_id = '" . (int)$product['product_id'] . "' AND pa.language_id = '" . $language_id . "'");

			$artist = '';
			$year = '';
			$label = '';
			$format = 'Vinyl LP';
			$genre = '';

			foreach ($attrs_query->rows as $attr) {
				if ($attr['attr_name'] == 'исполнитель' || $attr['attr_name'] == 'artist') {
					$artist = $attr['text'];
				} elseif ($attr['attr_name'] == 'год выпуска' || $attr['attr_name'] == 'year') {
					$year = $attr['text'];
				} elseif ($attr['attr_name'] == 'лейбл' || $attr['attr_name'] == 'label') {
					$label = $attr['text'];
				} elseif ($attr['attr_name'] == 'формат издания' || $attr['attr_name'] == 'format') {
					$format = (stripos($attr['text'], 'cd') !== false || stripos($attr['text'], 'компакт') !== false) ? 'КОМПАКТ-ДИСК CD' : 'ВИНИЛ LP';
				} elseif ($attr['attr_name'] == 'жанр' || $attr['attr_name'] == 'genre') {
					$genre = $attr['text'];
				}
			}

			if (!$artist) {
				$artist = 'Различные исполнители';
			}

			$meta_parts = array();
			if ($year) {
				$meta_parts[] = $year;
			}
			if ($label) {
				$meta_parts[] = $label;
			}
			$meta = !empty($meta_parts) ? implode(' • ', $meta_parts) : ($genre ? $genre : 'Оригинальный мастер');

			$data['music_recommendations'][] = array(
				'product_id'   => $product['product_id'],
				'thumb'        => $image,
				'name'         => $product['name'],
				'artist'       => $artist,
				'meta'         => $meta,
				'format_badge' => $format,
				'price'        => $price,
				'href'         => $this->url->link('product/product', 'product_id=' . $product['product_id'])
			);
		}

		// 2. Recommended Musical Instruments / Matching Gear (Categories 11, 12, 14, 15, 22, 24)
		$data['gear_recommendations'] = array();
		$gear_query = $this->db->query("SELECT DISTINCT p.product_id, p.image, p.price, p.tax_class_id, pd.name 
			FROM " . DB_PREFIX . "product p 
			JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . $language_id . "') 
			JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id) 
			WHERE ptc.category_id IN (11, 12, 14, 15, 22, 24) AND p.status = '1' AND p.image IS NOT NULL AND p.image != '' 
			ORDER BY p.product_id ASC 
			LIMIT 12");

		foreach ($gear_query->rows as $product) {
			if ($product['image'] && is_file(DIR_IMAGE . $product['image'])) {
				$image = $this->model_tool_image->resize($product['image'], 360, 360);
			} else {
				$image = $this->model_tool_image->resize('placeholder.png', 360, 360);
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$price = false;
			}

			$attrs_query = $this->db->query("SELECT LOWER(TRIM(ad.name)) as attr_name, pa.text 
				FROM " . DB_PREFIX . "product_attribute pa 
				JOIN " . DB_PREFIX . "attribute_description ad ON (pa.attribute_id = ad.attribute_id AND ad.language_id = '" . $language_id . "') 
				WHERE pa.product_id = '" . (int)$product['product_id'] . "' AND pa.language_id = '" . $language_id . "'");

			$brand = '';
			$gear_type = 'Инструмент';
			$sound_style = '';

			foreach ($attrs_query->rows as $attr) {
				if ($attr['attr_name'] == 'бренд' || $attr['attr_name'] == 'brand') {
					$brand = $attr['text'];
				} elseif ($attr['attr_name'] == 'тип инструмента' || $attr['attr_name'] == 'gear type') {
					$gear_type = $attr['text'];
				} elseif ($attr['attr_name'] == 'стиль звучания' || $attr['attr_name'] == 'sound style') {
					$sound_style = $attr['text'];
				}
			}

			if (!$brand) {
				$name_words = explode(' ', $product['name']);
				$brand = isset($name_words[1]) ? $name_words[1] : 'Студийное оборудование';
			}

			$data['gear_recommendations'][] = array(
				'product_id'  => $product['product_id'],
				'thumb'       => $image,
				'name'        => $product['name'],
				'brand'       => $brand,
				'gear_type'   => $gear_type,
				'sound_style' => $sound_style,
				'price'       => $price,
				'href'        => $this->url->link('product/product', 'product_id=' . $product['product_id'])
			);
		}

		// 3. Popular Categories (5-Column Square Grid)
		$data['popular_categories'] = array();
		$target_categories = array(2, 3, 15, 12, 24); // Vinyl, CDs, Electric Guitars, Acoustic Guitars, Synthesizers

		foreach ($target_categories as $cat_id) {
			$cat_query = $this->db->query("SELECT c.category_id, c.image, cd.name, 
				(SELECT COUNT(ptc.product_id) FROM " . DB_PREFIX . "product_to_category ptc JOIN " . DB_PREFIX . "product p ON (ptc.product_id = p.product_id AND p.status = '1') WHERE ptc.category_id = c.category_id) as total_products 
				FROM " . DB_PREFIX . "category c 
				JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id AND cd.language_id = '" . $language_id . "') 
				WHERE c.category_id = '" . (int)$cat_id . "' AND c.status = '1'");

			if ($cat_query->num_rows) {
				$cat = $cat_query->row;

				$cat_image = $cat['image'];
				if (!$cat_image || !is_file(DIR_IMAGE . $cat_image)) {
					// Fallback to top product image from this category
					$prod_img = $this->db->query("SELECT p.image FROM " . DB_PREFIX . "product p JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id) WHERE ptc.category_id = '" . (int)$cat_id . "' AND p.status = '1' AND p.image IS NOT NULL AND p.image != '' LIMIT 1");
					if ($prod_img->num_rows && is_file(DIR_IMAGE . $prod_img->row['image'])) {
						$cat_image = $prod_img->row['image'];
					}
				}

				if ($cat_image && is_file(DIR_IMAGE . $cat_image)) {
					$thumb = $this->model_tool_image->resize($cat_image, 300, 300);
				} else {
					$thumb = $this->model_tool_image->resize('placeholder.png', 300, 300);
				}

				$data['popular_categories'][] = array(
					'category_id' => $cat['category_id'],
					'name'        => $cat['name'],
					'thumb'       => $thumb,
					'count'       => $cat['total_products'],
					'href'        => $this->url->link('product/category', 'path=' . $cat['category_id'])
				);
			}
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}
}