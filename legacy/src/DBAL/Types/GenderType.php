<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class GenderType extends AbstractEnumType
{
    public const string MALE = 'M';

    public const string FEMALE = 'F';

    protected static array $choices = [
        self::MALE => 'Homme',
        self::FEMALE => 'Femme',
    ];
}
