<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

\session_start();

require \dirname(__DIR__) . '/cli-autoload.php';

// Register middleware
require DISCRETION_APP_ROOT . '/src/middleware.php';

// Register routes
require DISCRETION_APP_ROOT . '/src/routes.php';


$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// PHP Built-in-Webserver: Show CSS/JS properly.
$file = $_SERVER['DOCUMENT_ROOT'] . '/' . $uri;
if (\file_exists($file) && !\is_dir($file)) {
    $realpath = \realpath($file);
    if (\strpos($realpath, $_SERVER['DOCUMENT_ROOT']) === 0) {
        if (\preg_match('/\.(js|css)$/', \strtolower($realpath), $matches)) {
            $ext = $matches[1];
            switch($ext) {
                case 'css':
                    \header('Content-Type: text/css');
                    break;
                case 'js':
                    \header('Content-Type: application/javascript');
                    break;
            }
            echo \file_get_contents($realpath);
            exit;
        }
    }
}

// Run app
$app->run();
