<?php

define('DISCRETION_APP_ROOT', __DIR__);

require_once DISCRETION_APP_ROOT . '/vendor/autoload.php';


$settings = require DISCRETION_APP_ROOT . '/src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require DISCRETION_APP_ROOT . '/src/dependencies.php';

// Setup the database connection
require DISCRETION_APP_ROOT . '/src/database.php';

require DISCRETION_APP_ROOT . '/src/chronicle.php';
