<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Licensee;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LicenseeDisplayExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('licensee_display_name', $this->getLicenseeDisplayName(...)),
        ];
    }

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
