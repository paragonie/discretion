<?php
if (!\defined('DISCRETION_APP_ROOT')) {
    \define('DISCRETION_APP_ROOT', \dirname(__DIR__));
}

$cspDefault = [
    'allow' => [],
    'self' => true,
    'data' => false
];

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        'twig' => [
            'paths' => [
                \dirname(__DIR__) . '/templates/'
            ],
            'settings' => [
                // Defaults to 'html' strategy:
                'autoescape' => true
            ]
        ],

        // Default configuration for Content-Security-Policy headers.
        'csp-builder' => json_encode([
            'child-src' => $cspDefault,
            'connect-src' => $cspDefault,
            'font-src' => $cspDefault,
            'form-action' => $cspDefault,
            'frame-ancestors' => $cspDefault,
            'img-src' => [
                'allow' => ['*'],
                'self' => true,
                'data' => true
            ],
            'media-src' => $cspDefault,
            'object-src' => [
                'allow' => [],
                'self' => false,
                'data' => false
            ],
            /*
            'plugin-types' => [
                'allow' => [
                    'application/javascript'
                ]
            ],
            */
            'script-src' => [
                'allow' => [
                    'https://cdnjs.cloudflare.com/',
                    'https://code.jquery.com',
                    'https://maxcdn.bootstrapcdn.com/',
                ],
                'self' => true,
                'data' => false,
                'unsafe-inline' => false,
                'unsafe-eval' => false
            ],
            'style-src' => [
                'allow' => [
                    'https://maxcdn.bootstrapcdn.com/'
                ],
                'self' => true,
                'unsafe-inline' => false
            ],
            'upgrade-insecure-requests' => false
        ]),

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
    ],
];
