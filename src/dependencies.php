<?php
// DIC configuration

/** @var \Slim\App $app */
if (!isset($app)) {
    $app = new \Slim\App();
}
/** @var \Slim\Container $container */
$container = $app->getContainer();

$container['view'] =
    /**
     * @param \Slim\Container $c
     * @return Twig_Environment
     */
    function (\Slim\Container $c) {
        $settings = $c->get('settings')['twig'];
        $twigLoader = new \Twig_Loader_Filesystem($settings['paths']);
        return new \Twig_Environment($twigLoader, $settings['settings']);
    };

$container['logger'] =
    /**
     * @param \Slim\Container $c
     * @return \Monolog\Logger
     */
    function (\Slim\Container $c) {
        $settings = $c->get('settings')['logger'];
        $logger = new Monolog\Logger($settings['name']);
        $logger->pushProcessor(new Monolog\Processor\UidProcessor());
        $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
        return $logger;
    };
