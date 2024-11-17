<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class EventParticipationStateType extends AbstractEnumType
{
    public const string NOT_GOING = 'not_going';

    public const string INTERESTED = 'interested';

    public const string REGISTERED = 'registered';

    protected static array $choices = [
        self::NOT_GOING => "N'y va pas",
        self::INTERESTED => 'Intéressé',
        self::REGISTERED => 'Inscrit',
    ];
}
