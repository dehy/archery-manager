<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Helper\LicenseHelper;
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
        
        $currentUser = $this->getUser();
        
        // Check permissions
        if (!$this->isGranted('ROLE_ADMIN')) {
            // If not admin, check if it's the current user
            if ($currentUser->getId() !== $user->getId()) {
                // If not current user, check if club admin from same club
                if ($this->isGranted('ROLE_CLUB_ADMIN')) {
                    $currentLicensee = $licenseHelper->currentLicensee();
                    $currentClub = $currentLicensee?->getCurrentLicense()?->getClub();
                    
                    // Check if the user has any licensee in the same club
                    $hasAccessToClub = false;
                    foreach ($user->getLicensees() as $licensee) {
                        $licenseeClub = $licensee->getCurrentLicense()?->getClub();
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
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }
}
