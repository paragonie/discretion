<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Tests;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\Discretion\Data\HiddenString;
use ParagonIE\Discretion\SimpleCrypto;
use PHPUnit\Framework\TestCase;

class SimpleCryptoTest extends TestCase
{
    /**
     * @covers SimpleCrypto::decryptRaw()
     * @covers SimpleCrypto::encryptRaw()
     */
    public function testEncryptDecryptRaw()
    {
        $key = new HiddenString(\random_bytes(32));
        $message = new HiddenString('Test message goes here.');

        $encrypted = SimpleCrypto::encryptRaw($message, $key);
        $decrypted = SimpleCrypto::decryptRaw($encrypted, $key);

        $this->assertSame($decrypted->getString(), $message->getString());

        $forged = '' . $encrypted;
        $forged[0] = \chr(\ord($forged[0]) ^ 0xff);
        try {
            SimpleCrypto::decrypt($forged, $key);
            $this->fail('This should be failing.');
        } catch (\Throwable $ex) {
            $this->assertTrue(true);
        }

        // Test all bitflips.
        for ($i = 0; $i < \mb_strlen($encrypted, '8bit'); ++$i) {
            for ($j = 0; $j < 8; ++$j) {
                $forged = '' . $encrypted;
                $forged[$i] = \chr(\ord($forged[0]) ^ 1 << $j);
                try {
                    SimpleCrypto::decrypt($forged, $key);
                    $this->fail('This should be failing.');
                } catch (\Throwable $ex) {
                    $this->assertTrue(true);
                }
            }
        }
    }

    /**
     * @covers SimpleCrypto::decrypt()
     * @covers SimpleCrypto::encrypt()
     */
    public function testEncryptDecrypt()
    {
        $key = new HiddenString(\random_bytes(32));
        $message = new HiddenString('Test message goes here.');

        $encrypted = SimpleCrypto::encrypt($message, $key);
        $decrypted = SimpleCrypto::decrypt($encrypted, $key);

        $this->assertSame($decrypted->getString(), $message->getString());
        $tmp = Base64UrlSafe::decode($encrypted);
        $tmp[0] = \chr(\ord($tmp[0]) ^ 0xff);
        $forged = Base64UrlSafe::encode($tmp);

        try {
            SimpleCrypto::decrypt($forged, $key);
            $this->fail('This should be failing.');
        } catch (\Throwable $ex) {
        }

        // Test all bitflips.
        for ($i = 0; $i < \mb_strlen($encrypted, '8bit'); ++$i) {
            for ($j = 0; $j < 8; ++$j) {
                $tmp = Base64UrlSafe::decode($encrypted);
                $tmp[0] = \chr(\ord($tmp[0]) ^ 1 << $j);
                $forged = Base64UrlSafe::encode($tmp);
                try {
                    SimpleCrypto::decrypt($forged, $key);
                    $this->fail('This should be failing.');
                } catch (\Throwable $ex) {
                    $this->assertTrue(true);
                }
            }
        }
    }
}
