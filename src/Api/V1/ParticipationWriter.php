<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\TargetTypeType;
use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Entity\Licensee;
use App\Entity\Season;

/**
 * Applies the answer a licensee sends for an event to their EventParticipation, enforcing the same rules
 * as the web form (EventParticipationType): contests take three answers, a target type and a departure,
 * the other events two answers; the activity is one of those of the license held for the event's season.
 */
final readonly class ParticipationWriter
{
    private const array DEPARTURES = [1, 2, 3, 4];

    public function __construct(private EventPresenter $presenter)
    {
    }

    /**
     * @param array<mixed> $input the decoded JSON body
     *
     * @throws InvalidParticipationException
     */
    public function apply(EventParticipation $participation, Event $event, Licensee $licensee, array $input): void
    {
        $allowedStates = $this->presenter->allowedStates($event);
        $state = $input['participation_state'] ?? null;
        if (!\is_string($state) || !\in_array($state, $allowedStates, true)) {
            throw new InvalidParticipationException(\sprintf('participation_state must be one of: %s.', implode(', ', $allowedStates)));
        }

        $activity = $this->activity($input, $participation, $event, $licensee);

        if ($this->presenter->isContest($event)) {
            $targetType = $this->targetType($input['target_type'] ?? null);
            $departure = $this->departure($input['departure'] ?? null);
        } elseif (null !== ($input['target_type'] ?? null) || null !== ($input['departure'] ?? null)) {
            throw new InvalidParticipationException('target_type and departure only apply to contests.');
        } else {
            $targetType = null;
            $departure = null;
        }

        // Nothing is touched before everything is valid.
        $participation->setParticipationState($state);
        $participation->setActivity($activity);
        $participation->setTargetType($targetType);
        $participation->setDeparture($departure);
    }

    /**
     * @param array<mixed> $input
     */
    private function activity(array $input, EventParticipation $participation, Event $event, Licensee $licensee): string
    {
        $allowed = $licensee->getLicenseForSeason(Season::seasonForDate($event->getStartsAt()))?->getActivities() ?? [];
        $activity = $input['activity'] ?? $participation->getActivity();

        if (!\is_string($activity) || !\in_array($activity, $allowed, true) || !LicenseActivityType::isValueExist($activity)) {
            throw new InvalidParticipationException('activity must be one of the activities of your license for the season of this event.');
        }

        return $activity;
    }

    private function targetType(mixed $value): ?string
    {
        if (null !== $value && (!\is_string($value) || !TargetTypeType::isValueExist($value))) {
            throw new InvalidParticipationException('target_type is not a known target type.');
        }

        return $value;
    }

    private function departure(mixed $value): ?int
    {
        if (null !== $value && !\in_array($value, self::DEPARTURES, true)) {
            throw new InvalidParticipationException('departure must be 1, 2, 3 or 4.');
        }

        return $value;
    }
}
