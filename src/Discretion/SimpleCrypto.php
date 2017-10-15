<?php
declare(strict_types=1);
namespace ParagonIE\Discretion;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\Discretion\Data\HiddenString;

/**
 * Class SimpleCrypto
 *
 * This class just encrypts data using XChaCha20-Poly1305, provided by libsodium.
 * By default, we opt for Base64url encoding on our ciphertext messages.
 *
 * Requires: ext/mbstring, paragonie/sodium_compat
 * Recommended: ext/sodium 2.0.7+, libsodium 1.0.15+
 *
 * @package ParagonIE\Discretion
 */
class SimpleCrypto
{
    const MIN_CIPHERTEXT_LENGTH = 40;

    /**
     * Decrypt a message with XChaCha20-Poly1305.
     *
     * @param string $ciphertext
     * @param HiddenString $key
     * @return HiddenString
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public static function decrypt(string $ciphertext, HiddenString $key): HiddenString
    {
        /** @var string $realCiphertext */
        $realCiphertext = Base64UrlSafe::decode($ciphertext);
        if (!\is_string($realCiphertext)) {
            throw new \InvalidArgumentException('Invalid encoding');
        }
        return static::decryptRaw($realCiphertext, $key);
    }

    /**
     * Base64url-encoded encrypted message.
     *
     * @param HiddenString $message
     * @param HiddenString $key
     * @return string
     */
    public static function encrypt(HiddenString $message, HiddenString $key): string
    {
        return Base64UrlSafe::encode(
            static::encryptRaw($message, $key)
        );
    }

    /**
     * Decrypt a message with XChaCha20-Poly1305.
     *
     * @param string $ciphertext
     * @param HiddenString $key
     * @return HiddenString
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public static function decryptRaw(string $ciphertext, HiddenString $key): HiddenString
    {
        if (\mb_strlen($ciphertext, '8bit') < static::MIN_CIPHERTEXT_LENGTH) {
            throw new \InvalidArgumentException('Ciphertext is too short.');
        }
        $nonce = \mb_substr($ciphertext, 0, 24, '8bit');
        $message = \mb_substr($ciphertext, 24, null, '8bit');

        $plaintext = \ParagonIE_Sodium_Compat::crypto_aead_xchacha20poly1305_ietf_decrypt(
            $message,
            $nonce,
            $nonce,
            $key->getString()
        );
        if (!\is_string($plaintext)) {
            throw new \Exception('Decryption failed');
        }
        return new HiddenString($plaintext);
    }

    /**
     * Encrypt a message with XChaCha20-Poly1305.
     *
     * @param HiddenString $message
     * @param HiddenString $key
     * @return string
     */
    public static function encryptRaw(HiddenString $message, HiddenString $key): string
    {
        $nonce = \random_bytes(24);
        return $nonce . \ParagonIE_Sodium_Compat::crypto_aead_xchacha20poly1305_ietf_encrypt(
                $message->getString(),
                $nonce,
                $nonce,
                $key->getString()
            );
    }
}
