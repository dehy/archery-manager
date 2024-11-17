<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class TargetTypeType extends AbstractEnumType
{
    public const string MONOSPOT = 'monospot';

    public const string TRISPOT = 'trispot';

    public const string FIELD = 'field';

    public const string ANIMAL = 'animal';

    public const string BEURSAULT = 'beursault';

    protected static array $choices = [
        self::MONOSPOT => 'Monospot',
        self::TRISPOT => 'Trispot',
        self::FIELD => 'Nature',
        self::ANIMAL => 'Animal',
        self::BEURSAULT => 'Beursault',
    ];
}
