<?php

/**
 * Router for PHP's built-in web server (php kohana serve): existing files in
 * the document root are served as they are, every other request goes to
 * index.php, like the rewrite rules in public/example.htaccess.
 */
$path = urldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($path !== '/' && is_file($_SERVER['DOCUMENT_ROOT'] . $path)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . '/index.php';

chdir($_SERVER['DOCUMENT_ROOT']);

require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
