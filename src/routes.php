<?php
declare(strict_types=1);

use ParagonIE\Discretion\Handlers\ControlPanel\{
    Index as ControlPanelIndex
};
use ParagonIE\Discretion\Handlers\{
    Index,
    Login,
    Register
};
use ParagonIE\Discretion\Middleware\{
    UserAuthentication
};
use Slim\App;

/** @var App $app */
if (!isset($app)) {
    $app = new App();
}

// Routes
$app->group('/manage',
    function (App $app) {
        $app->any('/', ControlPanelIndex::class);
        $app->any('', ControlPanelIndex::class);
    }
)->add(UserAuthentication::class);

$app->any('/register', Register::class);
$app->any('/login',    Login::class);
$app->get('/', Index::class);
$app->get('', Index::class);
