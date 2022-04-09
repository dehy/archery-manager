<?php
namespace App\DBAL\Types;

use DateTime;
use DateTimeInterface;
use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;
use LogicException;

final class LicenseAgeCategoryType extends AbstractEnumType
{
    public final const POUSSIN = 'P';
    public final const BENJAMIN = 'B';
    public final const MINIME = 'M';
    public final const CADET = 'C';
    public final const JUNIOR = 'J';
    public final const SENIOR_1 = 'S1';
    public final const SENIOR_2 = 'S2';
    public final const SENIOR_3 = 'S3';

    protected static array $choices = [
        self::POUSSIN => 'Poussin',
        self::BENJAMIN => 'Benjamin',
        self::MINIME => 'Minime',
        self::CADET => 'Cadet',
        self::JUNIOR => 'Junior',
        self::SENIOR_1 => 'Senior 1',
        self::SENIOR_2 => 'Senior 2',
        self::SENIOR_3 => 'Senior 3',
    ];

    public static function ageCategoryFromDate(DateTimeInterface $date): string
    {
        if ($date >= new DateTime('01/01/2012')) {
            return self::POUSSIN;
        }
        if ($date >= new DateTime('01/01/2010') && $date <= new DateTime('31/12/2011')) {
            return self::BENJAMIN;
        }
        if ($date >= new DateTime('01/01/2008') && $date <= new DateTime('31/12/2009')) {
            return self::MINIME;
        }
        if ($date >= new DateTime('01/01/2005') && $date <= new DateTime('31/12/2007')) {
            return self::CADET;
        }
        if ($date >= new DateTime('01/01/2002') && $date <= new DateTime('31/12/2004')) {
            return self::JUNIOR;
        }
        if ($date >= new DateTime('01/01/1983') && $date <= new DateTime('31/12/2001')) {
            return self::SENIOR_1;
        }
        if ($date >= new DateTime('01/01/1963') && $date <= new DateTime('31/12/1982')) {
            return self::SENIOR_2;
        }
        if ($date <= new DateTime('01/01/1963')) {
            return self::SENIOR_3;
        }
        throw new LogicException('Should not be triggered');
    }
}