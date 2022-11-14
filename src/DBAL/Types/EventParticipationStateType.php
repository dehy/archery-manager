<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class EventParticipationStateType extends AbstractEnumType
{
    public const NOT_GOING = 'not_going';
    public const INTERESTED = 'interested';
    public const REGISTERED = 'registered';

    protected static array $choices = [
        self::NOT_GOING => 'N\'y va pas',
        self::INTERESTED => 'Intéressé',
        self::REGISTERED => 'Inscrit',
    ];
}
