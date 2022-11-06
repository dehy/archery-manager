<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class EventParticipationStateType extends AbstractEnumType
{
    public const NOT_GOING = 'not_going';
    public const GOING = 'going';
    public const REGISTERED = 'registered';

    protected static array $choices = [
        self::NOT_GOING => 'N\'y va pas',
        self::GOING => 'Y va',
        self::REGISTERED => 'Inscrit',
    ];
}
