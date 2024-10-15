<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class BowType extends AbstractEnumType
{
    public const string INITIATION = 'initiation';

    public const string CLASSIQUE_COMPETITION = 'classique_competition';

    public const string POULIES = 'poulies';

    public const string BAREBOW = 'barebow';

    public const string LONGBOW = 'longbow';

    protected static array $choices = [
        self::INITIATION => 'Initiation',
        self::CLASSIQUE_COMPETITION => 'Classique compétition',
        self::POULIES => 'Poulies',
        self::BAREBOW => 'Barebow',
        self::LONGBOW => 'Longbow',
    ];
}
