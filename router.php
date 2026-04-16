<?php
/**
 * Router for PHP built-in development server.
 * Serves static files directly, routes everything else to index.php.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// Serve static files directly
if ($path !== '/' && is_file($file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'json' => 'application/json',
    ];
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
        readfile($file);
        return true;
    }
    return false; // Let PHP serve it
}

// Route all other requests to index.php or the matched PHP file
if (preg_match('/\.php$/', $path) && is_file($file)) {
    return false;
}

// Default: serve index.php
include __DIR__ . '/index.php';
