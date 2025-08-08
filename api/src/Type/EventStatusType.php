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
#[ReadableEnumAlias(prefix: 'event_status.')]
enum EventStatusType: string implements ReadableEnumInterface
{
    use EnumApiResourceTrait;
    use ReadableEnumTrait;

    case Cancelled = 'cancelled';
    case Postponed = 'postponed';
    case Rescheduled = 'rescheduled';
    case Scheduled = 'scheduled';
}
