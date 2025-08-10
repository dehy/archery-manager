<?php

declare(strict_types=1);

namespace App\Type;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Elao\Enum\Attribute\ReadableEnum as ReadableEnumAlias;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

#[
    ApiResource(
        normalizationContext: ['groups' => ['read']]
    ),
    GetCollection(provider: self::class.'::getCases'),
    Get(provider: self::class.'::getCase'),
]
#[ReadableEnumAlias(prefix: 'license.age.category.')]
enum LicenseAgeCategoryType: string implements ReadableEnumInterface
{
    use EnumApiResourceTrait;
    use ReadableEnumTrait;

    case SENIOR_1 = 'S1';
    case SENIOR_2 = 'S2';
    case SENIOR_3 = 'S3';
    case U11 = 'U11';
    case U13 = 'U13';
    case U15 = 'U15';
    case U18 = 'U18';
    case U21 = 'U21';

    // Old categories from previous seasons
    case POUSSIN = 'P';
    case BENJAMIN = 'B';
    case MINIME = 'M';
    case CADET = 'C';
    case JUNIOR = 'J';
    case SENIOR = 'S';
    case VETERAN = 'V';
    case SUPER_VETERAN = 'SV';

    public function getReadableValue(): string
    {
        return match ($this) {
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
        };
    }

    public function getOrderedChoices(): array
    {
        $choices = [
            self::U11, self::U13, self::U15, self::U18, self::U21, self::SENIOR_1, self::SENIOR_2, self::SENIOR_3,
        ];

        $orderedChoices = [];
        foreach ($choices as $choice) {
            $orderedChoices[$choice->getReadableValue()] = $choice;
        }

        return $orderedChoices;
    }
}
