<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class ContestType extends AbstractEnumType
{
    public const string INDIVIDUAL = 'individual';

    public const string TEAM = 'team';

    protected static array $choices = [
        self::INDIVIDUAL => 'Individuel',
        self::TEAM => 'Par équipe',
    ];
}
