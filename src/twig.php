<?php
declare(strict_types=1);
use ParagonIE\Discretion\Discretion;

/**
 * This file contains the custom Twig functions that we use in our templates.
 */
$twig = Discretion::getTwig();

$twig->addFunction(
    new Twig_Function(
        'form_token',
        function (string $lockTo = ''): string {
            return Discretion::getAntiCSRF()->insertToken($lockTo);
        }
    )
);
