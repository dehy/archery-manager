<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseType extends AbstractEnumType
{
    public const POUSSINS = "P";
    public const JEUNES = "J";
    public const ADULTES_COMPETITION = "A";
    public const ADULTES_CLUB = "L";
    public const ADULTES_SANS_PRATIQUE = "E";
    public const CONVENTION_UNSS = "S";
    public const CONVETION_FFSU = "U";
    public const CONVENTION_FFH_FSA = "H";
    public const DECOUVERTE = "D";

    protected static array $choices = [
        self::POUSSINS => "Poussins",
        self::JEUNES => "Jeunes",
        self::ADULTES_COMPETITION => "Adultes (compétition)",
        self::ADULTES_CLUB => "Adultes (club)",
        self::ADULTES_SANS_PRATIQUE => "Adultes (sans pratique)",
        self::CONVENTION_UNSS => "Convention UNSS",
        self::CONVETION_FFSU => "Convention FFSU",
        self::CONVENTION_FFH_FSA => "Convention FFH & FFSA",
        self::DECOUVERTE => "Découverte",
    ];
}
