<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Policies;

/**
 * Interface Unique
 *
 * This Struct must be 100% unique at runtime, which means using a global cache.
 *
 * @package ParagonIE\Discretion\Policies
 */
interface Unique
{
    /**
     * @param int $id
     * @return string
     * @throws \Error
     */
    public function getCacheKey(int $id = 0): string;
}
