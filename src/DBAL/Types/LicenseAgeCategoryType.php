<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseAgeCategoryType extends AbstractEnumType
{
    public const string SENIOR_1 = 'S1';

    public const string SENIOR_2 = 'S2';

    public const string SENIOR_3 = 'S3';

    public const string U11 = 'U11';

    public const string U13 = 'U13';

    public const string U15 = 'U15';

    public const string U18 = 'U18';

    public const string U21 = 'U21';

    // Old categories from previous seasons
    public const string POUSSIN = 'P';

    public const string BENJAMIN = 'B';

    public const string MINIME = 'M';

    public const string CADET = 'C';

    public const string JUNIOR = 'J';

    public const string SENIOR = 'S';

    public const string VETERAN = 'V';

    public const string SUPER_VETERAN = 'SV';

    protected static array $choices = [
        self::U11 => 'U11',
        self::U13 => 'U13',
        self::U15 => 'U15',
        self::U18 => 'U18',
        self::U21 => 'U21',
        self::SENIOR_1 => 'Senior 1',
        self::SENIOR_2 => 'Senior 2',
        self::SENIOR_3 => 'Senior 3',
        self::POUSSIN => 'Poussin (déprécié)',
        self::BENJAMIN => 'Benjamin (déprécié)',
        self::MINIME => 'Minime (déprécié)',
        self::CADET => 'Cadet (déprécié)',
        self::JUNIOR => 'Junior (déprécié)',
        self::SENIOR => 'Senior (déprécié)',
        self::VETERAN => 'Vétéran (déprécié)',
        self::SUPER_VETERAN => 'Super Vétéran (déprécié)',
    ];

    public static function getOrderedChoices(): array
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
