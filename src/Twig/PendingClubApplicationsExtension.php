<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Licensee;
use App\Helper\LicenseeHelper;
use App\Helper\SeasonHelper;
use App\Repository\ClubApplicationRepository;
use Symfony\Bundle\SecurityBundle\Security;

class PendingClubApplicationsExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly LicenseeHelper $licenseeHelper,
        private readonly SeasonHelper $seasonHelper,
        private readonly ClubApplicationRepository $applicationRepository,
    ) {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'pending_club_applications_count')]
    public function getPendingClubApplicationsCount(): int
    {
        if (!$this->security->isGranted('ROLE_CLUB_ADMIN')) {
            return 0;
        }

        $licensee = $this->licenseeHelper->getLicenseeFromSession();
        $currentSeason = $this->seasonHelper->getSelectedSeason();
        $license = $licensee instanceof Licensee ? $licensee->getLicenseForSeason($currentSeason) : null;
        if (!$license instanceof \App\Entity\License) {
            return 0;
        }

        $club = $license->getClub();

        return \count($this->applicationRepository->findPendingByClubAndSeason($club, $currentSeason));
    }
}
