<?php

declare(strict_types=1);

namespace App\Controller;

use App\DBAL\Types\ClubApplicationStatusType;
use App\Entity\ClubApplication;
use App\Form\ClubApplicationRejectType;
use App\Form\ClubApplicationType;
use App\Helper\LicenseeHelper;
use App\Helper\SeasonHelper;
use App\Repository\ClubApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ClubApplicationController extends AbstractController
{
    private const string ERROR_ALREADY_PROCESSED = 'Cette demande a déjà été traitée.';

    public function __construct(
        private readonly LicenseeHelper $licenseeHelper,
        private readonly SeasonHelper $seasonHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClubApplicationRepository $applicationRepository,
    ) {
    }

    #[Route('/club-application/new', name: 'app_club_application_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $licensee = $this->licenseeHelper->getLicenseeFromSession();
        if (!$licensee instanceof \App\Entity\Licensee) {
            $this->addFlash('danger', 'Vous devez être un licencié pour faire une demande d\'adhésion.');

            return $this->redirectToRoute('app_homepage');
        }

        $currentSeason = $this->seasonHelper->getSelectedSeason();

        $validationResponse = $this->validateClubApplication($licensee, $currentSeason);
        if ($validationResponse instanceof Response) {
            return $validationResponse;
        }

        return $this->handleApplicationForm($request, $licensee, $currentSeason);
    }

    private function handleApplicationForm(Request $request, \App\Entity\Licensee $licensee, int $currentSeason): Response
    {
        $application = new ClubApplication();
        $application->setLicensee($licensee);
        $application->setSeason($currentSeason);

        $form = $this->createForm(ClubApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($application);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre demande d\'adhésion a été envoyée avec succès.');

            return $this->redirectToRoute('app_club_application_status');
        }

        return $this->render('club_application/new.html.twig', [
            'form' => $form,
            'application' => $application,
        ]);
    }

    private function validateClubApplication(\App\Entity\Licensee $licensee, int $currentSeason): ?Response
    {
        // Check if already has a valid license for current season
        $currentLicense = $licensee->getLicenseForSeason($currentSeason);
        if ($currentLicense instanceof \App\Entity\License) {
            $this->addFlash('info', 'Vous avez déjà une licence valide pour cette saison.');

            return $this->redirectToRoute('app_homepage');
        }

        // Check if already has a pending application for current season
        $existingApplications = $this->applicationRepository->findByLicenseeAndSeason($licensee, $currentSeason);
        $pendingApplications = array_filter(
            $existingApplications,
            static fn (ClubApplication $app): bool => $app->isPending(),
        );

        if ([] !== $pendingApplications) {
            $this->addFlash('info', 'Vous avez déjà une demande d\'adhésion en attente.');

            return $this->redirectToRoute('app_club_application_status');
        }

        return null;
    }

    #[Route('/club-application/status', name: 'app_club_application_status', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function status(): Response
    {
        $licensee = $this->licenseeHelper->getLicenseeFromSession();
        if (!$licensee instanceof \App\Entity\Licensee) {
            $this->addFlash('danger', 'Vous devez être un licencié pour consulter vos demandes.');

            return $this->redirectToRoute('app_homepage');
        }

        $currentSeason = $this->seasonHelper->getSelectedSeason();
        $applications = $this->applicationRepository->findByLicenseeAndSeason($licensee, $currentSeason);

        return $this->render('club_application/status.html.twig', [
            'applications' => $applications,
            'currentSeason' => $currentSeason,
        ]);
    }

    #[Route('/club-application/manage', name: 'app_club_application_manage', methods: ['GET'])]
    #[IsGranted('ROLE_CLUB_ADMIN')]
    public function manage(): Response
    {
        $licensee = $this->licenseeHelper->getLicenseeFromSession();
        if (!$licensee instanceof \App\Entity\Licensee) {
            throw $this->createAccessDeniedException('Vous devez être un licencié pour gérer les demandes.');
        }

        $currentSeason = $this->seasonHelper->getSelectedSeason();
        $license = $licensee->getLicenseForSeason($currentSeason);

        if (!$license instanceof \App\Entity\License) {
            throw $this->createAccessDeniedException('Vous devez avoir une licence pour gérer les demandes.');
        }

        $club = $license->getClub();
        $applications = $this->applicationRepository->findByClubAndSeason($club, $currentSeason);

        return $this->render('club_application/manage.html.twig', [
            'applications' => $applications,
            'club' => $club,
            'currentSeason' => $currentSeason,
        ]);
    }

    #[Route('/club-application/{id}/validate', name: 'app_club_application_validate', methods: ['POST'])]
    #[IsGranted('ROLE_CLUB_ADMIN')]
    public function validate(ClubApplication $application): Response
    {
        $this->denyAccessUnlessGranted('manage', $application);

        if (!$application->isPending()) {
            $this->addFlash('warning', self::ERROR_ALREADY_PROCESSED);

            return $this->redirectToRoute('app_club_application_manage');
        }

        $application->setStatus(ClubApplicationStatusType::VALIDATED);
        $application->setProcessedBy($this->getUser());

        $this->entityManager->flush();

        $this->addFlash('success', \sprintf(
            'La demande de %s a été validée.',
            $application->getLicensee()->getFirstname().' '.$application->getLicensee()->getLastname(),
        ));

        return $this->redirectToRoute('app_club_application_manage');
    }

    #[Route('/club-application/{id}/waiting-list', name: 'app_club_application_waiting_list', methods: ['POST'])]
    #[IsGranted('ROLE_CLUB_ADMIN')]
    public function waitingList(ClubApplication $application): Response
    {
        $this->denyAccessUnlessGranted('manage', $application);

        if (!$application->isPending()) {
            $this->addFlash('warning', self::ERROR_ALREADY_PROCESSED);

            return $this->redirectToRoute('app_club_application_manage');
        }

        $application->setStatus(ClubApplicationStatusType::WAITING_LIST);
        $application->setProcessedBy($this->getUser());

        $this->entityManager->flush();

        $this->addFlash('success', \sprintf(
            'La demande de %s a été mise sur liste d\'attente.',
            $application->getLicensee()->getFirstname().' '.$application->getLicensee()->getLastname(),
        ));

        return $this->redirectToRoute('app_club_application_manage');
    }

    #[Route('/club-application/{id}/reject', name: 'app_club_application_reject', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLUB_ADMIN')]
    public function reject(Request $request, ClubApplication $application): Response
    {
        $this->denyAccessUnlessGranted('manage', $application);

        if (!$application->isPending()) {
            $this->addFlash('warning', self::ERROR_ALREADY_PROCESSED);

            return $this->redirectToRoute('app_club_application_manage');
        }

        $form = $this->createForm(ClubApplicationRejectType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $application->setStatus(ClubApplicationStatusType::REJECTED);
            $application->setRejectionReason($data['rejectionReason']);
            $application->setProcessedBy($this->getUser());
            $this->entityManager->flush();

            $this->addFlash('success', \sprintf(
                'La demande de %s a été refusée.',
                $application->getLicensee()->getFirstname().' '.$application->getLicensee()->getLastname(),
            ));

            return $this->redirectToRoute('app_club_application_manage');
        }

        return $this->render('club_application/reject.html.twig', [
            'form' => $form,
            'application' => $application,
        ]);
    }
}
