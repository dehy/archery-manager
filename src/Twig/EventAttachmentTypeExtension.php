<?php

namespace App\Twig;

use App\DBAL\Types\EventAttachmentType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class EventAttachmentTypeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('icon', [$this, 'icon']),
        ];
    }

    public function icon(string $eventAttachmentType): string
    {
        return EventAttachmentType::icon($eventAttachmentType);
    }
}
