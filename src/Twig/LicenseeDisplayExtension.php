<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Licensee;
use Symfony\Bundle\SecurityBundle\Security;

class LicenseeDisplayExtension
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'licensee_display_name')]
    public function getLicenseeDisplayName(Licensee $licensee): string
    {
        // Si l'utilisateur est admin ou coach, on affiche le nom complet
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_COACH')) {
            return $licensee->getFullname();
        }

        // Sinon, on affiche seulement le prénom avec l'initiale du nom
        return $licensee->getFirstnameWithInitial();
    }
}
