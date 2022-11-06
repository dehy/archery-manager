<?php

namespace App\Helper;

use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Entity\Licensee;
use App\Repository\EventParticipationRepository;

class EventHelper
{
    public function __construct(private readonly EventParticipationRepository $eventParticipationRepository)
    {
    }

    public function licenseeParticipationToEvent(Licensee $licensee, Event $event): ?EventParticipation
    {
        return $this->eventParticipationRepository->findOneBy([
            'participant' => $licensee,
            'event' => $event,
        ]);
    }
}
