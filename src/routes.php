<?php
declare(strict_types=1);

use ParagonIE\Discretion\Handlers\{
    Index
};

/** @var \Slim\App $app */
if (!isset($app)) {
    $app = new \Slim\App();
}

// Routes
$app->get('/', Index::class);
