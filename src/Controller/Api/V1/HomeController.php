<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\V1\HomePresenter;
use App\Api\V1\MemberContext;
use App\Api\V1\PrivateJson;
use App\Entity\Licensee;
use App\Helper\EventHelper;
use App\Repository\ClubApplicationRepository;
use App\Repository\EventParticipationRepository;
use App\Repository\EventRepository;
use App\Repository\ResultRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The dashboard. Like the web home page it has three states: an account without any
 * licensee, a licensee without a license for the selected season, and the real dashboard.
 */
final readonly class HomeController
{
    private const int NEXT_EVENTS = 5;

    public function __construct(
        private MemberContext $context,
        private ClubApplicationRepository $applications,
        private EventRepository $events,
        private EventParticipationRepository $participations,
        private ResultRepository $results,
        private EventHelper $eventHelper,
        private HomePresenter $presenter,
    ) {
    }

    #[Route('/api/v1/home', name: 'api_v1_home', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return PrivateJson::response($this->payload($this->context->licensee(), $this->context->season()));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(?Licensee $licensee, int $season): array
    {
        if (!$licensee instanceof Licensee) {
            return ['state' => 'blank_account', 'season' => $season, 'licensee' => null];
        }

        $base = [
            'season' => $season,
            'licensee' => [
                'id' => $licensee->getId(),
                'firstname' => $licensee->getFirstname(),
                'ffta_member_code' => $licensee->getFftaMemberCode(),
            ],
        ];

        if (!$licensee->getLicenseForSeason($season) instanceof \App\Entity\License) {
            return ['state' => 'no_license', ...$base, 'applications' => array_map(
                $this->presenter->application(...),
                $this->applications->findByLicenseeAndSeason($licensee, $season),
            )];
        }

        return ['state' => 'dashboard', ...$base, ...$this->dashboard($licensee)];
    }

    /**
     * @return array{next_events: list<array<string, mixed>>, last_results: list<array<string, mixed>>}
     */
    private function dashboard(Licensee $licensee): array
    {
        $events = array_values($this->events->findNextForLicensee($licensee, self::NEXT_EVENTS)->toArray());
        $attending = $this->participations->countAttendingByEvent($events);

        $nextEvents = [];
        foreach ($events as $event) {
            $nextEvents[] = $this->presenter->event(
                $event,
                $this->eventHelper->licenseeParticipationToEvent($licensee, $event),
                $attending[$event->getId()] ?? 0,
            );
        }

        return [
            'next_events' => $nextEvents,
            'last_results' => array_map($this->presenter->result(...), $this->results->findLastForLicensee($licensee) ?? []),
        ];
    }
}
