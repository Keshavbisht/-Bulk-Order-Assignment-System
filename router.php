<?php
/**
 * Router for PHP Built-in Server
 * This file handles routing when using: php -S localhost:8000 router.php
 */

$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// If it's an API request, route to api/index.php
if (strpos($requestPath, '/api') === 0) {
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    require __DIR__ . '/api/index.php';
    return true;
}

// If it's the root path, show index.php
if ($requestPath === '/' || $requestPath === '') {
    if (file_exists(__DIR__ . '/index.php')) {
        require __DIR__ . '/index.php';
        return true;
    }
}

// For other files, let PHP handle it normally
return false;
