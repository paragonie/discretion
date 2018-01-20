<?php
declare(strict_types=1);

$root = \dirname(__DIR__);
define('DISCRETION_APP_ROOT', $root);
require_once $root . '/vendor/autoload.php';

// Generate a signing key.
$signingKey = \ParagonIE\Sapient\CryptographyKeys\SigningSecretKey::generate();

// Store the signing key:
\file_put_contents(
    $root . '/local/signing-secret.key',
    $signingKey->getString()
);

// Store the local encryption key:
\file_put_contents(
    $root . '/local/encryption.key',
    \ParagonIE\ConstantTime\Base64UrlSafe::encode(\random_bytes(32))
);

// Chronicle setup
$chronicleSecretKey = \ParagonIE\Sapient\CryptographyKeys\SigningSecretKey::generate();
$chroniclePublicKey = $chronicleSecretKey->getPublickey();

// Write the default settings to the local settings file.
$localSettings = [
    'chronicle' => [
        'enabled' => false,
        'url' => 'https://local-chronicle.example.com',
        'public-key' => '/* obtain from upstream */',
        'encryption' => [
            'enabled' => false,
            'symmetric-key' => false,
            'key' => '/* public key for message encryption */'
        ],
        'local' => [
            'client-id' => 'obtain from the chronicle',
            'signing-public-key' => $chroniclePublicKey->getString(),
            'signing-secret-key' => $chronicleSecretKey->getString(),
        ]
    ],
    'database' => [
        'dsn' => 'pgsql:host=localhost;port=5432;dbname=discretion',
        'username' => 'charlie',
        'password' => 'correct horse battery staple'
    ],
    'recaptcha' => [
        'secret-key' => '',
        'site-key' => ''
    ],
    // The maximum window of opportunity for replay attacks:
    'signing-public-key' => $signingKey->getPublicKey()->getString(),
    'zxcvbn-min-strength' => 3
];

\file_put_contents(
    $root . '/local/settings.json',
    \json_encode($localSettings, JSON_PRETTY_PRINT)
);
