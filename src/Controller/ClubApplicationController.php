<?php

declare(strict_types=1);

namespace App\Controller;

use App\DBAL\Types\ClubApplicationStatusType;
use App\Entity\ClubApplication;
use App\Form\ClubApplicationProcessType;
use App\Form\ClubApplicationType;
use App\Helper\EmailHelper;
use App\Helper\LicenseeHelper;
use App\Helper\SeasonHelper;
use App\Repository\ClubApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
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
        private readonly EmailHelper $emailHelper,
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
        $userLicensees = $this->getUser()->getLicensees();
        $showLicenseeSelector = \count($userLicensees) > 1;

        $validationResponse = $this->validateClubApplication($licensee, $currentSeason);
        if ($validationResponse instanceof Response) {
            return $validationResponse;
        }

        return $this->handleApplicationForm($request, $licensee, $currentSeason, $showLicenseeSelector, $userLicensees);
    }

    /**
     * @param iterable<\App\Entity\Licensee> $userLicensees
     */
    private function handleApplicationForm(
        Request $request,
        \App\Entity\Licensee $licensee,
        int $currentSeason,
        bool $showLicenseeSelector,
        iterable $userLicensees,
    ): Response {
        $application = new ClubApplication();
        $application->setLicensee($licensee);
        $application->setSeason($currentSeason);

        $form = $this->createForm(ClubApplicationType::class, $application, [
            'show_licensee_selector' => $showLicenseeSelector,
            'user_licensees' => $userLicensees,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $chosenLicensee = $application->getLicensee();

            if ($showLicenseeSelector && $chosenLicensee !== $licensee) {
                $activeApplications = $this->applicationRepository->findActiveByLicensee($chosenLicensee, $currentSeason);
                if ([] !== $activeApplications) {
                    $this->addFlash('warning', \sprintf(
                        '%s %s a déjà une demande d\'adhésion en cours. Veuillez d\'abord annuler la demande existante.',
                        $chosenLicensee->getFirstname(),
                        $chosenLicensee->getLastname(),
                    ));

                    return $this->redirectToRoute('app_club_application_status');
                }
            }

            $this->entityManager->persist($application);
            $this->entityManager->flush();

            try {
                $this->emailHelper->sendClubApplicationNewEmail($application);
            } catch (TransportExceptionInterface) {
                // Non-blocking: email failure should not prevent the application from being saved
            }

            $this->addFlash('success', 'Votre demande d\'adhésion a été envoyée avec succès.');

            return $this->redirectToRoute('app_club_application_status');
        }

        return $this->render('club_application/new.html.twig', [
            'form' => $form,
            'application' => $application,
            'showLicenseeSelector' => $showLicenseeSelector,
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

        // Check if already has an active (pending or waiting_list) application for current season
        $activeApplications = $this->applicationRepository->findActiveByLicensee($licensee, $currentSeason);
        if ([] !== $activeApplications) {
            $this->addFlash('info', 'Vous avez déjà une demande d\'adhésion en cours. Vous pouvez annuler celle-ci pour en soumettre une nouvelle.');

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

    #[Route('/club-application/{id}/validate', name: 'app_club_application_validate', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLUB_ADMIN')]
    public function validate(Request $request, ClubApplication $application): Response
    {
        $this->denyAccessUnlessGranted('manage', $application);

        if (!$application->isPending()) {
            $this->addFlash('warning', self::ERROR_ALREADY_PROCESSED);

            return $this->redirectToRoute('app_club_application_manage');
        }

        $form = $this->createForm(ClubApplicationProcessType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $application->setStatus(ClubApplicationStatusType::VALIDATED);
            $application->setAdminMessage($data['adminMessage'] ?? null);
            $application->setProcessedBy($this->getUser());

            $this->entityManager->flush();

            try {
                $this->emailHelper->sendClubApplicationValidatedEmail($application);
            } catch (TransportExceptionInterface) {
                // Non-blocking
            }

            $this->addFlash('success', \sprintf(
                'La demande de %s a été validée.',
                $application->getLicensee()->getFirstname().' '.$application->getLicensee()->getLastname(),
            ));

            return $this->redirectToRoute('app_club_application_manage');
        }

        return $this->render('club_application/validate.html.twig', [
            'form' => $form,
            'application' => $application,
        ]);
    }

    #[Route('/club-application/{id}/waiting-list', name: 'app_club_application_waiting_list', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLUB_ADMIN')]
    public function waitingList(Request $request, ClubApplication $application): Response
    {
        $this->denyAccessUnlessGranted('manage', $application);

        if (!$application->isPending()) {
            $this->addFlash('warning', self::ERROR_ALREADY_PROCESSED);

            return $this->redirectToRoute('app_club_application_manage');
        }

        $form = $this->createForm(ClubApplicationProcessType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $application->setStatus(ClubApplicationStatusType::WAITING_LIST);
            $application->setAdminMessage($data['adminMessage'] ?? null);
            $application->setProcessedBy($this->getUser());

            $this->entityManager->flush();

            try {
                $this->emailHelper->sendClubApplicationWaitingListEmail($application);
            } catch (TransportExceptionInterface) {
                // Non-blocking
            }

            $this->addFlash('success', \sprintf(
                'La demande de %s a été mise sur liste d\'attente.',
                $application->getLicensee()->getFirstname().' '.$application->getLicensee()->getLastname(),
            ));

            return $this->redirectToRoute('app_club_application_manage');
        }

        return $this->render('club_application/waiting_list.html.twig', [
            'form' => $form,
            'application' => $application,
        ]);
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

        $form = $this->createForm(ClubApplicationProcessType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $application->setStatus(ClubApplicationStatusType::REJECTED);
            $application->setAdminMessage($data['adminMessage'] ?? null);
            $application->setProcessedBy($this->getUser());
            $this->entityManager->flush();

            try {
                $this->emailHelper->sendClubApplicationRejectedEmail($application);
            } catch (TransportExceptionInterface) {
                // Non-blocking
            }

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

    #[Route('/club-application/{id}/cancel', name: 'app_club_application_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(Request $request, ClubApplication $application): Response
    {
        $belongsToUser = false;
        foreach ($this->getUser()->getLicensees() as $userLicensee) {
            if ($userLicensee->getId() === $application->getLicensee()->getId()) {
                $belongsToUser = true;
                break;
            }
        }

        if (!$belongsToUser) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas annuler cette demande.');
        }

        if (!$this->isCsrfTokenValid('cancel_application_'.$application->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_club_application_status');
        }

        if (!$application->isPending()) {
            $this->addFlash('warning', 'Seule une demande en attente peut être annulée.');

            return $this->redirectToRoute('app_club_application_status');
        }

        $application->setStatus(ClubApplicationStatusType::CANCELLED);
        $this->entityManager->flush();

        $this->addFlash('success', \sprintf(
            'Votre demande d\'adhésion au club %s a été annulée.',
            $application->getClub()->getName(),
        ));

        return $this->redirectToRoute('app_club_application_status');
    }
}
