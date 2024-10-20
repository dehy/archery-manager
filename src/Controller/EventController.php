<?php

declare(strict_types=1);

namespace App\Controller;

use App\DBAL\Types\EventAttachmentType;
use App\DBAL\Types\EventParticipationStateType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\TargetTypeType;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\EventAttachment;
use App\Entity\FreeTrainingEvent;
use App\Entity\HobbyContestEvent;
use App\Entity\Result;
use App\Entity\Season;
use App\Entity\TrainingEvent;
use App\Factory\IcsFactory;
use App\Form\EventMandateType;
use App\Form\EventParticipationType;
use App\Form\EventResultsType;
use App\Helper\EventHelper;
use App\Helper\LicenseeHelper;
use App\Repository\ContestEventRepository;
use App\Repository\EventAttachmentRepository;
use App\Repository\EventRepository;
use App\Repository\ResultRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class EventController extends BaseController
{
    /**
     * @throws \Exception
     */
    #[Route('/events', name: 'app_event_index')]
    public function index(Request $request, EventRepository $eventRepository): Response
    {
        $this->assertHasValidLicense();

        $now = new \DateTime();
        $month = $request->query->getInt('m', (int) $now->format('n'));
        $year = $request->query->getInt('y', (int) $now->format('Y'));

        /** @var Event[] $events */
        $events = $eventRepository
            ->findForLicenseeByMonthAndYear($this->licenseeHelper->getLicenseeFromSession(), $month, $year);

        $firstDate = (new \DateTime(\sprintf('%s-%s-01 midnight', $year, $month)));
        $lastDate = (clone $firstDate)->modify('last day of this month')->setTime(23, 59, 59);
        if (1 !== (int) $firstDate->format('N')) {
            $firstDate->modify('previous monday');
        }

        if (7 !== (int) $lastDate->format('N')) {
            $lastDate->modify('next sunday 23:59:59');
        }

        $calendar = [];
        for ($currentDate = $firstDate; $currentDate <= $lastDate; $currentDate->modify('+1 day')) {
            $startOfDay = \DateTimeImmutable::createFromMutable($currentDate)->setTime(0, 0);
            $endOfDay = \DateTimeImmutable::createFromMutable($currentDate->setTime(23, 59, 59));
            $eventsForThisDay = array_filter(
                $events,
                fn (Event $event): bool => ($event->getStartsAt() >= $startOfDay && $event->getStartsAt() <= $endOfDay)
                    || ($event->getEndsAt() >= $startOfDay && $event->getEndsAt() <= $endOfDay)
            );
            // Sort events: multi-day all-day events, single-day all-day events, then other events
            usort($eventsForThisDay, function (Event $a, Event $b): int {
                if ($a->spanMultipleDays() !== $b->spanMultipleDays()) {
                    return $b->spanMultipleDays() <=> $a->spanMultipleDays();
                }

                if ($a->isAllDay() !== $b->isAllDay()) {
                    return $b->isAllDay() <=> $a->isAllDay();
                }

                return $a->getStartsAt() <=> $b->getStartsAt();
            });
            $calendar[$currentDate->format('Y-m-j')] = $eventsForThisDay;
        }

        return $this->render('event/index.html.twig', [
            'month' => $month,
            'year' => $year,
            'calendar' => $calendar,
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/events/{slug}.ics', name: 'app_event_ics')]
    public function ics(string $slug, EventRepository $eventRepository): Response
    {
        $this->assertHasValidLicense();

        $event = $eventRepository->findBySlug($slug);

        if (!$event instanceof Event) {
            throw $this->createNotFoundException();
        }

        $ics = IcsFactory::new($event->getTitle())
            ->setStart($event->getStartsAt())
            ->setEnd($event->getEndsAt())
            ->setLocation($event->getAddress())
            ->setAllDay($event->isAllDay())
            ->getICS();

        return new Response($ics, Response::HTTP_OK, [
            'Content-Type' => 'text/calendar',
            'Content-Disposition' => \sprintf('attachment; filename="%s.ics"', $event->getSlug()),
        ]);
    }

    #[Route('/events/attachments/{attachment}', name: 'events_attachments_download')]
    public function attachmentDownload(
        Request $request,
        EventAttachment $attachment,
        FilesystemOperator $eventsStorage
    ): Response {
        $this->assertHasValidLicense();

        $forceDownload = $request->query->get('forceDownload');
        $contentDisposition = \sprintf(
            '%s; filename="%s"',
            $forceDownload ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $attachment->getFile()->getName()
        );

        if (!$eventsStorage->fileExists($attachment->getFile()->getName())) {
            throw $this->createNotFoundException();
        }

        $response = new StreamedResponse(function () use ($eventsStorage, $attachment): void {
            $outputStream = fopen('php://output', 'w');
            $fileStream = $eventsStorage->readStream($attachment->getFile()->getName());

            stream_copy_to_stream($fileStream, $outputStream);
        }, Response::HTTP_OK, [
            'Content-Type' => $attachment->getFile()->getMimeType(),
            'Content-Disposition' => $contentDisposition,
            'Content-Length' => $attachment->getFile()->getSize(),
        ]);
        $response->setLastModified($attachment->getUpdatedAt());

        return $response;
    }

    #[Route('/events/{slug}/attachments/{attachmentType}/edit', name: 'events_attachment_edit')]
    public function attachmentEdit(
        string $slug,
        string $attachmentType,
        Request $request,
        EntityManagerInterface $entityManager,
        RouterInterface $router
    ): Response {
        $this->assertHasValidLicense();

        EventAttachmentType::assertValidChoice($attachmentType);

        /** @var EventRepository $eventRepository */
        $eventRepository = $entityManager->getRepository(Event::class);
        $event = $eventRepository->findBySlug($slug);

        if (!$event) {
            throw $this->createNotFoundException();
        }

        /** @var EventAttachmentRepository $eventAttachmentRepository */
        $eventAttachmentRepository = $entityManager->getRepository(EventAttachment::class);
        $attachment = $eventAttachmentRepository->findAttachmentForEvent($event, $attachmentType);
        if (!$attachment) {
            $attachment = new EventAttachment();
            $attachment
                ->setEvent($event)
                ->setType($attachmentType);
            $entityManager->persist($attachment);
        }

        $form = $this->createForm(EventMandateType::class, $attachment, [
            'action' => $router->generate('events_attachment_edit', [
                'slug' => $event->getSlug(),
                'attachmentType' => $attachmentType,
            ]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $attachment->getUploadedFile()) {
                $entityManager->remove($attachment);
            }

            if (null === $attachment->getId()) {
                $entityManager->flush();
                $attachment->setUpdatedAt(new \DateTimeImmutable());
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_event_show', ['slug' => $event->getSlug()]);
        }

        return $this->render('event/mandate_edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/events/{slug}')]
    public function show(
        string $slug,
        EntityManagerInterface $entityManager,
        Request $request,
        EventHelper $eventHelper,
        LicenseeHelper $licenseeHelper,
    ): Response {
        $this->assertHasValidLicense();

        /** @var EventRepository $eventRepository */
        $eventRepository = $entityManager->getRepository(Event::class);
        $event = $eventRepository->findBySlug($slug);

        if (!$event) {
            throw $this->createNotFoundException();
        }

        $eventParticipation = $eventHelper->licenseeParticipationToEvent(
            $licenseeHelper->getLicenseeFromSession(),
            $event
        );
        $eventParticipationForm = $this->createForm(EventParticipationType::class, $eventParticipation, [
            'license_context' => $licenseeHelper
                ->getLicenseeFromSession()
                ->getLicenseForSeason(Season::seasonForDate($event->getStartsAt())),
        ]);

        $eventParticipationForm->handleRequest($request);
        if ($eventParticipationForm->isSubmitted() && $eventParticipationForm->isValid()) {
            if (null === $eventParticipation->getId() || 0 === $eventParticipation->getId()) {
                $entityManager->persist($eventParticipation);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_event_show', ['slug' => $event->getSlug()]);
        }

        $modalTemplate = $request->query->getBoolean('modal');
        $templateSuffix = match ($event::class) {
            ContestEvent::class, HobbyContestEvent::class => '_contest',
            TrainingEvent::class, FreeTrainingEvent::class => '_training',
            default => '_default',
        };
        $template = $modalTemplate ? 'event/show.modal.html.twig' : \sprintf('event/show%s.html.twig', $templateSuffix);

        return $this->render($template, [
            'event' => $event,
            'event_participation_form' => $eventParticipationForm,
        ]);
    }

    #[Route('/events/{slug}/results/edit')]
    public function resultsEdit(
        string $slug,
        Request $request,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
    ): Response {
        $this->assertHasValidLicense();

        /** @var ContestEventRepository $contestEventRepository */
        $contestEventRepository = $entityManager->getRepository(ContestEvent::class);
        /** @var ContestEvent $event */
        $event = $contestEventRepository->findBySlug($slug);

        /** @var ResultRepository $resultRepository */
        $resultRepository = $entityManager->getRepository(Result::class);
        $results = $resultRepository->findBy([
            'event' => $event,
        ]);

        foreach ($event->getParticipations() as $participation) {
            if (EventParticipationStateType::NOT_GOING === $participation->getParticipationState()) {
                continue;
            }

            $foundResult = false;
            foreach ($results as $result) {
                if ($result->getLicensee()->getId() === $participation->getParticipant()->getId()) {
                    $foundResult = true;
                }
            }

            if (!$foundResult) {
                $season = Season::seasonForDate($event->getStartsAt());
                $license = $participation->getParticipant()->getLicenseForSeason($season);
                $licensee = $participation->getParticipant();

                [$distance, $size] = Result::distanceForContestAndActivity(
                    $event,
                    $participation->getActivity(),
                    $license->getAgeCategory(),
                );

                $result = new Result();
                $result
                    ->setLicensee($licensee)
                    ->setAgeCategory($license->getAgeCategory())
                    ->setEvent($event)
                    ->setDiscipline($event->getDiscipline())
                    ->setActivity($participation->getActivity())
                    ->setDistance($distance)
                    ->setTargetSize($size)
                    ->setTargetType(TargetTypeType::MONOSPOT);
                $entityManager->persist($result);

                $results[] = $result;
            }
        }

        usort($results, function (Result $a, Result $b): int {
            if ($a->getAgeCategory() === $b->getAgeCategory()) {
                return $a->getLicensee()->getFullname() <=> $b->getLicensee()->getFullname();
            }

            $choices = array_values(LicenseAgeCategoryType::getOrderedChoices());

            return array_search($a->getAgeCategory(), $choices, true) <=> array_search($b->getAgeCategory(), $choices, true);
        });

        $resultsForm = $this->createForm(
            EventResultsType::class,
            ['licensees_results' => $results],
            ['action' => $router->generate('app_event_resultsedit', ['slug' => $event->getSlug()])],
        );
        $resultsForm->handleRequest($request);

        if ($resultsForm->isSubmitted() && $resultsForm->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', _('Résultats enregistrés'));

            return $this->redirectToRoute('app_event_show', ['slug' => $event->getSlug()]);
        }

        return $this->render('event/results_edit.html.twig', [
            'event' => $event,
            'results_form' => $resultsForm,
        ]);
    }
}
