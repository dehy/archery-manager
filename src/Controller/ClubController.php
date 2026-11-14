<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Entity\FreeTrainingEvent;
use App\Entity\Licensee;
use App\Entity\TrainingEvent;
use App\Entity\User;
use App\Form\ClubRegistrationSettingsType;
use App\Helper\ClubHelper;
use App\Repository\EventRepository;
use App\Repository\GroupRepository;
use App\Repository\LicenseeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ClubController extends BaseController
{
    public function __construct(\App\Helper\LicenseeHelper $licenseeHelper, \App\Helper\SeasonHelper $seasonHelper, private readonly ClubHelper $clubHelper, private readonly GroupRepository $groupRepository, private readonly LicenseeRepository $licenseeRepository, private readonly EntityManagerInterface $entityManager, private readonly EventRepository $eventRepository)
    {
        parent::__construct($licenseeHelper, $seasonHelper);
    }

    #[Route('/my-club', name: 'app_club_show')]
    public function show(): Response
    {
        $this->assertHasValidLicense();
        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club) {
            throw $this->createNotFoundException('Aucun club actif trouvé');
        }

        $season = $this->seasonHelper->getSelectedSeason();
        // Récupération des groupes du club
        $groups = $this->groupRepository->findBy(['club' => $club], ['name' => 'ASC']);
        // Récupération des licenciés du club pour l'année courante
        $licensees = new ArrayCollection(
            $this->licenseeRepository->findByLicenseYear($club, $season),
        );
        /** @var ArrayCollection<int, Licensee> $orderedLicensees */
        $orderedLicensees = $licensees->matching(
            Criteria::create()->orderBy([
                'firstname' => Order::Ascending,
                'lastname' => Order::Ascending,
            ]),
        );
        // Compter les licenciés sans groupe
        $noGroupLicenseesCount = $licensees->filter(static fn ($licensee) => $licensee->getGroups()->isEmpty())->count();

        $parameters = [
            'club' => $club,
            'groups' => $groups,
            'licensees' => $orderedLicensees,
            'season' => $season,
            'noGroupLicenseesCount' => $noGroupLicenseesCount,
        ];
        $template = 'club/show.html.twig';

        /** @var User $user */
        $user = $this->getUser();
        if ($user->hasNewInterface()) {
            $template = 'club/show_next.html.twig';
            // The club's trainings of the coming week stand for its training times (the club has no schedule field)
            $now = new \DateTimeImmutable();
            $parameters['trainings'] = array_values(array_filter(
                $this->eventRepository->findInRangeForClubs([$club], $now, $now->modify('+7 days')),
                static fn (Event $event): bool => $event->getClub() === $club
                    && ($event instanceof TrainingEvent || $event instanceof FreeTrainingEvent),
            ));
        }

        return $this->render($template, $parameters);
    }

    #[Route('/my-club/settings', name: 'app_club_settings', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLUB_ADMIN')]
    public function settings(Request $request): Response
    {
        $this->assertHasValidLicense();
        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club) {
            throw $this->createNotFoundException('Aucun club actif trouvé');
        }

        $form = $this->createForm(ClubRegistrationSettingsType::class, $club);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Les paramètres d’inscription ont été enregistrés.');

            return $this->redirectToRoute('app_club_settings');
        }

        return $this->render('club/settings.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }
}
