<?php
/**
 * SoundNet Dynamic Tracklist & Audio Player
 * Catalog Controller
 *
 * Architecture: OpenCart 3 MVC-L
 * Strictly single quotes for PHP strings, SQL statements, and array indices.
 */

class ControllerExtensionModuleSoundnetTracklist extends Controller {
	public function index($setting = array()) {
		if (!$this->config->get('module_soundnet_tracklist_status')) {
			return '';
		}

		$product_id = 0;
		if (isset($setting['product_id']) && (int)$setting['product_id'] > 0) {
			$product_id = (int)$setting['product_id'];
		} elseif (isset($this->request->get['product_id']) && (int)$this->request->get['product_id'] > 0) {
			$product_id = (int)$this->request->get['product_id'];
		}

		if ($product_id <= 0) {
			return '';
		}

		$this->load->model('extension/module/soundnet_tracklist');
		$tracks_raw = $this->model_extension_module_soundnet_tracklist->getTracks($product_id);

		if (empty($tracks_raw)) {
			return '';
		}

		$this->load->language('extension/module/soundnet_tracklist');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		$album_title = $product_info ? $product_info['name'] : '';
		$album_model = $product_info ? $product_info['model'] : '';

		if ($product_info && !empty($product_info['image']) && is_file(DIR_IMAGE . $product_info['image'])) {
			$album_thumb = $this->model_tool_image->resize($product_info['image'], 120, 120);
		} else {
			$album_thumb = $this->model_tool_image->resize('placeholder.png', 120, 120);
		}

		$tracks = array();
		foreach ($tracks_raw as $t) {
			$preview_file = trim($t['preview_file']);
			$has_preview = !empty($preview_file);
			$audio_url = '';

			if ($has_preview) {
				if (preg_match('/^https?:\/\//i', $preview_file)) {
					$audio_url = $preview_file;
				} else {
					$audio_url = $this->url->link('extension/module/soundnet_tracklist/stream', 'track_id=' . (int)$t['track_id'], true);
				}
			}

			// Format badge detection
			$format_badge = 'MP3 320k';
			$ext = strtolower(pathinfo($preview_file, PATHINFO_EXTENSION));
			if ($ext === 'flac') {
				$format_badge = 'FLAC';
			} elseif ($ext === 'wav') {
				$format_badge = 'WAV';
			} elseif ($ext === 'ogg') {
				$format_badge = 'OGG';
			} elseif ($ext === 'm4a' || $ext === 'aac') {
				$format_badge = 'AAC';
			}

			$tracks[] = array(
				'track_id'     => (int)$t['track_id'],
				'track_num'    => (int)$t['track_num'],
				'title'        => $t['title'],
				'duration'     => !empty($t['duration']) ? $t['duration'] : '0:00',
				'has_preview'  => $has_preview,
				'audio_url'    => $audio_url,
				'format_badge' => $format_badge
			);
		}

		$data['product_id']   = $product_id;
		$data['album_title']  = $album_title;
		$data['album_model']  = $album_model;
		$data['album_thumb']  = $album_thumb;
		$data['tracks']       = $tracks;
		$data['track_count']  = count($tracks);

		// Settings & Themes
		$theme = $this->config->get('module_soundnet_tracklist_theme');
		$data['theme'] = in_array($theme, array('darkstudio', 'vinyl', 'minimalist')) ? $theme : 'darkstudio';

		$position = $this->config->get('module_soundnet_tracklist_position');
		$data['position'] = in_array($position, array('bottom', 'inline')) ? $position : 'bottom';

		$auto_advance = $this->config->get('module_soundnet_tracklist_auto_advance');
		$data['auto_advance'] = ($auto_advance !== null && $auto_advance == '0') ? 0 : 1;

		// Assets
		$this->document->addStyle('catalog/view/javascript/soundnet_tracklist/tracklist.css');
		$this->document->addScript('catalog/view/javascript/soundnet_tracklist/tracklist.js');

		return $this->load->view('extension/module/soundnet_tracklist', $data);
	}

	public function stream() {
		if (!isset($this->request->get['track_id'])) {
			$this->response->addHeader('HTTP/1.1 400 Bad Request');
			$this->response->setOutput('Missing track ID');
			return;
		}

		$track_id = (int)$this->request->get['track_id'];

		$this->load->model('extension/module/soundnet_tracklist');
		$track = $this->model_extension_module_soundnet_tracklist->getTrack($track_id);

		if (!$track || empty($track['preview_file'])) {
			$this->response->addHeader('HTTP/1.1 404 Not Found');
			$this->response->setOutput('Track audio not found');
			return;
		}

		$file_ref = trim($track['preview_file']);

		// Handle external URLs
		if (preg_match('/^https?:\/\//i', $file_ref)) {
			header('Location: ' . $file_ref);
			exit;
		}

		// Resolve local file system path
		$root_path = defined('DIR_CATALOG') ? dirname(DIR_CATALOG) . '/' : (defined('DIR_APPLICATION') ? dirname(DIR_APPLICATION) . '/' : '');
		$candidate_paths = array(
			$root_path . ltrim($file_ref, '/\\'),
			DIR_IMAGE . ltrim($file_ref, '/\\'),
			(defined('DIR_UPLOAD') ? DIR_UPLOAD : '') . ltrim($file_ref, '/\\')
		);

		$real_path = '';
		foreach ($candidate_paths as $p) {
			if (!empty($p) && is_file($p) && is_readable($p)) {
				$real_path = $p;
				break;
			}
		}

		if (empty($real_path)) {
			$this->response->addHeader('HTTP/1.1 404 Not Found');
			$this->response->setOutput('Audio file not found on server');
			return;
		}

		// Determine mime type
		$ext = strtolower(pathinfo($real_path, PATHINFO_EXTENSION));
		$mime_types = array(
			'mp3'  => 'audio/mpeg',
			'ogg'  => 'audio/ogg',
			'wav'  => 'audio/wav',
			'flac' => 'audio/flac',
			'm4a'  => 'audio/mp4',
			'aac'  => 'audio/aac'
		);
		$content_type = isset($mime_types[$ext]) ? $mime_types[$ext] : 'application/octet-stream';

		$filesize = filesize($real_path);
		$offset = 0;
		$length = $filesize;

		// Clean buffers
		while (ob_get_level() > 0) {
			ob_end_clean();
		}

		// Handle HTTP 206 Partial Content Range header
		if (isset($_SERVER['HTTP_RANGE'])) {
			if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
				$offset = (float)$matches[1];
				if (!empty($matches[2])) {
					$end = (float)$matches[2];
				} else {
					$end = $filesize - 1;
				}

				if ($offset > $end || $offset >= $filesize) {
					header('HTTP/1.1 416 Requested Range Not Satisfiable');
					header('Content-Range: bytes */' . $filesize);
					exit;
				}

				$length = $end - $offset + 1;

				header('HTTP/1.1 206 Partial Content');
				header('Content-Range: bytes ' . sprintf('%.0f', $offset) . '-' . sprintf('%.0f', $end) . '/' . $filesize);
			} else {
				header('HTTP/1.1 200 OK');
			}
		} else {
			header('HTTP/1.1 200 OK');
		}

		header('Content-Type: ' . $content_type);
		header('Accept-Ranges: bytes');
		header('Content-Length: ' . sprintf('%.0f', $length));
		header('Cache-Control: public, max-age=86400');
		header('Content-Disposition: inline; filename="' . basename($real_path) . '"');

		$handle = fopen($real_path, 'rb');
		if ($handle) {
			if ($offset > 0) {
				fseek($handle, $offset);
			}

			$bytes_remaining = $length;
			$buffer_size = 65536; // 64KB chunks

			while (!feof($handle) && $bytes_remaining > 0 && !(connection_aborted())) {
				$read_size = ($bytes_remaining < $buffer_size) ? $bytes_remaining : $buffer_size;
				$buffer = fread($handle, $read_size);
				echo $buffer;
				flush();
				$bytes_remaining -= strlen($buffer);
			}

			fclose($handle);
		}
		exit;
	}
}
