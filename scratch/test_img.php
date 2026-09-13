<?php
require_once __DIR__ . '/../config.php';
$img = 'catalog/products/888880028018/img_6cdaf4db2e6b.jpg';
echo "File exists in DIR_IMAGE: " . (is_file(DIR_IMAGE . $img) ? "YES" : "NO") . "\n";
require_once DIR_SYSTEM . 'startup.php';
$registry = new Registry();
$config = new Config();
$config->load('default');
$config->load('catalog');
$registry->set('config', $config);
$req = new Request();
$registry->set('request', $req);
$loader = new Loader($registry);
$registry->set('load', $loader);
$loader->model('tool/image');
$model_image = $registry->get('model_tool_image');
$resized = $model_image->resize($img, 47, 47);
echo "Resized URL: " . $resized . "\n";
