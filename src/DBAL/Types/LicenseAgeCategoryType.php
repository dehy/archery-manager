<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseAgeCategoryType extends AbstractEnumType
{
    public const SENIOR_1 = 'S1';
    public const SENIOR_2 = 'S2';
    public const SENIOR_3 = 'S3';
    public const U11 = 'U11';
    public const U13 = 'U13';
    public const U15 = 'U15';
    public const U18 = 'U18';
    public const U21 = 'U21';

    // Old categories from previous seasons
    public const POUSSIN = 'P';
    public const BENJAMIN = 'B';
    public const MINIME = 'M';
    public const CADET = 'C';
    public const JUNIOR = 'J';
    public const SENIOR = 'S';
    public const VETERAN = 'V';
    public const SUPER_VETERAN = 'SV';

    protected static array $choices = [
        self::U11 => 'U11',
        self::U13 => 'U13',
        self::U15 => 'U15',
        self::U18 => 'U18',
        self::U21 => 'U21',
        self::SENIOR_1 => 'Senior 1',
        self::SENIOR_2 => 'Senior 2',
        self::SENIOR_3 => 'Senior 3',
        self::POUSSIN => 'Poussin',
        self::BENJAMIN => 'Benjamin',
        self::MINIME => 'Minime',
        self::CADET => 'Cadet',
        self::JUNIOR => 'Junior',
        self::SENIOR => 'Senior',
        self::VETERAN => 'Vétéran',
        self::SUPER_VETERAN => 'Super Vétéran',
    ];

    public static function getOrderedChoices()
    {
        $choices = [
            self::U11, self::U13, self::U15, self::U18, self::U21, self::SENIOR_1, self::SENIOR_2, self::SENIOR_3,
        ];

        $orderedChoices = [];
        foreach ($choices as $choice) {
            $orderedChoices[self::getReadableValue($choice)] = $choice;
        }

        return $orderedChoices;
    }
}
