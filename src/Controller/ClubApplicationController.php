<?php

declare(strict_types=1);

namespace App\Controller;

use App\DBAL\Types\ClubApplicationStatusType;
use App\Entity\ClubApplication;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\User;
use App\Form\ClubApplicationProcessType;
use App\Form\ClubApplicationType;
use App\Form\FftaMemberCodeType;
use App\Form\Type\LicenseFormType;
use App\Helper\EmailHelper;
use App\Helper\FftaHelper;
use App\Helper\LicenseeHelper;
use App\Helper\LicenseHelper;
use App\Helper\SeasonHelper;
use App\Repository\ClubApplicationRepository;
use App\Repository\LicenseeRepository;
use App\Scrapper\FftaProfile;
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
        private readonly FftaHelper $fftaHelper,
        private readonly LicenseeRepository $licenseeRepository,
        private readonly LicenseHelper $licenseHelper,
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
        $user = $this->getUser();
        \assert($user instanceof User);
        $userLicensees = $user->getLicensees();
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

    #[Route('/club-application/{id}/activate', name: 'app_club_application_activate', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLUB_ADMIN')]
    public function activate(Request $request, ClubApplication $application): Response
    {
        $this->denyAccessUnlessGranted('manage', $application);

        if (!$application->isValidated()) {
            $this->addFlash('warning', 'La demande doit être acceptée avant l’activation de la licence FFTA.');

            return $this->redirectToRoute('app_club_application_manage');
        }

        $licensee = $application->getLicensee();
        $club = $application->getClub();
        $season = $application->getSeason();
        if (!$licensee instanceof Licensee || !$club instanceof \App\Entity\Club || null === $season) {
            throw $this->createAccessDeniedException('Demande d’adhésion incomplète.');
        }

        if ($licensee->getLicenseForSeason($season) instanceof License) {
            $this->addFlash('info', 'La licence de cette demande est déjà active.');

            return $this->redirectToRoute('app_club_application_manage');
        }

        return $this->processActivation($request, $application, $licensee, $club, $season);
    }

    private function processActivation(Request $request, ClubApplication $application, Licensee $licensee, \App\Entity\Club $club, int $season): Response
    {
        $codeForm = $this->createForm(FftaMemberCodeType::class);
        $codeForm->handleRequest($request);

        $fftaProfile = null;
        $searchedCode = null;

        if ($codeForm->isSubmitted() && $codeForm->isValid()) {
            $searchedCode = strtoupper(trim((string) $codeForm->get('fftaMemberCode')->getData()));
            $fftaProfile = $this->findFftaProfile($searchedCode, $licensee, $club, $season);
        } elseif ($request->isMethod('POST') && 'confirm' === $request->request->get('activation_stage')) {
            $searchedCode = strtoupper(trim((string) $request->request->get('ffta_member_code')));
            $fftaProfile = $this->findFftaProfile($searchedCode, $licensee, $club, $season);
        }

        $licenseForm = null;
        if ($fftaProfile instanceof FftaProfile) {
            $license = $this->buildActivationLicense($licensee, $club, $season);
            $licenseForm = $this->createForm(LicenseFormType::class, $license);
            if ('confirm' === $request->request->get('activation_stage')) {
                $licenseForm->handleRequest($request);
            }

            if ($licenseForm->isSubmitted() && $licenseForm->isValid()) {
                $this->updateLicenseeFromFftaProfile($licensee, $fftaProfile);
                $this->entityManager->persist($license);
                $this->entityManager->flush();

                $this->addFlash('success', 'La licence FFTA a été activée pour cette demande.');

                return $this->redirectToRoute('app_club_application_manage');
            }
        }

        return $this->render('club_application/activate.html.twig', [
            'application' => $application,
            'codeForm' => $codeForm,
            'licenseForm' => $licenseForm,
            'fftaProfile' => $fftaProfile,
            'searchedCode' => $searchedCode,
        ]);
    }

    private function buildActivationLicense(Licensee $licensee, \App\Entity\Club $club, int $season): License
    {
        $license = new License();
        $license->setLicensee($licensee);
        $license->setClub($club);
        $license->setSeason($season);

        $mostRecentLicense = $licensee->getMostRecentLicense();
        if ($mostRecentLicense instanceof License) {
            $license->setType($mostRecentLicense->getType());
            $license->setCategory($mostRecentLicense->getCategory());
            $license->setAgeCategory($mostRecentLicense->getAgeCategory());
            $license->setActivities($mostRecentLicense->getActivities());
        } elseif ($licensee->getBirthdate() instanceof \DateTimeInterface) {
            $license->setAgeCategory($this->licenseHelper->ageCategoryForBirthdate($licensee->getBirthdate()));
            $license->setCategory($this->licenseHelper->categoryTypeForAgeCategory($license->getAgeCategory()));
        }

        return $license;
    }

    private function findFftaProfile(string $code, Licensee $licensee, \App\Entity\Club $club, int $season): ?FftaProfile
    {
        $profile = null;
        $localLicensee = $this->licenseeRepository->findOneByCode($code);
        if ($localLicensee instanceof Licensee && $localLicensee !== $licensee) {
            $this->addFlash('danger', 'Ce code FFTA est déjà associé à un autre licencié.');
        } else {
            try {
                $scrapper = $this->fftaHelper->getScrapper($club);
                $fftaId = $scrapper->findLicenseeIdFromCode($code);
                if (null === $fftaId || 0 === $fftaId) {
                    $this->addFlash('warning', 'Ce code FFTA n’a pas été trouvé.');
                } else {
                    $candidate = $scrapper->fetchLicenseeProfile($fftaId, $season);
                    if ($this->matchesLicenseeIdentity($licensee, $candidate)) {
                        $profile = $candidate;
                    } else {
                        $this->addFlash('danger', 'Les informations FFTA ne correspondent pas au candidat.');
                    }
                }
            } catch (\Throwable) {
                $this->addFlash('danger', 'Impossible de vérifier ce code FFTA pour le moment.');
            }
        }

        return $profile;
    }

    private function matchesLicenseeIdentity(Licensee $licensee, FftaProfile $profile): bool
    {
        $birthdate = $licensee->getBirthdate();

        return $birthdate instanceof \DateTimeInterface
            && $this->normalizeIdentity($licensee->getLastname()) === $this->normalizeIdentity($profile->getNom())
            && $this->normalizeIdentity($licensee->getFirstname()) === $this->normalizeIdentity($profile->getPrenom())
            && $profile->getDateNaissance() instanceof \DateTime
            && $birthdate->format('Y-m-d') === $profile->getDateNaissance()->format('Y-m-d');
    }

    private function normalizeIdentity(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function updateLicenseeFromFftaProfile(Licensee $licensee, FftaProfile $profile): void
    {
        $licensee->setFftaId($profile->getId());
        $licensee->setFftaMemberCode(strtoupper((string) $profile->getCodeAdherent()));
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
        $user = $this->getUser();
        \assert($user instanceof User);
        foreach ($user->getLicensees() as $userLicensee) {
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
