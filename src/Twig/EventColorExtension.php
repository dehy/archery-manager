<?php

declare(strict_types=1);

namespace App\Twig;

use App\DBAL\Types\EventType;
use App\Entity\Event;

class EventColorExtension
{
    #[\Twig\Attribute\AsTwigFilter(name: 'eventColor')]
    public function eventColor(Event $event): string
    {
        return EventType::colorCode($event);
    }
}
