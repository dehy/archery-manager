<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\TargetTypeType;
use App\Entity\Event;
use App\Entity\Result;
use App\Entity\Season;

/**
 * Contest results: a member's history, and the results of one contest.
 */
final readonly class ResultPresenter
{
    public function __construct(private LicenseePresenter $licenseePresenter)
    {
    }

    /**
     * A result in a member's history: it carries its contest.
     *
     * @return array<string, mixed>
     */
    public function historyEntry(Result $result): array
    {
        $event = $result->getEvent();

        return [
            'event' => $event instanceof Event ? [
                'id' => $event->getId(),
                'slug' => $event->getSlug(),
                'title' => $event->getTitle(),
                'starts_at' => $event->getStartsAt()?->format(\DATE_ATOM),
                'ends_at' => $event->getEndsAt()?->format(\DATE_ATOM),
                'season' => $event->getEndsAt() instanceof \DateTimeImmutable ? Season::seasonForDate($event->getEndsAt()) : null,
            ] : null,
            ...$this->scores($result),
        ];
    }

    /**
     * A result in the list of a contest: it carries its archer, named as the viewer may see them.
     *
     * @return array<string, mixed>
     */
    public function contestEntry(Result $result, bool $mine): array
    {
        $licensee = $result->getLicensee();

        return [
            'licensee' => ['id' => $licensee->getId(), 'display_name' => $this->licenseePresenter->displayName($licensee)],
            'is_mine' => $mine,
            ...$this->scores($result),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function scores(Result $result): array
    {
        return [
            'id' => $result->getId(),
            'discipline' => EnumValue::of(DisciplineType::class, $result->getDiscipline()),
            'age_category' => EnumValue::of(LicenseAgeCategoryType::class, $result->getAgeCategory()),
            'activity' => EnumValue::of(LicenseActivityType::class, $result->getActivity()),
            'distance' => $result->getDistance(),
            'target_type' => EnumValue::of(TargetTypeType::class, $result->getTargetType()),
            'target_size' => $result->getTargetSize(),
            'score1' => $result->getScore1(),
            'score2' => $result->getScore2(),
            'total' => $result->getTotal(),
            'max_total' => $this->maxTotal($result),
            'nb10' => $result->getNb10(),
            'nb10p' => $result->getNb10p(),
            'tie_break_labels' => 'CO' === $result->getActivity() ? ['10', 'X'] : ['9', '10'],
            'position' => $result->getPosition(),
        ];
    }

    /**
     * The best possible total of the contest, `null` when the format has none we know (field, 3D...).
     */
    private function maxTotal(Result $result): ?int
    {
        try {
            return $result->getMaxTotal();
        } catch (\UnhandledMatchError|\LogicException) {
            return null;
        }
    }
}
