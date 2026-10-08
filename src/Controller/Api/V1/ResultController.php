<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\V1\LicenseePresenter;
use App\Api\V1\MemberContext;
use App\Api\V1\PrivateJson;
use App\Api\V1\ResultPresenter;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\Result;
use App\Helper\ResultHelper;
use App\Repository\ResultRepository;
use App\Security\Voter\EventVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Contest results: the history of the selected licensee, and the results of one contest.
 */
final readonly class ResultController
{
    public function __construct(
        private MemberContext $context,
        private AuthorizationCheckerInterface $authorizationChecker,
        private ResultRepository $results,
        private ResultPresenter $presenter,
        private LicenseePresenter $licenseePresenter,
    ) {
    }

    /**
     * The selected licensee's results over all seasons, newest contest first.
     */
    #[Route('/api/v1/results', name: 'api_v1_results', methods: ['GET'])]
    public function history(): JsonResponse
    {
        $this->context->requireLicense();
        $licensee = $this->context->licensee();
        $history = $licensee instanceof \App\Entity\Licensee ? $this->results->findHistoryForLicensee($licensee) : [];

        return PrivateJson::response(['results' => array_map($this->presenter->historyEntry(...), $history)]);
    }

    /**
     * The results of a contest, in the order of the web page: age category, activity, archer. Archers
     * are named as the viewer may see them, and sorted by that name.
     */
    #[Route('/api/v1/events/{id}/results', name: 'api_v1_event_results', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function forEvent(Event $event): JsonResponse
    {
        $this->context->requireLicense();
        if (!$event instanceof ContestEvent) {
            throw new NotFoundHttpException('This event has no results.');
        }

        if (!$this->authorizationChecker->isGranted(EventVoter::VIEW, $event)) {
            throw new AccessDeniedHttpException('You are not allowed to access this event.');
        }

        $mine = $this->context->licensee();
        $results = ResultHelper::sort(
            $this->results->findForEventWithLicensees($event),
            fn (Result $result): string => $this->licenseePresenter->displayName($result->getLicensee()),
        );

        return PrivateJson::response(['results' => array_map(
            fn (Result $result): array => $this->presenter->contestEntry($result, $result->getLicensee() === $mine),
            $results,
        )]);
    }
}
