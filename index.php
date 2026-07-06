<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: text/html; charset=utf-8');

$htmlPath = __DIR__ . DIRECTORY_SEPARATOR . 'index.html';
$html = file_get_contents($htmlPath);

if ($html === false) {
    http_response_code(500);
    exit('No se pudo cargar la interfaz.');
}

$assets = [
    'styles.css',
    'app.js'
];

foreach ($assets as $asset) {
    $assetPath = __DIR__ . DIRECTORY_SEPARATOR . $asset;
    $version = is_file($assetPath) ? (string) filemtime($assetPath) : (string) time();
    $html = preg_replace('/' . preg_quote($asset, '/') . '\?v=\d+/', $asset . '?v=' . $version, $html);
}

echo $html;
