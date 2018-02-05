<?php
declare(strict_types=1);
namespace ParagonIE\Discretion;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\Discretion\Exception\{
    DatabaseException,
    RecordNotFound
};
use ParagonIE\Discretion\Policies\Unique;

/**
 * Class Struct
 * @package ParagonIE\Discretion
 */
abstract class Struct
{
    const TABLE_NAME = '';
    const PRIMARY_KEY = '';
    const DB_FIELD_NAMES = [];
    const BOOLEAN_FIELDS = [];

    /** @var int $id */
    protected $id = 0;

    /** @var \DateTimeImmutable|null $created */
    protected $created = null;

    /** @var \DateTimeImmutable|null $modified */
    protected $modified = null;

    /** @var array<string, Struct> $objectCache */
    protected static $objectCache = [];

    /** @var string $runtimeCacheKey */
    protected static $runtimeCacheKey = '';

    /**
     * Get a new struct by its ID
     * @param int $id
     * @return static
     *
     * @throws RecordNotFound
     * @throws \Error
     */
    public static function byId(int $id): self
    {
        if (empty(static::TABLE_NAME) || empty(static::PRIMARY_KEY) || empty(static::DB_FIELD_NAMES)) {
            throw new \Error('Struct does not define necessary constants');
        }
        $self = new static();
        if ($self instanceof Unique) {
            if (\array_key_exists($self->getCacheKey($id), self::$objectCache)) {
                return self::$objectCache[$self->getCacheKey($id)];
            }
        }
        $db = Discretion::getDatabase();
        /** @var array<string, mixed> $row */
        $row = $db->row(
            "SELECT * FROM " .
                $db->escapeIdentifier((string) static::TABLE_NAME) .
            " WHERE " .
                $db->escapeIdentifier((string) static::PRIMARY_KEY) .
            " = ?",
            $id
        );
        if (empty($row)) {
            throw new RecordNotFound(static::class . '::' . $id);
        }
        /** @psalm-suppress MixedAssignment */
        foreach (static::DB_FIELD_NAMES as $field => $property) {
            /**
             * @psalm-suppress MixedArrayOffset
             * @psalm-suppress MixedAssignment
             */
            $self->{$property} = $row[$field];
        }
        if (isset($row['created'])) {
            $self->created = new \DateTimeImmutable((string) $row['created']);
        }
        if (isset($row['modified'])) {
            $self->modified = new \DateTimeImmutable((string) $row['modified']);
        }
        if ($self instanceof Unique) {
            self::$objectCache[$self->getCacheKey($id)] = $self;
        }
        return $self;
    }

    /**
     * @param int $id
     * @return string
     * @throws \Error
     * @throws \SodiumException
     * @throws \TypeError
     */
    public function getCacheKey(int $id = 0): string
    {
        if (empty(static::$runtimeCacheKey)) {
            static::$runtimeCacheKey = \random_bytes(
                \ParagonIE_Sodium_Compat::CRYPTO_SHORTHASH_KEYBYTES
            );
        }

        $plaintext = \json_encode([
            'class' => \get_class($this),
            'id' => $id > 0 ? $id : $this->id
        ]);
        if (!\is_string($plaintext)) {
            throw new \Error('Could not calculate cache key');
        }
        return Base64UrlSafe::encode(
            \ParagonIE_Sodium_Compat::crypto_shorthash(
                $plaintext,
                static::$runtimeCacheKey
            )
        );
    }

    /**
     * @return int
     * @throws DatabaseException
     */
    public function id(): int
    {
        if (!$this->id) {
            throw new DatabaseException('Record does not have a primary key. It may have not been created yet.');
        }
        return $this->id;
    }

    /**
     * @return array
     */
    public static function getRuntimeCache(): array
    {
        return static::$objectCache;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function create(): bool
    {
        if ($this->id) {
            return $this->update();
        }
        $db = Discretion::getDatabase();
        $db->beginTransaction();

        /** @var array<string, mixed> $fields */
        $fields = [];
        /** @psalm-suppress MixedAssignment */
        foreach (static::DB_FIELD_NAMES as $field => $property) {
            if ($field === static::PRIMARY_KEY) {
                // No
                continue;
            }
            if (\in_array($field, (array) static::BOOLEAN_FIELDS, true)) {
                /** @psalm-suppress MixedArrayOffset */
                $fields[$field] = Discretion::getDatabaseBoolean(
                    !empty($this->{$property})
                );
            } else {
                /** @psalm-suppress MixedArrayOffset */
                $fields[$field] = $this->{$property};
            }
        }
        $this->id = (int) $db->insertGet(
            (string) (static::TABLE_NAME),
            $fields,
            (string) (static::PRIMARY_KEY)
        );
        if ($this instanceof Unique) {
            self::$objectCache[$this->getCacheKey()] = $this;
        }
        return $db->commit();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function update(): bool
    {
        if (!($this->id)) {
            return $this->create();
        }
        $db = Discretion::getDatabase();
        $db->beginTransaction();

        /** @var array<string, mixed> $fields */
        $fields = [];
        /** @psalm-suppress MixedAssignment */
        foreach (static::DB_FIELD_NAMES as $field => $property) {
            if (!\is_string($field)) {
                throw new \TypeError('Field name must be a string');
            }
            if ($field === static::PRIMARY_KEY) {
                // No
                continue;
            }
            if (\in_array($field, (array) static::BOOLEAN_FIELDS, true)) {
                $fields[$field] = Discretion::getDatabaseBoolean(
                    !empty($this->{$property})
                );
            } else {
                /** @psalm-suppress MixedAssignment */
                $fields[$field] = $this->{$property};
            }
        }
        $db->update(
            (string) (static::TABLE_NAME),
            $fields,
            [static::PRIMARY_KEY => $this->id]
        );
        return $db->commit();
    }

    /**
     * Get the property from the object.
     *
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException If the property does not exist.
     */
    public function __get(string $name)
    {
        if (!\property_exists($this, $name)) {
            throw new \InvalidArgumentException('Property ' . $name . ' does not exist.');
        }
        return $this->{$name};
    }

    /**
     * Strict-typed property setter.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws \TypeError
     */
    public function __set(string $name, $value)
    {
        if (!\property_exists($this, $name)) {
            throw new \InvalidArgumentException('Property ' . $name . ' does not exist.');
        }

        if ($name === 'id') {
            // RESERVED
            throw new \InvalidArgumentException('Cannot override an object\'s primary key.');
        }

        if (!\is_null($this->{$name})) {
            /* Enforce type strictness if only if property had a pre-established type. */
            $propType = Discretion::getGenericType($this->{$name});
            $valueType = Discretion::getGenericType($value);
            if ($propType !== $valueType) {
                throw new \TypeError('Property ' . $name . ' expects type ' . $propType . ', ' . $valueType . ' given.');
            }
        }
        /** @psalm-suppress MixedAssignment */
        $this->{$name} = $value;
    }
}
