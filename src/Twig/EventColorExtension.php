<?php

namespace App\Twig;

use App\DBAL\Types\EventType;
use DH\AuditorBundle\Twig\Extension\TwigExtension;
use Twig\TwigFilter;

class EventColorExtension extends TwigExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('eventColor', [$this, 'eventColor']),
        ];
    }

    public function eventColor(string $eventType): string
    {
        return EventType::colorCode($eventType);
    }
}
