<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseType extends AbstractEnumType
{
    public const string POUSSINS = 'P';

    public const string JEUNES = 'J';

    public const string ADULTES_COMPETITION = 'A';

    public const string ADULTES_CLUB = 'L';

    public const string ADULTES_SANS_PRATIQUE = 'E';

    public const string CONVENTION_UNSS = 'S';

    public const string CONVENTION_FFSU = 'U';

    public const string CONVENTION_FFH_FSA = 'H';

    public const string DECOUVERTE = 'D';

    protected static array $choices = [
        self::POUSSINS => 'Poussin',
        self::JEUNES => 'Jeune',
        self::ADULTES_COMPETITION => 'Adulte (compétition)',
        self::ADULTES_CLUB => 'Adulte (club)',
        self::ADULTES_SANS_PRATIQUE => 'Adulte (sans pratique)',
        self::CONVENTION_UNSS => 'Convention UNSS',
        self::CONVENTION_FFSU => 'Convention FFSU',
        self::CONVENTION_FFH_FSA => 'Convention FFH & FFSA',
        self::DECOUVERTE => 'Découverte',
    ];
}
