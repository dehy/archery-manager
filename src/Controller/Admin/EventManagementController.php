<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DBAL\Types\EventType;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\FreeTrainingEvent;
use App\Entity\HobbyContestEvent;
use App\Entity\TrainingEvent;
use App\Form\ContestEventType;
use App\Form\FreeTrainingEventType;
use App\Form\TrainingEventType;
use App\Helper\ClubHelper;
use App\Repository\EventRepository;
use App\Repository\GroupRepository;
use App\Security\Voter\EventVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class EventManagementController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClubHelper $clubHelper,
        private readonly GroupRepository $groupRepository,
    ) {
    }

    #[Route('/admin/events', name: 'app_admin_events_index')]
    public function index(Request $request): Response
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $events = $eventRepository->createQueryBuilder('e')
            ->where('e.club = :club')
            ->setParameter('club', $this->clubHelper->activeClub())
            ->orderBy('e.startsAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $totalEvents = $eventRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.club = :club')
            ->setParameter('club', $this->clubHelper->activeClub())
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = ceil($totalEvents / $limit);

        return $this->render('admin/events/index.html.twig', [
            'events' => $events,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalEvents' => $totalEvents,
        ]);
    }

    #[Route('/admin/events/create', name: 'app_admin_events_create')]
    public function create(): Response
    {
        return $this->render('admin/events/create.html.twig', [
            'eventTypes' => EventType::getChoices(),
        ]);
    }

    #[Route('/admin/events/create/{type}', name: 'app_admin_events_create_type')]
    public function createType(string $type, Request $request): Response
    {
        $event = $this->createEventInstance($type);
        $event->setClub($this->clubHelper->activeClub());

        $form = $this->createForm($this->getFormTypeForEvent($event), $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'événement a été créé avec succès.');

            return $this->redirectToRoute('app_admin_events_index');
        }

        return $this->render('admin/events/form.html.twig', [
            'form' => $form,
            'event' => $event,
            'eventType' => $type,
            'isEdit' => false,
        ]);
    }

    #[Route('/admin/events/{id}/edit', name: 'app_admin_events_edit')]
    public function edit(Event $event, Request $request): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);

        $form = $this->createForm($this->getFormTypeForEvent($event), $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'événement a été modifié avec succès.');

            return $this->redirectToRoute('app_admin_events_index');
        }

        return $this->render('admin/events/form.html.twig', [
            'form' => $form,
            'event' => $event,
            'eventType' => $this->getEventTypeString($event),
            'isEdit' => true,
        ]);
    }

    #[Route('/admin/events/{id}/delete', name: 'app_admin_events_delete', methods: ['POST'])]
    public function delete(Event $event, Request $request): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::DELETE, $event);

        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($event);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'événement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_events_index');
    }

    private function createEventInstance(string $type): Event
    {
        return match ($type) {
            'contest' => new ContestEvent(),
            'hobby_contest' => new HobbyContestEvent(),
            'training' => new TrainingEvent(),
            'free_training' => new FreeTrainingEvent(),
            default => new Event(),
        };
    }

    private function getFormTypeForEvent(Event $event): string
    {
        return match ($event::class) {
            ContestEvent::class, HobbyContestEvent::class => ContestEventType::class,
            TrainingEvent::class => TrainingEventType::class,
            FreeTrainingEvent::class => FreeTrainingEventType::class,
            default => TrainingEventType::class,
        };
    }

    private function getEventTypeString(Event $event): string
    {
        return match ($event::class) {
            ContestEvent::class => 'contest',
            HobbyContestEvent::class => 'hobby_contest',
            TrainingEvent::class => 'training',
            FreeTrainingEvent::class => 'free_training',
            default => 'other',
        };
    }
}
