<?php

declare(strict_types=1);

namespace App\Controller;

use App\DBAL\Types\LicenseApplicationStatusType;
use App\Entity\LicenseApplication;
use App\Form\LicenseApplicationRejectType;
use App\Form\LicenseApplicationType;
use App\Helper\LicenseeHelper;
use App\Helper\LicenseHelper;
use App\Helper\SeasonHelper;
use App\Repository\LicenseApplicationRepository;
use App\Repository\LicenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/license-application')]
class LicenseApplicationController extends AbstractController
{
    public function __construct(
        private readonly LicenseeHelper $licenseeHelper,
        private readonly LicenseHelper $licenseHelper,
        private readonly SeasonHelper $seasonHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly LicenseApplicationRepository $applicationRepository,
        private readonly LicenseRepository $licenseRepository,
    ) {
    }

    #[Route('/new', name: 'app_license_application_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $licensee = $this->licenseeHelper->getLicenseeFromSession();
        if (!$licensee instanceof \App\Entity\Licensee) {
            $this->addFlash('danger', 'Vous devez être un licencié pour faire une demande de licence.');

            return $this->redirectToRoute('app_homepage');
        }

        $currentSeason = $this->seasonHelper->getSelectedSeason();

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
            static fn (LicenseApplication $app): bool => $app->isPending(),
        );

        if ([] !== $pendingApplications) {
            $this->addFlash('info', 'Vous avez déjà une demande de licence en attente.');

            return $this->redirectToRoute('app_license_application_status');
        }

        $application = new LicenseApplication();
        $application->setLicensee($licensee);
        $application->setSeason($currentSeason);

        $form = $this->createForm(LicenseApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($application);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre demande de licence a été envoyée avec succès.');

            return $this->redirectToRoute('app_license_application_status');
        }

        return $this->render('license_application/new.html.twig', [
            'form' => $form,
            'application' => $application,
        ]);
    }

    #[Route('/status', name: 'app_license_application_status', methods: ['GET'])]
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

        return $this->render('license_application/status.html.twig', [
            'applications' => $applications,
            'currentSeason' => $currentSeason,
        ]);
    }

    #[Route('/manage', name: 'app_license_application_manage', methods: ['GET'])]
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

        return $this->render('license_application/manage.html.twig', [
            'applications' => $applications,
            'club' => $club,
            'currentSeason' => $currentSeason,
        ]);
    }

    #[Route('/{id}/validate', name: 'app_license_application_validate', methods: ['POST'])]
    #[IsGranted('ROLE_CLUB_ADMIN')]
    public function validate(LicenseApplication $application): Response
    {
        $this->denyAccessUnlessGranted('manage', $application);

        if (!$application->isPending()) {
            $this->addFlash('warning', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('app_license_application_manage');
        }

        $application->setStatus(LicenseApplicationStatusType::VALIDATED);
        $application->setProcessedBy($this->getUser());

        $this->entityManager->flush();

        $this->addFlash('success', \sprintf(
            'La demande de %s a été validée.',
            $application->getLicensee()->getFirstname().' '.$application->getLicensee()->getLastname(),
        ));

        return $this->redirectToRoute('app_license_application_manage');
    }

    #[Route('/{id}/waiting-list', name: 'app_license_application_waiting_list', methods: ['POST'])]
    #[IsGranted('ROLE_CLUB_ADMIN')]
    public function waitingList(LicenseApplication $application): Response
    {
        $this->denyAccessUnlessGranted('manage', $application);

        if (!$application->isPending()) {
            $this->addFlash('warning', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('app_license_application_manage');
        }

        $application->setStatus(LicenseApplicationStatusType::WAITING_LIST);
        $application->setProcessedBy($this->getUser());

        $this->entityManager->flush();

        $this->addFlash('success', \sprintf(
            'La demande de %s a été mise sur liste d\'attente.',
            $application->getLicensee()->getFirstname().' '.$application->getLicensee()->getLastname(),
        ));

        return $this->redirectToRoute('app_license_application_manage');
    }

    #[Route('/{id}/reject', name: 'app_license_application_reject', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLUB_ADMIN')]
    public function reject(Request $request, LicenseApplication $application): Response
    {
        $this->denyAccessUnlessGranted('manage', $application);

        if (!$application->isPending()) {
            $this->addFlash('warning', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('app_license_application_manage');
        }

        $form = $this->createForm(LicenseApplicationRejectType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $application->setStatus(LicenseApplicationStatusType::REJECTED);
            $application->setRejectionReason($data['rejectionReason']);
            $application->setProcessedBy($this->getUser());
            $this->entityManager->flush();

            $this->addFlash('success', \sprintf(
                'La demande de %s a été refusée.',
                $application->getLicensee()->getFirstname().' '.$application->getLicensee()->getLastname(),
            ));

            return $this->redirectToRoute('app_license_application_manage');
        }

        return $this->render('license_application/reject.html.twig', [
            'form' => $form,
            'application' => $application,
        ]);
    }
}
