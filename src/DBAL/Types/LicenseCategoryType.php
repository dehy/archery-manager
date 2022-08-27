<?php
namespace App\DBAL\Types;

use DateTime;
use DateTimeInterface;
use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;
use LogicException;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseCategoryType extends AbstractEnumType
{
    public const POUSSINS = "P";
    public const JEUNES = "J";
    public const ADULTES = "A";

    protected static array $choices = [
        self::POUSSINS => "Poussins",
        self::JEUNES => "Jeunes",
        self::ADULTES => "Adultes",
    ];

    public static function categoryFromDate(DateTimeInterface $date): string
    {
        if ($date >= new DateTime("01/01/2012")) {
            return self::POUSSINS;
        }
        if (
            $date >= new DateTime("01/01/2002") &&
            $date <= new DateTime("31/12/2011")
        ) {
            return self::JEUNES;
        }
        if ($date <= new DateTime("31/12/2001")) {
            return self::ADULTES;
        }
        throw new LogicException("Should not be triggered");
    }
}
