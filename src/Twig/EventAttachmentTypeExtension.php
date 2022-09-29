<?php

namespace App\Twig;

use App\DBAL\Types\EventAttachmentType;
use DH\AuditorBundle\Twig\Extension\TwigExtension;
use Twig\TwigFilter;

class EventAttachmentTypeExtension extends TwigExtension
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
