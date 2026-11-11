<?php

declare(strict_types=1);

namespace App\Controller\Management;

use App\Controller\BaseController;
use App\Entity\Club;
use App\Entity\Licensee;
use App\Entity\User;
use App\Form\Type\LicenseeAccountChangeType;
use App\Helper\LicenseeHelper;
use App\Helper\LicenseHelper;
use App\Helper\SeasonHelper;
use App\Repository\GroupRepository;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use App\Security\Voter\LicenseeVoter;
use App\Service\LicenseeAccountMover;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLUB_ADMIN')]
class MemberManagementController extends BaseController
{
    public function __construct(
        LicenseeHelper $licenseeHelper,
        SeasonHelper $seasonHelper,
        private readonly LicenseHelper $licenseHelper,
        private readonly LicenseeRepository $licenseeRepository,
        private readonly GroupRepository $groupRepository,
        private readonly UserRepository $userRepository,
        private readonly LicenseeAccountMover $accountMover,
    ) {
        parent::__construct($licenseeHelper, $seasonHelper);
    }

    #[Route('/club/members', name: 'app_club_members', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $club = $this->currentClub();
        $season = $this->seasonHelper->getSelectedSeason();

        $search = trim((string) $request->query->get('q', ''));
        $groupId = $request->query->getInt('group');
        $group = $groupId > 0 ? $this->groupRepository->findOneBy(['id' => $groupId, 'club' => $club]) : null;
        $sharedOnly = $request->query->getBoolean('shared');

        $licensees = $this->licenseeRepository->findForMemberManagement($club, $season, $search, $group, $sharedOnly);

        $userIds = array_values(array_unique(array_filter(
            array_map(static fn (Licensee $licensee): ?int => $licensee->getUser()?->getId(), $licensees),
        )));

        return $this->render('management/member/index.html.twig', [
            'licensees' => $licensees,
            'accountSizes' => $this->licenseeRepository->countByUserIds($userIds),
            'season' => $season,
            'allGroups' => $this->groupRepository->findBy(['club' => $club], ['name' => 'ASC']),
            'search' => $search,
            'selectedGroup' => $group,
            'sharedOnly' => $sharedOnly,
        ]);
    }

    #[Route('/club/members/{id}/account', name: 'app_club_member_account', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted(LicenseeVoter::CHANGE_USER, subject: 'licensee')]
    public function changeAccount(Licensee $licensee, Request $request): Response
    {
        if ($this->licenseeHelper->getLicenseeFromSession() === $licensee) {
            $this->addFlash('danger', 'Vous ne pouvez pas déplacer le licencié avec lequel vous êtes connecté.');

            return $this->redirectToRoute('app_club_members');
        }

        $club = $this->currentClub();
        $sourceUser = $licensee->getUser();
        $sourceWillBeEmpty = 1 === $sourceUser?->getLicensees()->count();

        $form = $this->createForm(LicenseeAccountChangeType::class, null, [
            'offer_account_deletion' => $sourceWillBeEmpty,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = mb_strtolower(trim((string) $form->get('email')->getData()));
            $deleteEmptied = $sourceWillBeEmpty && (bool) $form->get('delete_emptied_account')->getData();

            if (LicenseeAccountChangeType::DESTINATION_NEW === $form->get('destination')->getData()) {
                return $this->moveToNewAccount($licensee, $email, $deleteEmptied, $form);
            }

            return $this->moveToExistingAccount($licensee, $email, $deleteEmptied, $club, $form);
        }

        return $this->render('management/member/account.html.twig', [
            'form' => $form,
            'licensee' => $licensee,
            'sourceUser' => $sourceUser,
            'sourceWillBeEmpty' => $sourceWillBeEmpty,
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    private function moveToNewAccount(Licensee $licensee, string $email, bool $deleteEmptied, FormInterface $form): Response
    {
        if ($this->userRepository->findOneByEmail($email) instanceof User) {
            $form->get('email')->addError(new FormError('Cette adresse email est déjà utilisée. Choisissez « Rattacher à un compte existant ».'));

            return $this->renderAccountForm($licensee, $form);
        }

        $user = $this->accountMover->moveToNewAccount($licensee, $email, $deleteEmptied);

        if ($this->accountMover->sendInvitation($user)) {
            $this->addFlash('success', \sprintf('%s dispose désormais de son propre compte. Un email d\'invitation a été envoyé à %s.', $licensee->getFullname(), $email));
        } else {
            $this->addFlash('warning', \sprintf('%s a été déplacé sur un nouveau compte, mais l\'email d\'invitation n\'a pas pu être envoyé.', $licensee->getFullname()));
        }

        return $this->redirectToRoute('app_club_members');
    }

    private function moveToExistingAccount(Licensee $licensee, string $email, bool $deleteEmptied, Club $club, FormInterface $form): Response
    {
        $target = $this->userRepository->findOneByEmailInClub($email, $club);

        if (!$target instanceof User) {
            $form->get('email')->addError(new FormError('Aucun compte de votre club ne correspond à cet email.'));

            return $this->renderAccountForm($licensee, $form);
        }

        if ($target === $licensee->getUser()) {
            $form->get('email')->addError(new FormError('Le licencié est déjà rattaché à ce compte.'));

            return $this->renderAccountForm($licensee, $form);
        }

        $this->accountMover->moveToExistingAccount($licensee, $target, $deleteEmptied);
        $this->addFlash('success', \sprintf('%s a été rattaché au compte %s.', $licensee->getFullname(), $email));

        return $this->redirectToRoute('app_club_members');
    }

    private function renderAccountForm(Licensee $licensee, FormInterface $form): Response
    {
        $sourceUser = $licensee->getUser();

        return $this->render('management/member/account.html.twig', [
            'form' => $form,
            'licensee' => $licensee,
            'sourceUser' => $sourceUser,
            'sourceWillBeEmpty' => 1 === $sourceUser?->getLicensees()->count(),
        ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    private function currentClub(): Club
    {
        $this->assertHasValidLicense();

        return $this->licenseHelper->getCurrentLicenseeCurrentLicense()->getClub();
    }
}
