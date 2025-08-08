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
#[ReadableEnumAlias(prefix: 'license.category.')]
enum LicenseCategoryType: string implements ReadableEnumInterface
{
    use EnumApiResourceTrait;
    use ReadableEnumTrait;

    case POUSSINS = 'P';
    case JEUNES = 'J';
    case ADULTES = 'A';

    public function getReadableValue(): string
    {
        return match($this) {
            self::POUSSINS => 'Poussins',
            self::JEUNES => 'Jeunes',
            self::ADULTES => 'Adultes',
        };
    }
}
