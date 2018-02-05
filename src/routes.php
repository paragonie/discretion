<?php
declare(strict_types=1);

use ParagonIE\Discretion\Handlers\ControlPanel\{
    Contacts,
    Index as ControlPanelIndex
};
use ParagonIE\Discretion\Handlers\{
    Index,
    Login,
    Register
};
use ParagonIE\Discretion\Middleware\{
    HTTPPost,
    UserAuthentication
};
use Slim\App;

/** @var App $app */
if (!isset($app)) {
    $app = new App();
}
if (!($app instanceof App)) {
    throw new TypeError('$app must be an instance of \\Slim\\App.');
}

// Routes
$app->group('/manage',
    function (App $app) {
        $app->any('/contacts', Contacts::class);
        $app->any('/', ControlPanelIndex::class);
        $app->any('', ControlPanelIndex::class);
    }
)->add(UserAuthentication::class);

$app->any('/register', Register::class)->add(HTTPPost::class);
$app->any('/login',    Login::class)->add(HTTPPost::class);
$app->get('/', Index::class);
$app->get('', Index::class);
