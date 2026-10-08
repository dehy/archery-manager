<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\V1\EventPresenter;
use App\Api\V1\FileStreamer;
use App\Api\V1\InvalidParticipationException;
use App\Api\V1\LicenseePresenter;
use App\Api\V1\MemberContext;
use App\Api\V1\ParticipationWriter;
use App\Api\V1\PrivateJson;
use App\Entity\Club;
use App\Entity\Event;
use App\Entity\EventAttachment;
use App\Entity\EventParticipation;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Helper\EventHelper;
use App\Repository\EventParticipationRepository;
use App\Repository\EventRepository;
use App\Repository\LicenseeRepository;
use App\Security\Api\ApiErrorResponse;
use App\Security\Voter\EventVoter;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Events as seen by a member: the calendar, an event's detail, the member's answer (RSVP), who answered,
 * and the event's files. Same rules as the web: a member sees the events of their clubs and the events
 * without club, and answers only the events open to their groups (EventHelper::canLicenseeParticipateInEvent).
 */
final readonly class EventController
{
    /** The longest window a client can ask for, in days. */
    private const int MAX_RANGE_DAYS = 100;

    public function __construct(
        private MemberContext $context,
        private AuthorizationCheckerInterface $authorizationChecker,
        private EventRepository $events,
        private EventParticipationRepository $participations,
        private LicenseeRepository $licensees,
        private EventHelper $eventHelper,
        private EventPresenter $presenter,
        private LicenseePresenter $licenseePresenter,
        private ParticipationWriter $writer,
        private FileStreamer $fileStreamer,
        private EntityManagerInterface $entityManager,
        private FilesystemOperator $eventsStorage,
    ) {
    }

    /**
     * The events overlapping [from, to] (dates, inclusive; the current month by default), oldest first.
     */
    #[Route('/api/v1/events', name: 'api_v1_events', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $this->context->requireLicense();
        $licensee = $this->licensee();
        [$from, $to] = $this->range($request);

        $clubs = [];
        foreach ($licensee->getLicenses() as $license) {
            $club = $license->getClub();
            if ($club instanceof Club) {
                $clubs[$club->getId()] = $club;
            }
        }

        $events = array_values(array_filter(
            $this->events->findInRangeForClubs(array_values($clubs), $from, $to),
            fn (Event $event): bool => $this->authorizationChecker->isGranted(EventVoter::VIEW, $event),
        ));
        $mine = $this->participations->indexedByEventFor($licensee, $events);
        $counts = $this->participations->countAttendingByEvent($events);

        return PrivateJson::response([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'events' => array_map(
                fn (Event $event): array => $this->presenter->summary(
                    $event,
                    ($mine[$event->getId()] ?? $this->eventHelper->defaultParticipation($licensee, $event))->getParticipationState(),
                    $counts[$event->getId()] ?? 0,
                    $this->eventHelper->canLicenseeParticipateInEvent($licensee, $event),
                ),
                $events,
            ),
        ]);
    }

    #[Route('/api/v1/events/{id}', name: 'api_v1_event', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Event $event): JsonResponse
    {
        $this->context->requireLicense();
        $this->denyUnlessVisible($event);

        return PrivateJson::response(['event' => $this->detail($event, $this->licensee())]);
    }

    /**
     * Answers an event: the whole answer is sent each time (it replaces the previous one), so repeating a
     * request is harmless.
     */
    #[Route('/api/v1/events/{id}/participation', name: 'api_v1_event_participation', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function answer(Event $event, Request $request): JsonResponse
    {
        $this->context->requireLicense();
        $this->denyUnlessVisible($event);
        $licensee = $this->licensee();

        if (!$this->eventHelper->canLicenseeParticipateInEvent($licensee, $event)) {
            return ApiErrorResponse::create('event_restricted', 'This event is reserved for other groups.', Response::HTTP_FORBIDDEN);
        }

        try {
            $input = $request->toArray();
        } catch (RequestExceptionInterface) {
            throw new BadRequestHttpException('The request body must be a JSON object.');
        }

        $participation = $this->participations->findOneBy(['participant' => $licensee, 'event' => $event])
            ?? $this->eventHelper->defaultParticipation($licensee, $event);

        try {
            $this->writer->apply($participation, $event, $licensee, $input);
        } catch (InvalidParticipationException $invalidParticipationException) {
            return ApiErrorResponse::create('validation_failed', $invalidParticipationException->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (null === $participation->getId()) {
            $this->entityManager->persist($participation);
        }

        $this->entityManager->flush();

        return PrivateJson::response(['event' => $this->detail($event, $licensee)]);
    }

    /**
     * Who is coming. Trainings assigned to groups list the group members too, with the default
     * answer "registered" for those who did not answer; contests list the recorded answers only.
     */
    #[Route('/api/v1/events/{id}/participants', name: 'api_v1_event_participants', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function participants(Event $event): JsonResponse
    {
        $this->context->requireLicense();
        $this->denyUnlessVisible($event);

        $participants = [];
        foreach ($this->participantsOf($event) as $participation) {
            $participant = $participation->getParticipant();
            $participants[] = [
                'licensee_id' => $participant->getId(),
                'display_name' => $this->licenseePresenter->displayName($participant),
                ...$this->presenter->participation($participation, null === $participation->getId()),
            ];
        }

        return PrivateJson::response(['participants' => $participants]);
    }

    #[Route('/api/v1/events/{id}/attachments/{attachmentId}', name: 'api_v1_event_attachment', requirements: ['id' => '\d+', 'attachmentId' => '\d+'], methods: ['GET'])]
    public function attachment(Event $event, int $attachmentId, Request $request): Response
    {
        $this->context->requireLicense();
        $this->denyUnlessVisible($event);

        $attachment = $event->getAttachments()->findFirst(static fn (int $key, EventAttachment $a): bool => $a->getId() === $attachmentId);

        return $this->fileStreamer->stream($this->eventsStorage, $attachment?->getFile(), $request, $request->query->getBoolean('download'));
    }

    private function licensee(): Licensee
    {
        return $this->context->licensee() ?? throw new AccessDeniedHttpException('No licensee selected.');
    }

    private function denyUnlessVisible(Event $event): void
    {
        if (!$this->authorizationChecker->isGranted(EventVoter::VIEW, $event)) {
            throw new AccessDeniedHttpException('You are not allowed to access this event.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Event $event, Licensee $licensee): array
    {
        $persisted = $this->participations->findOneBy(['participant' => $licensee, 'event' => $event]);
        $mine = $persisted ?? $this->eventHelper->defaultParticipation($licensee, $event);

        return $this->presenter->detail(
            $event,
            $mine,
            null === $persisted,
            $this->participations->countAttendingByEvent([$event])[$event->getId()] ?? 0,
            $this->eventHelper->canLicenseeParticipateInEvent($licensee, $event),
            $licensee,
        );
    }

    /**
     * @return list<EventParticipation>
     */
    private function participantsOf(Event $event): array
    {
        $recorded = $this->participations->findForEventWithParticipants($event);
        if ($this->eventHelper->isContest($event) || $event->getAssignedGroups()->isEmpty()) {
            return $recorded;
        }

        $answered = [];
        foreach ($recorded as $participation) {
            $answered[$participation->getParticipant()->getId()] = true;
        }

        // The members of the assigned groups who hold a license in the event's club for its season.
        $members = $this->licensees->findInGroupsForSeason(
            array_values($event->getAssignedGroups()->toArray()),
            Season::seasonForDate($event->getStartsAt()),
            $event->getClub(),
        );
        foreach ($members as $member) {
            if (!isset($answered[$member->getId()])) {
                $recorded[] = $this->eventHelper->defaultParticipation($member, $event);
            }
        }

        return $recorded;
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function range(Request $request): array
    {
        $today = new \DateTimeImmutable('today');
        $from = $this->date($request->query->get('from'), $today->modify('first day of this month'));
        $to = $this->date($request->query->get('to'), $today->modify('last day of this month'));

        if ($to < $from || $from->diff($to)->days > self::MAX_RANGE_DAYS) {
            throw new BadRequestHttpException(\sprintf('from and to must be dates (YYYY-MM-DD), to not before from, at most %d days apart.', self::MAX_RANGE_DAYS));
        }

        // "to" is a day, inclusive: the window ends at its last second.
        return [$from, $to->setTime(23, 59, 59)];
    }

    private function date(mixed $value, \DateTimeImmutable $default): \DateTimeImmutable
    {
        if (null === $value) {
            return $default->setTime(0, 0);
        }

        $date = \is_string($value) ? \DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;
        $errors = \DateTimeImmutable::getLastErrors();
        if (false === $date || (\is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new BadRequestHttpException('from and to must be dates (YYYY-MM-DD).');
        }

        return $date;
    }
}
