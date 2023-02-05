<?php

namespace App\Twig;

use App\DBAL\Types\EventKindType;
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

    public function eventColor(string $eventKind): string
    {
        return EventKindType::colorCode($eventKind);
    }
}
