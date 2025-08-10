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
#[ReadableEnumAlias(prefix: 'license.activity.')]
enum LicenseActivityType: string implements ReadableEnumInterface
{
    use EnumApiResourceTrait;
    use ReadableEnumTrait;

    case AC = 'AC';
    case AD = 'AD';
    case BB = 'BB';
    case CL = 'CL';
    case CO = 'CO';
    case TL = 'TL';

    public function getReadableValue(): string
    {
        return match ($this) {
            self::AC => 'Chasse',
            self::AD => 'Droit',
            self::BB => 'Bare bow',
            self::CL => 'Classique',
            self::CO => 'Poulies',
            self::TL => 'Libre',
        };
    }
}
