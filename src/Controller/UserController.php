<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Club;
use App\Entity\User;
use App\Form\Type\UserFormType;
use App\Helper\LicenseeHelper;
use App\Helper\SeasonHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends BaseController
{
    public function __construct(
        LicenseeHelper $licenseeHelper,
        SeasonHelper $seasonHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($licenseeHelper, $seasonHelper);
    }

    #[Route('/my-account', name: 'app_user_account', methods: ['GET'])]
    public function account(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('user/account.html.twig', [
            'user' => $user,
        ]);
    }

    /** Switches the current user between today's interface and the redesigned one (opt-in while it is built). */
    #[Route('/my-account/interface', name: 'app_user_interface_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleInterface(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('toggle_interface', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setNewInterface(!$user->hasNewInterface());

        $this->entityManager->flush();

        $this->addFlash('success', $user->hasNewInterface()
            ? 'Vous utilisez la nouvelle interface. Vous pouvez revenir à l\'ancienne depuis Mon compte.'
            : 'Vous êtes revenu·e à l\'interface actuelle.');

        return $this->redirectToRoute('app_user_account');
    }

    #[Route('/user/{id}', name: 'app_user_show', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function show(User $user): Response
    {
        $this->assertHasValidLicense();

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $this->checkUserAccess($currentUser, $user);

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/{id}/edit', name: 'app_user_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLUB_ADMIN')]
    public function edit(User $user, Request $request): Response
    {
        $this->assertHasValidLicense();

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $this->checkUserAccess($currentUser, $user);

        $form = $this->createForm(UserFormType::class, $user, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');

            return $this->redirectToRoute('app_user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Check if current user has access to view a user profile.
     */
    private function checkUserAccess(User $currentUser, User $targetUser): void
    {
        if ($this->isGranted('ROLE_ADMIN') || $currentUser->getId() === $targetUser->getId()) {
            return;
        }

        if ($this->isGranted('ROLE_CLUB_ADMIN')) {
            if ($this->hasClubAdminAccessToUser($currentUser, $targetUser)) {
                return;
            }

            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cet utilisateur.');
        }

        throw $this->createAccessDeniedException('Vous ne pouvez accéder qu\'à votre propre profil.');
    }

    /**
     * Check if club admin has access to user in same club.
     */
    private function hasClubAdminAccessToUser(User $currentUser, User $targetUser): bool
    {
        $currentSeason = $this->seasonHelper->getSelectedSeason();
        $currentClub = $this->getCurrentUserClub($currentUser, $currentSeason);

        if (!$currentClub instanceof Club) {
            return false;
        }

        foreach ($targetUser->getLicensees() as $licensee) {
            $licenseeClub = $licensee->getLicenseForSeason($currentSeason)?->getClub();
            if ($licenseeClub && $licenseeClub->getId() === $currentClub->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current user's club for given season.
     */
    private function getCurrentUserClub(User $user, int $season): ?Club
    {
        foreach ($user->getLicensees() as $licensee) {
            if ($license = $licensee->getLicenseForSeason($season)) {
                return $license->getClub();
            }
        }

        return null;
    }
}
