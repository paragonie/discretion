<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Struct;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\Discretion\{
    Discretion,
    SimpleCrypto,
    Struct
};
use ParagonIE\Discretion\Data\HiddenString;
use ParagonIE\Discretion\Exception\RecordNotFound;
use ParagonIE\Discretion\Policies\Unique;

/**
 * Class User
 * @package ParagonIE\Discretion\Struct
 */
class User extends Struct implements Unique
{
    const BCRYPT_COST = 12;
    const TABLE_NAME = 'discretion_users';
    const PRIMARY_KEY = 'userid';
    const DB_FIELD_NAMES = [
        'userid' => 'id',
        'active' => 'active',
        'username' => 'username',
        'pwhash' => 'pwHash',
        'twofactor' => 'twoFactorSecret',
        'email' => 'email',
        'fullname' => 'fullName',
        'chronicle' => 'chronicle'
    ];
    const BOOLEAN_FIELDS = ['active'];

    /** @var bool $active */
    protected $active = false;

    /** @var string $chronicle */
    protected $chronicle = '';

    /** @var string $email */
    protected $email = '';

    /** @var string $fullName */
    protected $fullName = '';

    /** @var string $username */
    protected $username = '';

    /** @var string $pwHash */
    protected $pwHash = '';

    /** @var string $twoFactorSecret */
    protected $twoFactorSecret = '';

    /**
     * @param string $username
     * @return self
     * @throws RecordNotFound
     */
    public static function byUsername(string $username): self
    {
        $userId = Discretion::getDatabase()->cell(
            "SELECT userid FROM discretion_users WHERE username = ?",
            $username
        );
        if (empty($userId)) {
            throw new RecordNotFound('No user with the username ' . $username);
        }
        return static::byId((int) $userId);
    }

    /**
     * @param HiddenString $password
     * @return bool
     */
    public function checkPassword(HiddenString $password): bool
    {
        $preHash = Base64UrlSafe::encode(
            \ParagonIE_Sodium_Compat::crypto_generichash(
                $password->getString(),
                '',
                54
            )
        );
        $stored = SimpleCrypto::decrypt(
            $this->pwHash,
            Discretion::getLocalEncryptionKey()
        );
        return \password_verify(
            $preHash,
            $stored->getString()
        );
    }

    /**
     * @return HiddenString
     */
    public function get2FASecret(): HiddenString
    {
        return SimpleCrypto::decrypt(
            $this->twoFactorSecret,
            Discretion::getLocalEncryptionKey()
        );
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Overwrite the 2FA secret.
     *
     * @param HiddenString $secret
     * @return self
     */
    public function set2FASecret(HiddenString $secret): self
    {
        $this->twoFactorSecret = SimpleCrypto::encrypt(
            $secret,
            Discretion::getLocalEncryptionKey()
        );
        return $this;
    }

    /**
     * @param bool $isActive
     * @return self
     */
    public function setActive(bool $isActive): self
    {
        $this->active = $isActive;
        return $this;
    }

    /**
     * @param string $email
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param string $fullName
     * @return self
     */
    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    /**
     * @param HiddenString $password
     * @return self
     */
    public function setPassword(HiddenString $password): self
    {
        // Prehash it to prevent
        $preHash = Base64UrlSafe::encode(
            \ParagonIE_Sodium_Compat::crypto_generichash(
                $password->getString(),
                '',
                54
            )
        );
        $this->pwHash = SimpleCrypto::encrypt(
            new HiddenString(
                \password_hash(
                    $preHash,
                    PASSWORD_DEFAULT,
                    ['cost' => static::BCRYPT_COST]
                )
            ),
            Discretion::getLocalEncryptionKey()
        );
        return $this;
    }

    /**
     * @param string $username
     * @return self
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @param string $username
     * @return bool
     */
    public static function usernameIsTaken(string $username): bool
    {
        /** @psalm-suppress InvalidArgument Trust me on this one, Psalm. */
        return Discretion::getDatabase()->exists(
            "SELECT count(*) FROM discretion_users WHERE username = ?",
            $username
        );
    }
}
