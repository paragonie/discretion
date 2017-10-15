<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Data;

/**
 * Class HiddenString
 * @package ParagonIE\Discretion\Data
 */
final class HiddenString
{
    /**
     * @var string
     */
    protected $internalStringValue = '';

    /**
     * HiddenString constructor.
     *
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->internalStringValue = '' . $value;
    }

    /**
     * @param HiddenString $other
     * @return bool
     */
    public function equals(HiddenString $other)
    {
        return \hash_equals(
            $this->getString(),
            $other->getString()
        );
    }

    /**
     * Hide its internal state from var_dump()
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [];
    }

    /**
     * Wipe it from memory after it's been used.
     */
    public function __destruct()
    {
        try {
            \sodium_memzero($this->internalStringValue);
        } catch (\Exception $ex) {
            $this->internalStringValue ^= $this->internalStringValue;
            unset($this->internalStringValue);
        }
    }

    /**
     * Explicit invocation -- get the raw string value
     *
     * @return string
     */
    public function getString(): string
    {
        return '' . $this->internalStringValue;
    }

    /**
     * Returns a copy of the string's internal value, which should be zeroed.
     * Optionally, it can return an empty string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return '';
    }

    /**
     * @return array
     */
    public function __sleep(): array
    {
        return [];
    }
}
