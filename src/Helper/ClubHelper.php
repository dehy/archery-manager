<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Club;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\User;
use App\Exception\NoActiveClubException;

class ClubHelper
{
    public function __construct(
        private readonly LicenseHelper $licenseHelper,
    ) {
    }

    public function activeClub(): ?Club
    {
        return $this->licenseHelper->getCurrentLicenseeCurrentLicense()?->getClub();
    }

    public function getClubForUser(?User $user): ?Club
    {
        if (!$user instanceof \App\Entity\User) {
            return null;
        }

        $season = Season::seasonForDate(new \DateTimeImmutable());

        foreach ($user->getLicensees() as $licensee) {
            $license = $licensee->getLicenseForSeason($season);
            if ($license && $license->getClub()) {
                return $license->getClub();
            }
        }

        return null;
    }

    public function activeClubFor(Licensee $licensee): Club
    {
        $club = $licensee->getLicenseForSeason(Season::seasonForDate(new \DateTimeImmutable()))?->getClub();
        if (!$club instanceof Club) {
            throw new NoActiveClubException();
        }

        return $club;
    }

    public function primaryColor(): string
    {
        return $this->activeClub()?->getPrimaryColor() ?? $this->defaultColor();
    }

    public function defaultColor(): string
    {
        return '#999999';
    }
}
