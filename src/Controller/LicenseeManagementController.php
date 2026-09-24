<?php

declare(strict_types=1);

namespace App\Controller;

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
use App\Form\Type\FftaLicenseeCsvUploadType;
use App\Helper\ClubHelper;
use App\Helper\FftaHelper;
use App\Helper\LicenseeHelper;
use App\Helper\LicenseHelper;
use App\Helper\SeasonHelper;
use App\Repository\GroupRepository;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use App\Security\Voter\LicenseeVoter;
use App\Service\AccountActivationEmailSender;
use App\Service\FftaLicenseeCsvImportService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLUB_ADMIN')]
class LicenseeManagementController extends BaseController
{
    public function __construct(LicenseeHelper $licenseeHelper, SeasonHelper $seasonHelper, private readonly FftaHelper $fftaHelper, private readonly ClubHelper $clubHelper, private readonly LicenseHelper $licenseHelper, private readonly GroupRepository $groupRepository, private readonly LicenseeRepository $licenseeRepository, private readonly UserRepository $userRepository, private readonly FftaLicenseeCsvImportService $csvImportService, private readonly AccountActivationEmailSender $accountActivationEmailSender, private readonly EntityManagerInterface $entityManager, private readonly LoggerInterface $logger)
    {
        parent::__construct($licenseeHelper, $seasonHelper);
    }

    /**
     * Search for an existing licensee by FFTA member code before falling back
     * to full creation. Looking up locally first (instead of browsing all
     * members) keeps club admins scoped to people who have a real link to
     * their club.
     */
    #[Route('/licensees/manage/new', name: 'app_licensee_new_choice', methods: ['GET', 'POST'])]
    public function newChoice(Request $request): Response
    {
        if (!$request->isMethod('POST')) {
            return $this->render('licensee_management/choice.html.twig');
        }

        $fftaMemberCode = strtoupper(trim((string) $request->request->get('ffta_member_code')));

        if ('' === $fftaMemberCode) {
            return $this->redirectToRoute('app_licensee_new_manual');
        }

        return $this->resolveLicenseeSearch($fftaMemberCode);
    }

    #[Route('/licensees/manage/import', name: 'app_licensee_csv_import', methods: ['GET', 'POST'])]
    public function importCsv(Request $request): Response
    {
        $form = $this->createForm(FftaLicenseeCsvUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('csv')->getData();
            if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                throw new \LogicException('Le formulaire CSV ne contient pas de fichier importé.');
            }

            try {
                $request->getSession()->set('ffta_licensee_csv_import', $this->csvImportService->createPreview($file));
            } catch (\RuntimeException $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirectToRoute('app_licensee_csv_import');
            }

            return $this->redirectToRoute('app_licensee_csv_import_review');
        }

        return $this->render('licensee_management/csv_import.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/licensees/manage/import/review', name: 'app_licensee_csv_import_review', methods: ['GET', 'POST'])]
    public function reviewCsvImport(Request $request): Response
    {
        $preview = $request->getSession()->get('ffta_licensee_csv_import');
        if (!is_array($preview) || !isset($preview['rows'])) {
            $this->addFlash('warning', 'Importez d’abord un fichier CSV.');

            return $this->redirectToRoute('app_licensee_csv_import');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('ffta-licensee-csv-import', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            try {
                $summary = $this->persistCsvImport($preview['rows'], $request->request->all('user_choices'));
            } catch (AccessDeniedException $exception) {
                throw $exception;
            } catch (\Throwable $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirectToRoute('app_licensee_csv_import_review');
            }

            $failedActivationEmails = $this->sendActivationEmails($summary['activationUsers']);
            $request->getSession()->remove('ffta_licensee_csv_import');
            $this->addFlash('success', \sprintf(
                'Import terminé : %d licence(s), %d licencié(s) et %d compte(s) créés.',
                $summary['licenses'],
                $summary['licensees'],
                $summary['users'],
            ));
            if ($failedActivationEmails > 0) {
                $this->addFlash('warning', \sprintf(
                    '%d email(s) d’activation n’ont pas pu être envoyés. Les comptes ont bien été créés.',
                    $failedActivationEmails,
                ));
            }

            return $this->redirectToRoute('app_licensee_index');
        }

        $displayRows = array_merge($preview['rows'], $preview['alreadyLicensed'] ?? []);
        usort($displayRows, static fn (array $a, array $b): int => ((int) $a['line']) <=> ((int) $b['line']));

        return $this->render('licensee_management/csv_import_review.html.twig', [
            'preview' => $preview,
            'displayRows' => $displayRows,
        ]);
    }

    #[Route('/licensees/manage/import/users', name: 'app_licensee_csv_import_users', methods: ['GET'])]
    public function searchUsersForCsvImport(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q'));
        if (mb_strlen($query) < 2) {
            return $this->json(['users' => []]);
        }

        $users = array_map(
            static fn (User $user): array => [
                'id' => $user->getId(),
                'label' => \sprintf('%s (%s)', $user->getFullname(), $user->getEmail()),
            ],
            $this->userRepository->searchByNameOrEmail($query),
        );

        return $this->json(['users' => $users]);
    }

    /**
     * @param list<array<string, int|string|bool|null>> $rows
     * @param array<int|string, string> $userChoices
     *
     * @return array{licenses: int, licensees: int, users: int, activationUsers: list<User>}
     */
    private function persistCsvImport(array $rows, array $userChoices): array
    {
        $club = $this->clubHelper->getClubForUser($this->getUser());
        if (!$club instanceof Club) {
            throw new \LogicException('Impossible de déterminer votre club.');
        }

        $this->entityManager->beginTransaction();
        try {
            $resolvedUsers = [];
            $createdUsers = [];
            $createdLicensees = 0;
            $licensees = [];

            // Resolve the "primary" rows (those not sharing an already-resolved user) first,
            // so a "share:" row can find its target user even if it appears earlier in the
            // CSV than the row it shares an account with (e.g. a minor listed before the adult).
            $sharedIndexes = [];
            foreach ($rows as $index => $row) {
                $choice = $userChoices[$index] ?? $this->defaultUserChoice($row);
                if (str_starts_with($choice, 'share:')) {
                    $sharedIndexes[] = $index;
                    continue;
                }

                $licensees[$index] = $this->licenseeForImportRow($index, $row, $userChoices[$index] ?? null, $resolvedUsers, $createdUsers);
            }

            foreach ($sharedIndexes as $index) {
                $licensees[$index] = $this->licenseeForImportRow($index, $rows[$index], $userChoices[$index] ?? null, $resolvedUsers, $createdUsers);
            }

            foreach ($rows as $index => $row) {
                $licensee = $licensees[$index];
                if ($licensee->getLicenseForSeason((int) $row['season']) instanceof \App\Entity\License) {
                    throw new \RuntimeException(\sprintf('La ligne %d a déjà été importée ou possède désormais une licence pour cette saison.', $row['line']));
                }

                $license = new License()
                    ->setLicensee($licensee)
                    ->setClub($club)
                    ->setSeason((int) $row['season'])
                    ->setType((string) $row['type'])
                    ->setCategory((string) $row['category'])
                    ->setAgeCategory((string) $row['ageCategory'])
                    ->setActivities([(string) $row['activities']]);

                $this->entityManager->persist($license);
                if (null === $row['licenseeId']) {
                    ++$createdLicensees;
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            throw $throwable;
        }

        return [
            'licenses' => \count($rows),
            'licensees' => $createdLicensees,
            'users' => \count($createdUsers),
            'activationUsers' => $createdUsers,
        ];
    }

    /**
     * @param array<string, int|string|bool|null> $row
     * @param array<int, User> $resolvedUsers
     * @param array<int, User> $createdUsers
     */
    private function licenseeForImportRow(int $index, array $row, ?string $userChoice, array &$resolvedUsers, array &$createdUsers): Licensee
    {
        if (null !== $row['licenseeId']) {
            $licensee = $this->licenseeRepository->find($row['licenseeId']);
            if (!$licensee instanceof Licensee) {
                throw new \RuntimeException(\sprintf('Le licencié de la ligne %d n’existe plus.', $row['line']));
            }

            $this->denyAccessUnlessGranted(LicenseeVoter::RENEW, $licensee);

            return $licensee;
        }

        $user = $this->userForImportRow($row, $userChoice, $resolvedUsers, $createdUsers);
        $resolvedUsers[$index] = $user;

        $licensee = new Licensee()
            ->setFirstname((string) $row['firstname'])
            ->setLastname((string) $row['lastname'])
            ->setGender((string) $row['gender'])
            ->setBirthdate(new \DateTime((string) $row['birthdate']))
            ->setFftaMemberCode((string) $row['fftaMemberCode'])
            ->setUser($user);
        $this->entityManager->persist($licensee);

        return $licensee;
    }

    /**
     * @param array<string, int|string|bool|null> $row
     * @param array<int, User> $resolvedUsers
     * @param array<int, User> $createdUsers
     */
    private function userForImportRow(array $row, ?string $userChoice, array $resolvedUsers, array &$createdUsers): User
    {
        $choice = $userChoice ?? $this->defaultUserChoice($row);
        if ('new' === $choice && null !== $row['userId']) {
            throw new \RuntimeException(\sprintf('Un compte utilisateur existe déjà pour l’adresse email de la ligne %d.', $row['line']));
        }

        if (str_starts_with($choice, 'user:')) {
            $user = $this->userRepository->find((int) substr($choice, 5));
            if ($user instanceof User) {
                return $user;
            }

            throw new \RuntimeException(\sprintf('Le compte utilisateur choisi pour la ligne %d est introuvable.', $row['line']));
        }

        if (str_starts_with($choice, 'share:')) {
            $sharedUser = $resolvedUsers[(int) substr($choice, 6)] ?? null;
            if ($sharedUser instanceof User) {
                return $sharedUser;
            }

            throw new \RuntimeException(\sprintf('Le compte partagé choisi pour la ligne %d est invalide.', $row['line']));
        }

        if ('new' !== $choice || null === $row['email']) {
            throw new \RuntimeException(\sprintf('Le choix de compte utilisateur pour la ligne %d est invalide.', $row['line']));
        }

        foreach ($createdUsers as $createdUser) {
            if ($createdUser->getEmail() === $row['email']) {
                throw new \RuntimeException(\sprintf('L’adresse email de la ligne %d est déjà attribuée à un nouveau compte dans cet import.', $row['line']));
            }
        }

        $user = new User()
            ->setEmail((string) $row['email'])
            ->setFirstname((string) $row['firstname'])
            ->setLastname((string) $row['lastname'])
            ->setGender((string) $row['gender'])
            ->setBirthdate(new \DateTimeImmutable((string) $row['birthdate']))
            ->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $createdUsers[] = $user;

        return $user;
    }

    /**
     * @param array<string, int|string|bool|null> $row
     */
    private function defaultUserChoice(array $row): string
    {
        if (null !== $row['userId']) {
            return \sprintf('user:%d', $row['userId']);
        }

        if (isset($row['sharedUserRow'])) {
            return \sprintf('share:%d', $row['sharedUserRow']);
        }

        return 'new';
    }

    /**
     * @param list<User> $users
     */
    private function sendActivationEmails(array $users): int
    {
        $failures = 0;
        foreach ($users as $user) {
            try {
                $this->accountActivationEmailSender->send($user);
            } catch (\Throwable $throwable) {
                ++$failures;
                $this->logger->error('Unable to send imported account activation email.', [
                    'exception' => $throwable,
                    'userId' => $user->getId(),
                ]);
            }
        }

        return $failures;
    }

    private function resolveLicenseeSearch(string $fftaMemberCode): Response
    {
        $licensee = $this->licenseeRepository->findOneByCode($fftaMemberCode);

        if (!$licensee instanceof Licensee) {
            $this->addFlash('warning', 'Aucun licencié trouvé localement avec ce code. La synchronisation FFTA est temporairement indisponible.');

            return $this->render('licensee_management/choice.html.twig', [
                'not_found_code' => $fftaMemberCode,
            ]);
        }

        $club = $this->clubHelper->getClubForUser($this->getUser());

        if (!$club instanceof Club || !$licensee->hasLicenseForClub($club)) {
            // Never expose more than identity for a licensee outside the admin's club.
            return $this->render('licensee_management/foreign_notice.html.twig', [
                'licensee' => $licensee,
            ]);
        }

        return $this->redirectForClubLicensee($licensee);
    }

    private function redirectForClubLicensee(Licensee $licensee): Response
    {
        $currentSeason = $this->seasonHelper->getSelectedSeason();

        if ($licensee->getLicenseForSeason($currentSeason) instanceof License) {
            $this->addFlash('info', 'Ce licencié possède déjà une licence pour cette saison.');

            return $this->redirectToRoute('app_licensee_profile', ['id' => $licensee->getId()]);
        }

        return $this->redirectToRoute('app_licensee_renew', ['id' => $licensee->getId()]);
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
        // Initialize session for manual creation, carrying over the searched
        // code (if any) so step 1 can pre-fill it.
        $session = $request->getSession();
        $session->set('licensee_creation', [
            'from_ffta' => false,
            'prefill_ffta_member_code' => strtoupper(trim((string) $request->query->get('ffta_member_code'))) ?: null,
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
        } elseif (!empty($creationData['prefill_ffta_member_code'])) {
            $licensee->setFftaMemberCode($creationData['prefill_ffta_member_code']);
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

    #[Route('/licensees/manage/renew/{id}', name: 'app_licensee_renew', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted(LicenseeVoter::RENEW, subject: 'licensee')]
    public function renew(Licensee $licensee, Request $request): Response
    {
        $currentSeason = $this->seasonHelper->getSelectedSeason();

        // Voter already scopes access to the admin's club, but re-check the
        // season to guard against a stale link (defense in depth).
        if ($licensee->getLicenseForSeason($currentSeason) instanceof License) {
            $this->addFlash('info', 'Ce licencié possède déjà une licence pour cette saison.');

            return $this->redirectToRoute('app_licensee_profile', ['id' => $licensee->getId()]);
        }

        $license = new License();
        $license->setLicensee($licensee);

        $mostRecentLicense = $licensee->getMostRecentLicense();
        if ($mostRecentLicense instanceof License) {
            $license->setType($mostRecentLicense->getType());
            $license->setCategory($mostRecentLicense->getCategory());
            $license->setAgeCategory($mostRecentLicense->getAgeCategory());
            $license->setActivities($mostRecentLicense->getActivities());
        }

        $form = $this->createForm(LicenseFormType::class, $license);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Club and season are never taken from client input.
            $license->setClub($this->clubHelper->getClubForUser($this->getUser()));
            $license->setSeason($currentSeason);

            $this->entityManager->persist($license);
            $this->entityManager->flush();

            $this->addFlash('success', 'Licence ajoutée avec succès.');

            return $this->redirectToRoute('app_licensee_profile', ['id' => $licensee->getId()]);
        }

        return $this->render('licensee_management/renew_form.html.twig', [
            'form' => $form,
            'licensee' => $licensee,
            'season' => $currentSeason,
        ]);
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
