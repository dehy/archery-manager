<?php

declare(strict_types=1);

namespace App\Helper;

/**
 * Return values for synchronization operations
 */
class SyncReturnValues
{
    public const UNTOUCHED = 'untouched';
    public const UPDATED = 'updated';
    public const CREATED = 'created';
}
