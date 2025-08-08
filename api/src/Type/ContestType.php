<?php

declare(strict_types=1);

namespace App\Type;

enum ContestType: string
{
    case Federal = 'federal';
    case International = 'international';
    case Challenge33 = 'challenge33';
    case Individual = 'individual';
    case Team = 'team';
}
