<?php
declare(strict_types=1);
namespace ParagonIE\Discretion;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\CSPBuilder\CSPBuilder;
use ParagonIE\Discretion\Data\HiddenString;
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
    /** @var CSPBuilder $cspBuilder */
    protected static $cspBuilder;

    /** @var EasyDB $easyDb */
    protected static $easyDb;

    /** @var array $settings */
    protected static $settings;

    /** @var HiddenString $localEncryptionKey */
    protected static $localEncryptionKey;

    /** @var SigningSecretKey $signingKey */
    protected static $signingKey;

    /** @var \Twig_Environment $twig */
    protected static $twig;

    /**
     * Create a generic HTTP response, unsigned.
     *
     * @param string $body
     * @param array $headers
     * @param int $status
     * @return Response
     */
    public static function createNormalResponse(string $body = '', array $headers = [], int $status = 200): Response
    {
        return new Response(
            $status,
            new Headers($headers),
            (new Slim())->stringToStream($body)
        );
    }

    /**
     * @param string $class
     * @return string
     */
    public static function decorateClassName($class = '')
    {
        return 'Object (' . \trim($class, '\\') . ')';
    }

    /**
     * @return CSPBuilder
     */
    public static function getCSPBuilder(): CSPBuilder
    {
        return self::$cspBuilder;
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
     * Default HTTP headers. Doesn't include the Content-Security-Policy header.
     *
     * @return array
     */
    public static function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block'
        ];
    }

    /**
     * Get a variable's type. If it's an object, also get the class name.
     *
     * @param mixed $obj
     * @return string
     */
    public static function getGenericType($obj = null)
    {
        if (\func_num_args() === 0) {
            return 'void';
        }
        if ($obj === null) {
            return 'null';
        }
        if (\is_object($obj)) {
            return static::decorateClassName(\get_class($obj));
        }
        $type = \gettype($obj);
        switch ($type) {
            case 'boolean':
                return 'bool';
            case 'double':
                return 'float';
            case 'integer':
                return 'int';
            default:
                return $type;
        }
    }

    /**
     * @return Sapient
     */
    public static function getSapient(): Sapient
    {
        return new Sapient(new Slim());
    }

    /**
     * Get the local encryption key.
     *
     * @return HiddenString
     * @throws FilesystemException
     */
    public static function getLocalEncryptionKey(): HiddenString
    {
        if (self::$localEncryptionKey) {
            return self::$localEncryptionKey;
        }

        // Load the signing key:
        $keyFile = \file_get_contents(DISCRETION_APP_ROOT . '/local/encryption.key');
        if (!\is_string($keyFile)) {
            throw new FilesystemException('Could not load key file');
        }
        self::$localEncryptionKey = new HiddenString(
            Base64UrlSafe::decode($keyFile)
        );
        return self::$localEncryptionKey;
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
        self::$signingKey = new SigningSecretKey(
            Base64UrlSafe::decode($keyFile)
        );
        return self::$signingKey;
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
     * @param CSPBuilder $CSPBuilder
     * @return CSPBuilder
     */
    public static function setCSPBuilder(CSPBuilder $CSPBuilder): CSPBuilder
    {
        self::$cspBuilder = $CSPBuilder;
        return self::$cspBuilder;
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

    /**
     * Quick shortcut method for generating an HTML response from a template.
     *
     * @param string $template
     * @param array $args
     * @param array $headers
     * @param int $status
     * @return Response
     */
    public static function view(
        string $template,
        array $args = [],
        array $headers = [],
        int $status = 200
    ): Response {
        if (empty($headers)) {
            $headers = static::getDefaultHeaders();
        }
        /** @var Response $response */
        $response = self::$cspBuilder->injectCSPHeader(
            new Response(
                $status,
                new Headers($headers),
                (new Slim())->stringToStream(
                    static::getTwig()->render($template, $args)
                )
            )
        );
        return $response;
    }
}
