<?php
use Slim\{
    App,
    Container
};
use ParagonIE\AntiCSRF\AntiCSRF;
use ParagonIE\CSPBuilder\CSPBuilder;
use ParagonIE\Discretion\Discretion;

// DIC configuration

/** @var App $app */
if (!isset($app)) {
    $app = new App();
}
if (!($app instanceof App)) {
    throw new TypeError('$app must be an instance of \\Slim\\App.');
}
/** @var Container $container */
$container = $app->getContainer();

/** @var array $settings */
$settings = $container->get('settings');

try {
    $antiCsrf = new AntiCSRF();
} catch (\Error $ex) {
    $session = [];
    $antiCsrf = new AntiCSRF($_POST, $session);
}
\ParagonIE\Discretion\Discretion::setAntiCSRF($antiCsrf);

$cspBuilder = CSPBuilder::fromData((string) $settings['csp-builder']);
Discretion::setCSPBuilder($cspBuilder);

/** @var array<string, array<string, string>> $twigSettings */
$twigSettings = $settings['twig'];
$twigLoader = new \Twig_Loader_Filesystem((array) $twigSettings['paths']);
Discretion::setTwig(new \Twig_Environment($twigLoader, (array) $twigSettings['settings']));

$container['view'] =
    /**
     * @param Container $c
     * @return Twig_Environment
     */
    function (\Slim\Container $c) {
        return Discretion::getTwig();
    };

$container['anticsrf'] =
    /**
     * @param Container $c
     * @return AntiCSRF
     */
    function (Container $c): AntiCSRF {
        return Discretion::getAntiCSRF();
    };

$container['csp'] =
    /**
     * @param Container $c
     * @return CSPBuilder
     */
    function (Container $c) {
        return Discretion::getCSPBuilder();
    };

$container['logger'] =
    /**
     * @param Container $c
     * @return \Monolog\Logger
     */
    function (\Slim\Container $c): \Monolog\Logger
    {
        /** @var array<string, array<string, string>> $set */
        $set = $c->get('settings');
        /** @var array<string, string> $settings */
        $settings = $set['logger'];
        $logger = new Monolog\Logger($settings['name']);
        $logger->pushProcessor(new Monolog\Processor\UidProcessor());
        $logger->pushHandler(
            new Monolog\Handler\StreamHandler(
                (string) $settings['path'],
                (int) $settings['level']
            )
        );
        return $logger;
    };

$container['view'] =
    /**
     * @param Container $c
     * @return Twig_Environment
     */
    function (\Slim\Container $c) {
        return Discretion::getTwig();
    };

require_once 'twig.php';
