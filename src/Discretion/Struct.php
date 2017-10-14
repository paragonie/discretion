<?php
declare(strict_types=1);
namespace ParagonIE\Discretion;

use ParagonIE\Discretion\Exception\RecordNotFound;

/**
 * Class Struct
 * @package ParagonIE\Discretion
 */
abstract class Struct
{
    const TABLE_NAME = '';
    const PRIMARY_KEY = '';
    const DB_FIELD_NAMES = [];

    /** @var int $id */
    protected $id = 0;

    /** @var \DateTimeImmutable $created */
    protected $created = null;

    /** @var \DateTimeImmutable $modified */
    protected $modified = null;

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
        $db = Discretion::getDatabase();
        $self = new static();
        $row = $db->row(
            "SELECT * FROM " .
                $db->escapeIdentifier(static::TABLE_NAME) .
            " WHERE " .
                $db->escapeIdentifier(static::PRIMARY_KEY) .
            " = ?",
            $id
        );
        if (empty($row)) {
            throw new RecordNotFound(static::class . '::' . $id);
        }
        foreach (static::DB_FIELD_NAMES as $field => $property) {
            $self->{$property} = $row[$field];
        }
        if (isset($row['created'])) {
            $self->created = new \DateTimeImmutable($row['created']);
        }
        if (isset($row['modified'])) {
            $self->modified = new \DateTimeImmutable($row['modified']);
        }
        return $self;
    }

    /**
     * @return bool
     */
    public function create(): bool
    {
        if ($this->id) {
            return $this->update();
        }
        $db = Discretion::getDatabase();
        $db->beginTransaction();

        $fields = [];
        foreach (static::DB_FIELD_NAMES as $field => $property) {
            if ($field === static::PRIMARY_KEY) {
                // No
                continue;
            }
            $fields[$field] = $this->{$property};
        }
        $this->id = (int) $db->insertGet(
            static::TABLE_NAME,
            $fields,
            static::PRIMARY_KEY
        );
        return $db->commit();

    }

    /**
     * @return bool
     */
    public function update(): bool
    {
        if (!($this->id)) {
            return $this->create();
        }
        $db = Discretion::getDatabase();
        $db->beginTransaction();

        $fields = [];
        foreach (static::DB_FIELD_NAMES as $field => $property) {
            if ($field === static::PRIMARY_KEY) {
                // No
                continue;
            }
            $fields[$field] = $this->{$property};
        }
        $db->update(
            static::TABLE_NAME,
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
        $this->{$name} = $value;
    }
}