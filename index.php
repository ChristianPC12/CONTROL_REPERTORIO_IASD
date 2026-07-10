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

// Servidor de media dedicado (app empaquetada): php -S atiende una petición a
// la vez en Windows, así que el streaming de video/audio va por un segundo
// proceso en otro puerto para no bloquear la API de control.
$mediaPort = intval(getenv('CM_MEDIA_PORT'));
if ($mediaPort > 0) {
    $mediaScript = '<script>window.CM_MEDIA_BASE = "http://127.0.0.1:' . $mediaPort . '/";</script>';
    $html = str_replace('</head>', $mediaScript . "\n</head>", $html);
}

echo $html;
