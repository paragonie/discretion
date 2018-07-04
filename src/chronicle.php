<?php
declare(strict_types=1);
use ParagonIE\Discretion\Discretion;
use Monolog\Logger;
use ParagonIE\MonologQuill\QuillHandler;
use ParagonIE\Quill\Quill;
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\Sapient\CryptographyKeys\{
    SealingPublicKey,
    SharedEncryptionKey,
    SigningSecretKey,
    SigningPublicKey
};
use Psr\Log\LogLevel;

/** @var array $settings */
$settings = \ParagonIE\Discretion\Discretion::getSettings();
if (isset($settings['chronicle'])) {
    /** @var array<string, string|array<string, string>> $chronicle */
    $chronicle = $settings['chronicle'];
    if (isset(
        $chronicle['enabled'],
        $chronicle['url'],
        $chronicle['public-key'],
        $chronicle['local']
    )) {
        if (!$chronicle['enabled']) {
            // Chronicle is not enabled.
            return;
        }
        if (!\is_string($chronicle['url'])) {
            throw new TypeError('"url" is not a string');
        }
        if (!\is_string($chronicle['public-key'])) {
            throw new TypeError('"public-key" is not a string');
        }
        if (!\is_array($chronicle['encryption'])) {
            throw new TypeError('"encryption" is not an array');
        }
        if (!\is_array($chronicle['local'])) {
            throw new TypeError('"local" is not an array');
        }

        $quill = (new Quill())
            ->setChronicleURL((string) $chronicle['url'])
            ->setServerPublicKey(
                new SigningPublicKey(
                    Base64UrlSafe::decode($chronicle['public-key'])
                )
            )
            ->setClientID($chronicle['local']['client-id'])
            ->setClientSecretKey(
                new SigningSecretKey(
                    Base64UrlSafe::decode($chronicle['local']['signing-secret-key'])
                )
            );

        if (empty($chronicle['log-level'])) {
            $logLevel = Logger::DEBUG;
        } else {
            switch ($chronicle['log-level']) {
                case LogLevel::DEBUG:
                    $logLevel = Logger::DEBUG;
                    break;
                case LogLevel::INFO:
                    $logLevel = Logger::INFO;
                    break;
                case LogLevel::NOTICE:
                    $logLevel = Logger::NOTICE;
                    break;
                case LogLevel::WARNING:
                    $logLevel = Logger::WARNING;
                    break;
                case LogLevel::ERROR:
                    $logLevel = Logger::ERROR;
                    break;
                case LogLevel::CRITICAL:
                    $logLevel = Logger::CRITICAL;
                    break;
                case LogLevel::ALERT:
                    $logLevel = Logger::ALERT;
                    break;
                case LogLevel::EMERGENCY:
                    $logLevel = Logger::EMERGENCY;
                    break;
                default:
                    $logLevel = Logger::DEBUG;
            }
        }

        // Push the Handler to Monolog
        $log = new Logger('security');
        $handler = (new QuillHandler($quill, $logLevel));
        if (!empty($chronicle['encryption']['enabled'])) {
            if (!empty($chronicle['encryption']['symmetric-key'])) {
                $encKey = new SharedEncryptionKey(
                    Base64UrlSafe::decode($chronicle['encryption']['key'])
                );
            } else {
                $encKey = new SealingPublicKey(
                    Base64UrlSafe::decode($chronicle['encryption']['key'])
                );
            }
            try {
                $handler->setEncryptionKey($encKey);
            } catch (\TypeError $ex) {
            }
        }
        $log->pushHandler($handler);
        Discretion::setMonolog($log);
    }
}
