<?php

declare(strict_types=1);

namespace App\Type;

enum EventParticipationStateType: string
{
    case NotGoing = 'not_going';
    case Interested = 'interested';
    case Registered = 'registered';
}
