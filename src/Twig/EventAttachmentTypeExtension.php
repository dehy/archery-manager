<?php

declare(strict_types=1);

namespace App\Twig;

use App\DBAL\Types\EventAttachmentType;

class EventAttachmentTypeExtension
{
    #[\Twig\Attribute\AsTwigFilter(name: 'icon')]
    public function icon(string $eventAttachmentType): string
    {
        return EventAttachmentType::icon($eventAttachmentType);
    }
}
