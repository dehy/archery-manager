<?php

namespace App\Twig;

use App\DBAL\Types\EventType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class EventColorExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('eventColor', $this->eventColor(...)),
        ];
    }

    public function eventColor(string $eventType): string
    {
        return EventType::colorCode($eventType);
    }
}
