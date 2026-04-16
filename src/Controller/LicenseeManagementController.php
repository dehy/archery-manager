<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Club;
use App\Entity\Group;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\User;
use App\Exception\NoActiveClubException;
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
use App\Repository\UserRepository;
use App\Validator\Constraints\UniqueUserEmail;
use App\Validator\Constraints\ValidMoveUserDestination;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[IsGranted('ROLE_CLUB_ADMIN')]
class LicenseeManagementController extends BaseController
{
    public function __construct(LicenseeHelper $licenseeHelper, SeasonHelper $seasonHelper, private readonly FftaHelper $fftaHelper, private readonly ClubHelper $clubHelper, private readonly LicenseHelper $licenseHelper, private readonly GroupRepository $groupRepository, private readonly EntityManagerInterface $entityManager, private readonly UserRepository $userRepository, private readonly ResetPasswordHelperInterface $resetPasswordHelper, private readonly MailerInterface $mailer, private readonly LoggerInterface $logger)
    {
        parent::__construct($licenseeHelper, $seasonHelper);
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

    #[Route('/licensees/manage/{id}/move-user/step1', name: 'app_licensee_move_user_step1', methods: ['GET', 'POST'])]
    public function moveUserStep1(Request $request, Licensee $licensee): Response
    {
        $adminClub = $this->clubHelper->getClubForUser($this->getUser());
        if (!$adminClub instanceof Club) {
            $this->addFlash('danger', 'Impossible de déterminer votre club.');

            return $this->redirectToRoute('app_licensee_index');
        }

        try {
            $licenseeClub = $this->clubHelper->activeClubFor($licensee);
        } catch (NoActiveClubException) {
            $this->addFlash('danger', 'Ce licencié n\'appartient à aucun club actif.');

            return $this->redirectToRoute('app_licensee_profile', ['id' => $licensee->getId()]);
        }

        if ($adminClub !== $licenseeClub) {
            $this->addFlash('danger', 'Vous ne pouvez pas gérer ce licencié.');

            return $this->redirectToRoute('app_licensee_profile', ['id' => $licensee->getId()]);
        }

        $currentUserId = $licensee->getUser()?->getId();

        $form = $this->createFormBuilder(null, [
            'constraints' => [new ValidMoveUserDestination()],
        ])
            ->add('user_choice', ChoiceType::class, [
                'label' => 'Destination',
                'choices' => [
                    'Créer un nouveau compte' => 'new',
                    'Rattacher à un compte existant' => 'existing',
                ],
                'choice_attr' => static fn (string $value): array => 'existing' === $value
                    ? ['data-user-choice-target' => 'existingRadio']
                    : [],
                'expanded' => true,
                'required' => true,
                'data' => 'new',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email du nouveau compte',
                'required' => false,
                'attr' => ['placeholder' => 'prenom.nom@exemple.fr'],
                'constraints' => [
                    new Assert\Email(),
                    new UniqueUserEmail(),
                ],
            ])
            ->add('existing_user', EntityType::class, [
                'label' => 'Compte existant',
                'class' => User::class,
                'choice_label' => static fn (User $user): string => \sprintf('%s %s (%s)', $user->getFirstname(), $user->getLastname(), $user->getEmail()),
                'placeholder' => 'Sélectionner un compte',
                'required' => false,
                'query_builder' => static function ($repo) use ($currentUserId): \Doctrine\ORM\QueryBuilder {
                    $qb = $repo->createQueryBuilder('u');
                    if (null !== $currentUserId) {
                        $qb->where('u.id != :currentUserId')
                            ->setParameter('currentUserId', $currentUserId);
                    }

                    return $qb->orderBy('u.lastname', 'ASC')->addOrderBy('u.firstname', 'ASC');
                },
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userChoice = (string) $form->get('user_choice')->getData();
            $session = $request->getSession();

            if ('new' === $userChoice) {
                $session->set('licensee_move_user', [
                    'licensee_id' => $licensee->getId(),
                    'choice' => 'new',
                    'email' => (string) $form->get('email')->getData(),
                    'target_user_id' => null,
                ]);
            } else {
                /** @var User $targetUser */
                $targetUser = $form->get('existing_user')->getData();
                $session->set('licensee_move_user', [
                    'licensee_id' => $licensee->getId(),
                    'choice' => 'existing',
                    'email' => null,
                    'target_user_id' => $targetUser->getId(),
                ]);
            }

            return $this->redirectToRoute('app_licensee_move_user_step2', ['id' => $licensee->getId()]);
        }

        return $this->render('licensee_management/move_user_step1.html.twig', [
            'form' => $form,
            'licensee' => $licensee,
        ]);
    }

    #[Route('/licensees/manage/{id}/move-user/step2', name: 'app_licensee_move_user_step2', methods: ['GET', 'POST'])]
    public function moveUserStep2(Request $request, Licensee $licensee): Response
    {
        $session = $request->getSession();
        $moveData = $session->get('licensee_move_user', []);

        if (empty($moveData) || ($moveData['licensee_id'] ?? null) !== $licensee->getId()) {
            return $this->redirectToRoute('app_licensee_move_user_step1', ['id' => $licensee->getId()]);
        }

        $sourceUser = $licensee->getUser();
        $sourceUserLicenseeCount = $sourceUser?->getLicensees()->count() ?? 0;
        $sourceUserWillBeDeleted = 1 === $sourceUserLicenseeCount;

        $targetUser = null;
        if ('existing' === $moveData['choice']) {
            $targetUser = $this->userRepository->find((int) $moveData['target_user_id']);

            if (!$targetUser instanceof User) {
                $this->addFlash('danger', "Le compte cible n'existe plus. Veuillez recommencer.");
                $session->remove('licensee_move_user');

                return $this->redirectToRoute('app_licensee_move_user_step1', ['id' => $licensee->getId()]);
            }
        }

        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $newUser = null;

                if ('new' === $moveData['choice']) {
                    $newUser = new User();
                    $newUser->setEmail((string) $moveData['email']);
                    $newUser->setFirstname($licensee->getFirstname());
                    $newUser->setLastname($licensee->getLastname());
                    $newUser->setGender($licensee->getGender());
                    if ($licensee->getBirthdate() instanceof \DateTimeInterface) {
                        $newUser->setBirthdate(\DateTimeImmutable::createFromInterface($licensee->getBirthdate()));
                    }

                    $newUser->setRoles(['ROLE_USER']);
                    $this->entityManager->persist($newUser);
                    $licensee->setUser($newUser);
                } else {
                    if (!$targetUser instanceof User) {
                        throw new \RuntimeException('Le compte cible est introuvable.');
                    }

                    $licensee->setUser($targetUser);
                }

                if ($sourceUserWillBeDeleted && $sourceUser instanceof User) {
                    // Detach licensee from the source user's collection before removing
                    // the user, to prevent cascade: ['remove'] from also deleting the
                    // (now-reassigned) licensee.
                    $sourceUser->removeLicensee($licensee);
                    $this->entityManager->remove($sourceUser);
                }

                $this->entityManager->flush();

                if ($newUser instanceof User) {
                    try {
                        $resetToken = $this->resetPasswordHelper->generateResetToken($newUser);
                        $resetEmail = new TemplatedEmail()
                            ->from(new Address('noreply@admds.net', 'Les Archers de Guyenne'))
                            ->to($newUser->getEmail())
                            ->subject('Créez votre mot de passe — Archery Manager')
                            ->htmlTemplate('reset_password/email.html.twig')
                            ->context(['resetToken' => $resetToken]);
                        $this->mailer->send($resetEmail);
                    } catch (ResetPasswordExceptionInterface|\Throwable $e) {
                        \Sentry\captureException($e);
                        $this->addFlash('warning', 'Le compte a été créé mais l\'email d\'invitation n\'a pas pu être envoyé.');
                    }
                }

                $session->remove('licensee_move_user');
                $this->addFlash('success', \sprintf('Le licencié %s a été déplacé avec succès.', $licensee->getFullname()));

                return $this->redirectToRoute('app_licensee_profile', ['id' => $licensee->getId()]);
            } catch (\Exception $e) {
                $errorReference = bin2hex(random_bytes(8));
                $this->logger->error('Failed to move licensee user', [
                    'reference' => $errorReference,
                    'licensee_id' => $licensee->getId(),
                    'exception' => $e,
                ]);
                \Sentry\captureException($e);
                $this->addFlash('danger', \sprintf('Une erreur est survenue (réf. : %s).', $errorReference));
            }
        }

        return $this->render('licensee_management/move_user_step2.html.twig', [
            'form' => $form,
            'licensee' => $licensee,
            'move_data' => $moveData,
            'source_user' => $sourceUser,
            'target_user' => $targetUser,
            'source_user_will_be_deleted' => $sourceUserWillBeDeleted,
        ]);
    }
}
