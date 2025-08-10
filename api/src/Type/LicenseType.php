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
#[ReadableEnumAlias(prefix: 'license.')]
enum LicenseType: string implements ReadableEnumInterface
{
    use EnumApiResourceTrait;
    use ReadableEnumTrait;

    case POUSSINS = 'P';
    case JEUNES = 'J';
    case ADULTES_COMPETITION = 'A';
    case ADULTES_CLUB = 'L';
    case ADULTES_SANS_PRATIQUE = 'E';
    case CONVENTION_UNSS = 'S';
    case CONVENTION_FFSU = 'U';
    case CONVENTION_FFH_FSA = 'H';
    case DECOUVERTE = 'D';

    public function getReadableValue(): string
    {
        return match ($this) {
            self::POUSSINS => 'Poussin',
            self::JEUNES => 'Jeune',
            self::ADULTES_COMPETITION => 'Adulte (compétition)',
            self::ADULTES_CLUB => 'Adulte (club)',
            self::ADULTES_SANS_PRATIQUE => 'Adulte (sans pratique)',
            self::CONVENTION_UNSS => 'Convention UNSS',
            self::CONVENTION_FFSU => 'Convention FFSU',
            self::CONVENTION_FFH_FSA => 'Convention FFH & FFSA',
            self::DECOUVERTE => 'Découverte',
        };
    }
}
