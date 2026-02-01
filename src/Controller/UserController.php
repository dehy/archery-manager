<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\UserFormType;
use App\Helper\LicenseHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
    public function show(User $user, LicenseHelper $licenseHelper): Response
    {
        $this->assertHasValidLicense();

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Check permissions
        // If not admin, check if it's the current user
        if (!$this->isGranted('ROLE_ADMIN') && $currentUser->getId() !== $user->getId()) {
            // If not current user, check if club admin from same club
            if ($this->isGranted('ROLE_CLUB_ADMIN')) {
                $currentSeason = $this->seasonHelper->getSelectedSeason();
                $currentUserLicensees = $currentUser->getLicensees();
                $currentClub = null;
                foreach ($currentUserLicensees as $currentUserLicensee) {
                    if ($license = $currentUserLicensee->getLicenseForSeason($currentSeason)) {
                        $currentClub = $license->getClub();
                        break;
                    }
                }

                // Check if the user has any licensee in the same club
                $hasAccessToClub = false;
                foreach ($user->getLicensees() as $licensee) {
                    $licenseeClub = $licensee->getLicenseForSeason($currentSeason)?->getClub();
                    if ($licenseeClub && $currentClub && $licenseeClub->getId() === $currentClub->getId()) {
                        $hasAccessToClub = true;
                        break;
                    }
                }

                if (!$hasAccessToClub) {
                    throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cet utilisateur.');
                }
            } else {
                throw $this->createAccessDeniedException('Vous ne pouvez accéder qu\'à votre propre profil.');
            }
        }

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
}
