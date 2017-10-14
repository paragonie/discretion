<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Struct;

use ParagonIE\Discretion\Struct;

/**
 * Class Theme
 * @package ParagonIE\Discretion\Struct
 */
class Theme extends Struct
{
    const TABLE_NAME = 'discretion_themes';
    const PRIMARY_KEY = 'themeid';
    const DB_FIELD_NAMES = [
        'themeid' => 'id',
        'name' => 'name',
        'public' => 'public',
        'config' => 'config',
        'userid' => 'userId'
    ];

    /** @var string $config */
    protected $config = '';

    /** @var string $name */
    protected $name = '';

    /** @var bool $public */
    protected $public = false;

    /** @var int $userId */
    protected $userId = 0;

    /**
     * @return User
     */
    public function getUser(): User
    {
        return User::byId($this->userId);
    }
}
