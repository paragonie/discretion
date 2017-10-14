<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Struct;

use ParagonIE\Discretion\Struct;

/**
 * Class Contact
 * @package ParagonIE\Discretion\Struct
 */
class Contact extends Struct
{
    const TABLE_NAME = 'discretion_contacts';
    const PRIMARY_KEY = 'contactid';
    const DB_FIELD_NAMES = [
        'contactid' => 'id',
        'userid' => 'userId',
        'name' => 'name',
        'email' => 'email',
        'gpgfingerprint' => 'gpgFingerprint'
    ];

    /** @var int $userId */
    protected $userId = 0;

    /** @var string $name */
    protected $name = '';

    /** @var string $email */
    protected $email = '';

    /** @var string $gpgFingerprint */
    protected $gpgFingerprint = '';

    /**
     * @return User
     */
    protected function getUser(): User
    {
        return User::byId($this->userId);
    }
}
