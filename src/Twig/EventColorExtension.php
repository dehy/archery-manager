<?php

declare(strict_types=1);

namespace App\Twig;

use App\DBAL\Types\EventType;
use App\Entity\Event;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class EventColorExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('eventColor', $this->eventColor(...)),
        ];
    }

    public function eventColor(Event $event): string
    {
        return EventType::colorCode($event);
    }
}
