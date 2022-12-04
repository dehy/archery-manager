<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class TargetTypeType extends AbstractEnumType
{
    public const MONOSPOT = 'monospot';
    public const TRISPOT = 'trispot';
    public const FIELD = 'field';
    public const ANIMAL = 'animal';
    public const BEURSAULT = 'beursault';

    protected static array $choices = [
        self::MONOSPOT => 'Monospot',
        self::TRISPOT => 'Trispot',
        self::FIELD => 'Nature',
        self::ANIMAL => 'Animal',
        self::BEURSAULT => 'Beursault',
    ];
}
