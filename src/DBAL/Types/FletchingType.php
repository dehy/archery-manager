<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class FletchingType extends AbstractEnumType
{
    public const string PLASTIC = 'plastic';

    public const string SPINWINGS = 'spinwings';

    #[\Override]
    protected static array $choices = [
        self::PLASTIC => 'Plastique',
        self::SPINWINGS => 'Spinwings',
    ];
}
