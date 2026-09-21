<?php
class ControllerExtensionFeedGoogleSitemap extends Controller {
	public function index() {
		// Output sitemap if status is enabled or fallback active
		if ($this->config->get('feed_google_sitemap_status') !== '0') {
			$output  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

			// 1. Homepage
			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . htmlspecialchars($this->url->link('common/home'), ENT_XML1, 'UTF-8') . '</loc>' . "\n";
			$output .= '    <changefreq>daily</changefreq>' . "\n";
			$output .= '    <priority>1.0</priority>' . "\n";
			$output .= '  </url>' . "\n";

			// 2. Special Offers Page
			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . htmlspecialchars($this->url->link('product/special'), ENT_XML1, 'UTF-8') . '</loc>' . "\n";
			$output .= '    <changefreq>daily</changefreq>' . "\n";
			$output .= '    <priority>0.8</priority>' . "\n";
			$output .= '  </url>' . "\n";

			// 3. Brands / Manufacturers List Page
			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . htmlspecialchars($this->url->link('product/manufacturer'), ENT_XML1, 'UTF-8') . '</loc>' . "\n";
			$output .= '    <changefreq>weekly</changefreq>' . "\n";
			$output .= '    <priority>0.7</priority>' . "\n";
			$output .= '  </url>' . "\n";

			// 4. Contact Us Page
			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . htmlspecialchars($this->url->link('information/contact'), ENT_XML1, 'UTF-8') . '</loc>' . "\n";
			$output .= '    <changefreq>monthly</changefreq>' . "\n";
			$output .= '    <priority>0.6</priority>' . "\n";
			$output .= '  </url>' . "\n";

			// 5. Categories (Hierarchical, without duplicate product loops)
			$this->load->model('catalog/category');
			$output .= $this->getCategories(0);

			// 6. Manufacturers
			$this->load->model('catalog/manufacturer');
			$manufacturers = $this->model_catalog_manufacturer->getManufacturers();
			foreach ($manufacturers as $manufacturer) {
				$output .= '  <url>' . "\n";
				$output .= '    <loc>' . htmlspecialchars($this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id']), ENT_XML1, 'UTF-8') . '</loc>' . "\n";
				$output .= '    <changefreq>weekly</changefreq>' . "\n";
				$output .= '    <priority>0.7</priority>' . "\n";
				$output .= '  </url>' . "\n";
			}

			// 7. Products (All active products, canonical links, optional image tags)
			$this->load->model('catalog/product');
			$this->load->model('tool/image');

			$products = $this->model_catalog_product->getProducts();

			foreach ($products as $product) {
				$date_mod = (!empty($product['date_modified']) && $product['date_modified'] != '0000-00-00 00:00:00') 
					? $product['date_modified'] 
					: (!empty($product['date_added']) ? $product['date_added'] : date('Y-m-d H:i:s'));

				$output .= '  <url>' . "\n";
				$output .= '    <loc>' . htmlspecialchars($this->url->link('product/product', 'product_id=' . $product['product_id']), ENT_XML1, 'UTF-8') . '</loc>' . "\n";
				$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date_mod)) . '</lastmod>' . "\n";
				$output .= '    <changefreq>weekly</changefreq>' . "\n";
				$output .= '    <priority>0.9</priority>' . "\n";

				if (!empty($product['image']) && is_file(DIR_IMAGE . $product['image'])) {
					$img_w = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width') ?: 800;
					$img_h = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height') ?: 800;
					$image_url = $this->model_tool_image->resize($product['image'], $img_w, $img_h);

					if ($image_url) {
						$clean_name = htmlspecialchars(strip_tags(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8')), ENT_XML1, 'UTF-8');
						$output .= '    <image:image>' . "\n";
						$output .= '      <image:loc>' . htmlspecialchars($image_url, ENT_XML1, 'UTF-8') . '</image:loc>' . "\n";
						$output .= '      <image:caption>' . $clean_name . '</image:caption>' . "\n";
						$output .= '      <image:title>' . $clean_name . '</image:title>' . "\n";
						$output .= '    </image:image>' . "\n";
					}
				}

				$output .= '  </url>' . "\n";
			}

			// 8. Information Pages
			$this->load->model('catalog/information');
			$informations = $this->model_catalog_information->getInformations();

			foreach ($informations as $information) {
				$output .= '  <url>' . "\n";
				$output .= '    <loc>' . htmlspecialchars($this->url->link('information/information', 'information_id=' . $information['information_id']), ENT_XML1, 'UTF-8') . '</loc>' . "\n";
				$output .= '    <changefreq>monthly</changefreq>' . "\n";
				$output .= '    <priority>0.5</priority>' . "\n";
				$output .= '  </url>' . "\n";
			}

			$output .= '</urlset>';

			$this->response->addHeader('Content-Type: application/xml; charset=utf-8');
			$this->response->setOutput($output);
		}
	}

	protected function getCategories($parent_id, $current_path = '') {
		$output = '';

		$results = $this->model_catalog_category->getCategories($parent_id);

		foreach ($results as $result) {
			if (!$current_path) {
				$new_path = $result['category_id'];
			} else {
				$new_path = $current_path . '_' . $result['category_id'];
			}

			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . htmlspecialchars($this->url->link('product/category', 'path=' . $new_path), ENT_XML1, 'UTF-8') . '</loc>' . "\n";
			$output .= '    <changefreq>weekly</changefreq>' . "\n";
			$output .= '    <priority>0.8</priority>' . "\n";
			$output .= '  </url>' . "\n";

			// Recurse into child subcategories
			$output .= $this->getCategories($result['category_id'], $new_path);
		}

		return $output;
	}
}
