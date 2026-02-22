<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\UserFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends BaseController
{
    #[Route('/my-account', name: 'app_user_account', methods: ['GET'])]
    public function account(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('user/account.html.twig', [
            'user' => $user,
        ]);
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
    #[IsGranted('ROLE_ADMIN')]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertHasValidLicense();

        $form = $this->createForm(UserFormType::class, $user, [
            'is_admin' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

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

        if (!$currentClub instanceof \App\Entity\Club) {
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
    private function getCurrentUserClub(User $user, int $season): ?\App\Entity\Club
    {
        foreach ($user->getLicensees() as $licensee) {
            if ($license = $licensee->getLicenseForSeason($season)) {
                return $license->getClub();
            }
        }

        return null;
    }
}
