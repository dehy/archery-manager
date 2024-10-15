<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseCategoryType extends AbstractEnumType
{
    public const string POUSSINS = 'P';

    public const string JEUNES = 'J';

    public const string ADULTES = 'A';

    protected static array $choices = [
        self::POUSSINS => 'Poussins',
        self::JEUNES => 'Jeunes',
        self::ADULTES => 'Adultes',
    ];
}
