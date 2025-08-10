<?php

declare(strict_types=1);

namespace App\Helper;

/**
 * Helper class for object comparison operations.
 */
class ObjectComparator
{
    /**
     * Compare two objects for equality.
     */
    public static function equal(mixed $obj1, mixed $obj2): bool
    {
        // Simple comparison - can be enhanced based on needs
        return $obj1 === $obj2;
    }
}
