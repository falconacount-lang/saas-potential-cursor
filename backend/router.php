<?php
/**
 * Router for PHP Built-in Server
 * Routes all requests through index.php
 */

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Remove query string
$path = strtok($path, '?');

// If it's a file that exists, serve it directly
if ($path !== '/' && file_exists(__DIR__ . $path)) {
    return false; // Let PHP serve the file
}

// Otherwise, route through index.php
require_once __DIR__ . '/index.php';
