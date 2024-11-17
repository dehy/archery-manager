<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class ArrowType extends AbstractEnumType
{
    public const string WOOD = 'wood';

    public const string ALUMINUM = 'aluminum';

    public const string CARBON = 'carbon';

    public const string ALUMINUM_CARBON = 'aluminum_carbon';

    protected static array $choices = [
        self::WOOD => 'Bois',
        self::ALUMINUM => 'Aluminium',
        self::CARBON => 'Carbone',
        self::ALUMINUM_CARBON => 'Aluminium Carbone',
    ];
}
