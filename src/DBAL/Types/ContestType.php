<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class ContestType extends AbstractEnumType
{
    public const INDIVIDUAL = 'individual';
    public const TEAM = 'team';

    protected static array $choices = [
        self::INDIVIDUAL => 'Individuel',
        self::TEAM => 'Par équipe',
    ];
}
