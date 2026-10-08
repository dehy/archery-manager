<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventAttachmentType;
use App\DBAL\Types\EventParticipationStateType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\TargetTypeType;
use App\Entity\Club;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\EventAttachment;
use App\Entity\EventParticipation;
use App\Entity\FreeTrainingEvent;
use App\Entity\HobbyContestEvent;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\TrainingEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The representations of events: the compact one listed on the dashboard and in the calendar, and the full one.
 */
final readonly class EventPresenter
{
    /** The departures (waves of shooters) a contest can offer. */
    private const array DEPARTURES = [1, 2, 3, 4];

    public function __construct(
        private ClubPresenter $clubPresenter,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Event $event, ?string $participationState, int $participantsCount, bool $canParticipate): array
    {
        $club = $event->getClub();

        return [
            'id' => $event->getId(),
            'slug' => $event->getSlug(),
            'title' => $event->getTitle(),
            'type' => $this->type($event),
            'club' => $club instanceof Club ? $this->clubPresenter->reference($club) : null,
            'starts_at' => $event->getStartsAt()?->format(\DATE_ATOM),
            'ends_at' => $event->getEndsAt()?->format(\DATE_ATOM),
            'all_day' => $event->isAllDay(),
            'address' => $event->getAddress(),
            'participation_state' => $participationState,
            'participants_count' => $participantsCount,
            'can_participate' => $canParticipate,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Event $event, EventParticipation $mine, bool $mineIsDefault, int $participantsCount, bool $canParticipate, ?Licensee $actingAs): array
    {
        return [
            ...$this->summary($event, $mine->getParticipationState(), $participantsCount, $canParticipate),
            'discipline' => EnumValue::of(DisciplineType::class, $event->getDiscipline()),
            'contest_type' => $event instanceof ContestEvent ? EnumValue::of(ContestType::class, $event->getContestType()) : null,
            'latitude' => $this->coordinate($event->getLatitude()),
            'longitude' => $this->coordinate($event->getLongitude()),
            'assigned_groups' => array_values(array_map(
                $this->clubPresenter->groupReference(...),
                $event->getAssignedGroups()->toArray(),
            )),
            'attachments' => array_values(array_map($this->attachment(...), $event->getAttachments()->toArray())),
            'my_participation' => $this->participation($mine, $mineIsDefault),
            'participation_options' => $this->options($event, $actingAs),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function participation(EventParticipation $participation, bool $isDefault): array
    {
        return [
            'participation_state' => $participation->getParticipationState(),
            'is_default' => $isDefault,
            'activity' => EnumValue::of(LicenseActivityType::class, $participation->getActivity()),
            'target_type' => EnumValue::of(TargetTypeType::class, $participation->getTargetType()),
            'departure' => $participation->getDeparture(),
        ];
    }

    /**
     * What a client may send to answer this event, so that it does not have to hard-code the rules:
     * contests accept three answers and a target type and departure, the other events two answers.
     * The activities are those of the license the licensee holds for the event's season.
     *
     * @return array{states: list<string>, activities: list<array{code: string, label: string}>, target_types: list<array{code: string, label: string}>|null, departures: list<int>|null}
     */
    public function options(Event $event, ?Licensee $actingAs): array
    {
        $contest = $this->isContest($event);

        return [
            'states' => $this->allowedStates($event),
            'activities' => EnumValue::listOf(
                LicenseActivityType::class,
                $actingAs?->getLicenseForSeason(Season::seasonForDate($event->getStartsAt()))?->getActivities(),
            ),
            'target_types' => $contest ? EnumValue::listOf(TargetTypeType::class, TargetTypeType::getValues()) : null,
            'departures' => $contest ? self::DEPARTURES : null,
        ];
    }

    /**
     * @return list<string>
     */
    public function allowedStates(Event $event): array
    {
        return $this->isContest($event)
            ? [EventParticipationStateType::NOT_GOING, EventParticipationStateType::INTERESTED, EventParticipationStateType::REGISTERED]
            : [EventParticipationStateType::NOT_GOING, EventParticipationStateType::REGISTERED];
    }

    public function isContest(Event $event): bool
    {
        return $event instanceof ContestEvent;
    }

    /**
     * HobbyContestEvent extends ContestEvent, so it has to be tested first.
     */
    public function type(Event $event): string
    {
        return match (true) {
            $event instanceof HobbyContestEvent => 'contest_hobby',
            $event instanceof ContestEvent => 'contest_official',
            $event instanceof TrainingEvent => 'training',
            $event instanceof FreeTrainingEvent => 'free_training',
            default => 'other',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function attachment(EventAttachment $attachment): array
    {
        $file = $attachment->getFile();

        return [
            'id' => $attachment->getId(),
            'type' => EnumValue::of(EventAttachmentType::class, $attachment->getType()),
            'file_name' => $file?->getOriginalName(),
            'mime_type' => $file?->getMimeType(),
            'size' => $file?->getSize(),
            'url' => $this->urlGenerator->generate('api_v1_event_attachment', [
                'id' => $attachment->getEvent()?->getId(),
                'attachmentId' => $attachment->getId(),
            ]),
        ];
    }

    private function coordinate(?string $value): ?float
    {
        return null !== $value && is_numeric($value) ? (float) $value : null;
    }
}
