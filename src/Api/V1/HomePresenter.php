<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\DBAL\Types\EventParticipationStateType;
use App\DBAL\Types\TargetTypeType;
use App\Entity\ClubApplication;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Entity\FreeTrainingEvent;
use App\Entity\HobbyContestEvent;
use App\Entity\Result;
use App\Entity\TrainingEvent;

/**
 * Compact representations of what the dashboard lists. The full event and result
 * resources arrive with the events and results screens.
 */
final readonly class HomePresenter
{
    public function __construct(private ClubPresenter $clubPresenter)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function event(Event $event, EventParticipation $participation): array
    {
        return [
            'id' => $event->getId(),
            'slug' => $event->getSlug(),
            'title' => $event->getTitle(),
            'type' => $this->eventType($event),
            'starts_at' => $event->getStartsAt()?->format(\DATE_ATOM),
            'ends_at' => $event->getEndsAt()?->format(\DATE_ATOM),
            'all_day' => $event->isAllDay(),
            'address' => $event->getAddress(),
            'participation_state' => $participation->getParticipationState(),
            'participants_count' => $event->getParticipations()
                ->filter(static fn (EventParticipation $p): bool => EventParticipationStateType::NOT_GOING !== $p->getParticipationState())
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function result(Result $result): array
    {
        $event = $result->getEvent();

        return [
            'id' => $result->getId(),
            'event' => $event instanceof Event ? [
                'id' => $event->getId(),
                'slug' => $event->getSlug(),
                'title' => $event->getTitle(),
                'starts_at' => $event->getStartsAt()?->format(\DATE_ATOM),
                'ends_at' => $event->getEndsAt()?->format(\DATE_ATOM),
            ] : null,
            'distance' => $result->getDistance(),
            'target_type' => EnumValue::of(TargetTypeType::class, $result->getTargetType()),
            'target_size' => $result->getTargetSize(),
            'total' => $result->getTotal(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function application(ClubApplication $application): array
    {
        $club = $application->getClub();

        return [
            'id' => $application->getId(),
            'club' => $club instanceof \App\Entity\Club ? $this->clubPresenter->reference($club) : null,
            'season' => $application->getSeason(),
            'status' => $application->getStatus(),
            'admin_message' => $application->getAdminMessage(),
            'created_at' => $application->getCreatedAt()?->format(\DATE_ATOM),
        ];
    }

    private function eventType(Event $event): string
    {
        return match (true) {
            $event instanceof ContestEvent => 'contest_official',
            $event instanceof HobbyContestEvent => 'contest_hobby',
            $event instanceof TrainingEvent => 'training',
            $event instanceof FreeTrainingEvent => 'free_training',
            default => 'other',
        };
    }
}
