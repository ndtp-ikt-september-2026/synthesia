<?php
$file = 'd:/OSPanel/domains/synthesia/admin/controller/catalog/product.php';
$content = file_get_contents($file);

$urlSnippet = "			if (isset(\$this->request->get['filter_artist'])) {
				\$url .= '&filter_artist=' . urlencode(html_entity_decode(\$this->request->get['filter_artist'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset(\$this->request->get['filter_year'])) {
				\$url .= '&filter_year=' . urlencode(html_entity_decode(\$this->request->get['filter_year'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset(\$this->request->get['filter_genre'])) {
				\$url .= '&filter_genre=' . urlencode(html_entity_decode(\$this->request->get['filter_genre'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset(\$this->request->get['filter_brand'])) {
				\$url .= '&filter_brand=' . urlencode(html_entity_decode(\$this->request->get['filter_brand'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset(\$this->request->get['filter_format'])) {
				\$url .= '&filter_format=' . urlencode(html_entity_decode(\$this->request->get['filter_format'], ENT_QUOTES, 'UTF-8'));
			}
";

// 1. In index(), edit(), delete(), copy(), getForm(), getList() sorting, getList() pagination:
// Search for:
// if (isset($this->request->get['filter_noindex'])) {
//     $url .= '&filter_noindex=' . $this->request->get['filter_noindex'];
// }
$target = "if (isset(\$this->request->get['filter_noindex'])) {
				\$url .= '&filter_noindex=' . \$this->request->get['filter_noindex'];
			}";

$replacement = $target . "\n\n" . $urlSnippet;
// Replace all occurrences where $url .= '&filter_noindex'
$count = 0;
$content = str_replace($target, $replacement, $content, $count);
echo "Replaced \$url in $count places\n";

// 2. In getList(): reading params after filter_noindex
$targetGetList = "if (isset(\$this->request->get['filter_noindex'])) {
			\$filter_noindex = \$this->request->get['filter_noindex'];
		} else {
			\$filter_noindex = '';
		}";

$replacementGetList = $targetGetList . "

		if (isset(\$this->request->get['filter_artist'])) {
			\$filter_artist = \$this->request->get['filter_artist'];
		} else {
			\$filter_artist = '';
		}

		if (isset(\$this->request->get['filter_year'])) {
			\$filter_year = \$this->request->get['filter_year'];
		} else {
			\$filter_year = '';
		}

		if (isset(\$this->request->get['filter_genre'])) {
			\$filter_genre = \$this->request->get['filter_genre'];
		} else {
			\$filter_genre = '';
		}

		if (isset(\$this->request->get['filter_brand'])) {
			\$filter_brand = \$this->request->get['filter_brand'];
		} else {
			\$filter_brand = '';
		}

		if (isset(\$this->request->get['filter_format'])) {
			\$filter_format = \$this->request->get['filter_format'];
		} else {
			\$filter_format = '';
		}";

$content = str_replace($targetGetList, $replacementGetList, $content, $count2);
echo "Replaced param reading in getList: $count2\n";

// 3. In getList(): adding to $filter_data
$targetFilterData = "'filter_noindex' 		=> \$filter_noindex,";
$replacementFilterData = "'filter_noindex' 		=> \$filter_noindex,
			'filter_artist' 		=> \$filter_artist,
			'filter_year' 			=> \$filter_year,
			'filter_genre' 			=> \$filter_genre,
			'filter_brand' 			=> \$filter_brand,
			'filter_format' 		=> \$filter_format,";
$content = str_replace($targetFilterData, $replacementFilterData, $content, $count3);
echo "Replaced filter_data: $count3\n";

// 4. In getList(): music attributes batch fetch before loop
$targetLoop = "\$results = \$this->model_catalog_product->getProducts(\$filter_data);";
$replacementLoop = "\$results = \$this->model_catalog_product->getProducts(\$filter_data);

		\$product_ids = array();
		foreach (\$results as \$result) {
			\$product_ids[] = \$result['product_id'];
		}
		\$music_attributes = \$this->model_catalog_product->getProductsMusicAttributes(\$product_ids);";
$content = str_replace($targetLoop, $replacementLoop, $content, $count4);
echo "Replaced loop preparation: $count4\n";

// 5. In getList(): in $data['products'][]
$targetProductArr = "'noindex'    => \$result['noindex'] ? \$this->language->get('text_enabled') : \$this->language->get('text_disabled'),";
$replacementProductArr = "'noindex'    => \$result['noindex'] ? \$this->language->get('text_enabled') : \$this->language->get('text_disabled'),
				'artist'     => isset(\$music_attributes[\$result['product_id']]['artist']) ? \$music_attributes[\$result['product_id']]['artist'] : '',
				'year'       => isset(\$music_attributes[\$result['product_id']]['year']) ? \$music_attributes[\$result['product_id']]['year'] : '',
				'genre'      => isset(\$music_attributes[\$result['product_id']]['genre']) ? \$music_attributes[\$result['product_id']]['genre'] : '',
				'brand'      => isset(\$music_attributes[\$result['product_id']]['brand']) ? \$music_attributes[\$result['product_id']]['brand'] : '',
				'format'     => isset(\$music_attributes[\$result['product_id']]['format']) ? \$music_attributes[\$result['product_id']]['format'] : '',";
$content = str_replace($targetProductArr, $replacementProductArr, $content, $count5);
echo "Replaced data['products'] array: $count5\n";

// 6. In getList(): passing variables to $data
$targetDataVars = "\$data['filter_noindex'] = \$filter_noindex;";
$replacementDataVars = "\$data['filter_noindex'] = \$filter_noindex;
		\$data['filter_artist'] = \$filter_artist;
		\$data['filter_year'] = \$filter_year;
		\$data['filter_genre'] = \$filter_genre;
		\$data['filter_brand'] = \$filter_brand;
		\$data['filter_format'] = \$filter_format;

		\$data['filter_years_list'] = \$this->model_catalog_product->getDistinctAttributeValues(3, 'DESC');
		\$data['filter_genres_list'] = \$this->model_catalog_product->getDistinctAttributeValues(2, 'ASC');
		\$data['filter_formats_list'] = \$this->model_catalog_product->getDistinctAttributeValues(5, 'ASC');";
$content = str_replace($targetDataVars, $replacementDataVars, $content, $count6);
echo "Replaced data vars: $count6\n";

// 7. Add autocomplete methods before the closing class bracket
$targetEnd = "	public function autocomplete() {";
$replacementEnd = "	public function autocompleteArtist() {
		\$json = array();
		if (isset(\$this->request->get['filter_name'])) {
			\$this->load->model('catalog/product');
			\$results = \$this->model_catalog_product->getAttributeValues(1, \$this->request->get['filter_name']);
			foreach (\$results as \$result) {
				\$json[] = array(
					'name' => strip_tags(html_entity_decode(\$result, ENT_QUOTES, 'UTF-8'))
				);
			}
		}
		\$this->response->addHeader('Content-Type: application/json');
		\$this->response->setOutput(json_encode(\$json));
	}

	public function autocompleteBrand() {
		\$json = array();
		if (isset(\$this->request->get['filter_name'])) {
			\$this->load->model('catalog/product');
			\$results = \$this->model_catalog_product->getAttributeValues(7, \$this->request->get['filter_name']);
			foreach (\$results as \$result) {
				\$json[] = array(
					'name' => strip_tags(html_entity_decode(\$result, ENT_QUOTES, 'UTF-8'))
				);
			}
		}
		\$this->response->addHeader('Content-Type: application/json');
		\$this->response->setOutput(json_encode(\$json));
	}

	public function autocomplete() {";

$content = str_replace($targetEnd, $replacementEnd, $content, $count7);
echo "Added autocomplete methods: $count7\n";

file_put_contents($file, $content);
echo "admin/controller/catalog/product.php updated!\n";
