<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Struct;

use ParagonIE\Discretion\Struct;

/**
 * Class Form
 * @package ParagonIE\Discretion\Struct
 */
class Form extends Struct
{
    const TABLE_NAME = 'discretion_forms';
    const PRIMARY_KEY = 'formid';
    const DB_FIELD_NAMES = [
        'formid' => 'id',
        'userid' => 'userId',
        'themeid' => 'themeId',
        'publicid' => 'publicId',
        'config' => 'config'
    ];

    /** @var string $config */
    protected $config = '';

    /** @var string $publicId */
    protected $publicId = '';

    /** @var int $userId */
    protected $userId = 0;

    /** @var int $userId */
    protected $themeId = 0;

    /**
     * @return Theme
     */
    public function getTheme(): Theme
    {
        return Theme::byId($this->userId);
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return User::byId($this->userId);
    }
}
