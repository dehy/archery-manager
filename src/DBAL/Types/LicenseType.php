<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class LicenseType extends AbstractEnumType
{
    public final const POUSSINS = 'P';
    public final const JEUNES = 'J';
    public final const ADULTES_COMPETITION = 'A';
    public final const ADULTES_CLUB = 'L';
    public final const ADULTES_SANS_PRATIQUE = 'E';
    public final const CONVENTION_UNSS = 'S';
    public final const CONVETION_FFSU = 'U';
    public final const CONVENTION_FFH_FSA = 'H';
    public final const DECOUVERTE = 'D';

    protected static array $choices = [
         self::POUSSINS => "Poussins",
         self::JEUNES => "Jeunes",
         self::ADULTES_COMPETITION => "Adultes (compétition)",
         self::ADULTES_CLUB => "Adultes (club)",
         self::ADULTES_SANS_PRATIQUE => "Adultes (sans pratique)",
         self::CONVENTION_UNSS => "Convention UNSS",
         self::CONVETION_FFSU => "Convention FFSU",
         self::CONVENTION_FFH_FSA => "Convention FFH & FFSA",
         self::DECOUVERTE => "Découverte"
    ];
}