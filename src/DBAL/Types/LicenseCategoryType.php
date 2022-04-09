<?php
namespace App\DBAL\Types;

use DateTime;
use DateTimeInterface;
use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;
use LogicException;

final class LicenseCategoryType extends AbstractEnumType
{
    public final const POUSSINS = 'P';
    public final const JEUNES = 'J';
    public final const ADULTES = 'A';

    protected static array $choices = [
        self::POUSSINS => 'Poussins',
        self::JEUNES => 'Jeunes',
        self::ADULTES => 'Adultes',
    ];

    public static function categoryFromDate(DateTimeInterface $date): string
    {
        if ($date >= new DateTime('01/01/2012')) {
            return self::POUSSINS;
        }
        if ($date >= new DateTime('01/01/2002') && $date <= new DateTime('31/12/2011')) {
            return self::JEUNES;
        }
        if ($date <= new DateTime('31/12/2001')) {
            return self::ADULTES;
        }
        throw new LogicException('Should not be triggered');
    }
}