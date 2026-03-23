<?php

declare(strict_types=1);

namespace App\Controller;

use App\DBAL\Types\LicenseeAttachmentType;
use App\Entity\Club;
use App\Entity\Group;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\User;
use App\Exception\UserNotFoundException;
use App\Form\Type\LicenseeFormType;
use App\Form\Type\LicenseeGroupSelectionType;
use App\Form\Type\LicenseeUserLinkType;
use App\Form\Type\LicenseFormType;
use App\Helper\ClubHelper;
use App\Helper\FftaHelper;
use App\Helper\LicenseeHelper;
use App\Helper\LicenseHelper;
use App\Helper\SeasonHelper;
use App\Repository\GroupRepository;
use App\Repository\LicenseeRepository;
use App\Repository\LicenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLUB_ADMIN')]
class LicenseeManagementController extends BaseController
{
    public function __construct(LicenseeHelper $licenseeHelper, SeasonHelper $seasonHelper, private readonly FftaHelper $fftaHelper, private readonly ClubHelper $clubHelper, private readonly LicenseHelper $licenseHelper, private readonly GroupRepository $groupRepository, private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct($licenseeHelper, $seasonHelper);
    }

    #[Route('/licensees/manage/caci', name: 'app_licensee_caci', methods: ['GET'])]
    public function caciOverview(): Response
    {
        $club = $this->clubHelper->getClubForUser($this->getUser());
        if (!$club instanceof \App\Entity\Club) {
            $this->addFlash('danger', 'Impossible de déterminer votre club.');

            return $this->redirectToRoute('app_homepage');
        }

        $season = $this->seasonHelper->getSelectedSeason();
        $renewalDate = new \DateTimeImmutable(($season - 1).'-09-01');
        $threshold = $renewalDate->modify('-6 months');
        $licensees = $this->licenseeRepository->findByLicenseYear($club, $season);
        $caciData = array_map(
            fn (Licensee $licensee): array => $this->buildCaciEntry($licensee, $threshold, $season),
            $licensees,
        );
        usort($caciData, static fn (array $a, array $b): int => strcmp($a['licensee']->getFullname(), $b['licensee']->getFullname()));

        return $this->render('licensee_management/caci.html.twig', [
            'season' => $season,
            'renewalDate' => $renewalDate,
            'threshold' => $threshold,
            'caciData' => $caciData,
        ]);
    }

    private function buildCaciEntry(Licensee $licensee, \DateTimeImmutable $threshold, int $season): array
    {
        $license = $licensee->getLicenseForSeason($season);

        if ($license instanceof License && $license->isCaciExempt()) {
            return [
                'licensee' => $licensee,
                'license' => $license,
                'certificate' => null,
                'status' => 'exempt',
                'statusOrder' => 4,
            ];
        }

        $certificate = null;
        foreach ($licensee->getAttachments() as $attachment) {
            if (LicenseeAttachmentType::MEDICAL_CERTIFICATE !== $attachment->getType()) {
                continue;
            }

            if (
                !$certificate instanceof \App\Entity\LicenseeAttachment
                || ($attachment->getDocumentDate() instanceof \DateTimeImmutable
                    && $attachment->getDocumentDate() > $certificate->getDocumentDate())
            ) {
                $certificate = $attachment;
            }
        }

        $status = match (true) {
            !$certificate instanceof \App\Entity\LicenseeAttachment => 'none',
            !$certificate->getDocumentDate() instanceof \DateTimeImmutable => 'unknown',
            $certificate->getDocumentDate() >= $threshold => 'valid',
            default => 'expired',
        };

        $statusOrder = match ($status) {
            'expired' => 0,
            'none' => 1,
            'unknown' => 2,
            default => 3,
        };

        return [
            'licensee' => $licensee,
            'license' => $license,
            'certificate' => $certificate,
            'status' => $status,
            'statusOrder' => $statusOrder,
        ];
    }

    #[Route('/licensees/manage/caci/{licenseeId}/toggle-exempt', name: 'app_licensee_caci_toggle_exempt', methods: ['POST'])]
    public function toggleCaciExempt(int $licenseeId): Response
    {
        $licensee = $this->licenseeRepository->find($licenseeId);
        if (!$licensee instanceof Licensee) {
            throw $this->createNotFoundException();
        }

        $club = $this->clubHelper->getClubForUser($this->getUser());
        if (!$club instanceof \App\Entity\Club) {
            $this->addFlash('danger', 'Impossible de déterminer votre club.');

            return $this->redirectToRoute('app_homepage');
        }

        $season = $this->seasonHelper->getSelectedSeason();
        $license = $licensee->getLicenseForSeason($season);
        if (!$license instanceof License) {
            $this->addFlash('warning', 'Aucune licence trouvée pour ce licencié cette saison.');

            return $this->redirectToRoute('app_licensee_caci');
        }

        $license->setCaciExempt(!$license->isCaciExempt());
        $this->entityManager->flush();

        return $this->redirectToRoute('app_licensee_caci');
    }

    #[Route('/licensees/manage/new', name: 'app_licensee_new_choice', methods: ['GET', 'POST'])]
    public function newChoice(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $choice = $request->request->get('choice');
            $fftaMemberCode = $request->request->get('ffta_member_code');

            if ('sync' === $choice && $fftaMemberCode) {
                return $this->redirectToRoute('app_licensee_new_sync', [
                    'fftaMemberCode' => $fftaMemberCode,
                ]);
            }

            return $this->redirectToRoute('app_licensee_new_manual');
        }

        return $this->render('licensee_management/choice.html.twig');
    }

    #[Route('/licensees/manage/new/sync/{fftaMemberCode}', name: 'app_licensee_new_sync', methods: ['GET', 'POST'])]
    public function newFromFfta(
        string $fftaMemberCode,
        Request $request,
    ): Response {
        $club = $this->clubHelper->getClubForUser($this->getUser());
        if (!$club instanceof Club) {
            $this->addFlash('danger', 'Impossible de déterminer votre club.');

            return $this->redirectToRoute('app_licensee_new_choice');
        }

        try {
            return $this->processFftaImport($club, $fftaMemberCode, $request);
        } catch (\Exception $exception) {
            $this->addFlash('danger', 'Erreur lors de la synchronisation FFTA : '.$exception->getMessage());

            return $this->redirectToRoute('app_licensee_new_choice');
        }
    }

    private function processFftaImport(
        Club $club,
        string $fftaMemberCode,
        Request $request,
    ): Response {
        $scrapper = $this->fftaHelper->getScrapper($club);
        $fftaId = $scrapper->findLicenseeIdFromCode($fftaMemberCode);

        if (null === $fftaId || 0 === $fftaId) {
            $this->addFlash('danger', 'Licencié non trouvé sur le site FFTA.');

            return $this->redirectToRoute('app_licensee_new_choice');
        }

        $currentSeason = $this->seasonHelper->getSelectedSeason();
        $fftaLicensee = $scrapper->fetchLicenseeProfile($fftaId, $currentSeason);

        if (!$fftaLicensee) {
            $this->addFlash('danger', 'Impossible de récupérer les données du licencié.');

            return $this->redirectToRoute('app_licensee_new_choice');
        }

        // Store FFTA data in session for next steps
        $session = $request->getSession();
        $session->set('licensee_creation', [
            'from_ffta' => true,
            'ffta_data' => $fftaLicensee,
        ]);

        return $this->redirectToRoute('app_licensee_new_step1');
    }

    #[Route('/licensees/manage/new/manual', name: 'app_licensee_new_manual', methods: ['GET'])]
    public function newManual(Request $request): Response
    {
        // Initialize session for manual creation
        $session = $request->getSession();
        $session->set('licensee_creation', [
            'from_ffta' => false,
        ]);

        return $this->redirectToRoute('app_licensee_new_step1');
    }

    #[Route('/licensees/manage/new/step1', name: 'app_licensee_new_step1', methods: ['GET', 'POST'])]
    public function step1Licensee(
        Request $request,
    ): Response {
        $session = $request->getSession();
        $creationData = $session->get('licensee_creation', []);

        if (empty($creationData)) {
            return $this->redirectToRoute('app_licensee_new_choice');
        }

        $licensee = new Licensee();

        // Pre-fill from FFTA if available
        if (!empty($creationData['from_ffta']) && !empty($creationData['ffta_data'])) {
            $fftaData = $creationData['ffta_data'];
            $licensee->setFirstname($fftaData['firstname'] ?? '');
            $licensee->setLastname($fftaData['lastname'] ?? '');
            $licensee->setGender($fftaData['gender'] ?? '');
            $licensee->setFftaMemberCode($fftaData['memberCode'] ?? null);
            $licensee->setFftaId($fftaData['id'] ?? null);
            if (!empty($fftaData['birthdate'])) {
                $licensee->setBirthdate(new \DateTime($fftaData['birthdate']));
            }
        }

        $form = $this->createForm(LicenseeFormType::class, $licensee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save licensee data to session
            $creationData['licensee'] = [
                'firstname' => $licensee->getFirstname(),
                'lastname' => $licensee->getLastname(),
                'gender' => $licensee->getGender(),
                'birthdate' => $licensee->getBirthdate()?->format('Y-m-d'),
                'fftaMemberCode' => $licensee->getFftaMemberCode(),
                'fftaId' => $licensee->getFftaId(),
            ];
            $session->set('licensee_creation', $creationData);

            return $this->redirectToRoute('app_licensee_new_step2');
        }

        return $this->render('licensee_management/step1_licensee.html.twig', [
            'form' => $form,
            'from_ffta' => !empty($creationData['from_ffta']),
        ]);
    }

    #[Route('/licensees/manage/new/step2', name: 'app_licensee_new_step2', methods: ['GET', 'POST'])]
    public function step2License(
        Request $request,
    ): Response {
        $session = $request->getSession();
        $creationData = $session->get('licensee_creation', []);

        if (empty($creationData['licensee'])) {
            return $this->redirectToRoute('app_licensee_new_step1');
        }

        $club = $this->clubHelper->getClubForUser($this->getUser());
        $currentSeason = Season::seasonForDate(new \DateTimeImmutable());

        $license = new License();
        $license->setClub($club);
        $license->setSeason($currentSeason);

        [$suggestedAgeCategory, $suggestedCategory, $birthdateDisplay] = $this->calculateLicenseSuggestions($creationData);

        $this->prefillLicenseData($license, $creationData, $suggestedAgeCategory, $suggestedCategory);

        $form = $this->createForm(LicenseFormType::class, $license);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save license data to session
            $creationData['license'] = [
                'type' => $license->getType(),
                'category' => $license->getCategory(),
                'ageCategory' => $license->getAgeCategory(),
                'activities' => $license->getActivities(),
                'club_id' => $club->getId(),
                'season' => $currentSeason,
            ];
            $session->set('licensee_creation', $creationData);

            return $this->redirectToRoute('app_licensee_new_step3');
        }

        return $this->render('licensee_management/step2_license.html.twig', [
            'form' => $form,
            'season' => $currentSeason,
            'licensee_data' => $creationData['licensee'] ?? [],
            'birthdate_display' => $birthdateDisplay,
            'suggested_age_category' => $suggestedAgeCategory,
            'suggested_category' => $suggestedCategory,
        ]);
    }

    #[Route('/licensees/manage/new/step3', name: 'app_licensee_new_step3', methods: ['GET', 'POST'])]
    public function step3Groups(
        Request $request,
    ): Response {
        $session = $request->getSession();
        $creationData = $session->get('licensee_creation', []);

        if (empty($creationData['license'])) {
            return $this->redirectToRoute('app_licensee_new_step2');
        }

        $club = $this->clubHelper->getClubForUser($this->getUser());
        $availableGroups = $this->groupRepository->findBy(['club' => $club]);

        $form = $this->createForm(LicenseeGroupSelectionType::class, null, [
            'groups' => $availableGroups,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedGroups = $form->get('groups')->getData();
            $creationData['groups'] = array_map(static fn (Group $g): ?int => $g->getId(), $selectedGroups->toArray());
            $session->set('licensee_creation', $creationData);

            return $this->redirectToRoute('app_licensee_new_step4');
        }

        return $this->render('licensee_management/step3_groups.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/licensees/manage/new/step4', name: 'app_licensee_new_step4', methods: ['GET', 'POST'])]
    public function step4User(
        Request $request,
    ): Response {
        $session = $request->getSession();
        $creationData = $session->get('licensee_creation', []);

        if (empty($creationData['groups'])) {
            return $this->redirectToRoute('app_licensee_new_step3');
        }

        $form = $this->createForm(LicenseeUserLinkType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userChoice = $form->get('user_choice')->getData();
            $existingUser = $form->get('existing_user')->getData();
            $email = $form->get('email')->getData();

            try {
                $licensee = $this->createAndLinkLicensee(
                    $creationData,
                    $userChoice,
                    $existingUser,
                    $email,
                    $this->entityManager
                );

                // Clear session
                $session->remove('licensee_creation');

                $this->addFlash('success', 'Licencié créé avec succès.');

                return $this->redirectToRoute('app_licensee_profile', [
                    'id' => $licensee->getId(),
                ]);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la création : '.$e->getMessage());
            }
        }

        return $this->render('licensee_management/step4_user.html.twig', [
            'form' => $form,
            'licensee_data' => $creationData['licensee'],
        ]);
    }

    #[Route('/licensees/manage/cancel', name: 'app_licensee_new_cancel', methods: ['GET'])]
    public function cancel(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('licensee_creation');

        $this->addFlash('info', 'Création de licencié annulée.');

        return $this->redirectToRoute('app_licensee_index');
    }

    /**
     * Calculate license suggestions based on birthdate.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function calculateLicenseSuggestions(array $creationData): array
    {
        if (empty($creationData['licensee']['birthdate'])) {
            return [null, null, null];
        }

        try {
            $birthdate = new \DateTimeImmutable($creationData['licensee']['birthdate']);
            $birthdateDisplay = $birthdate->format('d/m/Y');
            $suggestedAgeCategory = $this->licenseHelper->ageCategoryForBirthdate($birthdate);
            $suggestedCategory = $this->licenseHelper->categoryTypeForAgeCategory($suggestedAgeCategory);

            return [$suggestedAgeCategory, $suggestedCategory, $birthdateDisplay];
        } catch (\Exception) {
            return [null, null, null];
        }
    }

    /**
     * Pre-fill license data from FFTA or suggestions.
     */
    private function prefillLicenseData(License $license, array $creationData, ?string $suggestedAgeCategory, ?string $suggestedCategory): void
    {
        if (!empty($creationData['from_ffta']) && !empty($creationData['ffta_data'])) {
            $fftaData = $creationData['ffta_data'];
            if (!empty($fftaData['license'])) {
                $licenseData = $fftaData['license'];
                $license->setType($licenseData['type'] ?? null);
                $license->setCategory($licenseData['category'] ?? null);
                $license->setAgeCategory($licenseData['ageCategory'] ?? null);
                if (!empty($licenseData['activities'])) {
                    $license->setActivities($licenseData['activities']);
                }
            }
        } elseif ($suggestedAgeCategory && $suggestedCategory) {
            $license->setAgeCategory($suggestedAgeCategory);
            $license->setCategory($suggestedCategory);
        }
    }

    /**
     * Create licensee and link to user (existing or new).
     */
    private function createAndLinkLicensee(
        array $creationData,
        string $userChoice,
        ?User $existingUser,
        ?string $email,
        EntityManagerInterface $entityManager
    ): Licensee {
        $licensee = new Licensee();
        $licensee->setFirstname($creationData['licensee']['firstname']);
        $licensee->setLastname($creationData['licensee']['lastname']);
        $licensee->setGender($creationData['licensee']['gender']);
        $licensee->setBirthdate(new \DateTime($creationData['licensee']['birthdate']));
        $licensee->setFftaMemberCode($creationData['licensee']['fftaMemberCode']);
        $licensee->setFftaId($creationData['licensee']['fftaId']);

        if ('existing' === $userChoice) {
            $user = $existingUser;
            if (!$user instanceof User) {
                throw new UserNotFoundException('Utilisateur introuvable.');
            }
        } else {
            $user = new User();
            $user->setEmail($email);
            $user->setFirstname($creationData['licensee']['firstname']);
            $user->setLastname($creationData['licensee']['lastname']);
            $user->setGender($creationData['licensee']['gender']);
            $user->setBirthdate(new \DateTimeImmutable($creationData['licensee']['birthdate']));
            $user->setRoles(['ROLE_USER']);
            $entityManager->persist($user);
        }

        $licensee->setUser($user);

        $club = $this->clubHelper->getClubForUser($this->getUser());
        $license = new License();
        $license->setLicensee($licensee);
        $license->setClub($club);
        $license->setSeason($creationData['license']['season']);
        $license->setType($creationData['license']['type']);
        $license->setCategory($creationData['license']['category']);
        $license->setAgeCategory($creationData['license']['ageCategory']);
        $license->setActivities($creationData['license']['activities']);

        $entityManager->persist($licensee);
        $entityManager->persist($license);

        foreach ($creationData['groups'] as $groupId) {
            $group = $this->groupRepository->find($groupId);
            if ($group) {
                $licensee->addGroup($group);
            }
        }

        $entityManager->flush();

        return $licensee;
    }
}
