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
}
