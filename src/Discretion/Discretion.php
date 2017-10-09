<?php
declare(strict_types=1);
namespace ParagonIE\Discretion;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\Discretion\Exception\FilesystemException;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\Sapient\Adapter\Slim;
use ParagonIE\Sapient\CryptographyKeys\SigningSecretKey;
use ParagonIE\Sapient\Sapient;
use Slim\Http\Headers;
use Slim\Http\Response;

/**
 * Class Discretion
 * @package ParagonIE\Discretion
 */
class Discretion
{
    /** @var EasyDB $easyDb */
    protected static $easyDb;

    /** @var array $settings */
    protected static $settings;

    /** @var SigningSecretKey $signingKey */
    protected static $signingKey;

    /** @var \Twig_Environment $twig */
    protected static $twig;

    /**
     * Create a generic HTTP response, unsigned.
     *
     * @param int $status
     * @param string $body
     * @param array $headers
     * @return Response
     */
    public static function createNormalResponse(int $status = 200, string $body = '', array $headers = []): Response
    {
        return new Response(
            $status,
            new Headers($headers),
            (new Slim())->stringToStream($body)
        );
    }

    /**
     * Get the EasyDB object (used for database queries)
     *
     * @return EasyDB
     */
    public static function getDatabase(): EasyDB
    {
        return self::$easyDb;
    }

    /**
     * If we're using SQLite, we need a 1 or a 0.
     * Otherwise, TRUE/FALSE is fine.
     *
     * @param bool $value
     * @return bool|int
     */
    public static function getDatabaseBoolean(bool $value)
    {
        if (self::$easyDb->getDriver() === 'sqlite') {
            return $value ? 1 : 0;
        }
        return !empty($value);
    }

    /**
     * @return Sapient
     */
    public static function getSapient(): Sapient
    {
        return new Sapient(new Slim());
    }

    /**
     * This gets the server's signing key.
     *
     * We should audit all calls to this method.
     *
     * @return SigningSecretKey
     * @throws \Exception
     */
    public static function getSecretKey(): SigningSecretKey
    {
        if (self::$signingKey) {
            return self::$signingKey;
        }

        // Load the signing key:
        $keyFile = \file_get_contents(DISCRETION_APP_ROOT . '/local/signing-secret.key');
        if (!\is_string($keyFile)) {
            throw new FilesystemException('Could not load key file');
        }
        return new SigningSecretKey(
            Base64UrlSafe::decode($keyFile)
        );
    }

    /**
     * @return array
     */
    public static function getSettings(): array
    {
        return self::$settings;
    }

    /**
     * @return \Twig_Environment
     */
    public static function getTwig(): \Twig_Environment
    {
        return self::$twig;
    }

    /**
     * Store the database object in the Chronicle class.
     *
     * @param EasyDB $db
     * @return EasyDB
     */
    public static function setDatabase(EasyDB $db): EasyDB
    {
        self::$easyDb = $db;
        return self::$easyDb;
    }

    /**
     * @param \Twig_Environment $twig
     * @return \Twig_Environment
     */
    public static function setTwig(\Twig_Environment $twig): \Twig_Environment
    {
        self::$twig = $twig;
        return self::$twig;
    }
}
