<?php

declare(strict_types=1);

namespace App\Helper;

enum SyncReturnValues: int
{
    case UNTOUCHED = 0;
    case CREATED = 1;
    case UPDATED = 2;
    case REMOVED = 3;
}
