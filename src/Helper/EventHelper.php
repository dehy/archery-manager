<?php

declare(strict_types=1);

namespace App\Helper;

use App\DBAL\Types\EventParticipationStateType;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Entity\HobbyContestEvent;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Repository\EventParticipationRepository;

class EventHelper
{
    public function __construct(private readonly EventParticipationRepository $eventParticipationRepository)
    {
    }

    /**
     * Check if a licensee can participate in an event based on group membership.
     * A licensee can participate if they belong to at least one of the event's assigned groups.
     */
    public function canLicenseeParticipateInEvent(Licensee $licensee, Event $event): bool
    {
        // If event has no assigned groups, everyone can participate
        if ($event->getAssignedGroups()->isEmpty()) {
            return true;
        }

        // Check if licensee is in at least one of the event's assigned groups
        foreach ($event->getAssignedGroups() as $eventGroup) {
            if ($licensee->getGroups()->contains($eventGroup)) {
                return true;
            }
        }

        return false;
    }

    public function licenseeParticipationToEvent(Licensee $licensee, Event $event): EventParticipation
    {
        $eventParticipation = $this->eventParticipationRepository->findOneBy([
            'participant' => $licensee,
            'event' => $event,
        ]);

        if (null === $eventParticipation) {
            // Create a new virtual/dynamic EventParticipation (not persisted yet)
            $eventParticipation = new EventParticipation();
            $eventParticipation->setEvent($event);
            $eventParticipation->setParticipant($licensee);

            // Set default activity from licensee's current license
            $license = $licensee->getLicenseForSeason(Season::seasonForDate($event->getStartsAt()));
            if ($license instanceof \App\Entity\License && !\in_array($license->getActivities(), [null, []], true)) {
                // Use the first activity as default
                $eventParticipation->setActivity($license->getActivities()[0]);
            }

            // Set dynamic default participation state based on event type and group membership
            // This is only a default in the UI - will be saved only when user submits the form
            $isContest = $event instanceof ContestEvent || $event instanceof HobbyContestEvent;
            // For training events, check if licensee can participate (is in event's group)
            if (!$isContest && $this->canLicenseeParticipateInEvent($licensee, $event)) {
                // Default to REGISTERED (Present) only for licensees in the event's group
                $eventParticipation->setParticipationState(EventParticipationStateType::REGISTERED);
            }

            // For contests or if not in group, leave it null (no default, user must choose)
        }

        return $eventParticipation;
    }

    /**
     * Get all participants for an event, including those with default participation states.
     * For training events, includes all licensees from assigned groups with their default REGISTERED state.
     *
     * @return EventParticipation[] Array of EventParticipation objects (some may be virtual/not persisted)
     */
    public function getAllParticipantsForEvent(Event $event): array
    {
        $participants = [];
        $isContest = $event instanceof ContestEvent || $event instanceof HobbyContestEvent;

        // For training events with assigned groups, include all group members with default states
        if (!$isContest && !$event->getAssignedGroups()->isEmpty()) {
            // Get all licensees from assigned groups
            $licenseesByGroup = [];
            foreach ($event->getAssignedGroups() as $group) {
                foreach ($group->getLicensees() as $licensee) {
                    // Use licensee ID as key to avoid duplicates
                    $licenseesByGroup[$licensee->getId()] = $licensee;
                }
            }

            // For each unique licensee in the groups, get their participation (real or default)
            foreach ($licenseesByGroup as $licensee) {
                $participants[] = $this->licenseeParticipationToEvent($licensee, $event);
            }
        } else {
            // For contests or events without assigned groups, only show explicit participations
            foreach ($event->getParticipations() as $participation) {
                $participants[] = $participation;
            }
        }

        return $participants;
    }
}
