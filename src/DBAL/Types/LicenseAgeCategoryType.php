<?php
namespace App\DBAL\Types;

use DateTime;
use DateTimeInterface;
use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;
use LogicException;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseAgeCategoryType extends AbstractEnumType
{
    public const POUSSIN = "P";
    public const BENJAMIN = "B";
    public const MINIME = "M";
    public const CADET = "C";
    public const JUNIOR = "J";
    public const SENIOR_1 = "S1";
    public const SENIOR_2 = "S2";
    public const SENIOR_3 = "S3";

    // Old categories from previous seasons
    public const SENIOR = "S";
    public const VETERAN = "V";
    public const SUPER_VETERAN = "SV";

    protected static array $choices = [
        self::POUSSIN => "Poussin",
        self::BENJAMIN => "Benjamin",
        self::MINIME => "Minime",
        self::CADET => "Cadet",
        self::JUNIOR => "Junior",
        self::SENIOR_1 => "Senior 1",
        self::SENIOR_2 => "Senior 2",
        self::SENIOR_3 => "Senior 3",
        self::SENIOR => "Senior",
        self::VETERAN => "Vétéran",
        self::SUPER_VETERAN => "Super Vétéran",
    ];

    public static function ageCategoryFromDate(DateTimeInterface $date): string
    {
        if ($date >= new DateTime("01/01/2012")) {
            return self::POUSSIN;
        }
        if (
            $date >= new DateTime("01/01/2010") &&
            $date <= new DateTime("31/12/2011")
        ) {
            return self::BENJAMIN;
        }
        if (
            $date >= new DateTime("01/01/2008") &&
            $date <= new DateTime("31/12/2009")
        ) {
            return self::MINIME;
        }
        if (
            $date >= new DateTime("01/01/2005") &&
            $date <= new DateTime("31/12/2007")
        ) {
            return self::CADET;
        }
        if (
            $date >= new DateTime("01/01/2002") &&
            $date <= new DateTime("31/12/2004")
        ) {
            return self::JUNIOR;
        }
        if (
            $date >= new DateTime("01/01/1983") &&
            $date <= new DateTime("31/12/2001")
        ) {
            return self::SENIOR_1;
        }
        if (
            $date >= new DateTime("01/01/1963") &&
            $date <= new DateTime("31/12/1982")
        ) {
            return self::SENIOR_2;
        }
        if ($date <= new DateTime("01/01/1963")) {
            return self::SENIOR_3;
        }
        throw new LogicException("Should not be triggered");
    }
}
