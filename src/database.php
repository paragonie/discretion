<?php
if (!\is_readable(DISCRETION_APP_ROOT . '/local/settings.json')) {
    echo 'Settings are not loaded.', PHP_EOL;
    exit(1);
}

$settings = \json_decode(
    (string) \file_get_contents(DISCRETION_APP_ROOT . '/local/settings.json'),
    true
);
try {
    $db = \ParagonIE\EasyDB\Factory::create(
        $settings['database']['dsn'],
        $settings['database']['username'] ?? '',
        $settings['database']['password'] ?? '',
        $settings['database']['options'] ?? []
    );

    \ParagonIE\Discretion\Discretion::setDatabase($db);
} catch (\Exception $ex) {
    /* Error here. Don't leak passwords. */
    \http_response_code(500);
    \header('Content-Type: application/json');
    echo (string) \json_encode([
        'status' => 'ERROR',
        'message' => 'Could not connect to the database.'
    ], JSON_PRETTY_PRINT);
    exit(255);
}

return $db;