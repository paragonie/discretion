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

// Write the default settings to the local settings file.
$localSettings = [
    'database' => [
        'dsn' => 'sqlite:' . DISCRETION_APP_ROOT . '/local/database.sqlite'
    ],
    // The maximum window of opportunity for replay attacks:
    'signing-public-key' => $signingKey->getPublicKey()->getString()
];

\file_put_contents(
    $root . '/local/settings.json',
    \json_encode($localSettings, JSON_PRETTY_PRINT)
);
