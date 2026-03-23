<?php

declare(strict_types=1);

namespace App\Controller;

use App\DBAL\Types\EventParticipationStateType;
use App\Entity\ContestEvent;
use App\Entity\EventParticipation;
use App\Entity\Licensee;
use App\Entity\User;
use App\Factory\IcsFactory;
use App\Repository\EventParticipationRepository;
use App\Repository\LicenseeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CalendarController extends AbstractController
{
    private const string PRODID = '-//Les Archers de Bordeaux Guyenne//Archery Manager//FR';

    public function __construct(
        private readonly LicenseeRepository $licenseeRepository,
        private readonly EventParticipationRepository $eventParticipationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Personal iCal feed — no authentication required, token is the credential.
     */
    #[Route('/calendar/{token}.ics', name: 'app_calendar_feed', methods: ['GET'])]
    public function feed(string $token): Response
    {
        $licensee = $this->licenseeRepository->findOneByCalendarToken($token);

        if (!$licensee instanceof Licensee) {
            throw $this->createNotFoundException();
        }

        $participations = $this->eventParticipationRepository->findRegisteredForLicensee($licensee);

        $events = array_map(static function (EventParticipation $participation): IcsFactory {
            $event = $participation->getEvent();

            $descriptionParts = [EventParticipationStateType::getReadableValue(EventParticipationStateType::REGISTERED)];

            if ($event instanceof ContestEvent && null !== $participation->getDeparture()) {
                $descriptionParts[] = \sprintf('Départ : %d', $participation->getDeparture());
            }

            return IcsFactory::new($event->getTitle())
                ->setStart($event->getStartsAt())
                ->setEnd($event->getEndsAt())
                ->setLocation($event->getAddress() ?? '')
                ->setDescription(implode(' | ', $descriptionParts))
                ->setAllDay($event->isAllDay());
        }, $participations);

        $ics = IcsFactory::buildFeed(self::PRODID, $events);

        return new Response($ics, Response::HTTP_OK, [
            'Content-Type' => 'text/calendar; charset=utf-8',
        ]);
    }

    /**
     * Generate (or regenerate) the personal calendar token for a licensee owned by the current user.
     */
    #[Route('/my-account/calendar/{id}/generate-token', name: 'app_calendar_generate_token', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateToken(Licensee $licensee): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getLicensees()->contains($licensee)) {
            throw $this->createAccessDeniedException();
        }

        $licensee->generateCalendarToken();

        $this->entityManager->flush();

        $this->addFlash('success', 'Votre lien d\'abonnement au calendrier a été généré.');

        return $this->redirectToRoute('app_user_account');
    }

    /**
     * Revoke the personal calendar token for a licensee owned by the current user.
     */
    #[Route('/my-account/calendar/{id}/revoke-token', name: 'app_calendar_revoke_token', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function revokeToken(Licensee $licensee): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getLicensees()->contains($licensee)) {
            throw $this->createAccessDeniedException();
        }

        $licensee->revokeCalendarToken();

        $this->entityManager->flush();

        $this->addFlash('success', 'Votre lien d\'abonnement au calendrier a été révoqué.');

        return $this->redirectToRoute('app_user_account');
    }
}

