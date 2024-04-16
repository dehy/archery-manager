<?php

namespace App\Helper;

use App\Entity\Event;
use App\Entity\EventInstance;
use App\Entity\EventParticipation;
use App\Entity\Licensee;
use App\Repository\EventParticipationRepository;

class EventHelper
{
    public function __construct(private readonly EventParticipationRepository $eventParticipationRepository)
    {
    }

    public function licenseeParticipationToEventInstance(
        Licensee $licensee,
        EventInstance $eventInstance
    ): ?EventParticipation {
        $eventParticipation = $this->eventParticipationRepository->findOneBy([
            'participant' => $licensee,
            'event' => $eventInstance->getEvent(),
            'instanceDate' => $eventInstance->getInstanceDate(),
        ]);

        if (null === $eventParticipation) {
            $eventParticipation = (new EventParticipation())
                ->setEvent($eventInstance->getEvent())
                ->setInstanceDate($eventInstance->getInstanceDate())
                ->setParticipant($licensee);
        }

        return $eventParticipation;
    }
}
