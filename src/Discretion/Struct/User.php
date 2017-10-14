<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Struct;

use ParagonIE\Discretion\Policies\Unique;
use ParagonIE\Discretion\Struct;

/**
 * Class User
 * @package ParagonIE\Discretion\Struct
 */
class User extends Struct implements Unique
{
    const TABLE_NAME = 'discretion_users';
    const PRIMARY_KEY = 'userid';
    const DB_FIELD_NAMES = [
        'userid' => 'id',
        'username' => 'username',
        'pwhash' => 'pwHash',
        'twofactor' => 'twoFactorSecret',
        'email' => 'email',
        'fullname' => 'fullName',
        'chronicle' => 'chronicle'
    ];

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
}
