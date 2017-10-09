<?php
declare(strict_types=1);

use Slim\Http\Request;
use Slim\Http\Response;

/** @var \Slim\App $app */
if (!isset($app)) {
    $app = new \Slim\App();
}

// Routes
$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
