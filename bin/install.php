<?php
declare(strict_types=1);

$root = \dirname(__DIR__);
require_once $root . '/cli-autoload.php';

// Generate a signing key.
$signingKey = \ParagonIE\Sapient\CryptographyKeys\SigningSecretKey::generate();

// Store the signing key:
\file_put_contents(
    $root . '/local/signing-secret.key',
    $signingKey->getString()
);

// Write the default settings to the local settings file.
$localSettings = [
    'database' => [
        'dsn' => 'pgsql:host=localhost;port=5432;dbname=discretion',
        'username' => 'charlie',
        'password' => 'correct horse battery staple'
    ],
    // The maximum window of opportunity for replay attacks:
    'signing-public-key' => $signingKey->getPublicKey()->getString()
];

\file_put_contents(
    $root . '/local/settings.json',
    \json_encode($localSettings, JSON_PRETTY_PRINT)
);
