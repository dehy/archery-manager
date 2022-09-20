<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseType extends AbstractEnumType
{
    public const POUSSINS = 'P';
    public const JEUNES = 'J';
    public const ADULTES_COMPETITION = 'A';
    public const ADULTES_CLUB = 'L';
    public const ADULTES_SANS_PRATIQUE = 'E';
    public const CONVENTION_UNSS = 'S';
    public const CONVENTION_FFSU = 'U';
    public const CONVENTION_FFH_FSA = 'H';
    public const DECOUVERTE = 'D';

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
